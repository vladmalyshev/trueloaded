<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\NetSuite;

use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use common\api\models\AR\Warehouses;
use yii\helpers\ArrayHelper;
use NetSuite\Classes as NS;
use NetSuite\NetSuiteService;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadWarehouses implements DatasourceInterface
{

    protected $total_pages = 1;
    protected $current_page = 0;
    protected $process_list;

    protected $config = [];

    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $client;

    /**
     * remote_key => [local_key, static callback to transform]
     * @var array mapRemoteLocal
     */
    static protected $mapRL = [
                    'name' => ['warehouse_name'],
                    'locationType' => ['is_store'],
                    'isInactive' => ['status', '_Not'],
                    'internalId' => ['warehouse_id'],
                    //'allowStorePickup' => []
                    //[parent] =>
                    //[includeChildren] =>
                    //[subsidiaryList] =>
                    //[tranPrefix] =>
/*                    'mainAddress' => NetSuite\Classes\Address Object
                        (
                            [internalId] => 14
                            [country] => _unitedStates
                            [attention] => Wolfe Electronics [V9]
                            [addressee] =>
                            [addrPhone] =>
                            [addr1] => 1500 3rd St
                            [addr2] =>
                            [addr3] =>
                            [city] => San Mateo
                            [state] => CA
                            [zip] => 94403
                            [addrText] =>
                            [override] =>
                            [customFieldList] =>
                            [nullFieldList] =>
                        )*/

                    //[returnAddress] =>
                    //[timeZone] =>
                    //[latitude] =>
                    //[longitude] =>
                    //[logo] =>
                    //[useBins] =>
                 /*   [makeInventoryAvailable] => 1
                    [makeInventoryAvailableStore] => 1
                    [geolocationMethod] =>
                    [autoAssignmentRegionSetting] =>
                    [nextPickupCutOffTime] =>
                    [bufferStock] =>
                    [storePickupBufferStock] =>
                    [dailyShippingCapacity] =>
                    [totalShippingCapacity] =>
                    [includeLocationRegionsList] =>
                    [excludeLocationRegionsList] =>
                    [businessHoursList] =>
                    [classTranslationList] =>
                    [customFieldList] =>
                    [externalId] =>
                    [nullFieldList] => */
    ];

    function __construct($config)
    {
      if (empty($config['client']['email']) && isset($config['client']['username'])) {
        $config['client']['email'] = $config['client']['username'];
        unset($config['client']['username']);
      }
      if (empty($config['client']['app_id']) && isset($config['client']['appid'])) {
        $config['client']['app_id'] = $config['client']['appid'];
        unset($config['client']['appid']);
      }
      $this->config = $config;
    }

    public function allowRunInPopup()
    {
        return true;
    }

    public function getProgress()
    {
        if ( $this->total_pages>0 ) {
            $percentDone = min(100, ($this->current_page / $this->total_pages) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }


    public function prepareProcess(Messages $message)
    {
        // init client
        try {

          /*$ds = new NetSuiteLink();
          $this->client = &$ds->getClient($this->config['client']);
          */

          $this->client = new \NetSuite\NetSuiteService(array_merge(NetSuiteLink::$config, $this->config['client']));

        } catch (\Exception $ex) {
          throw new Exception('Configuration error');
        }

        // download remote ids and process it
        $this->fetchServerPage();

    }

    public function processRow(Messages $message)
    {

      if ( !is_array($this->process_list) ) return false;

      if ( count($this->process_list)==0 ) {
          $this->fetchServerPage($message);
      }
      if ( !is_array($this->process_list) || count($this->process_list)==0 ) {
          return false;
      }

      $data = array_shift($this->process_list);
      $this->updateLocalWarehouses($message, $data);

      return $data;
      
    }

    public function postProcess(Messages $message)
    {

    }

    protected function fetchServerPage()
    {
        $this->process_list = [];
        $this->current_page++;
        /*$params = [
            'searchConditions' => [
                /*'searchCondition'=> [
                    [
                        'column' => 'warehouses_id',
                        'operator' => '=',
                        'values'=>[['16523']]
                    ]
                 ]* /
            ],
            'paging' => [
                'page' => $this->current_page,
            ]
        ];*/
        echo '<pre>FETCH PAGE '; var_dump($this->current_page); echo '</pre>';
        //2do don't fetch next page if previous record count was less than page size.
        if ($this->current_page == 1 ){
          $response = Helper::basicSearch($this->client, 'warehouse');
        } else {
          $response = Helper::basicSearch($this->client, 'warehouse', [], ($this->current_page-1));
        }
/*
$response->
        status
        totalRecords
        pageSize
        totalPages
        pageIndex
        recordList Record[]
  */
        if (isset($response->totalRecords) && isset($response->recordList)) {
            foreach ($response->recordList->record as $record) {
              $this->process_list[$record->internalId] = $record;
            }
        }
    }

    protected function updateLocalWarehouses(Messages $message, $warehousesData)
    {
        $warehousesData = json_decode(json_encode($warehousesData), true);
        $wdr = [];
        Helper::applyMap(self::$mapRL, $warehousesData, $wdr);
        
        /* 2do transform address
        $addresses = isset($warehousesData['mainAddress'])?$warehousesData['mainAddress']:[];

        if ( count($addresses)>0 && !ArrayHelper::isIndexed($addresses) ) {
            $addresses = [$addresses];
        }*/

        $remote_warehouses_id = $wdr['warehouse_id'];
        unset($wdr['warehouse_id']);

        $local_warehouses_id = $this->lookupLocalId($remote_warehouses_id);
/* 2do ? lookup by name? */

        $create_local = false;
        $warehouseObj = Warehouses::findOne(['warehouse_id'=>$local_warehouses_id]);
        if ( !is_object($warehouseObj) || empty($warehouseObj->warehouse_id) ) {
            $warehouseObj = new Warehouses();
            $create_local = true;
        }

        $warehouseObj->importArray($wdr);
        $warehouseObj->save();

        if ( $warehouseObj->warehouse_id ) {

            if ($create_local) {
                tep_db_perform('ep_holbi_soap_mapping', [
                    'ep_directory_id' => $this->config['directoryId'],
                    'mapping_type' => 'warehouses',
                    'remote_id' => $remote_warehouses_id,
                    'local_id' => $warehouseObj->warehouse_id,
                ]);
            } else {
            }
        }
    }

    protected function lookupLocalId($remoteId)
    {
      static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
          $get_local_id_r = tep_db_query(
              "SELECT local_id ".
              "FROM ep_holbi_soap_mapping ".
              "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
              " AND remote_id='".$remoteId."' AND mapping_type='warehouses'"
          );
          if ( tep_db_num_rows($get_local_id_r)>0 ) {
              $_local_id = tep_db_fetch_array($get_local_id_r);
              tep_db_free_result($get_local_id_r);
              $mapping[$remoteId] = $_local_id['local_id'];
              return $_local_id['local_id'];
          }
          return false;
        }
        return intval($mapping[$remoteId]);
    }

    protected function getWarehousesSyncConfig( $productId, $configKey=null )
    {
        $datasourceConfig = $this->config['warehouses'];
        $datasourceConfig['create_on_client'] = isset($datasourceConfig['create_on_client'])?!!$datasourceConfig['create_on_client']:true;
        $datasourceConfig['create_on_server'] = isset($datasourceConfig['create_on_server'])?!!$datasourceConfig['create_on_server']:false;
        $datasourceConfig['update_on_client'] = isset($datasourceConfig['update_on_client'])?!!$datasourceConfig['update_on_client']:true;
        $datasourceConfig['update_on_server'] = isset($datasourceConfig['update_on_server'])?!!$datasourceConfig['update_on_server']:false;


        if ( !is_null($configKey) ) {
            return isset($datasourceConfig[$configKey])?$datasourceConfig[$configKey]:null;
        }
        return $datasourceConfig;
    }

}

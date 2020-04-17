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
use common\api\models\AR\Group;
use yii\helpers\ArrayHelper;
use NetSuite\Classes as NS;
use NetSuite\NetSuiteService;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadGroups implements DatasourceInterface
{

    protected $total_pages = 1;
    protected $current_page = 0;
    protected $process_list;

    protected $config = [];

    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $client;

    protected $defaultGroup = false;

    /**
     * remote_key => [local_key, static callback to transform]
     * @var array mapRemoteLocal
     */
    static protected $mapRL = [
                    'name' => ['groups_name'],
                    'discountpct' => ['groups_discount', 'abs'],
                    'internalId' => ['groups_id'],
/*
                            [discountpct] => -10
                            [updateExistingPrices] =>
                            [isOnline] =>
                            [isInactive] =>
                            [internalId] => 2
                            [externalId] =>
                            [nullFieldList] =>
  */
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

        /// NS saves all prices as group price - we don't need 1st one - default. Possible problem - 1st group in NS with discount
        if ( is_array($this->process_list) ) {

          $this->defaultGroup = (int)min(array_keys($this->process_list));
          unset($this->process_list[$this->defaultGroup]);
          tep_db_query("delete from ep_holbi_soap_mapping where ep_directory_id ='" . $this->config['directoryId'] . "' and mapping_type = 'groups' and remote_id = '" . $this->defaultGroup. "'");
          tep_db_perform('ep_holbi_soap_mapping', [
              'ep_directory_id' => $this->config['directoryId'],
              'mapping_type' => 'groups',
              'remote_id' => $this->defaultGroup,
              'local_id' => 0,
          ]);
        }
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
      $this->updateLocalGroups($message, $data);

      return $data;

    }

    public function postProcess(Messages $message)
    {

    }

    protected function fetchServerPage()
    {
        $this->process_list = [];
        $this->current_page++;

        echo '<pre>FETCH PAGE '; var_dump($this->current_page); echo '</pre>';
        //2do don't fetch next page if previous record count was less than page size.
        if ($this->current_page == 1 ){
          $response = Helper::basicSearch($this->client, 'group');
        } else {
          $response = Helper::basicSearch($this->client, 'group', [], ($this->current_page-1));
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

    protected function updateLocalGroups(Messages $message, $groupsData)
    {
        $groupsData = json_decode(json_encode($groupsData), true);
        $wdr = [];
        Helper::applyMap(self::$mapRL, $groupsData, $wdr);

        $remote_groups_id = $wdr['groups_id'];
        unset($wdr['groups_id']);

        $local_groups_id = $this->lookupLocalId($remote_groups_id);

        $create_local = false;
        $groupObj = Group::findOne(['groups_id'=>$local_groups_id]);
        if ( !is_object($groupObj) || empty($groupObj->groups_id) ) {
            $groupObj = new Group();
            $create_local = true;
        }

        $groupObj->importArray($wdr);
        $groupObj->save();

        if ( $groupObj->groups_id ) {
            if ($create_local) {
                tep_db_perform('ep_holbi_soap_mapping', [
                    'ep_directory_id' => $this->config['directoryId'],
                    'mapping_type' => 'groups',
                    'remote_id' => $remote_groups_id,
                    'local_id' => $groupObj->groups_id,
                ]);
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
              " AND remote_id='".$remoteId."' AND mapping_type='groups'"
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

    protected function getGroupsSyncConfig( $productId, $configKey=null )
    {
        $datasourceConfig = $this->config['groups'];
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

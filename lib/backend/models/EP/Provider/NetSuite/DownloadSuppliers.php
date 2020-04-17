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
use common\api\models\AR\Supplier;
use yii\helpers\ArrayHelper;
use NetSuite\Classes as NS;
use NetSuite\NetSuiteService;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadSuppliers implements DatasourceInterface
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
                    'entityId' => ['suppliers_name'],
                    'companyName' => ['suppliers_name'],
                    /*'entityId' => ['suppliers_name'],
/*
      [customForm] =>
                            [entityId] => State Board of Equalization
                            [altName] =>
                            [isPerson] =>
                            [phoneticName] =>
                            [salutation] =>
                            [firstName] =>
                            [middleName] =>
                            [lastName] =>
                            [companyName] =>
                            [phone] =>
                            [fax] =>
                            [email] =>
                            [url] =>
                            [defaultAddress] => State Board of Equalization<br>PO Box 924879<br>Sacramento CA
                            [isInactive] =>
                            [lastModifiedDate] => 2012-05-24T16:03:06.000-07:00
                            [dateCreated] => 2010-10-24T00:00:00.000-07:00
                            [category] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 3
                                    [externalId] =>
                                    [type] =>
                                    [name] => Tax agency
                                )

                            [title] =>
                            [printOnCheckAs] =>
                            [altPhone] =>
                            [homePhone] =>
                            [mobilePhone] =>
                            [altEmail] =>
                            [comments] =>
                            [globalSubscriptionStatus] => _softOptIn
                            [image] =>
                            [emailPreference] => _default
                            [subsidiary] =>
                            [representingSubsidiary] =>
                            [accountNumber] =>
                            [legalName] =>
                            [vatRegNumber] =>
                            [expenseAccount] =>
                            [payablesAccount] =>
                            [terms] =>
                            [incoterm] =>
                            [creditLimit] =>
                            [balancePrimary] => 0
                            [openingBalance] =>
                            [openingBalanceDate] =>
                            [openingBalanceAccount] =>
                            [balance] =>
                            [unbilledOrdersPrimary] => 0
                            [bcn] =>
                            [unbilledOrders] => 0
                            [currency] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 1
                                    [externalId] =>
                                    [type] =>
                                    [name] => USD
                                )

                            [is1099Eligible] =>
                            [isJobResourceVend] =>
                            [laborCost] =>
                            [purchaseOrderQuantity] =>
                            [purchaseOrderAmount] =>
                            [purchaseOrderQuantityDiff] =>
                            [receiptQuantity] =>
                            [receiptAmount] =>
                            [receiptQuantityDiff] =>
                            [workCalendar] =>
                            [taxIdNum] =>
                            [taxItem] =>
                            [giveAccess] =>
                            [sendEmail] =>
                            [billPay] =>
                            [isAccountant] =>
                            [password] =>
                            [password2] =>
                            [requirePwdChange] =>
                            [eligibleForCommission] =>
                            [emailTransactions] =>
                            [printTransactions] =>
                            [faxTransactions] =>
                            [pricingScheduleList] =>
                            [subscriptionsList] =>
                            [addressbookList] =>
                            [currencyList] =>
                            [rolesList] =>
                            [customFieldList] => NetSuite\Classes\CustomFieldList Object
                                (
                                    [customField] => Array
                                        (
                                            [0] => NetSuite\Classes\BooleanCustomFieldRef Object
                                                (
                                                    [value] =>
                                                    [internalId] => 9
                                                    [scriptId] => custentity_is_manufacturer
                                                )

                                        )

                                )

                            [internalId] => -100
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
      $this->updateLocalSuppliers($message, $data);

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
                        'column' => 'suppliers_id',
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
          $response = Helper::basicSearch($this->client, 'supplier');
        } else {
          $response = Helper::basicSearch($this->client, 'supplier', [], ($this->current_page-1));
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

    protected function updateLocalSuppliers(Messages $message, $suppliersData)
    {
        $suppliersData = json_decode(json_encode($suppliersData), true);
        $wdr = [];
        Helper::applyMap(self::$mapRL, $suppliersData, $wdr);

        /* 2do transform address
        $addresses = isset($suppliersData['mainAddress'])?$suppliersData['mainAddress']:[];

        if ( count($addresses)>0 && !ArrayHelper::isIndexed($addresses) ) {
            $addresses = [$addresses];
        }*/

        $remote_suppliers_id = $wdr['suppliers_id'];
        unset($wdr['suppliers_id']);

        $local_suppliers_id = $this->lookupLocalId($remote_suppliers_id);
/* 2do ? lookup by name? */

        $create_local = false;
        $supplierObj = Supplier::findOne(['suppliers_id'=>$local_suppliers_id]);
        if ( !is_object($supplierObj) || empty($supplierObj->suppliers_id) ) {
            $supplierObj = new Supplier();
            $create_local = true;
        }

        $supplierObj->importArray($wdr);
        $supplierObj->save();

        if ( $supplierObj->suppliers_id ) {

            if ($create_local) {
                tep_db_perform('ep_holbi_soap_mapping', [
                    'ep_directory_id' => $this->config['directoryId'],
                    'mapping_type' => 'suppliers',
                    'remote_id' => $remote_suppliers_id,
                    'local_id' => $supplierObj->suppliers_id,
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
              " AND remote_id='".$remoteId."' AND mapping_type='suppliers'"
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

    protected function getSuppliersSyncConfig( $productId, $configKey=null )
    {
        $datasourceConfig = $this->config['suppliers'];
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

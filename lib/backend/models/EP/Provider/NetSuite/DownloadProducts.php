<?php
//https://system.netsuite.com/images/retail/drive09a.jpg
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\NetSuite;

use backend\models\EP\Directory;
use backend\models\EP\Exception;
use backend\models\EP\JobDatasource;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use common\api\models\AR\Categories;
use common\api\models\AR\Products;
use common\classes\language;
use yii\helpers\ArrayHelper;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadProducts implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list;

    protected $config = [];
    protected $inventory = [];

    protected $afterProcessFilename = '';
    protected $afterProcessFile = false;

    /**
     * @var \SoapClient
     */
    protected $client;

    protected $xsellTypeMap = [];

    protected $completeCatalogDownloaded = false;
    static protected $lookupArray = [
      'itemId' => 'products_model',
      'displayName' => 'products_name',
      'vendorName' => 'products_model',
      'upcCode' => 'products_model',
      'upcCode' => 'products_ean',
      'upcCode' => 'products_upc',
      'upcCode' => 'products_isbn',
      'vendorName' => 'products_asin',
    ];

    /**
     * remote_key => [local_key, static callback to transform]
     * @var array mapRemoteLocal
     */
    static protected $mapRL = [
//ToDo                    'storeDescription' => [-1, 'getDescriptions', 'products_description_short'],
                    'translationsList' => [-1, 'getDescriptions', 'products_description'],
                    'pricingMatrix' => [-1, 'getPrices'],
                    'class' => [-1, 'getCategoriesFromClass'],//PKF custom
//                    'siteCategoryList' => ['assigned_categories', 'getSuppliers'],
                    'customFieldList' => [-1, 'getAttributesAndProperties'],
                    'itemVendorList' => [-1, 'getSuppliers'],
//images
//---- fields -----
                    'memberList' => ['set_products', 'getSetProducts'],
                    'locationsList' => [-1, 'getStock'],

                    'itemId' => ['products_model'],// first not empty 2check - bool isInactive => status
                    'vendorName' => ['products_model'],

                    'createdDate' => ['products_date_added', 'getDateTime'],
                    'lastModifiedDate' => ['products_last_modified', 'getDateTime'],
                    'weight' => [-1, 'getWeightDetails'], //weightUnit
                    'isInactive' => ['products_status', '_Not'],
                    'salesTaxCode' => ['products_tax_class_id', 'getTaxId'],
                    'manufacturer' => ['manufacturers_id', 'getManufacturersId'],
                    'producer' => ['manufacturers_id', 'getManufacturersId'],
                    'storeDisplayName' => [-1, 'getDescriptions', 'products_name'],
                    'displayName' => [-1, 'getDescriptions', 'products_name'],
                    'salesDescription' => [-1, 'getDescriptions', 'products_description_short'],
                    'storeDetailedDescription' => [-1, 'getDescriptions', 'products_description'],

                    'upcCode' => ['products_upc'],
                    ///'' => ['is_virtual'],
                    'shippingCost' => ['shipping_surcharge_price'],

      ];

    function __construct($config)
    {
        $config['client']['email'] = $config['client']['username'];
        $config['client']['app_id'] = $config['client']['appid'];
        unset($config['client']['appid']);
        unset($config['client']['username']);
        $this->config = $config;
        if (empty(Helper::$config)) {
          Helper::$config = $config;
        }

        $getMapping_r = tep_db_query(
            "SELECT remote_id, local_id ".
            "FROM ep_holbi_soap_mapping ".
            "WHERE ep_directory_id='".intval($this->config['directoryId'])."' AND mapping_type='xsell_type' "
        );
        if ( tep_db_num_rows($getMapping_r)>0 ) {
            while( $mapping = tep_db_fetch_array($getMapping_r) ){
                $this->xsellTypeMap[ $mapping['remote_id'] ] = $mapping['local_id'];
            }
        }
        tep_db_query("CREATE TABLE IF NOT EXISTS
ep_holbi_soap_remote_products_queue (
id INT(11) NOT NULL AUTO_INCREMENT ,
ep_directory_id INT(11) NOT NULL DEFAULT '0',
job_id INT(11) NOT NULL DEFAULT '0',
remote_id INT(11) NOT NULL DEFAULT '0',
date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
class_name varchar(255) NOT NULL,
info TEXT NOT NULL,
 PRIMARY KEY (id),
INDEX idx_ep_directory_id (ep_directory_id),
INDEX idx_remote_id (remote_id)
) ENGINE = InnoDB

");



    }

    public function getProgress()
    {
        if ( $this->total_count>0 ) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }


    public function prepareProcess(Messages $message)
    {
        $this->config['directoryId'];
        $this->config['assign_platform'] = \common\classes\platform::defaultId();
        $this->config['assign_platforms'] = [];
        $get_available_platforms_r = tep_db_query(
            "SELECT platform_id " .
            "FROM " . TABLE_PLATFORMS . " " .
            "WHERE is_virtual=0 and is_marketplace = 0 "
        );
        while( $available_platform = tep_db_fetch_array($get_available_platforms_r) ) {
            $this->config['assign_platforms'][] = [
                'platform_id' => $available_platform['platform_id'],
            ];
        }
        
        // init client
        try {
          $this->client = new \NetSuite\NetSuiteService(array_merge(NetSuiteLink::$config, $this->config['client']));
        }catch (\Exception $ex) {
            throw new Exception('Configuration error');
        }
        try{
            $this->syncRequiredEntities($message);
        }catch(\Exception $ex){
            \Yii::info('syncRequiredEntities [ERROR]:'.$ex->getMessage()."\n".$ex->getTraceAsString(), 'datasource');
            throw new Exception('Download required remote entities error');
        }

        // download remote ids and process it
        try {
            $this->getRemoteProducts();
        }catch (\Exception $ex){
            throw new Exception('Download remote products error');
        }
        if ( $this->completeCatalogDownloaded ) {
            $this->processUnassignedProducts($message);
        }

        $this->total_count = count($this->process_list);
        $message->info("Products for update - ".$this->total_count);
        $this->afterProcessFilename = tempnam($this->config['workingDirectory'],'after_process');
        $this->afterProcessFile = fopen( $this->afterProcessFilename, 'w+' );
    }

    public function processRow(Messages $message)
    {
        $remoteProduct = current($this->process_list);
        if ( !$remoteProduct ) return false;

        $this->processRemoteProduct($message, $remoteProduct, true);

        $this->row_count++;
        //if ( $this->row_count>15 ) return false;
        $r = tep_db_query("delete from ep_holbi_soap_remote_products_queue where ep_directory_id='" . (int)$this->config['directoryId'] . "' and remote_id='" . (int)$remoteProduct['remote_id'] . "'");

        next($this->process_list);
        return true;
    }

    public function postProcess(Messages $message)
    {
        if ( $this->afterProcessFile ) {
            // after process
            $message->info('After process - xsell update');
            fseek($this->afterProcessFile,0, SEEK_SET);
            while( $data = fgetcsv($this->afterProcessFile,4000000,"\t") ) {
                if ( $data[1]=='xsell' ) {
                    $localId = $this->lookupLocalId($data[0]);
                    if ( !$localId ) continue;
                    $updateArray = [
                        'xsell' => [],
                    ];
                    if ( strpos($data[2],'|')!==false ) {
                        foreach (explode('|', $data[2]) as $xsellInfo) {
                            $xsellA = explode(':', $xsellInfo, 3);
                            $xsellLocalId = $this->lookupLocalId($xsellA[0]);
                            $remoteXsellTypeId = intval($xsellA[2]);
                            $localXsellTypeId = isset($this->xsellTypeMap[$remoteXsellTypeId]) ? $this->xsellTypeMap[$remoteXsellTypeId] : 0;
                            $updateArray['xsell'][] = [
                                'xsell_id' => $xsellLocalId,
                                'xsell_type_id' => $localXsellTypeId,
                                'sort_order' => $xsellA[1],
                            ];
                        }
                    }
                    $productObj = Products::findOne(['products_id'=>$localId]);
                    if ( $productObj ) {
                        $productObj->importArray($updateArray);
                        $productObj->save();
                    }
                }
            }

            fclose($this->afterProcessFile);
            $this->afterProcessFile = false;
        }
        if ( $this->afterProcessFilename && is_file($this->afterProcessFilename) ) {
            @unlink($this->afterProcessFilename);
        }

        $allow_create_on_server = $this->getProductSyncConfig(0,'create_on_server');

        if ( $allow_create_on_server ) {
            $need_create_on_server_r =
                tep_db_query(
                    "SELECT p.products_id " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    " LEFT JOIN ep_holbi_soap_products_flags pf ON pf.ep_directory_id='" . $this->config['directoryId'] . "' AND pf.products_id=p.products_id AND pf.flag_name='create_on_server' AND pf.flag_value!=0 ".
                    " LEFT JOIN ep_holbi_soap_link_products lp ON lp.local_products_id=p.products_id AND lp.ep_directory_id='" . $this->config['directoryId'] . "' " .
                    "WHERE lp.remote_products_id IS NULL " .
                    "ORDER BY p.products_id"
                );
        }else{
            $need_create_on_server_r =
                tep_db_query(
                    "SELECT p.products_id " .
                    "FROM " . TABLE_PRODUCTS . " p " .
                    " INNER JOIN ep_holbi_soap_products_flags pf ON pf.ep_directory_id='" . $this->config['directoryId'] . "' AND pf.products_id=p.products_id AND pf.flag_name='create_on_server' AND pf.flag_value=1 ".
                    " LEFT JOIN ep_holbi_soap_link_products lp ON lp.ep_directory_id='" . $this->config['directoryId'] . "' AND lp.local_products_id=p.products_id " .
                    "WHERE lp.remote_products_id IS NULL " .
                    "ORDER BY p.products_id"
                );
        }
        if ( tep_db_num_rows($need_create_on_server_r)>0 ) {
            while ($need_create_on_server = tep_db_fetch_array($need_create_on_server_r)) {
                $this->createProductsOnServer($message, $need_create_on_server['products_id']);
            }
        }
    }

    protected function getRemoteProducts()
    {
        $_debug = true;
        $this->process_list = [];
        /*$this->process_list = [16523=>16523];
        return;*/

        $params = [
            'paging' => [
                'page' => 1,
            ]
        ];

        $r = tep_db_query("select remote_id from ep_holbi_soap_remote_products_queue where ep_directory_id='" . (int)$this->config['directoryId'] . "' /* and job_id=''*/ limit 1");
        if (tep_db_num_rows($r) == 0) {
          do {
              echo '<pre>GET PAGE '; var_dump($params['paging']['page']); echo '</pre>';
              if ($this->current_page == 1 ){
                $response = Helper::basicSearch($this->client, 'product');
              } else {
                $response = Helper::basicSearch($this->client, 'product', [], ($params['paging']['page']-1));
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
                  $sortOrder = $record->internalId . '_' . (isset($record->parent)?0:1);
                  $this->process_list[$record->internalId] =  ['remote_id' => $record->internalId, 'class_name' => get_class($record), 'sortOrder' => $sortOrder];
                  $sql_data = [
                    'ep_directory_id' => (int)$this->config['directoryId'],
                    'remote_id' => $record->internalId,
                    'class_name' => get_class($record),
                    'info' => serialize($record),
                  ];
                  tep_db_perform('ep_holbi_soap_remote_products_queue', $sql_data);
                }
              } else  {
                break;
              }
              $params['paging']['page']++;
          }while(true);
          // NOTE: important, this flag used for cleaning catalog. getProductList need to be without any filters
          $this->completeCatalogDownloaded = true;
          
        } elseif ($_debug) {

          $r = tep_db_query("select remote_id, class_name, info from ep_holbi_soap_remote_products_queue where ep_directory_id='" . (int)$this->config['directoryId'] . "' /* and job_id=''*/");
          while ($d = tep_db_fetch_array($r)) {
            $record = unserialize($d['info']);
            unset($d['info']);
            /**/
            if (!is_object($record)) {
              //to do - inventory!!!
              $response = Helper::basicSearch($this->client, 'product', ['internalIdNumber' => ['value' => $d['remote_id'], 'fieldType' => 'Long', 'operator' => 'equalTo']]);
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
                  $d['class_name'] = get_class($record);
                  break ; /*
                  $sortOrder = $record->internalId . '_' . (isset($record->parent)?0:1);
                  $this->process_list[$record->internalId] =  ['remote_id' => $record->internalId, 'class_name' => get_class($record), 'sortOrder' => $sortOrder];
                  $sql_data = [
                    'ep_directory_id' => (int)$this->config['directoryId'],
                    'remote_id' => $record->internalId,
                    'class_name' => get_class($record),
                    'info' => serialize($record),
                  ];
                  tep_db_perform('ep_holbi_soap_remote_products_queue', $sql_data);*/
                }
              }
            }

            $d['sortOrder'] = $d['remote_id'] . '_' . (isset($record->parent)?0:1);
            $this->process_list[$d['remote_id']] = $d;
          }
          //$this->completeCatalogDownloaded = true;

        }

        uasort($this->process_list, function($a, $b) { return(strcmp($b['sortOrder'], $a['sortOrder'])); });

// PKF categories from classes
        $response = Helper::basicSearch($this->client, 'category');
        if (isset($response->totalRecords) && isset($response->recordList)) {
          $cats = [];
          foreach ($response->recordList->record as $record) {
            $record = json_decode(json_encode($record),true);
            $cats[$record['internalId']] = $record;
          }
          if (count($cats)>0) {
            Helper::$nsCategories = $cats;
            Helper::buildTree();
          }
        }
// PKF categories from classes eof

    }

    protected function processUnassignedProducts(Messages $message)
    {
        tep_db_query(
            "DELETE epp ".
            "FROM ep_holbi_soap_link_products epp ".
            " LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id=epp.local_products_id AND epp.ep_directory_id='".(int)$this->config['directoryId']."' ".
            "WHERE p.products_id IS NULL "
        );
        $check_stored_remote_links_r = tep_db_query(
            "SELECT remote_products_id, local_products_id ".
            "FROM ep_holbi_soap_link_products ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            "ORDER BY remote_products_id"
        );
        if ( tep_db_num_rows($check_stored_remote_links_r)>0 ) {
            $disconnected_products = [];
            while( $check_link = tep_db_fetch_array($check_stored_remote_links_r) ){
                if ( isset($this->process_list[(int)$check_link['remote_products_id']]) ) continue;
                $disconnected_products[(int)$check_link['local_products_id']] = (int)$check_link['local_products_id'];
            }
            if ( count($disconnected_products)>0) {
                $action_for_removed = $this->config['products']['action_for_server_remove'];
                $text_action = 'No action';
                if ( $action_for_removed=='remove' ) {
                    $text_action = 'Removing from catalog';
                }elseif ($action_for_removed=='disable') {
                    $text_action = 'Set as inactive';
                }
                $message->info("Found ".count($disconnected_products)." unassigned products - \"{$text_action}\"");

                if ( $action_for_removed=='remove' ) {
                    foreach( $disconnected_products as $disconnected_product_id ) {
                        \common\helpers\Product::remove_product($disconnected_product_id);
                        tep_db_query(
                            "DELETE FROM ep_holbi_soap_link_products ".
                            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
                            " AND local_products_id = '".(int)$disconnected_product_id."' "
                        );
                    }
                    $message->info("Done - \"{$text_action}\"");
                }elseif ($action_for_removed=='disable') {
                    while ( count($disconnected_products)>0 ) {
                        $deactivate_list = array_splice($disconnected_products,0,1000);
                        if ( count($deactivate_list)>0 ) {
                            tep_db_query(
                                "UPDATE " . TABLE_PRODUCTS . " SET products_status = 0 " .
                                "WHERE products_id IN ('".implode("','", array_values($deactivate_list))."') "
                            );
                            tep_db_query(
                                "DELETE FROM ep_holbi_soap_link_products ".
                                "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
                                " AND local_products_id IN ('".implode("','", array_values($deactivate_list))."')"
                            );
                        }
                    }
                    $message->info("Done - \"{$text_action}\"");
                }
            }
        }
    }

    protected function processRemoteProduct(Messages $messages, $remoteProductData, $useAfterProcess = false)
    {
        static $timing = [
            'soap' => 0,
            'local' => 0,
        ];
        $t1 = microtime(true);
        //$remoteProduct = $this->client->getProduct($remoteProductId);
        $remoteProductId = $remoteProductData['remote_id'];
        $cls = str_replace('NetSuite\\Classes\\', '', $remoteProductData['class_name']);
        $remoteProductClass = strtolower(substr($cls, 0, 1)) . substr($cls, 1);
        $product = Helper::get($this->client, $remoteProductClass,  $remoteProductId);
        $remoteProduct = $product->record;
//if         ($remoteProduct->internalId==5 )
//  echo "before #### <PRE>" .print_r($remoteProduct, 1) ."</PRE>";


        // inventory is a separate product - save locally and don't process further
        if (isset($remoteProduct->matrixOptionList)) {
          $this->inventory[$remoteProduct->parent->internalId][] = json_decode(json_encode($remoteProduct),true);
          Helper::$current_product = [];
          return;
        } elseif (isset($this->inventory[$remoteProduct->internalId])) {
          Helper::$current_product['inventory'] =  $this->inventory[$remoteProduct->internalId];
          unset($this->inventory[$remoteProduct->internalId]);
        }
        // save custom fields for attributes and properties.
        if (count($this->customFields)==0 && isset($remoteProduct->customFieldList->customField)) {
          try {
            $response = Helper::getCustomizationId($this->client);
            if (isset($response->totalRecords) && isset($response->customizationRefList)) {
              $response = Helper::getList($this->client, $response->customizationRefList->customizationRef);
              foreach ($response->readResponse as $recordResponce) {
                if (!$recordResponce->status->isSuccess) { 
                  continue;
                }
                $d = $recordResponce->record;

                if ( isset($d->selectRecordType->internalId) && in_array($d->fieldType, ['_multipleSelect', '_listRecord']) ) {
                  $dd = Helper::get($this->client, 'customList', $d->selectRecordType->internalId);
                  if (isset($dd->status) && isset($dd->record)) {
                    $this->customFields[$dd->record->scriptId] = json_decode(json_encode($dd->record),true);
                    $d->selectRecordType->scriptId = $dd->record->scriptId;
                  }
                }
                $this->customFields[$d->scriptId] = json_decode(json_encode($d),true);
              }
            }

          } catch(\Exception $ex){
            \Yii::info('syncRequiredEntities [ERROR]:'.$ex->getMessage()."\n".$ex->getTraceAsString(), 'datasource');
            throw new Exception('Download required remote entities error');
          }

          Helper::$customFields = $this->customFields;

        }

        ///check/add groups
        if (count($this->priceGroups)==0 && isset($remoteProduct->pricingMatrix->pricing[1])) {
          foreach ($remoteProduct->pricingMatrix->pricing as $priceO) {
            if (!in_array($priceO->priceLevel->internalId, array_keys(Helper::getGroupsMap() ))) {
              $response = Helper::basicSearch($this->client, 'group',
                  ['internalIdNumber' => [
                    'value'=> $priceO->priceLevel->internalId,
                    'fieldType' => 'SearchLongField',
                    'operator' => 'equalTo'
                    ]]);
              if (isset($response->totalRecords) && isset($response->recordList)) {
                $dw = new DownloadGroups($this->config);
                foreach ($response->recordList->record as $record) {
                  $dw->processRow($record);
                }
              }
            }
          }
          Helper::$nsGroups = $this->priceGroups = Helper::getGroupsMap();
          
        }

        ///check/add locations/warehouses
        if (count($this->locationsList)==0 && isset($remoteProduct->locationsList->locations)) {
          foreach ($remoteProduct->locationsList->locations as $locationO) {
            if (!in_array($locationO->locationId->internalId, array_keys(Helper::getWarehousesMap()))) {
              $response = Helper::basicSearch($this->client, 'warehouse', 
                  ['internalIdNumber' => [
                    'value'=> $locationO->locationId->internalId,
                    'fieldType' => 'SearchLongField',
                    'operator' => 'equalTo'
                    ]]);
              if (isset($response->totalRecords) && isset($response->recordList)) {
                $dw = new DownloadWarehouses($this->config);
                foreach ($response->recordList->record as $record) {
                  $dw->processRow($record);
                }
              }
            }
          }
          Helper::$nsLocations = $this->locationsList = Helper::getWarehousesMap();
        }

/// vendors/suppliers
        if (false && count($this->suppliers)==0 && isset($remoteProduct->itemVendorList->itemVendor)) {
          foreach ($remoteProduct->pricingMatrix->pricing as $priceO) {
///VL2do
          }
          //Helper::$nsGroups = $this->suppliers;

        }


        //$product = tep_db_fetch_array(tep_db_query("select info from ep_holbi_soap_remote_products_queue where remote_id='" . (int)$remoteProductId . "'"));
        $t2 = microtime(true);
        $timing['soap']+=$t2-$t1;

        if ( $remoteProduct ) {
          $remoteProduct->products_id = $remoteProductId;
/*    [createdDate] => 2012-04-06T20:37:21.000-07:00
    [lastModifiedDate] => 2014-02-20T23:26:04.000-08:00*/

            $server_last_modified = date('Y-m-d H:i:s', strtotime($remoteProduct->createdDate));
            if ($remoteProduct->lastModifiedDate > 1000) {
                $_last_modified = date('Y-m-d H:i:s', strtotime($remoteProduct->lastModifiedDate));
                if ($_last_modified > $server_last_modified) {
                    $server_last_modified = $_last_modified;
                }
            }

            $localId = $this->lookupLocalId($remoteProduct->products_id);

// allow lookup
            if (!$localId && is_array(self::$lookupArray) && count(self::$lookupArray)>0) {
              $searchBy = [];
              foreach (self::$lookupArray as $key => $value) {
                if (isset($remoteProduct->$key) && is_scalar($remoteProduct->$key) && strlen(trim($remoteProduct->$key))>0) {
                  $searchBy[] = [
                                  'key' => $value,
                                  'value' =>trim($remoteProduct->$key)
                                ];
                }
              }

              if (count($searchBy)>0) {
                $localId = $this->searchLocalId($searchBy);
                if ( $localId ) {
                  $this->linkRemoteWithLocalId($remoteProductId, $localId, '1970-01-01', $server_last_modified);
                }
              }
            }

            if ( $localId ) {
                $localProduct = \common\api\models\AR\Products::findOne(['products_id'=>$localId]);
/*
$tmp = [];
foreach ([
        'descriptions' => [],
        'prices' => [],
        'assigned_categories' => false,
        'assigned_platforms' => false,
        'attributes' => false,
        'inventory' => false,
        'suppliers_data' => false,
        'images' => false,
        'properties' => false,
        'xsell' => false,
        'documents' => false,
        'suppliers_product' => false,
        'set_products' => false,
        'warehouses_products' => [],
    ] as $k =>$v) {
  $tmp[$k] = [];
}
$localProduct->importArray($tmp);
echo " formatAs #### <PRE>" .print_r($localProduct, 1) ."</PRE>";*/

                $local_modify_time = $localProduct->products_last_modified>1000?$localProduct->products_last_modified:$localProduct->products_date_added;

                $getProductsTimes_r = tep_db_query(
                    "SELECT server_last_modified, client_processed_last_modified ".
                    "FROM ep_holbi_soap_link_products ".
                    "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
                    " AND local_products_id='".$localId."' ".
                    "LIMIT 1"
                );
                if ( tep_db_num_rows($getProductsTimes_r)>0 ) {
                    $getModifyTimes = tep_db_fetch_array($getProductsTimes_r);

                    if ($local_modify_time>$getModifyTimes['client_processed_last_modified'] && $this->getProductSyncConfig($localId, 'update_on_server')===true){
                        if ($updatedProduct = $this->updateProductOnServer($messages, $remoteProduct->products_id, $localProduct)) {
                            $updateServerTimeColumn = '';
                            //if ( $getModifyTimes['server_last_modified']>1000 ) {
                            //    $getModifyTimes['server_last_modified'];
                            //}

                            tep_db_query(
                                "UPDATE ep_holbi_soap_link_products " .
                                "SET client_processed_last_modified='" . tep_db_input($local_modify_time) . "' {$updateServerTimeColumn} " .
                                "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                                " AND local_products_id='" . $localId . "' "
                            );
                        }
                    }elseif( $server_last_modified>$getModifyTimes['server_last_modified'] && $this->getProductSyncConfig($localId, 'update_on_client')===true ){
                        $this->createUpdateLocalProduct($localProduct, $remoteProduct, $useAfterProcess);
                    }
                }
            }else{
                if ($this->getProductSyncConfig(0, 'create_on_client')===true) {
                    $localProduct = new \common\api\models\AR\Products();
                    $this->createUpdateLocalProduct($localProduct, $remoteProduct, $useAfterProcess);
                }
            }
        }

        $t3 = microtime(true);
        $timing['local']+=$t3-$t2;
        //echo '<pre>'; var_dump($timing); echo '</pre>';
    }

    protected function createUpdateLocalProduct($localProduct, $product, $useAfterProcess)
    {
// all types linked via parent Id
// Matrix - attributes + inventory
// The maximum number of the total combinations of matrix options is 2000.
// 
// Groups,  Kit/Packages  - bundles (price - % discount or fixed)
// Assemblies  
        $remoteProductId = $product->products_id;

        $localId = false;
        if ( isset($localProduct->products_id) && intval($localProduct->products_id)>0 ) {
            $localId = $localProduct->products_id;
        }

        $updateFlags = $this->getProductSyncConfig($localId);
        //$descriptionKeys = ['products_name', 'products_description', 'products_description_short'];
        $descriptionKeys = ['storeDescription', 'storeDetailedDescription', 'pageTitle', 'searchKeywords', 'displayName', 'storeDisplayName'];
        if ( $localId ) {
            if ( isset($updateFlags['seo_client']) && $updateFlags['seo_client']===false && isset($updateFlags['description_client']) && $updateFlags['description_client']===false ) {
              foreach ($descriptionKeys as $key)  {
                unset($product->$key);
              }
              unset($product->translationsList);
              
            }

            if (isset($updateFlags['prices_client']) && $updateFlags['prices_client'] === 'disabled') {
                unset($product->pricingMatrix);
            }
            if (isset($updateFlags['stock_client']) && $updateFlags['stock_client'] === 'disabled') {
                unset($product->locationsList);
            }
            if (isset($updateFlags['attr_client']) && $updateFlags['attr_client'] === false) {
                unset($product->matrixOptionList);
                //unset($product->inventories);
            }
            if (isset($updateFlags['identifiers_client']) && $updateFlags['identifiers_client'] === false) {
                //foreach( ['products_model', 'products_ean', 'products_asin', 'products_isbn', 'products_upc', 'manufacturers_name','manufacturers_id'] as $_iiK ) {
                foreach( ['producer', 'manufacturer', 'mpn', 'upcCode', 'itemId', 'vendorName'] as $_iiK ) {
                    unset($product->{$_iiK});
                }
            }
            if (isset($updateFlags['images_client']) && $updateFlags['images_client'] === false) {
                unset($product->storeDisplayImage);
                unset($product->storeDisplayThumbnail);
            }
            /*if (isset($updateFlags['dimensions_client']) && $updateFlags['dimensions_client'] === false) {
                unset($product->dimensions);
            }*/
            if (isset($updateFlags['properties_client']) && $updateFlags['properties_client'] === false) {
                unset($product->customFieldList);
            }
        }

        $importArray = $this->transformProduct($product);
echo "\n transformed #### <PRE>" .print_r($importArray, 1) ."</PRE>";


        if ( $localId && isset($importArray['description']) && is_array($importArray['description']) && isset($updateFlags['seo_client']) && $updateFlags['seo_client']===false ) {
            foreach ( $importArray['description'] as $__key=>$__data ) {
                foreach ( array_keys($__data) as $__descKey ) {
                    if ( !in_array($__descKey, $descriptionKeys) ) unset($importArray['description'][$__key][$__descKey]);
                }
            }
        }
        if ( $localId && isset($importArray['description']) && is_array($importArray['description']) && isset($updateFlags['description_client']) && $updateFlags['description_client']===false ) {
            foreach ( $importArray['description'] as $__key=>$__data ) {
                foreach ( array_keys($__data) as $__descKey ) {
                    if ( in_array($__descKey, $descriptionKeys) ) unset($importArray['description'][$__key][$__descKey]);
                }
            }
        }

        if(1){
            if ( $useAfterProcess ) {
                $xsellArray = [];
                if (isset($importArray['xsell']) && is_array($importArray['xsell'])) {
                    foreach ($importArray['xsell'] as $idx => $xsellData) {
                        $remoteXsellId = $xsellData['product']['products_id'];
                        $remoteXsellTypeId = isset($xsellData['xsell_type_id'])?$xsellData['xsell_type_id']:0;
                        $xsellArray[] = (int)$remoteXsellId . ':' . (int)$xsellData['sort_order'].':'.(int)$remoteXsellTypeId;
                    }
                    unset($importArray['xsell']);
                }

                $detachAfterProcess = [
                    $importArray['products_id'],
                    'xsell',
                    implode("|", $xsellArray),
                ];
                fputcsv($this->afterProcessFile, $detachAfterProcess, "\t");
            }

            unset($importArray['products_id']);


            if ( isset($importArray['attributes']) && is_array($importArray['attributes']) ) {
                $_attributes_array_key_map = [];
                foreach( $importArray['attributes'] as $_idx=>$attributeInfo ) {
                    $attributeInfo['options_id'] = $this->lookupLocalOptionId($attributeInfo['options_id'],$attributeInfo['options_name']);
                    unset($attributeInfo['options_name']);

                    $attributeInfo['options_values_id'] = $this->lookupLocalOptionValueId(
                        $attributeInfo['options_id'],
                        $attributeInfo['options_values_id'],
                        $attributeInfo['options_values_name']
                    );
                    unset($attributeInfo['options_values_name']);
                    $_attributes_array_key_map[(int)$attributeInfo['options_id'].'-'.(int)$attributeInfo['options_values_id']] = $attributeInfo;
                }
                $importArray['attributes'] = $_attributes_array_key_map;
            }

            if ( isset($importArray['assigned_categories']) && is_array($importArray['assigned_categories']) ) {
                $newAssignedCategories = [];
                foreach( $importArray['assigned_categories'] as $assigned_category ) {
                    $localCategoryId = $this->getLocalCategoryId($assigned_category);
                    $newAssignedCategories[] = [
                        'categories_id' => $localCategoryId,
                        'sort_order' => isset($assigned_category['sort_order'])?$assigned_category['sort_order']:0,
                    ];
                }
                $importArray['assigned_categories'] = $newAssignedCategories;
            }

            if ( isset($importArray['stock_info']) && is_array($importArray['stock_info']) ) {
                if ( array_key_exists('quantity', $importArray['stock_info']) ){
                    $importArray['products_quantity'] = $importArray['stock_info']['quantity'];
                }
                if ( isset($importArray['inventory']) && is_array($importArray['inventory']) ) {
                    foreach( $importArray['inventory'] as $idx=>$inventoryInfo ) {
                        if ( isset($inventoryInfo['stock_info']) && is_array($inventoryInfo['stock_info']) && isset($inventoryInfo['stock_info']['quantity']) ) {
                            $importArray['inventory'][$idx]['products_quantity'] = $inventoryInfo['stock_info']['quantity'];
                        }
                    }
                }
            }

/// fill in default prices
            $inventory_prices_data = false;
            if (isset($importArray['prices']) ) {
              
                static $objCurrencies = false;
                if ( $objCurrencies===false ) $objCurrencies = new \common\classes\Currencies();

                $useCurrency = DEFAULT_CURRENCY;
                if ( isset($importArray['prices'][DEFAULT_CURRENCY.'_0']) ) {
                    $defPrice = $importArray['prices'][DEFAULT_CURRENCY . '_0'];
                }else{
                  // get default price from any other
                    foreach (\common\helpers\Currencies::get_currencies() as $currency){
                        $checkKey = $currency['code'].'_0';
                        if ( isset($importArray['prices'][$checkKey]) ) {
                            $useCurrency = $currency['code'];
                            $rateConvertFrom = $objCurrencies->get_value($currency['code']);
                            if ($rateConvertFrom!=0) { // exchange rate could be empty
                              $defSource = $importArray['prices'][$checkKey];
                              $defPrice = [];
                              if ( isset($defSource['products_group_price']) ) {
                                  $defPrice['products_group_price'] = $this->applyRate($defSource['products_group_price'],1/$rateConvertFrom);
                              }
                              if ( isset($defSource['products_group_discount_price']) ) {
                                  $defPrice['products_group_discount_price'] = $this->applyRate($defSource['products_group_discount_price'],1/$rateConvertFrom);
                              }
                              if ( isset($defSource['products_group_price_pack_unit']) ) {
                                  $defPrice['products_group_price_pack_unit'] = $this->applyRate($defSource['products_group_price_pack_unit'],1/$rateConvertFrom);
                              }
                              if ( isset($defSource['products_group_discount_price_pack_unit']) ) {
                                  $defPrice['products_group_discount_price_pack_unit'] = $this->applyRate($defSource['products_group_discount_price_pack_unit'],1/$rateConvertFrom);
                              }
                              if ( isset($defSource['products_group_price_packaging']) ) {
                                  $defPrice['products_group_price_packaging'] = $this->applyRate($defSource['products_group_price_packaging'],1/$rateConvertFrom);
                              }
                              if ( isset($defSource['products_group_discount_price_packaging']) ) {
                                  $defPrice['products_group_discount_price_packaging'] = $this->applyRate($defSource['products_group_discount_price_packaging'],1/$rateConvertFrom);
                              }
                            }

                            break;
                        } 
                    }
                }
                if ($defPrice) {
                    if ( isset($defPrice['products_group_price']) ) {
                        $importArray['products_price'] = $defPrice['products_group_price'];
                    }
                    if ( isset($defPrice['products_group_discount_price']) ) {
                        $importArray['products_price_discount'] = $defPrice['products_group_discount_price'];
                    }
                    if ( isset($defPrice['products_group_price_pack_unit']) ) {
                        $importArray['products_price_pack_unit'] = $defPrice['products_group_price_pack_unit'];
                    }
                    if ( isset($defPrice['products_group_discount_price_pack_unit']) ) {
                        $importArray['products_price_discount_pack_unit'] = $defPrice['products_group_discount_price_pack_unit'];
                    }
                    if ( isset($defPrice['products_group_price_packaging']) ) {
                        $importArray['products_price_packaging'] = $defPrice['products_group_price_packaging'];
                    }
                    if ( isset($defPrice['products_group_discount_price_packaging']) ) {
                        $importArray['products_price_discount_packaging'] = $defPrice['products_group_discount_price_packaging'];
                    }
                    //fill in 0 group for other currencies (NS has less currencies)
                    if (defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') {
                      foreach (\common\helpers\Currencies::get_currencies() as $currency){
                          $checkKey = $currency['code'].'_0';
                          if ( !isset($importArray['prices'][$checkKey]) ) {
                              $rateConvertFrom = $objCurrencies->get_value($currency['code']);
                              foreach ($defPrice as $k => $v) {
                                if (!in_array($k, ['products_group_discount_price'])) {
                                  $importArray['prices'][$checkKey][$k] =  $this->applyRate($v,$rateConvertFrom);
                                }
                              }
                          }
                      }
                    }

                } elseif (count($importArray['prices'])>0){
                    throw new Exception('Currency not detected');
                }

                $rateConvertFrom = $objCurrencies->get_value($useCurrency);
                if ( isset($importArray['attributes']) && is_array($importArray['attributes']) ) {
                    foreach ($importArray['attributes'] as $attrKey=>$attrInfo) {
                        if ( !isset($attrInfo['prices'][$useCurrency . '_0']['attributes_group_price']) ) continue;
                        $importArray['attributes'][$attrKey]['options_values_price'] = $attrInfo['prices'][$useCurrency . '_0']['attributes_group_price'];
                        if ( $useCurrency!=DEFAULT_CURRENCY ) {
                            $importArray['attributes'][$attrKey]['options_values_price'] = $this->applyRate($importArray['attributes'][$attrKey]['options_values_price'],1/$rateConvertFrom);
                        }
                        if ( isset($attrInfo['prices'][$useCurrency . '_0']['attributes_group_discount_price']) ) {
                            $importArray['attributes'][$attrKey]['products_attributes_discount_price'] = $attrInfo['prices'][$useCurrency . '_0']['attributes_group_discount_price'];
                            if ( $useCurrency!=DEFAULT_CURRENCY ) {
                                $importArray['attributes'][$attrKey]['products_attributes_discount_price'] = $this->applyRate($importArray['attributes'][$attrKey]['products_attributes_discount_price'], 1 / $rateConvertFrom);
                            }
                        }
                    }
                }
//todo attributes prices _0 other currencies

                if ( isset($importArray['inventory']) && is_array($importArray['inventory']) ) {
                    foreach ($importArray['inventory'] as $inventoryIdx=>$inventoryInfo) {
                        $inventoryInfo['products_id'];

                        //fill in 0 group for other currencies (NS has less currencies)
                        $defPr = $inventoryInfo['prices'][DEFAULT_CURRENCY . '_0'];
                        foreach (\common\helpers\Currencies::get_currencies() as $currency){
                            $checkKey = $currency['code'].'_0';
                            if ( !isset($inventoryInfo['prices'][$checkKey]) ) {
                                $rateConvertFrom = $objCurrencies->get_value($currency['code']);
                                foreach ($defPr as $k => $v) {
                                  if (!in_array($k, ['products_group_discount_price'])) {
                                    $importArray['inventory'][$inventoryIdx]['prices'][$checkKey][$k] =  $this->applyRate($v,$rateConvertFrom);
                                  }
                                }
                            }
                        }

                        $inventoryPriceInfo = isset($inventory_prices_data[$inventoryInfo['products_id']])?$inventory_prices_data[$inventoryInfo['products_id']]:[];
                        if ( !isset($inventoryPriceInfo['prices']) ) continue;

                        $importArray['inventory'][$inventoryIdx] = array_merge($importArray['inventory'][$inventoryIdx], $inventoryPriceInfo);

                        if ( $importArray['products_price_full'] ) {
                            $importArray['inventory'][$inventoryIdx]['inventory_full_price'] = $inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_full_price'];
                            if ( $useCurrency!=DEFAULT_CURRENCY ) {
                                $importArray['inventory'][$inventoryIdx]['inventory_full_price'] = $this->applyRate($importArray['inventory'][$inventoryIdx]['inventory_full_price'],1/$rateConvertFrom);
                            }

                            if ( isset($inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_discount_full_price']) ) {
                                $importArray['inventory'][$inventoryIdx]['inventory_discount_full_price'] = $inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_discount_full_price'];
                                if ( $useCurrency!=DEFAULT_CURRENCY ) {
                                    $importArray['inventory'][$inventoryIdx]['inventory_discount_full_price'] = $this->applyRate($importArray['inventory'][$inventoryIdx]['inventory_discount_full_price'],1/$rateConvertFrom);
                                }
                            }
                        }else{
                            $importArray['inventory'][$inventoryIdx]['inventory_price'] = $inventory_prices_data['prices'][$useCurrency . '_0']['inventory_group_price'];
                            if ( $useCurrency!=DEFAULT_CURRENCY ) {
                                $importArray['inventory'][$inventoryIdx]['inventory_price'] = $this->applyRate($importArray['inventory'][$inventoryIdx]['inventory_price'],1/$rateConvertFrom);
                            }

                            if ( isset($inventory_prices_data['prices'][$useCurrency . '_0']['inventory_group_discount_price']) ) {
                                $importArray['inventory'][$inventoryIdx]['inventory_discount_price'] = $inventory_prices_data['prices'][$useCurrency . '_0']['inventory_group_discount_price'];
                                if ( $useCurrency!=DEFAULT_CURRENCY ) {
                                    $importArray['inventory'][$inventoryIdx]['inventory_discount_price'] = $this->applyRate($importArray['inventory'][$inventoryIdx]['inventory_discount_price'],1/$rateConvertFrom);
                                }
                            }
                        }
                    }
                }
            }


/// 2do mark inventory doesn't exists if it's not set.
            //\common\helpers\Inventory::get_inventory_uprid($ar, $idx);


            if ( isset($importArray['inventory']) && is_array($importArray['inventory']) ) {
              unset($importArray['warehouses_products']);
//echo 'inventory <pre>'; var_dump($importArray['inventory']); echo '</pre>';
            }
            if ( isset($importArray['attributes']) && is_array($importArray['attributes']) ) {
              $importArray['attributes'] = array_values($importArray['attributes']);
            }

            unset($importArray['stock_indication_id']);
            unset($importArray['stock_delivery_terms_id']);
            unset($importArray['stock_indication_text']);
            unset($importArray['stock_delivery_terms_text']);

            $patch_platform_assign = false;
            if ( $localProduct->isNewRecord ) {
                /*
                if ( !isset($importArray['products_price']) ) {
                    $importArray['products_price'] = 10;
                }
                $importArray['products_quantity'] = 100;
                */
                $patch_platform_assign = true;
                $importArray['assigned_platforms'] = $this->config['assign_platforms'];

                $importArray['stock_indication_id'] = intval($this->config['product_new']['stock_indication_id']);
                $importArray['stock_delivery_terms_id'] = intval($this->config['product_new']['stock_delivery_terms_id']);
            }else{
                unset($importArray['products_status']);
                unset($importArray['products_last_modified']);
            }
echo "\n\n AR Import#### <PRE>" .print_r($importArray, 1) ."</PRE>";
/*
            if (!isset($importArray['set_products'])) {
              $importArray['set_products'] = [];
            }*/
            $localProduct->importArray($importArray);
echo "imported #### <PRE>" .print_r($localProduct, 1) ."</PRE>";

            if ($localProduct->save(false)) {
                $localProduct->refresh();

                $localId = $localProduct->products_id;

                $server_last_modified = date('Y-m-d H:i:s', strtotime($product->products_date_added));
                if ($product->products_last_modified > 1000) {
                    $_last_modified = date('Y-m-d H:i:s', strtotime($product->products_last_modified));
                    if ($_last_modified > $server_last_modified) {
                        $server_last_modified = $_last_modified;
                    }
                }
                $local_modify_time = $localProduct->products_last_modified>1000?$localProduct->products_last_modified:$localProduct->products_date_added;

                $this->linkRemoteWithLocalId($remoteProductId, $localId, $local_modify_time, $server_last_modified);

                if ( $patch_platform_assign ) {
//                        tep_db_query(
//                            "INSERT IGNORE INTO platforms_products (platform_id, products_id) ".
//                            "VALUES ('".intval(\common\classes\platform::defaultId())."', '".intval($localProduct->products_id)."')"
//                        );
                    tep_db_query(
                        "INSERT IGNORE INTO platforms_products (platform_id, products_id) ".
                        "SELECT platform_id, '".intval($localProduct->products_id)."' FROM platforms WHERE is_virtual=0 and is_marketplace = 0 "
                    );
                }

                echo '<pre>!!! '; var_dump($localId); echo '</pre>';
            }
        }
        unset($localProduct);
    }

    protected function updateProductOnServer(Messages $message, $remote_product_id, Products $product)
    {

        $local_product_id = $product->products_id;
        \Yii::info('updateProductOnServer #'.$local_product_id.'=>#'.$remote_product_id, 'datasource');

        $productData = $this->makeSoapProduct($product);
        $productData['products_id'] = $remote_product_id;

        $updateFlags = $this->getProductSyncConfig($local_product_id);

        if ( isset($updateFlags['seo_server']) && $updateFlags['seo_server']===false && isset($updateFlags['description_server']) && $updateFlags['description_server']===false ) {
            unset($productData['descriptions']);
        }elseif(isset($productData['descriptions']) && is_array($productData['descriptions'])){
            $descriptionKeys = ['products_name', 'products_description', 'products_description_short'];
            foreach ($productData['descriptions'] as $_idx=>$cData ){
                if (isset($updateFlags['seo_server']) && $updateFlags['seo_server']===false){
                    foreach( array_keys($cData) as $_dK ) {
                        if ( !in_array($_dK,$descriptionKeys) ) unset($productData['descriptions'][$_idx][$_dK]);
                    }
                }
                if (isset($updateFlags['description_server']) && $updateFlags['description_server']===false){
                    foreach( array_keys($cData) as $_dK ) {
                        if ( in_array($_dK,$descriptionKeys) ) unset($productData['descriptions'][$_idx][$_dK]);
                    }
                }
            }
        }
        if ( isset($updateFlags['dimensions_server']) && $updateFlags['dimensions_server']===false ) {
            unset($productData['dimensions']);
        }

        if ( isset($updateFlags['identifiers_server']) && $updateFlags['identifiers_server']===false ) {
            foreach( ['products_model', 'products_ean', 'products_asin', 'products_isbn', 'products_upc', 'manufacturers_name','manufacturers_id'] as $identifier_key ) {
                unset($productData[$identifier_key]);
            }
        }
        if ( isset($updateFlags['prices_server']) && $updateFlags['prices_server']==='as_is' ) {
            // leave && pass
        }else{
            $productData['prices'] = [];
        }

        if ( isset($updateFlags['stock_server']) && $updateFlags['stock_server']==='as_is' ) {
            //$productData['stock_info'] = [];
        }else{
            $productData['stock_info'] = null;
        }

        //$productData['assigned_categories'] = null;
        if ( !isset($updateFlags['attr_server']) || $updateFlags['attr_server']===true ) {

        }else {
            $productData['attributes'] = null;
        }
        if ( !isset($updateFlags['images_server']) || $updateFlags['images_server']===true ) {

        }else{
            $productData['images'] = null;
        }
        if ( !isset($updateFlags['properties_server']) || $updateFlags['properties_server']===true ) {

        }else {
            $productData['properties'] = null;
        }

        $updateProductResult = false;
        try{
            $response = $this->client->updateProduct($productData);
            \Yii::info('updateProduct OK '."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            if ( $response && $response->status=='ERROR' ) {
                $messageText = '';
                if (isset($response->messages) && isset($response->messages->message)) {
                    $messages = json_decode(json_encode($response->messages->message), true);
                    $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                    $messageText = '';
                    foreach ($messages as $messageItem) {
                        $messageText .= "\n" . ' * [' . $messageItem['code'] . '] ' . $messageItem['text'];
                    }
                }
                $message->info("Update product #{$local_product_id} error ".$messageText);
            }else{
                //$updateProductResult = true;
                $updateProductResult = (isset($response->product) && is_object($response->product))?$response->product:true;
            }
        }catch (\Exception $ex){
            \Yii::info('updateProduct SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            $message->info("Update product #{$local_product_id} exception: ".$ex->getMessage());
        }
        return $updateProductResult;
    }

    protected function createProductsOnServer(Messages $message, $local_product_id)
    {

        $product = Products::findOne(['products_id'=>$local_product_id]);
        if ( !is_object($product) || empty($product->products_id) ) {
            \Yii::info('createProductOnServer #'.$local_product_id.' fail - product load error', 'datasource');
            return;
        }

        $productData = $this->makeSoapProduct($product);

        try{
            $response = $this->client->createProduct($productData);
            \Yii::info('createProduct OK '."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            if ( $response && $response->status=='ERROR' ) {
                $messageText = '';
                if (isset($response->messages) && isset($response->messages->message)) {
                    $messages = json_decode(json_encode($response->messages->message), true);
                    $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                    $messageText = '';
                    foreach ($messages as $messageItem) {
                        $messageText .= "\n" . ' * [' . $messageItem['code'] . '] ' . $messageItem['text'];
                    }
                }
                $message->info("Create product #{$local_product_id} error ".$messageText);
            }else{

                $remote_product_id = $response->productId;
                if ( $remote_product_id ) {
                    $server_last_modified = date('Y-m-d H:i:s', strtotime($response->product->products_date_added));
                    if ($response->product->products_last_modified > 1000) {
                        $_last_modified = date('Y-m-d H:i:s', strtotime($response->product->products_last_modified));
                        if ($_last_modified > $server_last_modified) {
                            $server_last_modified = $_last_modified;
                        }
                    }

                    $local_modify_time = $product->products_last_modified > 1000 ? $product->products_last_modified : $product->products_date_added;

                    tep_db_perform('ep_holbi_soap_link_products', [
                        'ep_directory_id' => $this->config['directoryId'],
                        'remote_products_id' => $remote_product_id,
                        'local_products_id' => $local_product_id,
                        'server_last_modified' => $server_last_modified,
                        'client_processed_last_modified' => $local_modify_time,
                    ]);
                }
            }
        }catch (\Exception $ex){
            \Yii::info('createProduct ERROR '."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            $message->info("Create product #{$local_product_id} exception: ".$ex->getMessage());
        }

        \Yii::info('createProductOnServer #'.$local_product_id.'=>#'.$remote_product_id, 'datasource');
    }

    protected function makeSoapProduct(Products $product)
    {
        $exportArray = $product->exportArray([]);

        $productData = [];
        foreach( $exportArray as $key=>$value ) {
            if ( isset($childCollection[$key]) ) continue;
            $productData[$key] = $value;
        }
        unset($productData['products_id']);
        if ( isset($exportArray['descriptions']) ){
            $productData['descriptions'] = [];
            if ( is_array($exportArray['descriptions']) ) {
                foreach ($exportArray['descriptions'] as $cKey=>$cData) {
                    list( $langCode, $_tmp ) = explode('_',$cKey,2);
                    if ( (int)$_tmp!=0 ) continue;
                    $cData['language'] = $langCode;
                    $productData['descriptions'][] = $cData;
                }
            }
        }

        $dimensions = [];
        foreach ( preg_grep('/_cm$/', array_keys($exportArray)) as $dimKey ) {
            $dimensions[$dimKey] = $exportArray[$dimKey];
        }
        if (count($dimensions)>0){
            $productData['dimensions'] = $dimensions;
        }

        $productData['prices'] = [
            //'products_id' => $productData['products_id'],
            'price_info' => \backend\models\EP\Provider\NetSuite\Helper::makeExportProductPrices($product->products_id),
        ];


        $productData['stock_info'] = null;
        $productData['stock_info'] = \backend\models\EP\Provider\NetSuite\Helper::makeExportProductStock($product);

        $productData['assigned_categories'] = null;
        if ( isset($exportArray['assigned_categories']) && is_array($exportArray['assigned_categories']) ) {
            foreach ($exportArray['assigned_categories'] as $idx => $categoryInfo) {
                $remoteCategoryId = \backend\models\EP\Provider\NetSuite\Helper::getRemoteCategoryId($this->config['directoryId'], $categoryInfo['categories_id']);
                if ( $remoteCategoryId!==false ) {
                    $productData['assigned_categories'][] = [
                        'categories_id' => $remoteCategoryId,
                        'sort_order' => $categoryInfo['sort_order'],
                    ];
                }elseif(false){
                    $categories_path = [];
                    foreach ( $categoryInfo['categories_path_array'] as $categories_path_item ) {
                        $remoteCategoryId = \backend\models\EP\Provider\NetSuite\Helper::getRemoteCategoryId($this->config['directoryId'], $categories_path_item['id']);
                        if ( $remoteCategoryId!==false ) {
                            $categories_path[] = [
                                'id' => $remoteCategoryId,
                                'text' => $categories_path_item['text'],
                            ];
                        }else{
                            $categories_path[] = [
                                'text' => $categories_path_item['text'],
                            ];
                        }
                    }
                    $productData['assigned_categories'][] = [
                        'sort_order' => $categoryInfo['sort_order'],
                        'categories_path' => $categoryInfo['categories_path'],
                        'categories_path_array' => [
                            'category'=> $categories_path,
                        ]
                    ];
                }
            }
        }

        $productData['attributes'] = null;

        if (isset($exportArray['attributes']) && is_array($exportArray['attributes']) && count($exportArray['attributes']) > 0) {
            $productData['attributes']['attribute'] = [];
            foreach ($exportArray['attributes'] as $attribute) {
                $exportAttribute = [
                    'options_name' => $attribute['options_name'],
                    'options_values_name' => $attribute['options_values_name'],
                    'products_options_sort_order' => $attribute['products_options_sort_order'],
                ];
                $options_id = \backend\models\EP\Provider\NetSuite\Helper::lookupRemoteOptionId($this->config['directoryId'], $attribute['options_id']);
                if ($options_id !== false) {
                    $exportAttribute['options_id'] = $options_id;
                    $options_values_id = \backend\models\EP\Provider\NetSuite\Helper::lookupRemoteOptionValueId($this->config['directoryId'], $attribute['options_id'], $attribute['options_values_id']);
                    if ($options_values_id !== false) {
                        $exportAttribute['options_values_id'] = $options_values_id;
                    }
                }

                $productData['attributes']['attribute'][] = $exportAttribute;
            }
        }

        $productData['images'] = null;

        if ( isset($exportArray['images']) && is_array($exportArray['images']) && count($exportArray['images'])>0 ) {
            $productData['images'] = ['image'=>[]];
            foreach( $exportArray['images'] as $image ) {
                $image_descriptions = ['description' => []];
                foreach( $image['image_description'] as $langCode => $image_description ) {
                    $image_description['language'] = $langCode;

                    if ( empty($image_description['image_source_url']) && !empty($image_description['external_image_original']) ) {
                        $image_description['image_source_url'] = $image_description['external_image_original'];
                    }

                    if ( isset($image_description['image_sources']) && is_array($image_description['image_sources']) ) {
                        $image_description['image_sources'] = ['image_source'=>$image_description['image_sources']];
                    }

                    $image_descriptions['description'][] = $image_description;
                }
                unset($image['image_description']);
                $image['image_descriptions'] = $image_descriptions;
                $productData['images']['image'][] = $image;
            }
        }

        $productData['properties'] = null;
        $productData['properties'] = null;
        if ( isset($exportArray['properties']) && is_array($exportArray['properties']) && count($exportArray['properties'])>0 ) {
            $properties = [
                'property' => [],
            ];
            foreach( $exportArray['properties'] as $property ) {
                $property['names'] = Helper::makeLanguageValueMap($property['names']);
                $property['name_path'] = Helper::makeLanguageValueMap($property['name_path']);
                $property['values'] = Helper::makeLanguageValueMap($property['values']);
                $properties['property'][] = $property;
            }
            $productData['properties'] = $properties;
        }
        $productData['xsells'] = null;
        $productData['documents'] = null;
        if ( isset($exportArray['documents']) && is_array($exportArray['documents']) && count($exportArray['documents'])>0 ) {
            $productData['documents'] = ['document'=>[]];
            foreach ($exportArray['documents'] as $document) {
                $documentData = $document;
                $documentData['descriptions'] = [];
                unset($documentData['titles']);
                if ( isset($document['titles']) && is_array($document['titles']) ) {
                    $documentData['descriptions']['description'] = [];
                    foreach ($document['titles'] as $docTitleLanguageCode=>$documentDescription) {
                        $documentDescription['language'] = $docTitleLanguageCode;
                        $documentData['descriptions']['description'][] = $documentDescription;
                    }
                }
                $productData['documents']['document'][] = $documentData;
            }
        }

        if ( isset($productData['products_date_added']) && $productData['products_date_added']>1000 ) {
            $productData['products_date_added'] = (new \DateTime($productData['products_date_added']))->format(DATE_ISO8601);
        }
        if ( isset($productData['products_last_modified']) && $productData['products_last_modified']>1000 ) {
            $productData['products_last_modified'] = (new \DateTime($productData['products_last_modified']))->format(DATE_ISO8601);
        }

        return $productData;
    }

    protected function searchLocalId($searchBy) {
      $ret = false;
      if (is_array($searchBy)) {
        foreach ($searchBy as $v) {
          $r = tep_db_query("select distinct p.products_id from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id=pd.products_id where " . $v['key'] . "='" . tep_db_input($v['value']) . "'");
          if (tep_db_num_rows($r)==1) {
            $d = tep_db_fetch_array($r);
            $ret = $d['products_id'];
            break;
          }
          if (false && $v['key']=='products_name') {
            $strip = [' ', ',', '-', '_', '.'];
            $v['value'] = str_replace($strip, '', $v['value']);
            $r = $v['key'];
            foreach ($strip as $s) {
              $r = "REPLACE(" . $r . ", '" . $s . "', '')";
            }
            $v['key'] = $r;

            $r = tep_db_query("select distinct p.products_id from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id=pd.products_id where " . $v['key'] . "='" . tep_db_input($v['value']) . "'");
            if (tep_db_num_rows($r)==1) {
              $d = tep_db_fetch_array($r);
              $ret = $d['products_id'];
              break;
            }
          }
        }
      }
      return $ret;
    }

    protected function lookupLocalId($remoteId)
    {
        $get_local_id_r = tep_db_query(
            "SELECT local_products_id ".
            "FROM ep_holbi_soap_link_products ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND remote_products_id='".$remoteId."'"
        );
        if ( tep_db_num_rows($get_local_id_r)>0 ) {
            $_local_id = tep_db_fetch_array($get_local_id_r);
            tep_db_free_result($get_local_id_r);
            return $_local_id['local_products_id'];
        }
        return false;
    }

    protected function lookupLocalOptionId($remoteId, $remoteNames)
    {
        static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
            $getMapping_r = tep_db_query(
                "SELECT m.local_id, po.products_options_id " .
                "FROM ep_holbi_soap_mapping m " .
                " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON po.products_options_id=m.local_id ".
                "WHERE m.ep_directory_id='" . intval($this->config['directoryId']) . "' AND m.mapping_type='attr_option' " .
                " AND m.remote_id='" . $remoteId . "' ".
                "LIMIT 1"
            );
            $createNewLink = true;
            if (tep_db_num_rows($getMapping_r) > 0) {
                $db_mapping = tep_db_fetch_array($getMapping_r);
                if ( is_null($db_mapping['products_options_id']) ) {
                    // lost link
                    tep_db_query(
                        "DELETE FROM ep_holbi_soap_mapping ".
                        "WHERE ep_directory_id='" . intval($this->config['directoryId']) . "' AND mapping_type='attr_option' " .
                        " AND remote_id='" . $remoteId . "'"
                    );
                }else{
                    $mapping[$remoteId] = $db_mapping['local_id'];
                    $createNewLink = false;
                }
            }
            if ($createNewLink) {
                $tools = new Tools();
                $localOptionId = $tools->get_option_by_name($remoteNames);
                $mapping[$remoteId] = $localOptionId;
                tep_db_perform('ep_holbi_soap_mapping',[
                    'ep_directory_id' => intval($this->config['directoryId']),
                    'mapping_type' => 'attr_option',
                    'remote_id' => $remoteId,
                    'local_id' => $localOptionId,
                ]);
            }
        }
        return intval($mapping[$remoteId]);
    }

    protected function lookupLocalOptionValueId($localOptionId, $remoteId, $remoteNames)
    {
        static $mapping = [];
        if ( !isset($mapping[$localOptionId . '_' . $remoteId]) ) {
            $getMapping_r = tep_db_query(
                "SELECT m.local_id, pov.products_options_values_id " .
                "FROM ep_holbi_soap_mapping m " .
                " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pov.products_options_values_id=m.local_id ".
                "WHERE m.ep_directory_id='" . intval($this->config['directoryId']) . "' AND m.mapping_type='attr_option_value_" . (int)$localOptionId . "' " .
                " AND m.remote_id='" . $remoteId . "' ".
                "LIMIT 1"
            );
            $createNewLink = true;
            if (tep_db_num_rows($getMapping_r) > 0) {
                $db_mapping = tep_db_fetch_array($getMapping_r);
                if ( is_null($db_mapping['products_options_values_id']) ) {
                    // lost link
                    tep_db_query(
                        "DELETE FROM ep_holbi_soap_mapping ".
                        "WHERE ep_directory_id='" . intval($this->config['directoryId']) . "' AND mapping_type='attr_option_value_" . (int)$localOptionId . "' " .
                        " AND remote_id='" . $remoteId . "'"
                    );
                }else{
                    $mapping[$localOptionId . '_' . $remoteId] = $db_mapping['local_id'];
                    $createNewLink = false;
                }
            }
            if ($createNewLink) {
                $tools = new Tools();
                $localOptionValueId = $tools->get_option_value_by_name($localOptionId, $remoteNames);
                $mapping[$localOptionId . '_' . $remoteId] = $localOptionValueId;
                tep_db_perform('ep_holbi_soap_mapping',[
                    'ep_directory_id' => intval($this->config['directoryId']),
                    'mapping_type' => 'attr_option_value_' . (int)$localOptionId,
                    'remote_id' => $remoteId,
                    'local_id' => $localOptionValueId,
                ]);
            }
        }
        return intval($mapping[$localOptionId . '_' . $remoteId]);
    }



    protected function linkRemoteWithLocalId($remoteId, $localId, $local_modify_time=null, $server_last_modified=null)
    {
        $columns = '';
        $columnsValues = '';
        $columnsUpdate = '';
        if ( !empty($local_modify_time) ) {
            $columns .= ', client_processed_last_modified ';
            $columnsValues .= ", '".tep_db_input($local_modify_time)."' ";
            $columnsUpdate .= ", client_processed_last_modified='".tep_db_input($local_modify_time)."' ";
        }
        if ( !empty($server_last_modified) ) {
            $columns .= ', server_last_modified ';
            $columnsValues .= ", '".tep_db_input($server_last_modified)."' ";
            $columnsUpdate .= ", server_last_modified='".tep_db_input($server_last_modified)."' ";
        }

        tep_db_query(
            "INSERT INTO ep_holbi_soap_link_products(ep_directory_id, remote_products_id, local_products_id{$columns} ) ".
            " VALUES ".
            " ('".(int)$this->config['directoryId']."', '".$remoteId."','".$localId."'{$columnsValues}) ".
            "ON DUPLICATE KEY UPDATE ep_directory_id='".(int)$this->config['directoryId']."', remote_products_id='".$remoteId."'{$columnsUpdate}"
        );
        return true;
    }

    protected function transformProduct($responseObject)
    {
        $product = [];
        $nsp =  json_decode(json_encode($responseObject),true);
        Helper::applyMap(self::$mapRL, $nsp, $product);
echo "\n mytransform  #### <PRE>" .print_r($product, 1) ."</PRE>";
        $indexed_arrays = [
            'descriptions.description' => 'description',
            'assigned_categories.assigned_category' => 'assigned_category',
            'categories_path_array.category' => 'category',
            'attributes.attribute' => 'attribute',
            'inventories.inventory' => 'inventory',
            'attribute_maps.attribute_map' => 'attribute_map',
            'images.image' => 'image',
            'image_descriptions.image_description'=>'image_description',
            'image_sources.image_source' => 'image_source',
            'properties.property' => 'property',
            'name_path.language_value' => 'language_value',
            'names.language_value' => 'language_value',
            'values.language_value' => 'language_value',
            'xsells.xsell' => 'xsell',
            'documents.document' => 'document',
        ];
        $t1 = microtime(true);
        $product = \backend\models\EP\ArrayTransform::transformMulti(
            function($fromPath) use ($indexed_arrays) {
                foreach ($indexed_arrays as $search=>$exclude) {
                    if ( strpos($fromPath,$search)!==false ) {
                        if ( preg_match('/(^|\.)'.preg_quote($search,'/').'\.\d/',$fromPath) ) {
                            $fromPath = str_replace($search.'.', str_replace('.'.$exclude.'.','.',$search.'.'), $fromPath);
                        }else {
                            $chunks = explode('.', $fromPath);
                            $removalIdx = array_search($exclude, $chunks);

                            if ($removalIdx !== false) {
                                if (is_numeric($chunks[$removalIdx + 1])) {
                                    unset($chunks[$removalIdx]);
                                } else {
                                    $chunks[$removalIdx] = '0';
                                }
                                $fromPath = implode('.', $chunks);
                            }
                        }
                    }
                }
                $fromPath = str_replace('inventories.','inventory.',$fromPath);
                $fromPath = str_replace('.attribute_maps.','.attribute_map.',$fromPath);
                $fromPath = str_replace('.image_descriptions.','.image_description.',$fromPath);
                $fromPath = str_replace('xsells.','xsell.',$fromPath);

                return $fromPath;
            },
            $product);

        if ( isset($product['descriptions']) && is_array($product['descriptions']) ) {
            $rebuild = [];
            foreach( $product['descriptions'] as $description ) {
                $rebuild[$description['language'].'_0'] = $description;
            }
            $product['descriptions'] = $rebuild;
            unset($rebuild);
        }
        
        if ( isset($product['images']) && is_array($product['images']) ) {
            $rebuild = [];
            foreach( $product['images'] as $idx=>$data ) {
                if ( isset($data['image_description']) && is_array($data['image_description']) ) {
                    $desc = [];
                    foreach ($data['image_description'] as $image_description){
                        $desc[$image_description['language']] = $image_description;
                    }
                    $data['image_description'] = $desc;
                }
                $rebuild[$idx] = $data;
            }
            $product['images'] = $rebuild;
            unset($rebuild);
        }
        if ( isset($product['dimensions']) && is_array($product['dimensions']) ) {
            $dimensionData = $product['dimensions'];
            foreach ( $dimensionData as $dimKey=>$dimValue ) {
                if ( preg_match('/(length|width|height)_cm$/',$dimKey) ) {
                    $dimensionData[preg_replace('/_cm$/','_in',$dimKey)] = round(0.393707143*$dimValue,2);
                    //$create_data_array[$metricKey.'cm'] = 2.539959*$create_data_array[$metricKey.'in'];
                }elseif ( preg_match('/(weight)_cm$/',$dimKey) ) {
                    $dimensionData[preg_replace('/_cm$/','_in',$dimKey)] = round(2.20462262*$dimValue, 2);
                    //$create_data_array[$metricKey.'cm'] = 0.45359237*$create_data_array[$metricKey.'in'];
                }
            }
            if ( isset($dimensionData['weight_cm']) ) {
                $dimensionData['products_weight'] = $dimensionData['weight_cm'];
            }
            $product = array_merge($product, $dimensionData);
            unset($product['dimensions']);
        }

        if ( isset($product['properties']) && is_array($product['properties']) ) {
            foreach ($product['properties'] as $idx=>$property) {
                if ( isset($property['names']) && is_array($property['names']) ) {
                    $rebuild_array = $property['names'];
                    $product['properties'][$idx]['names'] = [];
                    foreach ($rebuild_array as $language_value) {
                        $product['properties'][$idx]['names'][ $language_value['language'] ] = $language_value['text'];
                    }
                }
                if ( isset($property['name_path']) && is_array($property['name_path']) ) {
                    $rebuild_array = $property['name_path'];
                    $product['properties'][$idx]['name_path'] = [];
                    foreach ($rebuild_array as $language_value) {
                        $product['properties'][$idx]['name_path'][ $language_value['language'] ] = $language_value['text'];
                    }
                }
                if ( isset($property['values']) && is_array($property['values']) ) {
                    $rebuild_array = $property['values'];
                    $product['properties'][$idx]['values'] = [];
                    foreach ($rebuild_array as $language_value) {
                        $product['properties'][$idx]['values'][ $language_value['language'] ] = $language_value['text'];
                    }
                }
            }
        }

        if ( isset($product['documents']) && is_array($product['documents']) ) {
            foreach ($product['documents'] as $idx => $documentData) {
                if ( isset($documentData['descriptions']) && is_array($documentData['descriptions']) ) {
                    $product['documents'][$idx]['titles'] = [];
                    foreach ($documentData['descriptions'] as $documentDescription){
                        $product['documents'][$idx]['titles'][ $documentDescription['language'] ] = $documentDescription;
                    }
                    unset($product['documents'][$idx]['descriptions']);
                }
            }
        }

        if ( !empty($product['products_date_added']) ) {
            $product['products_date_added'] = date('Y-m-d H:i:s', strtotime($product['products_date_added']));
        }
        if ( !empty($product['products_last_modified']) && $product['products_last_modified']>1 ) {
            $product['products_last_modified'] = date('Y-m-d H:i:s', strtotime($product['products_last_modified']));
        }

        // {{ switch external images ON
        if ( isset($product['images']) && is_array($product['images']) ) {
            foreach ($product['images'] as $_idx=>$images) {
                if ( isset($images['image_description']) && is_array($images['image_description']) ) {
                    foreach( $images['image_description'] as $__idx=>$image_description ) {
                        if ( isset($this->config['products']['images_copy']) && $this->config['products']['images_copy']==='copy' ) {
                            $product['images'][$_idx]['image_description'][$__idx]['use_external_images'] = 0;
                            $product['images'][$_idx]['image_description'][$__idx]['external_urls'] = [];
                        }else {
                            $product['images'][$_idx]['image_description'][$__idx]['external_image_original'] = $image_description['image_source_url'];
                            if (isset($image_description['image_sources']) && is_array($image_description['image_sources']) && count($image_description['image_sources']) > 0) {
                                $product['images'][$_idx]['image_description'][$__idx]['image_source_url'] = '';
                                $product['images'][$_idx]['image_description'][$__idx]['hash_file_name'] = '';
                                $product['images'][$_idx]['image_description'][$__idx]['use_external_images'] = 1;
                                $external_urls = [];
                                foreach ($image_description['image_sources'] as $image_source) {
                                    $external_urls[] = [
                                        'image_types_name' => $image_source['size'],
                                        'image_url' => $image_source['url'],
                                    ];
                                }
                                $product['images'][$_idx]['image_description'][$__idx]['external_urls'] = $external_urls;
                                unset($product['images'][$_idx]['image_description'][$__idx]['image_sources']);
                            }
                        }
                    }
                }
            }
        }
        // }} switch external images ON
        return $product;
    }

    protected function lookupLocalCategoryId($remoteId)
    {
        static $cached = [];
        $key = (int)$this->config['directoryId'].'^'.(int)$remoteId;
        if ( isset($cached[$key]) ) {
            return $cached[$key];
        }
        $getMap_r = tep_db_query(
            "SELECT local_category_id ".
            "FROM ep_holbi_soap_link_categories ".
            "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
            " AND remote_category_id='".$remoteId."' ".
            "LIMIT 1 "
        );
        if ( tep_db_num_rows($getMap_r)>0 ) {
            $getMap = tep_db_fetch_array($getMap_r);
            $cached[$key] = $getMap['local_category_id'];
            return $getMap['local_category_id'];
        }
        return false;
    }

    protected function getLocalCategoryId($remoteCategoryInfo)
    {
        $localId = false;
        $lastKnownIdIdx = false;
        for( $i=count($remoteCategoryInfo['categories_path_array'])-1; $i>=0; $i-- ) {
            $localId = $this->lookupLocalCategoryId($remoteCategoryInfo['categories_path_array'][$i]['id']);
            if ( $localId!==false ) {
                $lastKnownIdIdx = $i;
                break;
            }
        }

        if ( $lastKnownIdIdx===false && $remoteCategoryInfo['categories_path_array'][0]['id']==0 && $localId===false ) {
            return 0; // not mapped top - map to local top
        }
        if ( $lastKnownIdIdx===false ) $lastKnownIdIdx = -1;

        for( $i=$lastKnownIdIdx+1; $i<count($remoteCategoryInfo['categories_path_array']); $i++ ) {
            $categoryParentId = $localId?$localId:0;

            $db_lookup_r = tep_db_query(
                "SELECT c.categories_id ".
                "FROM ".TABLE_CATEGORIES." c, ".TABLE_CATEGORIES_DESCRIPTION." cd ".
                "WHERE cd.categories_id = c.categories_id AND cd.language_id = '".\common\classes\language::defaultId()."' AND cd.affiliate_id=0 ".
                " AND c.parent_id='".(int)$categoryParentId."' ".
                " AND cd.categories_name = '".tep_db_input($remoteCategoryInfo['categories_path_array'][$i]['text'])."' ".
                "LIMIT 1"
            );
            if ( tep_db_num_rows($db_lookup_r)>0 ) {
                $db_lookup = tep_db_fetch_array($db_lookup_r);
                $localId = $db_lookup['categories_id'];
            }else {
                $categoryData = [];
                try {
                  //2do (translations)
                    $category = Helper::$nsCategories[(int)$remoteCategoryInfo['categories_path_array'][$i]['id']];
                    $categoryData = $this->transformCategory($category);
                    unset($categoryData['categories_status']);
                    unset($categoryData['categories_id']);
                    unset($categoryData['parent_id']);

/*                    $response = $this->client->getCategory((int)$remoteCategoryInfo['categories_path_array'][$i]['id']);
                    if ($response->status != 'ERROR' && $response->category) {
                        $categoryData = $this->transformCategory($response->category);
                        unset($categoryData['categories_status']);
                        unset($categoryData['categories_id']);
                        unset($categoryData['parent_id']);
                    }*/
                }catch (\Exception $ex){

                }
                $categoryData['categories_status'] = 1;
                $categoryData['parent_id'] = $categoryParentId;
                if ( !isset($categoryData['descriptions']) || !is_array($categoryData['descriptions']) || count($categoryData['descriptions'])==0 ) {
                    $categoryData['descriptions'] = [
                        '*' => [
                            'categories_name' => $remoteCategoryInfo['categories_path_array'][$i]['text'],
                        ]
                    ];
                }
                $categoryData['assigned_platforms'] = $this->config['assign_platforms'];

                $category = new Categories();
                $category->importArray($categoryData);
                $category->save();
                $localId = $category->categories_id;
            }
            tep_db_perform('ep_holbi_soap_link_categories',[
                'ep_directory_id' => (int)$this->config['directoryId'],
                'remote_category_id' => (int)$remoteCategoryInfo['categories_path_array'][$i]['id'],
                'local_category_id' => (int)$localId,
            ]);
        }
        return $localId;
    }
/**
 * sync x-sell ???
 * @param Messages $messages
 */
    protected function syncRequiredEntities(Messages $messages)
    {

      /*
        $catalogJob = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'NetSuite\\SynchronizeCatalog',
        ]);
        $catalogJob->run($messages);

        //$jobForCategories = Directory::loadById($this->config['directoryId'])->findJobByFilename('SynchronizeCatalog');
        //echo '<pre>'; var_dump($jobForCategories); echo '</pre>';
        // {{ xsell type list
        foreach( $this->xsellTypeMap as $remoteId=>$localId ) {
            $check = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c FROM ".TABLE_PRODUCTS_XSELL_TYPE." WHERE xsell_type_id='".(int)$localId."' "
            ));
            if ( $check['c']==0 ) {
                // invalid mapping
                unset($this->xsellTypeMap[$remoteId]);
                tep_db_query(
                    "DELETE FROM ep_holbi_soap_mapping ".
                    "WHERE ep_directory_id='".intval($this->config['directoryId'])."' ".
                    " AND mapping_type='xsell_type' AND remote_id='".(int)$remoteId."'"
                );
            }
        }
        $remoteXsellList = $this->client->getXsellTypes();
        if ( $remoteXsellList->xsell_types ){
            $xsell_types = isset($remoteXsellList->xsell_types->xsell_type)?$remoteXsellList->xsell_types->xsell_type:[];
            if (is_object($xsell_types)){
                $xsell_types = [$xsell_types];
            }
            foreach( $xsell_types as $xsell_type ) {
                $remote_id = $xsell_type->id;

                $names = is_array($xsell_type->names->language_value)?$xsell_type->names->language_value:[$xsell_type->names->language_value];

                if ( !isset( $this->xsellTypeMap[$remote_id] ) ) {
                    // create
                    $local_xsell_id = false;

                    foreach( $names as $name ) {
                        $name = (array)$name;
                        //$name['text'];
                        $language_id = language::get_id($name['language']);
                        if ( !$local_xsell_id ) {
                            // try search not linked same name before create
                            $reuse_existing_r = tep_db_query(
                                "SELECT xsell_type_id ".
                                "FROM ".TABLE_PRODUCTS_XSELL_TYPE." ".
                                "WHERE xsell_type_name = '" . tep_db_input($name['text']) . "' ".
                                ( count($this->xsellTypeMap[$remote_id])>0?" AND xsell_type_id NOT IN('".implode("','",$this->xsellTypeMap[$remote_id])."') ":'' )." ".
                                "LIMIT 1"
                            );
                            if ( tep_db_num_rows($reuse_existing_r)>0 ) {
                                $reuse_existing = tep_db_fetch_array($reuse_existing_r);
                                $local_xsell_id = $reuse_existing['xsell_type_id'];
                                break; // foreach
                            }

                            $get_max_id = tep_db_fetch_array(tep_db_query("SELECT MAX(xsell_type_id) AS max_id FROM " . TABLE_PRODUCTS_XSELL_TYPE));
                            $local_xsell_id = intval($get_max_id['max_id']) + 1;
                            tep_db_query(
                                "INSERT INTO " . TABLE_PRODUCTS_XSELL_TYPE . " (xsell_type_id, language_id, xsell_type_name) " .
                                "SELECT {$local_xsell_id}, languages_id, '" . tep_db_input($name['text']) . "' FROM " . TABLE_LANGUAGES
                            );
                        }else{
                            tep_db_query(
                                "UPDATE " . TABLE_PRODUCTS_XSELL_TYPE . " ".
                                "SET xsell_type_name = '" . tep_db_input($name['text']) . "' ".
                                "WHERE xsell_type_id = '".$local_xsell_id."' AND language_id='".$language_id."' "
                            );
                        }
                    }
                    $this->xsellTypeMap[$remote_id] = $local_xsell_id;
                    tep_db_perform(
                        'ep_holbi_soap_mapping',
                        [
                            'ep_directory_id' => intval($this->config['directoryId']),
                            'mapping_type' => 'xsell_type',
                            'remote_id' => $remote_id,
                            'local_id' => $local_xsell_id,
                        ]
                    );
                }else{
                    // update if need
                    $local_xsell_id = $this->xsellTypeMap[$remote_id];
                    foreach( $names as $name ) {
                        $name = (array)$name;
                        $language_id = language::get_id($name['language']);
                        tep_db_query(
                            "UPDATE " . TABLE_PRODUCTS_XSELL_TYPE . " " .
                            "SET xsell_type_name = '" . tep_db_input($name['text']) . "' " .
                            "WHERE xsell_type_id = '" . $local_xsell_id . "' AND language_id='" . $language_id . "' AND link_update_disable=0 "
                        );
                    }

                }

                //
            }
        }
        // }} xsell type list
*/
    }

    protected function buildDiscountString($tableArray)
    {
        $discount_string = '';
        if ( !is_array($tableArray) ) return $discount_string;
        foreach ($tableArray as $discountI) {
            $discount_string .= "{$discountI['quantity']}:{$discountI['discount_price']};";
        }
        return $discount_string;
    }

    protected function applyRate($price, $rate)
    {
        if ( strpos($price,':')!==false ) {
            // table
            $table = preg_split('/[:;]/',$price,-1);
            $price = '';
            for($i=0; $i<count($table);$i+=2) {
                $price .= "{$table[$i]}:".$table[$i+1]*$rate.";";
            }
        }elseif ( $price>0 ) {
            $price = $price * $rate;
        }
        return $price;
    }

    protected function updateCategoriesFromServer($message)
    {
        $get_data_r = tep_db_query(
            "SELECT lc.remote_category_id AS remote_id, lc.local_category_id AS local_id, ".
            " c.ep_holbi_soap_disable_update ".
            "FROM ep_holbi_soap_link_categories lc ".
            " left join ".TABLE_CATEGORIES." c on c.categories_id=lc.local_category_id ".
            "WHERE lc.ep_directory_id = '".intval($this->config['directoryId'])."' ".
            "/*LIMIT 1*/"
        );
        while($data = tep_db_fetch_array($get_data_r)){
            if ( is_null($data['ep_holbi_soap_disable_update']) ) {
                // local category removed
                tep_db_query(
                    "DELETE FROM ep_holbi_soap_link_categories ".
                    "WHERE ep_directory_id = '".intval($this->config['directoryId'])."' ".
                    " AND local_category_id='".(int)$data['local_id']."' "
                );
                continue;
            }elseif ( $data['ep_holbi_soap_disable_update'] ) {
                continue;
            }
            $response = $this->client->getCategory($data['remote_id']);
            if ( $response->status!='ERROR' && $response->category ) {
                $category = $this->transformCategory($response->category);

                $categoryObj = Categories::findOne(['categories_id'=>$data['local_id']]);
                if ( $categoryObj ) {
                    unset($category['categories_status']);
                    unset($category['categories_id']);
                    unset($category['parent_id']);
                    unset($category['last_modified']);
                    unset($category['date_added']);
                    unset($category['assigned_platforms']);
                    $categoryObj->importArray($category);
                    $categoryObj->save();
                }
            }elseif ( $response->status=='ERROR' ) {
                if ( isset($response->messages) && isset($response->messages->message) ) {
                    $messages = json_decode(json_encode($response->messages->message),true);
                    $messages = ArrayHelper::isIndexed($messages)?$messages:[$messages];
                    foreach( $messages as $message ) {
                        if ( $message['code']=='ERROR_CATEGORY_NOT_FOUND' ) {
                            tep_db_query(
                                "DELETE FROM ep_holbi_soap_link_categories ".
                                "WHERE ep_directory_id = '".intval($this->config['directoryId'])."' ".
                                " AND remote_category_id='".(int)$data['remote_id']."' "
                            );
                            tep_db_query(
                                "UPDATE ".TABLE_CATEGORIES." ".
                                "SET ep_holbi_soap_disable_update=0 ".
                                "WHERE categories_id='".(int)$data['local_id']."' "
                            );
                        }
                    }
                }
            }
        }
    }

    protected function transformCategory($responseObject)
    {
        $category =  json_decode(json_encode($responseObject),true);

        if ( isset($category['descriptions']) && is_array($category['descriptions']) ) {
            $makeFrom = isset($category['descriptions']['description'])?(ArrayHelper::isIndexed($category['descriptions']['description'])?$category['descriptions']['description']:[$category['descriptions']['description']]):[];
            $category['descriptions'] = [];
            foreach( $makeFrom as $description ) {
                $category['descriptions'][$description['language'].'_0'] = $description;
            }
        }

        return $category;
    }
    
    protected function getProductSyncConfig( $productId, $configKey=null )
    {
        $datasourceConfig = $this->config['products'];
        $datasourceConfig['create_on_client'] = isset($datasourceConfig['create_on_client'])?!!$datasourceConfig['create_on_client']:true;
        $datasourceConfig['create_on_server'] = isset($datasourceConfig['create_on_server'])?!!$datasourceConfig['create_on_server']:false;
        $datasourceConfig['update_on_client'] = isset($datasourceConfig['update_on_client'])?!!$datasourceConfig['update_on_client']:true;
        $datasourceConfig['update_on_server'] = isset($datasourceConfig['update_on_server'])?!!$datasourceConfig['update_on_server']:false;

        $get_custom_flags_r = tep_db_query(
            "SELECT pf.flag_name, pf.flag_value ".
            "FROM ep_holbi_soap_category_products_flags pf ".
            " INNER JOIN ".TABLE_CATEGORIES." c ON c.categories_id=pf.categories_id ".
            " INNER JOIN (".
            "     SELECT nsc.categories_id, nsc.categories_left, nsc.categories_right ".
            "     FROM ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ".
            "       INNER JOIN ".TABLE_CATEGORIES." nsc on nsc.categories_id=p2c.categories_id WHERE p2c.products_id=".(int)$productId." ".
            " ) ncj ON c.categories_left < ncj.categories_right AND c.categories_right > ncj.categories_left ".
            "WHERE pf.ep_directory_id='".$this->config['directoryId']."' ".
            "ORDER BY c.categories_left"
        );

        if ( tep_db_num_rows($get_custom_flags_r)>0 ) {
            while($_custom_flag = tep_db_fetch_array($get_custom_flags_r)) {
                $datasourceConfig[$_custom_flag['flag_name']] = !!$_custom_flag['flag_value'];
            }
        }

        $get_custom_flags_r = tep_db_query(
            "SELECT flag_name, flag_value ".
            "FROM ep_holbi_soap_products_flags ".
            "WHERE ep_directory_id='".$this->config['directoryId']."' AND products_id IN (-1, ".(int)$productId.") ".
            "ORDER BY products_id"
        );
        if ( tep_db_num_rows($get_custom_flags_r)>0 ) {
            while($_custom_flag = tep_db_fetch_array($get_custom_flags_r)) {
                $datasourceConfig[$_custom_flag['flag_name']] = !!$_custom_flag['flag_value'];
            }
        }

        if ( !is_null($configKey) ) {
            return isset($datasourceConfig[$configKey])?$datasourceConfig[$configKey]:null;
        }
        return $datasourceConfig;
    }

}
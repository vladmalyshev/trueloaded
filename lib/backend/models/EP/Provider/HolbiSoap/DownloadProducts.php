<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\HolbiSoap;

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

class DownloadProducts implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list;

    protected $config = [];

    protected $afterProcessFilename = '';
    protected $afterProcessFile = false;

    protected $updateImageForModels = [];
    /**
     * @var \SoapClient
     */
    protected $client;

    protected $xsellTypeMap = [];

    protected $completeCatalogDownloaded = false;

    protected $suppliersSupport = false;

    function __construct($config)
    {
        $this->config = $config;
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
        //$this->updateImageForModels = ['1FH45AT', '1FH47AT', '1FH50AT', '2DW53AA', '2MW62AA', '2TS94EA', '2TS99EA', '2UK37AA', '2UW00AA', '2UW01AA', '3JW91EA', '3JX06EA', '3JX13EA', '3JX22EA', '3JY05ET', '3UP82ET', '3YE87AA', '3ZH02EA', '3ZH10EA', '4KW05ET', '4KW14ET', '4KW29ET', '4KW36ET', '4KX09EA', '4KX23ET', '4PD67ET', '4PD71ET', 'K7V17AA', 'T6L04AA', 'T6T83AA', 'W3K09AA',];
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
            "WHERE is_virtual=0"
        );
        while( $available_platform = tep_db_fetch_array($get_available_platforms_r) ) {
            $this->config['assign_platforms'][] = [
                'platform_id' => $available_platform['platform_id'],
            ];
        }

        // init client
        try {
            $this->client = new \SoapClient(
                $this->config['client']['wsdl_location'],
                [
                    'trace' => 1,
                    //'proxy_host'     => "localhost",
                    //'proxy_port'     => 8080,
                    //'proxy_login'    => "some_name",
                    //'proxy_password' => "some_password",
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'stream_context' => stream_context_create([
                        'http' => [
                            //'header'  => "APIToken: $api_token\r\n",
                        ]
                    ]),
                ]
            );
            $auth = new \stdClass();
            $auth->api_key = $this->config['client']['department_api_key'];
            $soapHeaders = new \SoapHeader('http://schemas.xmlsoap.org/ws/2002/07/utility', 'auth', $auth, false);
            $this->client->__setSoapHeaders($soapHeaders);
        }catch (\Exception $ex) {
            throw new Exception('Configuration error');
        }
        try{
            $this->syncRequiredEntities($message);
        }catch(\Exception $ex){
            \Yii::info('syncRequiredEntities [ERROR]:'.$ex->getMessage()."\n".$ex->getTraceAsString()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
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
        static $errorCount = 10;
        $remoteProductRef = current($this->process_list);
        if ( !$remoteProductRef ) return false;

        try {
            $this->processRemoteProduct($message, $remoteProductRef, true);
        }catch (\Exception $ex){
            \Yii::info('processRemoteProduct EXCEPTION '."\n".$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            $errorCount--;
            sleep(2);
            if ( $errorCount==0 ) {
                throw $ex;
            }
        }

        $this->row_count++;
        //if ( $this->row_count>15 ) return false;
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
                            if ( !$xsellLocalId ) continue;
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
            $this->total_count = tep_db_num_rows($need_create_on_server_r);
            $this->row_count = 0;
            $message->info("Create ".$this->total_count." products on server");
            $message->progress(0);
            while ($need_create_on_server = tep_db_fetch_array($need_create_on_server_r)) {
                $this->createProductsOnServer($message, $need_create_on_server['products_id']);
                $this->row_count++;
                if ( ($this->row_count%20)==0 ) {
                    $message->progress(floatval($this->getProgress()));
                }
            }
            $message->progress(100);
        }
    }

    protected function getRemoteProducts()
    {
        $this->process_list = [];
        /*$this->process_list = [16523=>16523];
        return;*/

        $params = [
            'paging' => [
                'page' => 1,
            ]
        ];
        do {
            echo '<pre>GET PAGE '; var_dump($params['paging']['page']); echo '</pre>';
            $response = $this->client->getProductList($params['paging']);
            if (isset($response->products) && isset($response->products->product)) {
                if ( is_array($response->products->product) ) {

                }elseif(is_object($response->products->product)){
                    $response->products->product = [$response->products->product];
                }
                foreach ($response->products->product as $productRef) {
                    $this->process_list[intval($productRef->products_id)] = $productRef;
                }
            }
            $params['paging']['page']++;
            if ( !isset($response->paging) || $params['paging']['page']>$response->paging->totalPages ) {
                break;
            }
        }while(true);
        // NOTE: important, this flag used for cleaning catalog. getProductList need to be without any filters
        $this->completeCatalogDownloaded = true;
    }

    protected function processUnassignedProducts(Messages $message)
    {
        // process removed own products
        $get_removed_products_owned_on_server_r = tep_db_query(
            "SELECT DISTINCT epp.remote_products_id ".
            "FROM ep_holbi_soap_link_products epp ".
            " LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id=epp.local_products_id AND epp.ep_directory_id='".(int)$this->config['directoryId']."' ".
            "WHERE p.products_id IS NULL ".
            "  AND epp.is_own_product=1"
        );
        if ( tep_db_num_rows($get_removed_products_owned_on_server_r) ) {
            while ($removed_id = tep_db_fetch_array($get_removed_products_owned_on_server_r)) {
                try {
                    $response = $this->client->removeProduct($removed_id['remote_products_id']);
                    if ( isset($response->status) && $response->status!='ERROR' ) {
                        tep_db_query(
                            "DELETE FROM ep_holbi_soap_link_products " .
                            "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                            " AND remote_products_id='" . (int)$removed_id['remote_products_id'] . "' " .
                            " AND is_own_product=1"
                        );
                    }
                }catch (\Exception $ex){

                }
            }
        }

        if ( false ) {
            // clear links for locally removed products - removed product create again (if tracked modify time mismatch)
            // ??? better flag them
            tep_db_query(
                "DELETE epp " .
                "FROM ep_holbi_soap_link_products epp " .
                " LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id=epp.local_products_id AND epp.ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                "WHERE p.products_id IS NULL " .
                " AND epp.is_own_product=0"
            );
        }
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

    protected function processRemoteProduct(Messages $messages, $remoteProductRef, $useAfterProcess = false)
    {
        static $timing = [
            'soap' => 0,
            'local' => 0,
        ];
        $t0 = microtime(true);
        $soap_time = 0;

        $remoteProduct = false;

        $server_is_own_product = null;
        if ( is_object($remoteProductRef) ) {
            $remoteProductId = intval($remoteProductRef->products_id);
            $server_last_modified = date('Y-m-d H:i:s', strtotime($remoteProductRef->products_date_added));
            if ($remoteProductRef->products_last_modified > 1000) {
                $_last_modified = date('Y-m-d H:i:s', strtotime($remoteProductRef->products_last_modified));
                if ($_last_modified > $server_last_modified) {
                    $server_last_modified = $_last_modified;
                }
            }
            if ( isset($remoteProductRef->is_own_product) && is_bool($remoteProductRef->is_own_product) ) {
                $server_is_own_product = $remoteProductRef->is_own_product;
            }
        }else{
            $remoteProductId = (int)$remoteProductRef;
            $remoteProductRef = false;

            $t1 = microtime(true);
            $remoteProduct = $this->client->getProduct($remoteProductId);
            $soap_time += microtime(true) - $t1;

            if ( isset($remoteProduct->is_own_product) && is_bool($remoteProduct->is_own_product) ) {
                $server_is_own_product = $remoteProduct->is_own_product;
            }
            $server_last_modified = date('Y-m-d H:i:s', strtotime($remoteProduct->product->products_date_added));
            if ($remoteProduct->product->products_last_modified > 1000) {
                $_last_modified = date('Y-m-d H:i:s', strtotime($remoteProduct->product->products_last_modified));
                if ($_last_modified > $server_last_modified) {
                    $server_last_modified = $_last_modified;
                }
            }

        }

        $localId = $this->lookupLocalId($remoteProductId);

        if ( $localId ) {
            $localProduct = \common\api\models\AR\Products::findOne(['products_id' => $localId]);
            if ( !$localProduct || !$localProduct->products_id ) {
                // product removed locally
                \Yii::info('Skip product (removed locally) remote PID '.$remoteProductId.', old local ID '.$localId,'datasource');
                // touch server time in track table
                tep_db_query(
                    "UPDATE ep_holbi_soap_link_products " .
                    "SET server_last_modified = '".tep_db_input($server_last_modified)."' " .
                    "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                    " AND local_products_id='" . $localId . "' "
                );
                return;
            }
            $local_modify_time = $localProduct->products_last_modified > 1000 ? $localProduct->products_last_modified : $localProduct->products_date_added;

            $getProductsTimes_r = tep_db_query(
                "SELECT server_last_modified, client_processed_last_modified, is_own_product " .
                "FROM ep_holbi_soap_link_products " .
                "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                " AND local_products_id='" . $localId . "' " .
                "LIMIT 1"
            );
            if ( tep_db_num_rows($getProductsTimes_r)>0 ) {
                $getModifyTimes = tep_db_fetch_array($getProductsTimes_r);
                if ( !is_null($server_is_own_product) && is_null($getModifyTimes['is_own_product']) ) {
                    tep_db_query(
                        "UPDATE ep_holbi_soap_link_products ".
                        " SET is_own_product='".($server_is_own_product?'1':'0')."' ".
                        "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
                        " AND local_products_id='" . $localId . "' " .
                        "LIMIT 1"
                    );
                }

                if ($local_modify_time>$getModifyTimes['client_processed_last_modified'] && $this->getProductSyncConfig($localId, 'update_on_server')===true){
                    if ($updatedProduct = $this->updateProductOnServer($messages, $remoteProductId, $localProduct)) {
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
                }elseif(
                    ($server_last_modified>$getModifyTimes['server_last_modified'] && $this->getProductSyncConfig($localId, 'update_on_client')===true)
                    ||
                    (count($this->updateImageForModels)>0 && in_array(strval($localProduct->products_model), $this->updateImageForModels))
                ){
                    if ( $remoteProduct===false ) {
                        $t1 = microtime(true);
                        $remoteProduct = $this->client->getProduct($remoteProductId);
                        $soap_time += microtime(true) - $t1;
                    }
                    if ( $remoteProduct->product ) {
                        $this->createUpdateLocalProduct($localProduct, $remoteProduct->product, $useAfterProcess);
                    }
                }
            }
        }else{
            if ($this->getProductSyncConfig(0, 'create_on_client')===true) {
                if ( $remoteProduct===false ) {
                    $t1 = microtime(true);
                    try {
                        $remoteProduct = $this->client->getProduct($remoteProductId);
                    }catch (\Exception $ex){
                        \Yii::info('getProduct('.$remoteProductId.') EXCEPTION '."\n".$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                        throw $ex;
                    }
                    $soap_time += microtime(true) - $t1;
                }
                if ( $remoteProduct->product ) {
                    $localProduct = new \common\api\models\AR\Products();
                    $this->createUpdateLocalProduct($localProduct, $remoteProduct->product, $useAfterProcess);
                }
            }
        }

        $t3 = microtime(true);
        $timing['soap'] += $soap_time;
        $timing['local']+=($t3-$t0)-$soap_time;
        echo '<pre>'; var_dump($timing); echo '</pre>';
    }

    protected function createUpdateLocalProduct($localProduct, $product, $useAfterProcess)
    {
        $remoteProductId = $product->products_id;
        $is_own_product = $product->is_own_product;

        $localId = false;
        if ( isset($localProduct->products_id) && intval($localProduct->products_id)>0 ) {
            $localId = $localProduct->products_id;
        }

        $updateFlags = $this->getProductSyncConfig($localId);
        $descriptionKeys = ['products_name', 'products_description', 'products_description_short'];
        if ( $localId ) {
            if ( isset($updateFlags['seo_client']) && $updateFlags['seo_client']===false && isset($updateFlags['description_client']) && $updateFlags['description_client']===false ) {
                unset($product->descriptions);
            }

            if ($is_own_product || (isset($updateFlags['prices_client']) && $updateFlags['prices_client'] === 'disabled')) {
                unset($product->prices);
            }
            if ($is_own_product || (isset($updateFlags['stock_client']) && $updateFlags['stock_client'] === 'disabled')) {
                unset($product->stock_info);
            }
            if (isset($updateFlags['attr_client']) && $updateFlags['attr_client'] === false) {
                unset($product->attributes);
                unset($product->inventories);
            }
            if (isset($updateFlags['identifiers_client']) && $updateFlags['identifiers_client'] === false) {
                foreach( ['products_model', 'products_ean', 'products_asin', 'products_isbn', 'products_upc', 'manufacturers_name','manufacturers_id'] as $_iiK ) {
                    unset($product->{$_iiK});
                }
            }
            if (isset($updateFlags['images_client']) && $updateFlags['images_client'] === false) {
                unset($product->images);
            }
            if (isset($updateFlags['dimensions_client']) && $updateFlags['dimensions_client'] === false) {
                unset($product->dimensions);
            }
            if (isset($updateFlags['properties_client']) && $updateFlags['properties_client'] === false) {
                unset($product->properties);
            }
            if (isset($updateFlags['documents_client']) && $updateFlags['documents_client'] === false) {
                unset($product->documents);
            }

            if ( $this->suppliersSupport ) {

            }else{
                unset($product->supplier_product_data);
            }
        }


        if (isset($product->manufacturers_id)) {
            $remoteBrandId = $product->manufacturers_id;
            $localBrandId = $this->getLocalBrandId($remoteBrandId);
            if ( is_numeric($localBrandId) && $localBrandId>0 ) {
                $product->manufacturers_id = $localBrandId;
                unset($product->manufacturers_name);
            }else{
                unset($product->manufacturers_id);
                unset($product->manufacturers_name);
            }
        }

        $importArray = $this->transformProduct($product);
        if ( $localId && isset($importArray['descriptions']) && is_array($importArray['descriptions']) && isset($updateFlags['seo_client']) && $updateFlags['seo_client']===false ) {
            foreach ( $importArray['descriptions'] as $__key=>$__data ) {
                foreach ( array_keys($__data) as $__descKey ) {
                    if ( !in_array($__descKey, $descriptionKeys) ) unset($importArray['descriptions'][$__key][$__descKey]);
                }
            }
        }
        if ( $localId && isset($importArray['descriptions']) && is_array($importArray['descriptions']) && isset($updateFlags['description_client']) && $updateFlags['description_client']===false ) {
            foreach ( $importArray['descriptions'] as $__key=>$__data ) {
                foreach ( array_keys($__data) as $__descKey ) {
                    if ( in_array($__descKey, $descriptionKeys) ) unset($importArray['descriptions'][$__key][$__descKey]);
                }
            }
        }
        if ( $this->suppliersSupport && isset($importArray['supplier_product_data']) ) {
            $supplier_product_data = $importArray['supplier_product_data'];
            unset($importArray['supplier_product_data']);
            $suppliers_data = false;
            if ( isset($supplier_product_data['supplier_product']) ) {
                $suppliers_data = [];
                $supplier_product_data = ArrayHelper::isIndexed($supplier_product_data['supplier_product'])?$supplier_product_data['supplier_product']:[$supplier_product_data['supplier_product']];
                foreach ($supplier_product_data as $supplier_product) {
                    $localSupplierId = Helper::getLocalSupplierId($this->config['directoryId'],$supplier_product['suppliers_id']);
                    if ( $localSupplierId===false ) {
                        Helper::createMapLocalSupplier($this->client, $this->config['directoryId'], $supplier_product['suppliers_id']);
                        $localSupplierId = Helper::getLocalSupplierId($this->config['directoryId'], $supplier_product['suppliers_id']);
                    }
                    if ( !is_numeric($localSupplierId) || $localSupplierId<=0 ) continue;

                    $supplier_product['suppliers_id'] = $localSupplierId;
                    unset($supplier_product['suppliers_name']);
                    if ( isset($supplier_product['date_added']) && $supplier_product['date_added']>1000 ) {
                        $supplier_product['date_added'] = date('Y-m-d H:i:s', strtotime($supplier_product['date_added']));
                    }else{
                        unset($supplier_product['date_added']);
                    }
                    if ( isset($supplier_product['last_modified']) && $supplier_product['last_modified']>1000 ) {
                        $supplier_product['last_modified'] = date('Y-m-d H:i:s', strtotime($supplier_product['last_modified']));
                    }else{
                        unset($supplier_product['last_modified']);
                    }
                    unset($supplier_product['products_id']);

                    $suppliers_data[] = $supplier_product;
                }
            }
            if ( is_array($suppliers_data) && count($suppliers_data)>0 ) {
                 $importArray['suppliers_data'] = $suppliers_data;
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

            $inventory_prices_data = false;
            if ( isset($importArray['prices']) ) {
                $price_in = $importArray['prices'];
                $importArray['prices'] = [];
                $inventory_prices_data = [];
                if ( isset($price_in['price_info']) && is_array($price_in['price_info']) ) {
                    if (!ArrayHelper::isIndexed($price_in['price_info'])) {
                        $price_in['price_info'] = [$price_in['price_info']];
                    }
                    foreach($price_in['price_info'] as $price_info){
                        $importArray['products_price_full'] = $price_info['products_price_full']?1:0;

                        $price_info['currency'];

                        $discountTable = [];
                        if ( isset($price_info['discount_table']['price']) ) {
                            $discountTable = ArrayHelper::isIndexed($price_info['discount_table']['price'])?$price_info['discount_table']['price']:[$price_info['discount_table']['price']];
                        }

                        $importArray['prices'][$price_info['currency'].'_0'] = [
                            'products_group_price' => $price_info['price'],
                            'products_group_discount_price' => $this->buildDiscountString($discountTable),
//                                  //'bonus_points_price' =>
//                                  //'bonus_points_cost' =>
//                                  'products_group_price_pack_unit' => -2.000000,
//                                  'products_group_discount_price_pack_unit' => '',
//                                  'products_group_price_packaging' => -2.000000,
//                                  'products_group_discount_price_packaging' => '',
                        ];

                        if ( isset($price_info['pack']) && is_array($price_info['pack']) ) {
                            if ( isset($price_info['pack']['products_qty']) ) {
                                $importArray['pack_unit'] = $price_info['pack']['products_qty'];
                            }

                            $discountTable = [];
                            if ( isset($price_info['pack']['discount_table']) ) {
                                $discountTable = ArrayHelper::isIndexed($price_info['pack']['discount_table']['price'])?$price_info['pack']['discount_table']['price']:[$price_info['pack']['discount_table']['price']];
                            }

                            $importArray['prices'][$price_info['currency'].'_0']['products_group_price_pack_unit'] = is_null($price_info['pack']['price'])?'-2.000000':$price_info['pack']['price'];
                            $importArray['prices'][$price_info['currency'].'_0']['products_group_discount_price_pack_unit'] = $this->buildDiscountString($discountTable);

                            // pallet inside of pack!! - pallet require pack qty for multiply
                            if ( isset($price_info['pallet']) && is_array($price_info['pallet']) ) {
                                if ( isset($price_info['pallet']['pack_qty']) ) {
                                    $importArray['packaging'] = $price_info['pallet']['pack_qty'];
                                }

                                $discountTable = [];
                                if ( isset($price_info['pallet']['discount_table']) ) {
                                    $discountTable = ArrayHelper::isIndexed($price_info['pallet']['discount_table']['price'])?$price_info['pallet']['discount_table']['price']:[$price_info['pallet']['discount_table']['price']];
                                }

                                $importArray['prices'][$price_info['currency'].'_0']['products_group_price_packaging'] = is_null($price_info['pallet']['price'])?'-2.000000':$price_info['pallet']['price'];
                                $importArray['prices'][$price_info['currency'].'_0']['products_group_discount_price_packaging'] = $this->buildDiscountString($discountTable);
                            }
                        }

                        if ( isset($price_info['attributes_prices']) && $price_info['attributes_prices']['attribute_price'] ) {
                            $attribute_prices = $price_info['attributes_prices']['attribute_price'];
                            if ( !ArrayHelper::isIndexed($attribute_prices) ) $attribute_prices = [$attribute_prices];
                            foreach( $attribute_prices as $attribute_price ) {
                                $localOptId = $this->lookupLocalOptionId($attribute_price['option_id'],$attribute_price['option_name']);
                                $localOptValId = $this->lookupLocalOptionValueId($localOptId, $attribute_price['option_value_id'],$attribute_price['option_value_name']);
                                $attrKey = $localOptId.'-'.$localOptValId;
                                if ( !isset($importArray['attributes'][$attrKey]) ) continue;
                                if ( !isset($importArray['attributes'][$attrKey]['prices']) ) $importArray['attributes'][$attrKey]['prices'] = [];
                                $importArray['attributes'][$attrKey]['price_prefix'] = $attribute_price['price_prefix'];
                                $discountTable = [];
                                if ( isset($attribute_price['discount_table']) ) {
                                    $discountTable = ArrayHelper::isIndexed($attribute_price['discount_table']['price'])?$attribute_price['discount_table']['price']:[$attribute_price['discount_table']['price']];
                                }
                                $importArray['attributes'][$attrKey]['prices'][$price_info['currency'].'_0'] = [
                                    'attributes_group_price' => $attribute_price['price'],
                                    'attributes_group_discount_price' => $this->buildDiscountString($discountTable),
                                ];
                            }
                        }

                        if ( isset($price_info['inventory_prices']) && $price_info['inventory_prices']['inventory_price'] ) {
                            $inventory_prices = $price_info['inventory_prices']['inventory_price'];
                            if ( !ArrayHelper::isIndexed($inventory_prices) ) $inventory_prices = [$inventory_prices];

                            foreach( $inventory_prices as $inventory_price ) {
                                $remote_uprid = $inventory_price['products_id'];

                                if ( !isset($inventory_prices_data[$remote_uprid]) ) $inventory_prices_data[$remote_uprid] = [];
                                if ( !isset($inventory_prices_data[$remote_uprid]['prices']) ) $inventory_prices_data[$remote_uprid]['prices'] = [];
                                //$inventory_prices_data[$remote_uprid]['price_prefix'] = ''; TODO: update SOAP server ????
                                ;
                                $discountTable = [];
                                if ( isset($inventory_price['discount_table']) ) {
                                    $discountTable = ArrayHelper::isIndexed($inventory_price['discount_table']['price'])?$inventory_price['discount_table']['price']:[$inventory_price['discount_table']['price']];
                                }

                                $price_item = [];
                                if ($price_info['products_price_full']){
                                    $price_item['inventory_full_price'] = $inventory_price['price'];
                                    $price_item['inventory_discount_full_price'] = $this->buildDiscountString($discountTable);
                                }else{
                                    $price_item['inventory_group_price'] = $inventory_price['price'];
                                    $price_item['inventory_group_discount_price'] = $this->buildDiscountString($discountTable);
                                }
                                $inventory_prices_data[$remote_uprid]['prices'][ $price_info['currency'].'_0' ] = $price_item;
                            }
                        }
                    }
                }

                static $objCurrencies = false;
                if ( $objCurrencies===false ) $objCurrencies = new \common\classes\Currencies();

                $useCurrency = DEFAULT_CURRENCY;
                if ( isset($importArray['prices'][DEFAULT_CURRENCY.'_0']) ) {
                    $defPrice = $importArray['prices'][DEFAULT_CURRENCY . '_0'];
                }else{
                    foreach (\common\helpers\Currencies::get_currencies() as $currency){
                        $checkKey = $currency['code'].'_0';
                        if ( isset($importArray['prices'][$checkKey]) ) {
                            $useCurrency = $currency['code'];
                            $rateConvertFrom = $objCurrencies->get_value($currency['code']);
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
                if ( isset($importArray['inventory']) && is_array($importArray['inventory']) ) {
                    foreach ($importArray['inventory'] as $inventoryIdx=>$inventoryInfo) {
                        $inventoryInfo['products_id'];

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

            if ( isset($importArray['inventory']) && is_array($importArray['inventory']) ) {
                //echo '<pre>'; var_dump($importArray['inventory']); echo '</pre>';
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

                $importArray['manual_control_status'] = 0;

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
                unset($importArray['products_last_modified']);
            }
            // {{ automatically status
            if ( \common\helpers\Acl::checkExtension('AutomaticallyStatus', 'allowed') && isset($importArray['products_status']) ) {
                $importArray['AutoStatus'] = $importArray['products_status'];
            }
            unset($importArray['products_status']);
            // }} automatically status

            if ( !$localProduct->isNewRecord && isset($importArray['suppliers_data']) && is_array($importArray['suppliers_data']) ) {
                $localProduct->indexedCollectionAppendMode('suppliers_data');
            }

            $localProduct->importArray($importArray);
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

                $this->linkRemoteWithLocalId($remoteProductId, $localId, $local_modify_time, $server_last_modified, $is_own_product);

                if ( $patch_platform_assign ) {
//                        tep_db_query(
//                            "INSERT IGNORE INTO platforms_products (platform_id, products_id) ".
//                            "VALUES ('".intval(\common\classes\platform::defaultId())."', '".intval($localProduct->products_id)."')"
//                        );
                    tep_db_query(
                        "INSERT IGNORE INTO platforms_products (platform_id, products_id) ".
                        "SELECT platform_id, '".intval($localProduct->products_id)."' FROM platforms WHERE is_virtual=0 "
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

        if ( !isset($updateFlags['prices_server']) || $updateFlags['prices_server']==='as_is' ) {
            // leave && pass
        }else{
            $productData['prices'] = [];
        }

        if ( !isset($updateFlags['stock_server']) || $updateFlags['stock_server']==='as_is' ) {
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
        if ( !isset($updateFlags['documents_server']) || $updateFlags['documents_server']===true ) {

        }else {
            $productData['documents'] = null;
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
                        'is_own_product' => 1,
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
        if ( isset($productData['manufacturers_id']) ) {
            $remoteBrandId = $this->getRemoteBrandId($productData['manufacturers_id']);
            if ( is_numeric($remoteBrandId) && $remoteBrandId>0 ) {
                $productData['manufacturers_id'] = $remoteBrandId;
            }else {
                unset($productData['manufacturers_id']);
            }
            unset($productData['manufacturers_name']);
        }

        if ( isset($exportArray['descriptions']) ){
            $productData['descriptions'] = [];
            if ( is_array($exportArray['descriptions']) ) {
                foreach ($exportArray['descriptions'] as $cKey=>$cData) {
                    list( $langCode, $_tmp ) = explode('_',$cKey,2);
                    if ( (int)$_tmp!=1 ) continue;
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
            'price_info' => \backend\models\EP\Provider\HolbiSoap\Helper::makeExportProductPrices($product->products_id, $this->config['directoryId']),
        ];


        $productData['stock_info'] = null;
        $productData['stock_info'] = \backend\models\EP\Provider\HolbiSoap\Helper::makeExportProductStock($product);

        $productData['assigned_categories'] = null;
        if ( isset($exportArray['assigned_categories']) && is_array($exportArray['assigned_categories']) ) {
            foreach ($exportArray['assigned_categories'] as $idx => $categoryInfo) {
                $remoteCategoryId = \backend\models\EP\Provider\HolbiSoap\Helper::getRemoteCategoryId($this->config['directoryId'], $categoryInfo['categories_id']);
                if ( $remoteCategoryId!==false ) {
                    $productData['assigned_categories'][] = [
                        'categories_id' => $remoteCategoryId,
                        'sort_order' => $categoryInfo['sort_order'],
                    ];
                }elseif(false){
                    $categories_path = [];
                    foreach ( $categoryInfo['categories_path_array'] as $categories_path_item ) {
                        $remoteCategoryId = \backend\models\EP\Provider\HolbiSoap\Helper::getRemoteCategoryId($this->config['directoryId'], $categories_path_item['id']);
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

        $__attr_map_opt = [];
        $__attr_map_val = [];
        if (isset($exportArray['attributes']) && is_array($exportArray['attributes']) && count($exportArray['attributes']) > 0) {
            $productData['attributes']['attribute'] = [];
            foreach ($exportArray['attributes'] as $attribute) {
                if (!isset($__attr_map_opt[$attribute['options_id']])) $__attr_map_opt[$attribute['options_id']] = ['options_name' => $attribute['options_name']];
                if (!isset($__attr_map_val[$attribute['options_values_id']])) $__attr_map_val[$attribute['options_values_id']] = ['options_values_name' => $attribute['options_values_name']];
                $exportAttribute = [
                    'options_name' => $attribute['options_name'],
                    'options_values_name' => $attribute['options_values_name'],
                    'products_options_sort_order' => $attribute['products_options_sort_order'],
                ];
                $options_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionId($this->config['directoryId'], $attribute['options_id']);
                if ($options_id !== false) {
                    $__attr_map_opt[$attribute['options_id']]['options_id'] = $options_id;
                    $exportAttribute['options_id'] = $options_id;
                    $options_values_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionValueId($this->config['directoryId'], $attribute['options_id'], $attribute['options_values_id']);
                    if ($options_values_id !== false) {
                        $__attr_map_val[$attribute['options_values_id']]['options_values_id'] = $options_values_id;
                        $exportAttribute['options_values_id'] = $options_values_id;
                    }
                }

                $productData['attributes']['attribute'][] = $exportAttribute;
            }
        }

        $productData['inventories'] = null;
        if ( isset($exportArray['inventory']) && is_array($exportArray['inventory']) && count($exportArray['inventory'])>0 ) {
            $productData['inventories'] = ['inventory'=>[]];
            foreach( $exportArray['inventory'] as $_inventory ) {
                $_inventory['attribute_maps'] = ['attribute_map' => []];
                preg_match_all('/{(\d+)}(\d+)/', $_inventory['products_id'], $_attr);
                foreach ($_attr[1] as $idx => $optId) {
                    if ( isset($__attr_map_opt[$optId]) && isset($__attr_map_val[$_attr[2][$idx]]) ) {
                        $_inventory['attribute_maps']['attribute_map'][] = array_merge($__attr_map_opt[$optId], $__attr_map_val[$_attr[2][$idx]]);
                    }
                }
                unset($_inventory['prid']);
                unset($_inventory['products_id']);
                if ( array_key_exists('products_quantity', $_inventory) ) {
                    $_inventory['stock_info'] = [
                        'quantity' => $_inventory['products_quantity'],
                        'allocated_quantity' => $_inventory['allocated_stock_quantity'],
                        'stock_indication_id' => $_inventory['stock_indication_id'],
                        'stock_indication_text' => $_inventory['stock_indication_text'],
                        'stock_delivery_terms_id' => $_inventory['stock_delivery_terms_id'],
                        'stock_delivery_terms_text' => $_inventory['stock_delivery_terms_text'],
                    ];
                }
                $productData['inventories']['inventory'][] = $_inventory;
            }
            unset($exportArray['inventory']);
        }

        $productData['images'] = null;

        if ( isset($exportArray['images']) && is_array($exportArray['images']) && count($exportArray['images'])>0 ) {
            $productData['images'] = ['image'=>[]];
            foreach( $exportArray['images'] as $image ) {
                $image_descriptions = ['description' => []];
                foreach( $image['image_description'] as $langCode => $image_description ) {
                    if ( !array_key_exists('image_title',$image_description) ) continue;
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
        if ( !isset($mapping[$remoteId]) ) {
            $getMapping_r = tep_db_query(
                "SELECT m.local_id, pov.products_options_values_id " .
                "FROM ep_holbi_soap_mapping m " .
                " LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pov.products_options_values_id=m.local_id ".
                "WHERE m.ep_directory_id='" . intval($this->config['directoryId']) . "' AND m.mapping_type='attr_option_value' " .
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
                        "WHERE ep_directory_id='" . intval($this->config['directoryId']) . "' AND mapping_type='attr_option_value' " .
                        " AND remote_id='" . $remoteId . "'"
                    );
                }else{
                    $mapping[$remoteId] = $db_mapping['local_id'];
                    $createNewLink = false;
                }
            }
            if ($createNewLink) {
                $tools = new Tools();
                $localOptionValueId = $tools->get_option_value_by_name($localOptionId, $remoteNames);
                $mapping[$remoteId] = $localOptionValueId;
                tep_db_perform('ep_holbi_soap_mapping',[
                    'ep_directory_id' => intval($this->config['directoryId']),
                    'mapping_type' => 'attr_option_value',
                    'remote_id' => $remoteId,
                    'local_id' => $localOptionValueId,
                ]);
            }
        }
        return intval($mapping[$remoteId]);
    }



    protected function linkRemoteWithLocalId($remoteId, $localId, $local_modify_time=null, $server_last_modified=null, $is_own_product=null)
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
        if ( !is_null($is_own_product) ) {
            $columns .= ', is_own_product ';
            $columnsValues .= ", '".tep_db_input($is_own_product?'1':'0')."' ";
            $columnsUpdate .= ", is_own_product='".tep_db_input($is_own_product?'1':'0')."' ";
        }

        tep_db_query(
            "INSERT INTO ep_holbi_soap_link_products(ep_directory_id, remote_products_id, local_products_id{$columns} ) ".
            " VALUES ".
            " ('".(int)$this->config['directoryId']."', '".$remoteId."','".$localId."'{$columnsValues}) ".
            "ON DUPLICATE KEY UPDATE ep_directory_id='".(int)$this->config['directoryId']."', remote_products_id='".$remoteId."'{$columnsUpdate}"
        );
        return true;
    }

    protected function getLocalBrandId($remoteId)
    {
        static $mapping = [];
        if (!isset($mapping[$remoteId]))
        {
            $getMapping_r = tep_db_query(
                "SELECT sm.local_id, m.manufacturers_id " .
                "FROM ep_holbi_soap_mapping sm " .
                " LEFT JOIN ".TABLE_MANUFACTURERS." m ON m.manufacturers_id=sm.local_id ".
                "WHERE sm.ep_directory_id='" . intval($this->config['directoryId']) . "' AND sm.mapping_type='brand' " .
                " AND sm.remote_id='" . $remoteId . "' ".
                "LIMIT 1"
            );

            if (tep_db_num_rows($getMapping_r) == 0){
                return false;
            }else{
                $db_mapping = tep_db_fetch_array($getMapping_r);
                if (is_null($db_mapping['manufacturers_id'])) {
                    $mapping[$remoteId] = -1;
                }else{
                    $mapping[$remoteId] = $db_mapping['manufacturers_id'];
                }
            }
        }
        return intval($mapping[$remoteId]);
    }

    protected function getRemoteBrandId($localId)
    {
        if ( !is_numeric($localId) || (int)$localId<=0 ) return 0;

        static $lookuped = array();
        if ( !isset($lookuped[$localId]) ) {
            $lookuped[$localId] = false;
            $getMapping_r = tep_db_query(
                "SELECT sm.remote_id " .
                "FROM ep_holbi_soap_mapping sm " .
                "WHERE sm.ep_directory_id='" . intval($this->config['directoryId']) . "' AND sm.mapping_type='brand' " .
                " AND sm.local_id='" . (int)$localId . "' ".
                "LIMIT 1"
            );
            if ( tep_db_num_rows($getMapping_r)>0 ) {
                $mapping = tep_db_fetch_array($getMapping_r);
                $lookuped[$localId] = (int)$mapping['remote_id'];
            }

        }
        return $lookuped[$localId];
    }

    protected function transformProduct($responseObject)
    {
        $product =  json_decode(json_encode($responseObject),true);

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
                $rebuild[$description['language'].'_1'] = $description;
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

                if ( isset($property['properties_id']) ) {
                    $product['properties'][$idx]['properties_id'] = Helper::lookupLocalPropertyId((int)$this->config['directoryId'],$property['properties_id']);
                    if ( !is_numeric($product['properties'][$idx]['properties_id']) || $product['properties'][$idx]['properties_id']<=0 ) {
                        unset($product['properties'][$idx]);
                        continue;
                    }
                    unset($product['properties'][$idx]['names']);
                    unset($product['properties'][$idx]['name_path']);
                }else{
                    if (isset($property['names']) && is_array($property['names'])) {
                        $rebuild_array = $property['names'];
                        $product['properties'][$idx]['names'] = [];
                        foreach ($rebuild_array as $language_value) {
                            $product['properties'][$idx]['names'][$language_value['language']] = $language_value['text'];
                        }
                    }
                    if (isset($property['name_path']) && is_array($property['name_path'])) {
                        $rebuild_array = $property['name_path'];
                        $product['properties'][$idx]['name_path'] = [];
                        foreach ($rebuild_array as $language_value) {
                            $product['properties'][$idx]['name_path'][$language_value['language']] = $language_value['text'];
                        }
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
                    $response = $this->client->getCategory((int)$remoteCategoryInfo['categories_path_array'][$i]['id']);
                    if ($response->status != 'ERROR' && $response->category) {
                        $categoryData = $this->transformCategory($response->category);
                        //unset($categoryData['categories_status']);
                        unset($categoryData['categories_id']);
                        unset($categoryData['parent_id']);
                    }
                }catch (\Exception $ex){

                }
                $categoryData['manual_control_status'] = 0;
                //$categoryData['categories_status'] = 0;

                // {{ automatically status
                if ( \common\helpers\Acl::checkExtension('AutomaticallyStatus', 'allowed') && isset($categoryData['categories_status']) ) {
                    $importArray['AutoStatus'] = $categoryData['categories_status'];
                }
                unset($categoryData['categories_status']);
                // }} automatically status

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

    protected function syncRequiredEntities(Messages $messages)
    {
        $catalogJob = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'HolbiSoap\\SynchronizeCatalog',
        ]);
        $catalogJob->run($messages);

        $catalogJob = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'HolbiSoap\\SynchronizeProperties',
        ]);
        $catalogJob->run($messages);

        $catalogJob = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'HolbiSoap\\SynchronizeBrands',
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

    /**
     * @deprecated
     * @param $responseObject
     * @return mixed
     */
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

        // {{ update images for model from server
        if ( count($this->updateImageForModels)>0 ) {
            $model = \common\helpers\Product::get_products_info($productId, 'products_model');
            if (!empty($model) && in_array($model, $this->updateImageForModels)) {
                $datasourceConfig['images_client'] = true;
            }
        }
        // }} update images for model from server

        if ( !is_null($configKey) ) {
            return isset($datasourceConfig[$configKey])?$datasourceConfig[$configKey]:null;
        }
        return $datasourceConfig;
    }

}
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

use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use yii\helpers\ArrayHelper;


class DownloadPriceAndStock implements DatasourceInterface
{

    protected $total_pages = 0;
    protected $current_page = 0;
    protected $process_list;

    protected $config = [];

    /**
     * @var \SoapClient
     */
    protected $client;

    protected $suppliersSupport = false;

    function __construct($config)
    {
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
        $this->config['directoryId'];

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
                    //'wsdl_cache' => WSDL_CACHE_MEMORY,
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

        // download remote ids and process it
        $this->fetchServerPage();

        /*try {
            $this->getPriceAndStock([
                //'searchConditions' => []
            ]);
        }catch (\Exception $ex){
            throw new Exception('Download remote products error');
        }*/
    }

    public function processRow(Messages $message)
    {
        if ( is_array($this->process_list) && count($this->process_list)>0 ) {
            foreach (array_keys($this->process_list) as $remoteProductId ) {
                $this->processRemoteProductPriceAndStock($remoteProductId);
            }
            $this->fetchServerPage();
            return true;
        }else{
            return false;
        }
    }

    public function postProcess(Messages $message)
    {

    }

    protected function fetchServerPage()
    {
        $this->process_list = [];
        $this->current_page++;
        $params = [
            'searchConditions' => [
                /*'searchCondition'=> [
                    [
                        'column' => 'products_id',
                        'operator' => '=',
                        'values'=>[['16523']]
                    ]
                 ]*/
            ],
            'paging' => [
                'page' => $this->current_page,
            ]
        ];
        echo '<pre>FETCH PAGE '; var_dump($this->current_page); echo '</pre>';
        $response = $this->client->getPriceAndStock($params['searchConditions'], $params['paging']);
        if (isset($response->products_price_stock) && isset($response->products_price_stock->product_price_stock)) {
            if ( is_array($response->products_price_stock->product_price_stock) ) {

            }elseif(is_object($response->products_price_stock->product_price_stock)){
                $response->products_price_stock->product_price_stock = [$response->products_price_stock->product_price_stock];
            }
            foreach ($response->products_price_stock->product_price_stock as $productPriceStock) {
                $this->process_list[$productPriceStock->products_id] = $productPriceStock;
            }
        }
        if ( isset($response->paging) ) {
            $this->total_pages = $response->paging->totalPages;
        }

    }

    protected function processRemoteProductPriceAndStock($remoteProductId)
    {
        $productPriceStock = $this->process_list[$remoteProductId];

        $localProductId = $this->lookupLocalId($remoteProductId);
        if ( !$localProductId ) return false;

        $getProductLinkInfo_r = tep_db_query(
            "SELECT is_own_product " .
            "FROM ep_holbi_soap_link_products " .
            "WHERE ep_directory_id='" . (int)$this->config['directoryId'] . "' " .
            " AND local_products_id='" . $localProductId . "' " .
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getProductLinkInfo_r)>0 ) {
            $productLinkInfo = tep_db_fetch_array($getProductLinkInfo_r);
            tep_db_free_result($getProductLinkInfo_r);
            if ( !is_null($productLinkInfo['is_own_product']) && $productLinkInfo['is_own_product'] ) {
                // this store own server product - stock and price from server ignored
                $update_server = [];

                $localProduct = \common\api\models\AR\Products::findOne(['products_id'=>$localProductId]);

                if ( !$localProduct || !$localProduct->products_id ) return false;

                $currentData = $localProduct->exportArray([
                    'warehouses_products'=>['*'=>['*']],
                    'attributes' => ['*'=>['*']],
                    'inventory' => ['*'=>['*']],
                ]);
                if ( !$localProduct->hasAssignedProductAttributes() ) {
                    $importArray = json_decode(json_encode($productPriceStock),true);
                    $remote_pid = (int)$importArray['products_id'];
                    if ( isset($importArray['stock']) && isset($importArray['stock']['stock_info']) ) {
                        $server_stock_array = ArrayHelper::isIndexed($importArray['stock']['stock_info'])?$importArray['stock']['stock_info']:[$importArray['stock']['stock_info']];
                        if ( count($server_stock_array)==1 && strval($server_stock_array[0]['products_id'])==strval($remote_pid) ) {
                            if ( isset($server_stock_array[0]['quantity']) && (int)$server_stock_array[0]['quantity']!=(int)$currentData['products_quantity'] ) {
                                $update_server['products_id'] = $remote_pid;
                                $update_server['stock'] = [
                                    'stock_info' => []
                                ];
                                $update_server['stock']['stock_info'][] = [
                                    'products_id' => $remote_pid,
                                    'quantity' => $currentData['products_quantity'],
                                ];
                                try{
                                    $this->client->updatePriceAndStock($update_server);
                                    \Yii::info("updatePriceAndStock: {$localProductId}=>{$remote_pid} \n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                                }catch (\Exception $ex){
                                    \Yii::error("updatePriceAndStock Exception: ".$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                                }
                            }
                        }
                    }
                }

                return false;
            }
        }

        $localProduct = \common\api\models\AR\Products::findOne(['products_id'=>$localProductId]);

        if ( !$localProduct || !$localProduct->products_id ) return false;

        $currentData = $localProduct->exportArray([
            'prices' => ['*'=>['*']],
            'attributes' => ['*'=>['*']],
            'inventory' => ['*'=>['*']],
        ]);

        unset($productPriceStock->products_id);

        $updateFlags = $this->getProductSyncConfig($localProduct->products_id);
        if ( isset($updateFlags['prices_client']) && $updateFlags['prices_client']==='disabled' ) {
            unset($productPriceStock->prices);
        }

        if ( isset($updateFlags['stock_client']) && $updateFlags['stock_client']==='disabled' ) {
            unset($productPriceStock->stock);
        }
        if ( !isset($productPriceStock->stock) && !isset($productPriceStock->prices) ) return false;

        $importArray = json_decode(json_encode($productPriceStock),true);

        // {{
        if ( isset($currentData['attributes']) && is_array($currentData['attributes']) ) {
            $importArray['attributes'] = [];
            foreach ($currentData['attributes'] as $currentAttribute){
                $attrKey =  $currentAttribute["options_id"].'-'.$currentAttribute["options_values_id"];
                unset($currentAttribute["options_name"]);
                unset($currentAttribute["options_values_name"]);
                unset($currentAttribute['prices']);
                $importArray['attributes'][$attrKey] = $currentAttribute;
            }
        }
        if ( isset($importArray['stock']) && isset($importArray['stock']['stock_info']) ) {
            $stock_in = $importArray['stock']['stock_info'];
            unset($importArray['stock']);
            if ( !ArrayHelper::isIndexed($stock_in) ) $stock_in = [$stock_in];
            foreach( $stock_in as $stock_info_item ) {
                if ( strpos($stock_info_item['products_id'],'{')!==false ){
                    $stock_info_item['_remote_uprid'] = $stock_info_item['products_id'];
                    // inventory item
                    $localUprid = $this->makeLocalUprid($stock_info_item['products_id']);
                    if ( empty($localUprid) ) continue;
                    $stock_info_item['prid'] = \common\helpers\Inventory::get_prid($localUprid);
                    $stock_info_item['products_id'] = $localUprid;
                    if ( !isset($importArray['inventory']) ) $importArray['inventory'] = [];
                    if ( array_key_exists('quantity',$stock_info_item) && !array_key_exists('products_quantity',$stock_info_item) ) {
                        $stock_info_item['products_quantity'] = $stock_info_item['quantity'];
                    }
                    $importArray['inventory'][] = $stock_info_item;
                }else{
                    $importArray = array_merge($importArray,['stock_info'=>$stock_info_item]);
                }
            }
        }
        // }}

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

                        if ( array_key_exists('price', $price_info['pack']) ) {
                            if (floatval($price_info['pack']['price'])>0) {
                                $importArray['prices'][$price_info['currency'] . '_0']['products_group_price_pack_unit'] = is_null($price_info['pack']['price']) ? '-2.000000' : $price_info['pack']['price'];
                                $discountTable = ArrayHelper::isIndexed($price_info['pack']['discount_table']['price'])?$price_info['pack']['discount_table']['price']:[$price_info['pack']['discount_table']['price']];
                                $importArray['prices'][$price_info['currency'].'_0']['products_group_discount_price_pack_unit'] = $this->buildDiscountString($discountTable);
                            }
                        }

                        // pallet inside of pack!! - pallet require pack qty for multiply
                        if ( isset($price_info['pallet']) && is_array($price_info['pallet']) ) {
                            if ( isset($price_info['pallet']['pack_qty']) ) {
                                $importArray['packaging'] = $price_info['pallet']['pack_qty'];
                            }

                            if ( array_key_exists('price', $price_info['pallet']) ) {
                                if ( floatval($price_info['pallet']['price'])>0 ) {
                                    $importArray['prices'][$price_info['currency'] . '_0']['products_group_price_packaging'] = is_null($price_info['pallet']['price']) ? '-2.000000' : $price_info['pallet']['price'];
                                    $discountTable = ArrayHelper::isIndexed($price_info['pallet']['discount_table']['price'])?$price_info['pallet']['discount_table']['price']:[$price_info['pallet']['discount_table']['price']];
                                    $importArray['prices'][$price_info['currency'].'_0']['products_group_discount_price_packaging'] = $this->buildDiscountString($discountTable);
                                }
                            }
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
                    $priceUprid = $inventoryInfo['_remote_uprid'];

                    $inventoryPriceInfo = isset($inventory_prices_data[$priceUprid])?$inventory_prices_data[$priceUprid]:[];
                    if ( !isset($inventoryPriceInfo['prices']) ) continue;
                    unset($inventoryPriceInfo['products_id']);
                    unset($inventoryPriceInfo['prid']);

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
                        $importArray['inventory'][$inventoryIdx]['inventory_price'] = $inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_group_price'];
                        if ( $useCurrency!=DEFAULT_CURRENCY ) {
                            $importArray['inventory'][$inventoryIdx]['inventory_price'] = $this->applyRate($importArray['inventory'][$inventoryIdx]['inventory_price'],1/$rateConvertFrom);
                        }

                        if ( isset($inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_group_discount_price']) ) {
                            $importArray['inventory'][$inventoryIdx]['inventory_discount_price'] = $inventoryPriceInfo['prices'][$useCurrency . '_0']['inventory_group_discount_price'];
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
                if ( !$localProduct->isNewRecord ) {
                    $localProduct->indexedCollectionAppendMode('suppliers_data');
                }
            }
        }
        if ( isset($importArray['status']) && \common\helpers\Acl::checkExtension('AutomaticallyStatus', 'allowed') ) {
            // own product skipped before
            $importArray['AutoStatus'] = $importArray['status'];
        }

        $localProduct->importArray($importArray);
        return $localProduct->save();
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

    protected function lookupLocalOptionId($remoteId)
    {
        static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
            $localId = Helper::lookupLocalOptionId($this->config['directoryId'], $remoteId);
            if ( $localId===false ) {
                return false;
            }
            $mapping[$remoteId] = $localId;
        }
        return intval($mapping[$remoteId]);
    }

    protected function lookupLocalOptionValueId($localOptionId, $remoteId)
    {
        static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
            $localId = Helper::lookupLocalOptionValueId($this->config['directoryId'], $localOptionId, $remoteId);
            if ( $localId===false ) {
                return false;
            }
            $mapping[$remoteId] = $localId;
        }
        return intval($mapping[$remoteId]);
    }

    protected function makeLocalUprid($remoteUprid)
    {
        $localUprid = $this->lookupLocalId((int)$remoteUprid);
        if ( $localUprid && strpos($remoteUprid, '{')!==false ) {
            if (preg_match_all('/\{(\d+)\}(\d+)/', $remoteUprid, $attrMatch)) {
                foreach ( $attrMatch[1] as $_idx=>$remoteOptId ) {
                    $localOptId = $this->lookupLocalOptionId($remoteOptId);
                    $localOptValId = $this->lookupLocalOptionValueId($localOptId,$attrMatch[2][$_idx]);
                    if ( $localOptId===false || $localOptValId===false ) {
                        return false;
                    }
                    $localUprid .= '{'.$localOptId.'}'.$localOptValId;
                }
                $localUprid = \common\helpers\Inventory::normalize_id($localUprid);
            }else{
                $localUprid = false;
            }
        }
        return $localUprid;
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
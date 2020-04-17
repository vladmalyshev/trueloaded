<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Products\ArrayOfStockInfo;
use common\api\models\Soap\Products\StockInfo;
use yii\helpers\ArrayHelper;

class UpdateStockResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Products\ArrayOfStockInfo Array of StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock;

    /**
     * @var \common\api\models\Soap\UpdateStockRequest
     */
    public $stock_in;
    protected $response_data = [];

    /**
     * UpdateStockResponse constructor.
     */
    public function __construct(array $config = [])
    {
        $this->stock = new ArrayOfStockInfo();

        parent::__construct($config);
    }


    protected function processIn()
    {

        // group by products
        $grouped = [];

        if ( is_array($this->stock_in->stock->stock_info)) {
            $stock_list = $this->stock_in->stock->stock_info;
        }else{
            $stock_list = [$this->stock_in->stock->stock_info];
        }

        $valid_warehouse_ids = ArrayHelper::map(\common\helpers\Warehouses::get_warehouses(true),'id','id');

        $response_uprids = [];
        foreach ($stock_list as $stock_info){
            /**
             * @var  StockInfo $stock_info
             */
            if (!isset($grouped[ (int)$stock_info->products_id ])) $grouped[ (int)$stock_info->products_id ] = [];
            $grouped[ (int)$stock_info->products_id ][ $stock_info->products_id ] = $stock_info;
            $response_uprids[$stock_info->products_id] = $stock_info->products_id;
        }

        $process_validate = [];
        foreach ($grouped as $prid=>$variants){
            $prodCheck = \common\models\Products::find()
                ->where(['products_id'=>$prid])
                ->select(['products_id', 'products_id_stock','products_quantity', 'products_model', 'stock_indication_id', 'stock_delivery_terms_id'])
                ->asArray()
                ->one();
            if ( $prodCheck['products_id']!=$prodCheck['products_id_stock'] ){
                $this->warning('Product #'.$prid.' child of #'.$prodCheck['products_id_stock']);
                continue;
            }
            if ( isset($response_uprids[$prodCheck['products_id']]) ) {
                $this->response_data[] = $prodCheck;
            }

            $__inventory = \common\models\Inventory::find()
                ->where(['prid'=>$prid])
                ->select(['products_id','products_quantity', 'products_model', 'stock_indication_id', 'stock_delivery_terms_id'])
                ->asArray()
                ->all();
            $valid_uprids = ArrayHelper::map($__inventory, 'products_id', 'products_id');
            if ( count($valid_uprids)==0 ) $valid_uprids[$prid] = $prid;

            foreach ($__inventory as $_inv){
                if (!isset($variants[$_inv['products_id']])) continue;
                $this->response_data[] = $_inv;
            }

            foreach ($variants as $uprid=>$stock_info){
                if ( !isset($valid_uprids[$uprid]) ){
                    $this->warning("Product id '{$uprid}' not exist");
                    continue;
                }
                $valid_supplier_ids = ArrayHelper::map(\common\helpers\Suppliers::getSuppliersToUprid($uprid), 'suppliers_id','suppliers_id');
                if ( count($valid_supplier_ids)==0 ){
                    $valid_supplier_ids[\common\helpers\Suppliers::getDefaultSupplierId()] = \common\helpers\Suppliers::getDefaultSupplierId();
                }

                if (isset($stock_info->warehouses_stock) && isset($stock_info->warehouses_stock->warehouse_stock)){
                    $warehouses_list = (is_array($stock_info->warehouses_stock->warehouse_stock)?$stock_info->warehouses_stock->warehouse_stock:[$stock_info->warehouses_stock->warehouse_stock]);

                    $dbWarehouseProducts = ArrayHelper::index(
                        \common\models\WarehousesProducts::find()
                            ->where(['prid'=>(int)$prid, 'products_id'=>$uprid])
                            ->all(),
                        function($wh){
                            return (int)$wh->warehouse_id.'_'.(int)$wh->suppliers_id.'_'.(int)$wh->location_id;
                        }
                    );
                    if ( count($dbWarehouseProducts)==0 ){

                    }
                        //'warehouse_id'=>$warehouseId, 'suppliers_id'=>$supplierId, 'location_id' => $locationId,

                    foreach ($warehouses_list as $warehouse){
                        /**
                         * @var $warehouse \common\api\models\Soap\Products\WarehouseStock
                         */
                        $process_validate = array_merge($process_validate, $warehouse->inputValidate());

                        if ( empty($warehouse->suppliers_id) ){
                            continue;
                        }
                        if ( !isset($valid_supplier_ids[(int)$warehouse->suppliers_id]) ){
                            $this->warning('Not valid supplier "'.(int)$warehouse->suppliers_id.'" for "'.$uprid.'" - skipped ');
                            $dbWarehouseProducts = [];
                            continue;
                        }
                        if ( !isset($valid_warehouse_ids[(int)$warehouse->warehouse_id]) ){
                            $this->warning('Not valid warehouse "'.(int)$warehouse->warehouse_id.'" for "'.$uprid.'" - skipped ');
                            $dbWarehouseProducts = [];
                            continue;
                        }

                        $productId = $uprid;
                        $warehouseId = $warehouse->warehouse_id;
                        $supplierId = $warehouse->suppliers_id;
                        $locationId = $warehouse->location_id;
                        //$quantity;

                        $current_warehouse_stock_quantity = \common\models\WarehousesProducts::find()
                            ->where(['warehouse_id'=>$warehouseId, 'suppliers_id'=>$supplierId, 'location_id' => $locationId, 'prid'=>(int)$productId, 'products_id'=>$productId])
                            ->select(['warehouse_stock_quantity'])
                            ->scalar();
                        $quantity = intval($warehouse->warehouse_stock_quantity)-(int)$current_warehouse_stock_quantity;
                        $quantity = \common\helpers\Warehouses::update_products_quantity($productId, $warehouseId, abs($quantity), ($quantity > 0 ? '+' : '-'), $supplierId, $locationId, [
                            'comments' => 'SOAP Stock feed update',
                            'admin_id' => 0
                        ]);

                        if ( strval($prid)!=strval($uprid) ) {
                            \common\helpers\Product::doCache($productId);
                        }
                        // unset removal
                        $updated_key = (int)$warehouseId.'_'.(int)$supplierId.'_'.(int)$locationId;
                        unset($dbWarehouseProducts[$updated_key]);
                    }
                    if ( count($warehouses_list)>0 && count($dbWarehouseProducts)>0 ){
                        foreach ($dbWarehouseProducts as $removeWarehouseProduct){
                            //$this->warning('Remove '.);
                            $removeWarehouseProduct->delete();
                        }
                        if ( strval($prid)!=strval($uprid) ) {
                            \common\helpers\Product::doCache($uprid);
                        }
                    }
                }else{
                    // skip
                }
            }

            \common\helpers\Product::doCache($prid);
        }

        foreach ($process_validate as $validateResult){
            $this->addMessage($validateResult['code'], $validateResult['text']);
        }
    }

    public function build()
    {
        $this->processIn();

        foreach($this->response_data as $_stock ){
            if ( !isset($this->stock->stock_info[$_stock['products_id']]) ) {
                $_stock['products_quantity'] = \common\helpers\Product::get_products_stock($_stock['products_id']);
                $_stock['quantity'] = $_stock['products_quantity'];
                $this->stock->stock_info[$_stock['products_id']] = new StockInfo($_stock);
            }
        }
        $this->stock->stock_info = array_values($this->stock->stock_info);

        $this->stock->build();
    }


}
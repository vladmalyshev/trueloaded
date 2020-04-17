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


use backend\models\EP\Tools;
use common\api\models\AR\Products\SuppliersData;
use common\api\models\Soap\Products\ArrayOfSupplierProductData;

class UpdateProductSuppliersResponse extends SoapModel
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
     * @var \common\api\models\Soap\UpdateProductSuppliersRequest
     */
    protected $update;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfSupplierProductData ArrayOfSupplierProductData
     * @soap
     */
    public $supplier_products;

    protected $update_collections;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setUpdate(UpdateProductSuppliersRequest $update)
    {
        $this->update = $update;

        $check = \common\models\Products::find()
            ->where(['products_id'=>(int)$this->update->product_id])
            ->one();
        if ( !$check ){
            $this->error('Product not found');
            return;
        }

        $this->update_collections = [];
        // load current
        $inventory = \common\models\Inventory::find()
            ->where(['prid'=>$this->update->product_id])
            ->select(['products_id'])
            ->asArray()
            ->all();
        if ( count($inventory)>0 ){
            foreach ($inventory as $_inventory){
                $this->update_collections[$_inventory['products_id']] = [];
            }
        }else{
            $this->update_collections[(int)$this->update->product_id] = [];
        }

        $supplier_products = SuppliersData::find()
            ->where(['products_id'=>$update->product_id])
            ->all();

        foreach ( $supplier_products as $supplier_product ){
            if ( !isset($this->update_collections[$supplier_product->uprid]) ) $this->update_collections[$supplier_product->uprid] = [];
            $this->update_collections[$supplier_product->uprid][$supplier_product->suppliers_id] = $supplier_product;
            $supplier_product->pendingRemoval = true;
        }
        // prepare request
        $request_supplier_products = $this->update->supplier_products->supplier_product;
        if (!is_array($request_supplier_products)){
            $request_supplier_products = [$request_supplier_products];
        }
        $valid_ids = \common\helpers\Suppliers::orderedIds();
        foreach ( $request_supplier_products as $request_supplier_product ) {
            if ( empty($request_supplier_product->suppliers_id) ){
                if ( !empty($request_supplier_product->suppliers_name) ) {
                    $request_supplier_product->suppliers_id = Tools::getInstance()->getSupplierIdByName($request_supplier_product->suppliers_name);
                }
            }
            if ( !in_array((int)$request_supplier_product->suppliers_id, $valid_ids) ){
                $this->error("Supplier not found: id [".intval($request_supplier_product->suppliers_id)."] name [".strval($request_supplier_product->suppliers_name)."]");
            }
            if ( $request_supplier_product->date_added ) {
                $request_supplier_product->date_added = date('Y-m-d H:i:s', strtotime($request_supplier_product->date_added));
            }
            if ( $request_supplier_product->last_modified ) {
                $request_supplier_product->last_modified = date('Y-m-d H:i:s', strtotime($request_supplier_product->last_modified));
                unset($request_supplier_product->last_modified);
            }
            if ( !isset($this->update_collections[$request_supplier_product->products_id]) ){
                $this->error("ProductId {$request_supplier_product->products_id} not found");
            }else{
                $data = json_decode(json_encode($request_supplier_product),true);
                if ( isset($this->update_collections[$request_supplier_product->products_id][$data['suppliers_id']]) ){
                    $this->update_collections[$request_supplier_product->products_id][$data['suppliers_id']]->importArray($data);
                    $this->update_collections[$request_supplier_product->products_id][$data['suppliers_id']]->pendingRemoval = false;
                }else{
                    $obj = new SuppliersData(array_merge($data,['uprid'=>$data['products_id'],'products_id'=>(int)$data['products_id']]));
                    $obj->loadDefaultValues();
                    $this->update_collections[$request_supplier_product->products_id][$data['suppliers_id']] = $obj;
                }
            }
        }

        //echo '<pre>'; var_dump($this->update_collections, $update); echo '</pre>'; die;
    }

    public function build()
    {
        if ( $this->status!='ERROR' ) {
            foreach ($this->update_collections as $uprid=>$variant_collections) {
                $all_deleted = true;
                foreach ($variant_collections as $obj) {
                    if ($obj->pendingRemoval) {
                        $obj->delete();
                    } else {
                        $obj->save(false);
                        $all_deleted = false;
                    }
                }
                if ($all_deleted){
                    $obj = new SuppliersData([
                        'uprid'=>$uprid,
                        'products_id'=>(int)$uprid,
                        'suppliers_id'=>\common\helpers\Suppliers::getDefaultSupplierId()
                    ]);
                    $obj->loadDefaultValues();
                    $obj->save(false);
                }
            }
        }

        $supplier_products = SuppliersData::find()
            ->where(['products_id'=>$this->update->product_id])
            ->orderBy(['uprid'=>SORT_ASC])
            ->all();
        $product_array = [];
        foreach ($supplier_products as $supplier_product){
            $product_array[] = $supplier_product->exportArray([]);
        }
        $this->supplier_products = new ArrayOfSupplierProductData($product_array);

        parent::build();
    }


}
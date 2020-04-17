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


use common\api\models\AR\Products;
use common\api\models\Soap\Products\PriceAndStockInfo;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;
use yii\helpers\ArrayHelper;

class UpdatePriceAndStockInfoResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\Product
     */
    protected $productIn;

    protected $stock = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setPriceAndStockInfo(PriceAndStockInfo $product)
    {
        if (!ServerSession::get()->acl()->allowUpdateProduct()) {
            $this->error('Product update is not allowed');
            return;
        }

        $product_owner = false;
        $get_owner = array('c'=>0);
        if (!isset($product->products_id) || empty($product->products_id)) {
            $this->error('Field "products_id" missing');
        } elseif (ServerSession::get()->getDepartmentId()) {
            $get_owner = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c " .
                "FROM " . TABLE_PRODUCTS . " " .
                "WHERE products_id='" . (int)$product->products_id . "' AND created_by_department_id='" . ServerSession::get()->getDepartmentId() . "'"
            ));
        }elseif (ServerSession::get()->getPlatformId()){
            $ownCheck = '';
            if ( !ServerSession::get()->acl()->siteAccessPermission() ){
                $ownCheck = " AND created_by_platform_id='" . ServerSession::get()->getPlatformId() . "' ";
            }
            $get_owner = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c " .
                "FROM " . TABLE_PRODUCTS . " " .
                "WHERE products_id='" . (int)$product->products_id . "' ".
                " {$ownCheck}"
            ));
        }
        if ($get_owner['c']) {
            $product_owner = true;
        }

        $pfx = '';
        if ($product_owner) $pfx = '_own';

        $this->stock = [];
        if ( isset($product->stock) ) {
            if ( isset($product->stock->stock_info) ) {
                if ( !is_array($product->stock->stock_info) ) $product->stock->stock_info = [$product->stock->stock_info];

                $validateResults = [];
                foreach ($product->stock->stock_info as $_v_stock_info){
                    $validateResults = array_merge($validateResults,$_v_stock_info->inputValidate());
                }
                foreach ($validateResults as $validateResult){
                    $this->addMessage($validateResult['code'], $validateResult['text']);
                }
            }
            $stock_array = json_decode(json_encode($product->stock),true);
            if ( isset($stock_array['stock_info']) ) {
                $stock_array['stock_info'] = ArrayHelper::isIndexed($stock_array['stock_info'])?$stock_array['stock_info']:[$stock_array['stock_info']];
                foreach( $stock_array['stock_info'] as $stock_info ) {
                    if ( !is_array($this->stock[(int)$stock_info['products_id']]) ) $this->stock[(int)$stock_info['products_id']] = [];

                    if ( isset($stock_info['warehouses_stock']) ) {
                        $warehouses_stock = ArrayHelper::isIndexed($stock_info['warehouses_stock']['warehouse_stock'])?$stock_info['warehouses_stock']['warehouse_stock']:[$stock_info['warehouses_stock']['warehouse_stock']];
                        unset($stock_info['warehouses_stock']);
                        $stock_info['warehouses_products'] = $warehouses_stock;
                    }

                    $this->stock[(int)$stock_info['products_id']][] = $stock_info;
                }

            }
        }
    }

    public function build()
    {
        if ( $this->status!='ERROR' && count($this->stock)>0 ) {
            foreach ( $this->stock as $products_id=>$stock_info ) {
                $product = Products::findOne(['products_id'=>$products_id]);
                if ( $product && $product->products_id) {
                    $currentData = $product->exportArray([
                        'attributes' => ['*'=>['*']],
                        'inventory' => ['*'=>['*']],
                        'warehouses_products' => ['*'=>['*']],
                    ]);

                    $updateData = [
                        'products_model' => $currentData['products_model'],
                        'products_quantity' => $currentData['products_quantity'],
                    ];
                    if ( isset($currentData['inventory']) ) {
                        $updateData['inventory'] = [];
                        foreach ($currentData['inventory'] as $_inventory){
                            $updateData['inventory'][$_inventory['products_id']] = [
                                //'prid' => $_inventory['prid'],
                                //'products_id' => $_inventory['products_id'],
                                'attribute_map' => $_inventory['attribute_map'],
                                'products_quantity' => $_inventory['products_quantity'],
                                'products_model' => $_inventory['products_model'],
                            ];
                        }
                    }
                    foreach ($stock_info as $_stock) {
                        if (strpos($_stock['products_id'],'{')!==false){
                            if ( isset($updateData['inventory']) && is_array($updateData['inventory']) ) {
                                if ( isset($updateData['inventory'][$_stock['products_id']]) ) {
                                    $updateData['inventory'][$_stock['products_id']]['products_quantity'] = $_stock['quantity'];
                                    if ( isset($_stock['warehouses_products']) && is_array($_stock['warehouses_products']) && count($_stock['warehouses_products'])>0 ){
                                        $updateData['inventory'][$_stock['products_id']]['warehouses_products'] = $_stock['warehouses_products'];
                                        unset($updateData['inventory'][$_stock['products_id']]['products_quantity']);
                                    }
                                }
                            }
                        }else{
                            $updateData['products_quantity'] = $_stock['quantity'];
                            if ( isset($_stock['warehouses_products']) && is_array($_stock['warehouses_products']) && count($_stock['warehouses_products'])>0 ){
                                $updateData['warehouses_products'] = $_stock['warehouses_products'];
                                unset($updateData['products_quantity']);
                            }
                        }
                    }
                    if ( isset($updateData['inventory']) && count($updateData['inventory']) ) {
                        $updateData['inventory'] = array_values($updateData['inventory']);
                        unset($updateData['products_quantity']);
                    }
                    //$product->products_quantity = $stock_info['quantity'];
                    $product->importArray($updateData);
                    $product->save(false);
                }
            }
        }

        parent::build();
    }


}
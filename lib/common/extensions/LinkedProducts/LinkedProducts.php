<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\LinkedProducts;



use common\extensions\LinkedProducts\models\ProductsLinkedChildren;
use yii\helpers\ArrayHelper;

class LinkedProducts extends \common\classes\modules\ModuleExtensions
{

    public static function allowed() {
        return self::enabled();
    }

    public function install($platform_id)
    {
        $migrate = new \common\classes\Migration();
        $migrate->compact = true;

        $migrate->addTranslation('admin/main', [
            'TEXT_LINKED_PRODUCTS' => 'Linked Products',
        ]);
        if ($migrate->db->getTableSchema('products_linked_parent', true) === null) {
            $migrate->createTable('products_linked_parent',[
                'id' => $migrate->primaryKey(),
                'product_id' => $migrate->integer(11)->notNull(),
                'show_on_invoice' => $migrate->integer(11)->notNull()->defaultValue(1),
                'show_on_packing_slip' => $migrate->integer(11)->notNull()->defaultValue(0),
            ]);
        }
        if ($migrate->db->getTableSchema('products_linked_children', true) === null) {
            $migrate->createTable('products_linked_children',[
                'id' => $migrate->primaryKey(),
                'parent_product_id' => $migrate->integer(11)->notNull(),
                'linked_product_id' => $migrate->integer(11)->notNull(),
                'sort_order'=> $migrate->integer(11)->notNull()->defaultValue(0),
                'linked_product_quantity' => $migrate->integer(11)->notNull()->defaultValue(1),
                'show_on_invoice' => $migrate->integer(11)->notNull()->defaultValue(0),
                'show_on_packing_slip' => $migrate->integer(11)->notNull()->defaultValue(1),
            ]);
        }
        // ALTER TABLE customers_basket ADD COLUMN relation_type VARCHAR(32) NOT NULL DEFAULT '';

        $migrate->addTranslation('admin/linked-products', [
            'TEXT_LINKED_PRODUCT_SHOW_ON_INVOICE' => 'Show on invoice',
            'TEXT_LINKED_PRODUCT_SHOW_ON_PACKING_SLIP' => 'Show on packing slip',
        ]);

        try {
            $migrate->createIndex('products_linked_parent_product_id', 'products_linked_parent', ['product_id']);
            $migrate->createIndex('products_linked_children_parent', 'products_linked_children', ['parent_product_id']);
            $migrate->createIndex('products_linked_children_children', 'products_linked_children', ['linked_product_id']);
        }catch (\Exception $ex){ }

        return parent::install($platform_id);
    }

    public function configure_keys(){
        return array_merge(parent::configure_keys(),[
            $this->code . '_EXTENSION_STOCK_MODE' => [
                'title' => 'Check linked product stock availability',
                'description' => 'If True - required quantity linked product checked',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            $this->code . '_EXTENSION_EXPAND_IN_ORDER' => [
                'title' => 'Show linked product on order detail page',
                'description' => '',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            $this->code . '_EXTENSION_EXPAND_IN_PACKING_SLIP' => [
                'title' => 'Show linked product on Packing Slip',
                'description' => '',
                'value' => 'True',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            $this->code . '_EXTENSION_EXPAND_IN_INVOICE' => [
                'title' => 'Show linked product on Invoice',
                'description' => '',
                'value' => 'False',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
        ]);
    }

    public function remove($platform_id)
    {
        parent::remove($platform_id);
    }

    public static function configCheckLinkedStock()
    {
        if (defined('LinkedProducts_EXTENSION_STOCK_MODE')){
            return constant('LinkedProducts_EXTENSION_STOCK_MODE')=='True';
        }
        return false;
    }

    public static function configShowLinkedChildOnOrder()
    {
        if (defined('LinkedProducts_EXTENSION_EXPAND_IN_ORDER')){
            return constant('LinkedProducts_EXTENSION_EXPAND_IN_ORDER')=='True';
        }
        return false;
    }

    public static function configShowLinkedChildOnPackingSlip()
    {
        if (defined('LinkedProducts_EXTENSION_EXPAND_IN_PACKING_SLIP')){
            return constant('LinkedProducts_EXTENSION_EXPAND_IN_PACKING_SLIP')=='True';
        }
        return false;
    }

    public static function configShowLinkedChildOnInvoice()
    {
        if (defined('LinkedProducts_EXTENSION_EXPAND_IN_INVOICE')){
            return constant('LinkedProducts_EXTENSION_EXPAND_IN_INVOICE')=='True';
        }
        return false;
    }

    public static function productBlock($pInfo)
    {
        if ( !static::allowed() ) return '';
        \common\helpers\Translation::init('admin/linked-products');

        return \common\extensions\LinkedProducts\ProductEdit::widget(['pInfo'=>$pInfo]);
    }

    public static function productSave($product)
    {
        $sort_order = [];
        parse_str(\Yii::$app->request->post('linked_sort_order',''), $ui_sort_order);
        if ( is_array($ui_sort_order) && isset($ui_sort_order['linked-box']) ) {
            $sort_order = array_flip($ui_sort_order['linked-box']);
        }

        $postedChildren = \Yii::$app->request->post('products_linked_children',[]);
        $Children = [];
        foreach ($postedChildren as $_idx=>$postedChild){
            $postedChild['linked_product_id'] = intval($postedChild['linked_product_id']);
            $postedChild['linked_product_quantity'] = intval($postedChild['linked_product_quantity']);
            // check boxes
            $postedChild['show_on_packing_slip'] = isset($postedChild['show_on_packing_slip'])?intval($postedChild['show_on_packing_slip']):0;
            $postedChild['show_on_invoice'] = isset($postedChild['show_on_invoice'])?intval($postedChild['show_on_invoice']):0;
            $postedChild['sort_order'] = intval(isset($sort_order[$postedChild['linked_product_id']])?$sort_order[$postedChild['linked_product_id']]:$_idx);
            $Children[$postedChild['linked_product_id']] = $postedChild;
        }

        $LinkedChildren = ProductsLinkedChildren::findAll(['parent_product_id'=>$product->products_id]);
        foreach ($LinkedChildren as $_idx => $LinkedChild){
            if ( !isset($Children[$LinkedChild->linked_product_id]) ) {
                $LinkedChild->delete();
            }else{
                $LinkedChild->setAttributes($Children[$LinkedChild->linked_product_id], false);
                $LinkedChild->save(false);
                unset($Children[$LinkedChild->linked_product_id]);
            }
        }
        foreach( $Children as $AppendChild ){
            $NewChildren = new ProductsLinkedChildren($AppendChild);
            $NewChildren->loadDefaultValues();
            $NewChildren->parent_product_id = $product->products_id;
            $NewChildren->save(false);
        }
    }

    public static function getChildrenProducts($parentId)
    {
        if ( !static::allowed() ) return [];

        $ChildrenProductsData = [];

        $childProducts = ProductsLinkedChildren::find()
            ->select(['linked_product_id','linked_product_quantity'])
            ->where(['parent_product_id'=>(int)$parentId])
            ->orderBy(['sort_order'=>SORT_ASC])
            ->asArray()
            ->all();
        if ( count($childProducts)>0 ) {
            foreach ($childProducts as $childProduct){
                if (\common\models\Products::find()
                    ->where(['products_id'=>$childProduct['linked_product_id']])
                    ->count()>0){
                    $ChildrenProductsData[$childProduct['linked_product_id']] = $childProduct['linked_product_quantity'];
                }
            }
        }

        return $ChildrenProductsData;
    }

    public static function filterStockIndication($data_array)
    {
        if ( !static::allowed() ) return $data_array;

        if ( !static::configCheckLinkedStock() ) return $data_array;

        $linked_products = static::getChildrenProducts((int)$data_array['products_id']);
        foreach ($linked_products as $linked_product_id=>$linked_product_qty){
            $linked_stock_available = \common\helpers\Product::get_products_stock($linked_product_id);
            /*$return[$linked_product_id] = [
                'products_id' => $linked_product_id,
                'num_product' => $linked_product_qty,
                'products_quantity' => $linked_stock_available,
            ];*/
            $part_qty = floor($linked_stock_available/$linked_product_qty);
            if ($data_array['products_quantity']>$part_qty){
                $data_array['products_quantity'] = $part_qty;
            }
        }
        return $data_array;
    }

}
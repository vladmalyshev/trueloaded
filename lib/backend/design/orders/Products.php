<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\orders;


use Yii;
use yii\base\Widget;
use common\helpers\OrderProduct;

class Products extends Widget {

    public $order;
    public $manager;

    public function init(){
        parent::init();
    }

    public function run(){
        global $languages_id;

        $opsArray = array();
        foreach (\common\models\OrdersProductsStatus::findAll(['language_id' => (int)$languages_id]) as $opsRecord) {
            $opsArray[$opsRecord->orders_products_status_id] = $opsRecord;
        }
        unset($opsRecord);

        $handlers_array = [];
        if (defined('EXTENSION_HANDLERS_ENABLED') && EXTENSION_HANDLERS_ENABLED == 'True') {
            $handlers_query = tep_db_query("select handlers_id from handlers_access_levels where access_levels_id='" . (int)$_SESSION['access_levels_id'] . "'");
            while ($handlers = tep_db_fetch_array($handlers_query)) {
                $handlers_array[] = $handlers['handlers_id'];
            }
        }

        $warehouseList = [];
        foreach (\common\models\Warehouses::find()->asArray(true)->all() as $warehouseRecord) {
            $warehouseList[$warehouseRecord['warehouse_id']] = $warehouseRecord['warehouse_name'];
        }
        unset($warehouseRecord);

        $locationBlockList = [];
        foreach (\common\models\LocationBlocks::find()->asArray(true)->all() as $locationBlockRecord) {
            $locationBlockList[$locationBlockRecord['block_id']] = $locationBlockRecord['block_name'];
        }
        unset($locationBlockRecord);

        return $this->render('products',[
            'manager' => $this->manager,
            'opsRecord' => $opsRecord,
            'order' => $this->order,
            'opsArray' => $opsArray,
            'handlers_array' => $handlers_array,
            'warehouseList' => $warehouseList,
            'locationBlockList' => $locationBlockList,
            'headers' => [
                'cancel'     => $opsArray[OrderProduct::OPS_CANCELLED]->orders_products_status_name_long ?? TEXT_STATUS_LONG_OPS_CANCELLED,
                'ordered'    => $opsArray[OrderProduct::OPS_STOCK_ORDERED]->orders_products_status_name_long ?? TEXT_STATUS_LONG_OPS_STOCK_ORDERED,
                'received'   => $opsArray[OrderProduct::OPS_RECEIVED]->orders_products_status_name_long ?? TEXT_STATUS_LONG_OPS_RECEIVED,
                'dispatched' => $opsArray[OrderProduct::OPS_DISPATCHED]->orders_products_status_name_long ?? TEXT_STATUS_LONG_OPS_DISPATCHED,
                'delivered'  => $opsArray[OrderProduct::OPS_DELIVERED]->orders_products_status_name_long ?? TEXT_STATUS_LONG_OPS_DELIVERED
            ],
        ]);
    }
}

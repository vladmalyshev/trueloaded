<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;

class FreezeStockController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_FREEZE_STOCK'];

    public function actionIndex() {
        $this->selectedMenu = array('reports', 'freeze-stock');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('freeze-stock/'), 'title' => BOX_FREEZE_STOCK);
        $this->view->headingTitle = BOX_FREEZE_STOCK;
        
        if (defined('ENABLE_FREEZE_STOCK') && ENABLE_FREEZE_STOCK == '1') {
            $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl('freeze-stock/unfreeze-now').'" onclick="return confirm(\'' . TEXT_FREEZE_CONFIRM . '\')" class="create_item">' . TEXT_UNFREEZE . '</a>';
            $this->view->ViewTable = array(
                array(
                    'title' => 'Products',
                    'not_important' => 0,
                ),
                array(
                    'title' => 'Stock',
                    'not_important' => 0,
                ),
                array(
                    'title' => 'Actions',
                    'not_important' => 0,
                ),
            );
            
            return $this->render('index', []);
        } elseif (defined('ENABLE_FREEZE_STOCK')) {
            $this->topButtons[] = '<a href="'.Yii::$app->urlManager->createUrl('freeze-stock/freeze-now').'" onclick="return confirm(\'' . TEXT_FREEZE_CONFIRM . '\')" class="create_item">' . TEXT_FREEZE . '</a>';
            return $this->render('index-inactive', []);
        }
    }
    
    public function actionFreezeNow() {
        \common\models\Configuration::updateByKey('ENABLE_FREEZE_STOCK', '1');
        \common\models\Configuration::updateByKey('FREEZE_STOCK_DATE', date('Y-m-d H:i:s'));

        // customer basket
        foreach (\common\models\OrdersProductsTemporaryStock::find()->all() as $TemporaryStockRecord) {
            $FreezeTemporaryStock = new \common\models\FreezeTemporaryStock();
            $FreezeTemporaryStock->setAttributes($TemporaryStockRecord->getAttributes());
            $FreezeTemporaryStock->temporary_stock_id = $TemporaryStockRecord->temporary_stock_id;
            $FreezeTemporaryStock->child_id = $TemporaryStockRecord->child_id;
            $FreezeTemporaryStock->save();
            $TemporaryStockRecord->delete();
            \common\helpers\Product::doCache($FreezeTemporaryStock->products_id);
        }
        
        $connection = Yii::$app->getDb();
        //$connection->createCommand("INSERT IGNORE INTO freeze_products (products_id,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity) SELECT products_id,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity FROM products;")->execute();
        //$connection->createCommand("INSERT IGNORE INTO freeze_inventory (inventory_id,products_id,prid,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity) SELECT inventory_id,products_id,prid,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity FROM inventory;")->execute();
        $connection->createCommand("TRUNCATE TABLE `freeze_orders_products_allocate`;")->execute();
        $connection->createCommand("INSERT IGNORE INTO freeze_products (products_id,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity) SELECT products_id,(products_quantity-allocated_stock_quantity),0,temporary_stock_quantity,(warehouse_stock_quantity-allocated_stock_quantity),suppliers_stock_quantity,ordered_stock_quantity FROM products;")->execute();
        $connection->createCommand("INSERT IGNORE INTO freeze_inventory (inventory_id,products_id,prid,products_quantity,allocated_stock_quantity,temporary_stock_quantity,warehouse_stock_quantity,suppliers_stock_quantity,ordered_stock_quantity) SELECT inventory_id,products_id,prid,(products_quantity-allocated_stock_quantity),0,temporary_stock_quantity,(warehouse_stock_quantity-allocated_stock_quantity),suppliers_stock_quantity,ordered_stock_quantity FROM inventory;")->execute();
        
        return $this->redirect(Yii::$app->urlManager->createUrl('freeze-stock/'));
    }
    
    public function actionUnfreezeNow() {
        \common\models\Configuration::updateByKey('ENABLE_FREEZE_STOCK', '0');
        \common\models\Configuration::updateByKey('FREEZE_STOCK_DATE', '');
        
        foreach (\common\models\FreezeTemporaryStock::find()->all() as $FreezeTemporaryStock) {
            $TemporaryStockRecord = new \common\models\OrdersProductsTemporaryStock();
            $TemporaryStockRecord->setAttributes($FreezeTemporaryStock->getAttributes());
            $TemporaryStockRecord->temporary_stock_id = $FreezeTemporaryStock->temporary_stock_id;
            $TemporaryStockRecord->child_id = $FreezeTemporaryStock->child_id;
            $TemporaryStockRecord->save();
            $FreezeTemporaryStock->delete();
            \common\helpers\Product::doCache($TemporaryStockRecord->products_id);
        }
        
        return $this->redirect(Yii::$app->urlManager->createUrl('freeze-stock/after-unfreeze'));
    }
    
    public function actionAfterUnfreeze() {
        $connection = Yii::$app->getDb();
        
        foreach (\common\models\FreezeProducts::find()->all() as $FreezeProducts) {
            \common\helpers\Product::doCache($FreezeProducts->products_id);
        }
        $connection->createCommand("TRUNCATE TABLE `freeze_products`;")->execute();
        $connection->createCommand("TRUNCATE TABLE `freeze_inventory`;")->execute();
        foreach (\common\models\FreezeOrdersProductsAllocate::find()->asArray(true)->all() as $orderProductRecord) {
            \common\helpers\OrderProduct::doAllocateAutomatic($orderProductRecord['orders_products_id'], true);
        }
        $connection->createCommand("TRUNCATE TABLE `freeze_orders_products_allocate`;")->execute();
        return $this->redirect(Yii::$app->urlManager->createUrl('freeze-stock/'));
    }
    
    public function actionProductListing() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        if ($length == -1) {
            $length = 10000;
        }
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');
        
        $queryRaw = \common\models\Products::find()
                ->select([
                    'p.products_id', 
                    'p.products_model', 
                    'if(length(pd.products_name) > 0, pd.products_name, pdd.products_name) as products_name',
                    'p.products_quantity',
                    'p.allocated_stock_quantity',
                    'p.temporary_stock_quantity',
                    'p.warehouse_stock_quantity',
                ])
                ->from(TABLE_PRODUCTS . " p")
                ->leftJoin(TABLE_PRODUCTS_DESCRIPTION . " pd", "p.products_id = pd.products_id")
                ->leftJoin(TABLE_PRODUCTS_DESCRIPTION . " pdd", "p.products_id = pdd.products_id")
                ->leftJoin(TABLE_PRODUCTS_TO_CATEGORIES . " p2c", "p.products_id = p2c.products_id")
                ->where("p.manual_stock_unlimited = 0 and p.is_bundle = 0")
                ->andwhere("pd.language_id = '" . (int) $languages_id . "' and pdd.language_id = '" . \common\helpers\Language::get_default_language_id() . "'");
        
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $queryRaw->andWhere("(pd.products_name like '%" . $keywords . "%' or p.products_model like '%" . $keywords . "%')");
        }

        $queryRaw->groupBy('p.products_id')->orderBy('p2c.sort_order', 'pd.products_name');
        $query_numrows = $queryRaw->count();
        $queryRaw->limit($length)->offset($start)->asArray();
        
        $responseList = [];
        foreach ($queryRaw->each() as $item_data) {
            $image = \common\classes\Images::getImage($item_data['products_id']);
            $allocatedTemporary = \common\helpers\Product::getAllocatedTemporary($item_data['products_id'], true);
            
            $product_categories_string = '';
            if (true) {
                $product_categories = \common\helpers\Categories::generate_category_path($item_data['products_id'], 'product');
                $product_categories_string .= '';
                for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
                    $category_path = '';
                    for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
                        $category_path .= '<span class="category_path__location">' . $product_categories[$i][$j]['text'] . '</span>&nbsp;&gt;&nbsp;';
                    }
                    $category_path = substr($category_path, 0, -16);
                    $product_categories_string .= '<li class="category_path">' . $category_path . '</li>';
                }
                $product_categories_string = '<span class="category_path" style="display:block">' . TEXT_LIST_PRODUCT_PLACED_IN . '</span> <ul class="category_path_list">' . $product_categories_string . '</ul>';
            }

            $existLocations = \common\models\WarehousesProducts::find()
                ->select(['sum(warehouse_stock_quantity) as warehouse_stock_quantity'])
                ->where(['products_id' => $item_data['products_id']])
                ->asArray()
                ->one();
             $item_data['warehouse_stock_quantity'] = $existLocations['warehouse_stock_quantity'];
            
            $responseList[] = array(
                '<div class="prod_name">' .
                (!empty($image) ? '<span class="prodImgC">' . $image . '</span>' : '<span class="cubic"></span>') .
                $item_data['products_name'] . 
                ' <br><b>' . $item_data['products_model'] . '</b> ' .
                $product_categories_string . 
                tep_draw_hidden_field('id', $item_data['products_id'], 'class="cell_identify"') .
                '<input class="cell_type" type="hidden" value="top">' .
                '</div>',
                TEXT_STOCK_WAREHOUSE_QUANTITY . ': ' . $item_data['warehouse_stock_quantity'] . '<br>'.
                //TEXT_STOCK_TEMPORARY_QUANTITY . ': ' . $item_data['temporary_stock_quantity'] . (($allocatedTemporary > 0) ? (' / ' . $allocatedTemporary) : '') . '<br>' .
                TEXT_STOCK_ALLOCATED_QUANTITY . ': ' . $item_data['allocated_stock_quantity'] . '<br>' .
                TEXT_STOCK_QUANTITY_INFO . ': '  . ($item_data['warehouse_stock_quantity'] - $item_data['allocated_stock_quantity']) /*$item_data['products_quantity']*/ . '<br>' ,
                //($item_data['temporary_stock_quantity'] > 0 ? '<a href="'.Yii::$app->urlManager->createUrl(['categories/temporary-stock', 'prid' => $item_data['products_id']]).'" class="btn right-link-upd edp-qty-update">'.TEXT_TEMPORARY_STOCK.'</a><br><br>' : '') .
                '<a href="'.Yii::$app->urlManager->createUrl(['freeze-stock/warehouse-location', 'products_id' => $item_data['products_id']]).'" class="btn right-link edp-qty-update" data-class="update-stock-popup">'.TEXT_UPDATE_STOCK.'</a>' . '<br><br>'.
                ($item_data['allocated_stock_quantity'] > 0 ? '<a href="'.Yii::$app->urlManager->createUrl(['categories/order-reallocate', 'prid' => $item_data['products_id']]).'" class="btn right-link edp-qty-update" data-class="product-relocate-popup">'.TEXT_ORDER_UNLOCATE.'</a>' : ''),
            );
        }
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }
    
    public function actionWarehouseLocation() {
        \common\helpers\Translation::init('admin/categories');
        $this->layout = false;

        

        if (Yii::$app->request->isPost) {
            $products_id = (string)Yii::$app->request->post('products_id');
            $stock_equal_qty = Yii::$app->request->post('stock_equal_qty');//suppliers_id warehouse_id location_id
            $updateData = [];
            $existLocations = \common\models\WarehousesProducts::find()
                ->select(['suppliers_id', 'warehouse_id', 'location_id', 'warehouse_stock_quantity', 'products_quantity'])
                ->where(['products_id' => $products_id])
                ->asArray()
                ->all();
            foreach ($existLocations as $location) {
                if (isset($stock_equal_qty[$location['suppliers_id']][$location['warehouse_id']][$location['location_id']]) && $stock_equal_qty[$location['suppliers_id']][$location['warehouse_id']][$location['location_id']] >= 0) {
                    $newQty = (int)$stock_equal_qty[$location['suppliers_id']][$location['warehouse_id']][$location['location_id']];
                    $updQty = ($newQty - $location['products_quantity']);
                    if ($updQty < 0) {
                        $updQty = -1 * $updQty;
                        $updQtyPrefix = '-';
                    } else {
                        $updQtyPrefix = '+';
                    }
                    $updateData[] = [
                        'quantity' => $updQty,
                        'prefix' => $updQtyPrefix,
                        'suppliers_id' => $location['suppliers_id'],
                        'warehouse_id' => $location['warehouse_id'],
                        'location_id' => $location['location_id'],
                    ];
                }
            }
            //---------------------------
            if (strpos($products_id, '{') !== false && \common\helpers\Inventory::get_prid($products_id) > 0) {
                foreach ($updateData as $updateItem) {
                    if ($updateItem['quantity'] > 0) {
                        $check_data = tep_db_fetch_array(tep_db_query("select products_quantity, ordered_stock_quantity, suppliers_stock_quantity from " . TABLE_INVENTORY . " where products_id = '" . tep_db_input($products_id) . "'"));
                        if (!$check_data) {
                            tep_db_query("insert into " . TABLE_INVENTORY . " set inventory_id = '', products_id = '" . tep_db_input($products_id) . "', prid = '" . (int) \common\helpers\Inventory::get_prid($products_id) . "'");
                        }

                        global $login_id;

                        if ($updateItem['warehouse_id'] > 0) {
                            $parameters = [
                                'admin_id' => $login_id,
                                'date_added' => FREEZE_STOCK_DATE,
                                'comments' => '*** Frozen ***' . TEXT_MANUALL_STOCK_UPDATE
                            ];
                            \common\helpers\Warehouses::update_products_quantity($products_id, $updateItem['warehouse_id'], $updateItem['quantity'], $updateItem['prefix'], $updateItem['suppliers_id'], $updateItem['location_id'], $parameters);
                            \common\helpers\Product::getAllocated($products_id);
                            \common\helpers\Product::getAllocatedTemporary($products_id);
                        } else {
                            tep_db_query("update " . TABLE_INVENTORY . " set products_quantity = products_quantity " . $updateItem['prefix'] . $updateItem['quantity'] . " where products_id = '" . tep_db_input($products_id) . "'");
                            \common\helpers\Product::getAllocated($products_id);
                            \common\helpers\Product::getAllocatedTemporary($products_id);
                        }
                    }
                }
            } elseif ($products_id > 0) {
                foreach ($updateData as $updateItem) {
                    if ($updateItem['quantity'] > 0) {
                        //$check_data = tep_db_fetch_array(tep_db_query("select products_quantity, ordered_stock_quantity, suppliers_stock_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int) $products_id . "'"));

                        global $login_id;
                        if ($updateItem['warehouse_id'] > 0) {
                            $parameters = [
                                'stock_freeze' => 1,
                                'admin_id' => $login_id,
                                'date_added' => FREEZE_STOCK_DATE,
                                'comments' => '*** Frozen ***' . TEXT_MANUALL_STOCK_UPDATE
                            ];
                            \common\helpers\Warehouses::update_products_quantity($products_id, $updateItem['warehouse_id'], $updateItem['quantity'], $updateItem['prefix'], $updateItem['suppliers_id'], $updateItem['location'], $parameters);
                            //\common\helpers\Product::getAllocated($products_id);
                            //\common\helpers\Product::getAllocatedTemporary($products_id);
                        } else {
                            tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity " . $updateItem['prefix'] . $updateItem['quantity'] . " where products_id = '" . (int) $products_id . "'");
                            //\common\helpers\Product::getAllocated($products_id);
                            //\common\helpers\Product::getAllocatedTemporary($products_id);
                        }
                    }
                }
            }
            \common\helpers\Product::doCache($products_id);
            //---------------------------
            $return = ['status' => 'ok', 'message' => ''];
            return json_encode($return);
        }
        
        $products_id = (string)Yii::$app->request->get('products_id');
        
        $blocks =\common\models\LocationBlocks::find()->asArray()->all();
        $blocksList = [];
        foreach ($blocks as $value) {
            $blocksList[$value['block_id']] = $value['block_name'];
        }

        $existLocations = \common\models\WarehousesProducts::find()
            ->select(['suppliers_id', 'warehouse_id', 'location_id', 'products_quantity'])
            ->where(['products_id' => $products_id])
            ->orderBy(['suppliers_id' => SORT_ASC, 'warehouse_id' => SORT_ASC, 'location_id' => SORT_ASC])
            ->asArray()
            ->all();
      
        $locationList = [];
        foreach ($existLocations as $location) {
            /*if ($location['products_quantity'] <= 0) {
                continue;
            }*/
            $name = \common\helpers\Warehouses::getLocationPath($location['location_id'], $location['warehouse_id'], $blocksList);
            if (empty($name)) {
                $name = 'N/A';
            }
            $locationList[] = [
                'location_id' => $location['location_id'],
                'suppliers_id' => $location['suppliers_id'],
                'warehouse_id' => $location['warehouse_id'],
                'name' => $name,
                'qty' => $location['products_quantity'],
            ];
        }
        return $this->render('warehouse-location-equal', ['locationList' => $locationList]);
    }

}

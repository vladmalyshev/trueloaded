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
use common\extensions\WeddingRegistry\services\AdminWeddingRegistryService;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use common\extensions\WeddingRegistry\WeddingRegistry;
use common\extensions\WeddingRegistry\models\WeddingRegistry as WRModel;

class WeddingRegistriesController extends Sceleton {

    /**
     * @var WeddingRegistryService
     */
    public $service;
    
    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_WEDDING_REGISTRIES'];

    public function __construct($id, $module = null, AdminWeddingRegistryService $service, array $config = []) {
        
        $this->service = $service;

        \common\helpers\Translation::init('admin/wedding-registries');
        \common\helpers\Translation::init('admin/orders/order-edit');

        $this->selectedMenu = array('customers', 'wedding-registries');

        parent::__construct($id, $module, $config);
    }

    public function actionIndex() {


        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('wedding-registries/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $this->view->weddindTable = array(
            array(
                'title' => ENTRY_LAST_NAME,
                'not_important' => 0
            ),
            array(
                'title' => ENTRY_FIRST_NAME,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_EMAIL/* . '/' . TABLE_HEADING_PLATFORM*/,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_PRODCTS_COUNT,
                'not_important' => 1
            ),
        );

        $row = Yii::$app->request->get('row', 0);
        $messages = Yii::$app->session->getAllFlashes();
        return $this->render('index', ['messages' => $messages, $row => $row]);
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $paramsQuery = Yii::$app->request->queryParams;

        $responseList = array();

        if ($length == -1) {
            $length = 10000;
        }

        $weddingListQuery = $this->service->searchByQueryParams($paramsQuery);

        $weddingListQueryCount = clone $weddingListQuery;

        $weddingListQuery->offset($start)
                ->limit($length);

        $registries = $weddingListQuery->all();

        foreach ($registries as $registry) {
            $responseList[] = array(
                $registry->customer_lastname . '<input class="cell_identify" type="hidden" value="' . $registry->id . '">',
                $registry->customer_firstname,
                '<a class="ord-name-email" href="mailto:' . $registry->customer_email . '"><b>' . $registry->customer_email . '</b></a>',
                ($count = $registry->getCountProducts()) ? $count .'/' . $registry->getCountBoughtProducts(): '0',
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $weddingListQueryCount->count(),
            'recordsFiltered' => $weddingListQueryCount->count(),
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionItempreedit() {

        $registry_id = Yii::$app->request->post('item_id');
        $registry = $this->findModel($registry_id);

        return $this->renderAjax('preedit.tpl', ['model' => $registry]);
    }

    public function actionEdit() {
        $languages_id = \Yii::$app->settings->get('languages_id');


        $registry_id = Yii::$app->request->get('item_id');

        $registry = $this->service->getInfo($registry_id, $languages_id);

        $products = [];
        if ($registry->products) {
            foreach ($registry->products as $item) {
                $product['name'] = $item->product->description->products_name;
                $product['model'] = $item->product->products_model;
                $product['qty'] = $item->qty;
                $product['product_id'] = $item->product_id;
                $product['ordered_qty'] = $item->ordered_qty;
                $priceInstance = \common\models\Product\Price::getInstance($item->uprid);
                $product['price'] = $priceInstance->getInventoryPrice(['qty' => 1]);
                $product['tax'] = \common\helpers\Tax::get_tax_rate($item->product->products_tax_class_id);
                $products[] = $product;
            }
        }

        $currencies = \Yii::$container->get('currencies');

        return $this->render('edit', ['model' => $registry, 'products' => $products, 'currencies' => $currencies]);
    }

    public function actionConfirmItemDelete() {
        $this->layout = false;
        $id = Yii::$app->request->post('item_id');

        $registry = $this->findModel($id);

        return $this->renderAjax('confirm-delete', ['model' => $registry]);
    }

    public function actionDelete() {
        $model = $this->findModel(Yii::$app->request->post('item_id'));
        $this->service->remove($model);
        exit;
    }

    /**
     * @param $id
     *
     * @return null|ActiveRecord
     * @throws NotFoundHttpException
     */
    public function findModel($id) {
        if (( $model = WRModel::findOne($id) ) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

}

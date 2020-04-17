<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use backend\models\EP\Exception;
use common\extensions\WeddingRegistry\WeddingRegistry as WRExtension;
use common\models\Customers;
/*
  use common\models\WeddingRegistry;
  use common\models\WeddingRegistryInviting;
  use frontend\forms\wedding\InviteForm;
  use frontend\forms\wedding\InviteFormContainer;
  use frontend\forms\wedding\SearchForm;
  use frontend\forms\wedding\TestFormContainer;
  use frontend\forms\wedding\WeddingRegistryForm;
  use frontend\services\WeddingRegistryService;
  use \frontend\design\boxes\product\WeddingRegistryButton;
  use common\models\WeddingRegistryProducts;
 */
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use \yii\web\Response;
use \common\helpers\Inventory;
use \common\helpers\Product;
use \common\helpers\Attributes;
use common\classes\Images;
use common\helpers\Sorting;
use frontend\design\Info;
use frontend\design\SplitPageResults;
use frontend\design\ListingSql;
use frontend\design\boxes\Listing;

class WeddingRegistryController extends Sceleton {

    /**
     * @var WeddingRegistryService
     */
    private $service;

    public function __construct($id, $module = null, array $config = []) {
        parent::__construct($id, $module, $config);
        \common\helpers\Translation::init('wedding');
        if (!($weddingExt = \common\helpers\Acl::checkExtensionAllowed('WeddingRegistry', 'allowed'))) {
            throw new NotFoundHttpException('Wedding Registry Module is not initialized');
        }
        Yii::$app->urlManager->createAbsoluteUrl(['shopping-cart']);
    }

    public function actionIndex() {
        return $this->render('index', [
                    'content' => WRExtension::renderMainSelection()
        ]);
    }

    public function actionShare() {

        if (Yii::$app->user->isGuest) {
            return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl(['account/login']));
        }
        
        return $this->render('index', [
                    'content' => WRExtension::renderRegistryShare()
        ]);        
    }

    public function actionSearch() {
        return $this->render('index', [
                    'content' => WRExtension::renderRegistrySearch()
        ]);
    }

    public function actionView() {
        
        return $this->render('index', [
                   'content' => WRExtension::renderRegistryView()
        ]);      
        
    }

    public function actionManage() {

        if (Yii::$app->user->isGuest) {
            return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('wedding-registry'));
        }
        
        return $this->render('index', [
                    'content' => WRExtension::renderRegistryManage()
        ]);
    }

    public function actionCreate() {
        global $navigation;
        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('js');

        define("WEDDING_REGISTRY_FORM_HEADING", CREATE_YOUR_WEDDING_REGISTRY);
        
        if (Yii::$app->user->isGuest) {
            if (is_object($navigation) && method_exists($navigation, 'set_snapshot')){
                $navigation->set_snapshot();
            }            
            return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('account/login'));
        }
        
        $customer = Yii::$app->user->getIdentity();//Customers::findOne(['customers_id' => Yii::$app->user->getId()]);
        
        return $this->render('index', [
            'content' => WRExtension::renderRegistryUpdate()
        ]);        
    }

    public function actionSuccess() {
        return $this->render('index', [
            'content' => WRExtension::renderRegistrySuccess()
        ]);
    }

    public function actionUpdate() {
        
        \common\helpers\Translation::init('checkout');
        \common\helpers\Translation::init('js');

        define("WEDDING_REGISTRY_FORM_HEADING", MANAGE_YOUR_WEDDING_REGISTRY);
        
        if (Yii::$app->user->isGuest) {
            $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('wedding-registry'));
        }
        $model = (WRExtension::getWeddingService())->getByCustomerId((int)Yii::$app->user->getId());
        if (!$model) {
            $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('wedding-registry'));
        }
        
        return $this->render('index', [
            'content' => WRExtension::renderRegistryUpdate()
        ]);
    }
    
    public function actionUploadImage(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $response = ['status' => 'error'];
        if (isset($_FILES['file'])) {
            $path = \Yii::getAlias('@webroot');
            $path .= DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR;

            \yii\helpers\FileHelper::createDirectory($path, 0777);

            $uploadfile = $path . basename($_FILES['file']['name']);
            
            if ( is_writeable(dirname($uploadfile)) ) {
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
                    $save = (bool)Yii::$app->request->post('save', false);
                    $type = Yii::$app->request->post('type', 'avatar');
                    $_service = WRExtension::getWeddingService();
                    $file = $_service->makeImage($type, $uploadfile, $save);
                    if ($file){
                        $response = ['status' => 'ok', 'image' => $_service->getImage($type, $file), 'file' => $file];
                    } else {
                        $response = ['status' => 'error', 'text' => 'bad'];
                    }
                } 
            }
        }
        return $response;
    }
    
    public function actionRemoveImage(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $type = Yii::$app->request->post('type', 'banner');
        $_service = WRExtension::getWeddingService();
        
        if ($_service->removeImage($type)){
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error'];
        }
        return $response;
    }

    public function actionAddProduct() {        

        $products_id = \Yii::$app->request->post('products_id', 0);
        $attributesIds = \Yii::$app->request->post('id', []);
        $check_attribute = (boolean) \Yii::$app->request->post('check_attribute', true);
        $qty = Yii::$app->request->post('qty', 1);
        if (is_array($qty)){
            $qty = 1;
        }

        $attributes = [];
        if (!is_numeric($products_id)) {
            $products_id = Inventory::normalize_id($products_id, $attributes);
        }
        $name_product = Product::get_products_name($products_id);

        $message = false;
        if ($products_id == 0 || Yii::$app->user->isGuest || (!Product::check_product((int) $products_id) )) {
            $message = sprintf(ADD_TO_PERSONAL_CATALOG_ERROR, $name_product);
        }

        if (Attributes::has_product_attributes((int) $products_id) && $check_attribute) {
            if (is_array($attributesIds) && count($attributesIds) > 0) {
                foreach ($attributesIds as $attributeId) {
                    if (!$attributeId) {
                        $message = PLEASE_CHOOSE_ATTRIBUTES;
                        break;
                    }
                }
                $products_id = Inventory::get_uprid((int) $products_id, $attributesIds);
                $products_id = Inventory::normalize_id($products_id);
            } else {
                $message = PLEASE_CHOOSE_ATTRIBUTES;
            }
        }

        if ($message == false) {
            try {
                (WRExtension::getWeddingService())->addProduct($products_id, $qty);
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
            $message = TEXT_ADDED;
        }
        $response = [];
        $response['message'] = $message;
        $params = [];
        $params['products_id'] = $products_id;
        $params['get_button'] = true;
        $response['button'] = WRExtension::getWeddingRegistryButton($params);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }
    
    public function actionCheckProduct(){
        $products_id = \Yii::$app->request->get('products_id');
        $params = [];
        $params['products_id'] = $products_id;
        $params['get_button'] = true;
        $response['button'] = WRExtension::getWeddingRegistryButton($params);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionRemoveProduct() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $products_id = \Yii::$app->request->post('products_id', 0);
        try {            
            (WRExtension::getWeddingService())->removeProduct($products_id);
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            return $response;
        }
        $response = [];
        $response['message'] = TEXT_REMOVED;
        $response['success'] = true;
        $params = [];
        $params['products_id'] = $products_id;
        $params['get_button'] = true;
        $response['button'] = WRExtension::getWeddingRegistryButton($params);
        return $response;
    }

    public function actionDelete() {
        
        if (Yii::$app->user->isGuest) {
            return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('wedding-registry'));
        }

        try {
            (WRExtension::getWeddingService())->delete();
        } catch (\DomainException $e) {
            return $e->getMessage();
        }

        return $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('wedding-registry'));
    }

    public function actionChangeQty() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $product_id = Yii::$app->request->post('product_id');
        $qty = Yii::$app->request->post('qty');
        $_service = WRExtension::getWeddingService();
        if ($_service->updateQty($_service->getRegistry()->id, $product_id, $qty)) {
            return [
                'success' => true,
                'qty' => $qty
            ];
        }

        return ['success' => false];
    }
    
    public function actionChangeAttributes(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $product_id = Yii::$app->request->post('product_id');
        $id = Yii::$app->request->post('id', []);
        if ($id && $product_id){
            $_service = WRExtension::getWeddingService();
            $uprid = \common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid((int)$product_id, $id));
            if ($_service->updateAttributes($_service->getRegistry()->id, $product_id, $uprid)){
                return [
                'success' => true,                
                ];
            }
        }
        return ['success' => false];
    }
    
}

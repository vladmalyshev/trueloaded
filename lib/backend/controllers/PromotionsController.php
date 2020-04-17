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
use common\models\promotions\Promotions;
use common\models\promotions\PromotionsSets;
use common\models\promotions\PromotionsConditions;
use common\models\promotions\PromotionsSetsConditions;
use common\models\promotions\PromotionService;
use common\models\Product\PromotionPrice;
use common\models\promotions\PromotionsBonusService;
use common\models\promotions\PromotionsBonusGroups;
use common\models\promotions\PromotionsBonusGroupsDescription;
use common\models\promotions\PromotionsBonusPointsDescription;
use common\models\promotions\PromotionsAssignement;
use yii\helpers\Url;
use common\classes\Images;

class PromotionsController extends Sceleton {

    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_PROMOTIONS', 'BOX_PROMOTIONS_PIRCES'];
    CONST MAX_ICON_WIDTH = 50;
    CONST MAX_ICON_HEIGHT = 50;

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/promotions');
        parent::__construct($id, $module);
    }

    public function actionIndex() {
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('promotions/index'), 'title' => HEADING_TITLE);
        $this->selectedMenu = array('marketing', 'promotions-box', 'promotions');

        $first = \common\classes\platform::firstId();
        $paltform_id = Yii::$app->request->get('platform_id', $first);

        $this->topButtons[] = '<a href="' . Url::toRoute(['promotions/edit', 'platform_id' => $paltform_id]) . '" class="create_item">' . IMAGE_NEW . '</a>';
        $this->view->headingTitle = HEADING_TITLE;

        $this->view->promotionsTable = [
            array(
                'title' => TABLE_HEADING_PROMOTIONS_LABEL,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_PROMOTIONS_DESCRIPTION,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_STATUS,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_PROMOTIONS_ITEMS,
                'not_important' => 0
            ),
        ];

        $this->view->row_id = Yii::$app->request->get('row_id', 0);

        return $this->render('index', [
                    'platforms' => \common\classes\platform::getList(false),
                    'first_platform_id' => $first,
                    'paltform_id' => $paltform_id,
                    'isMultiPlatform' => \common\classes\platform::isMulti(),
                    'settings' => PromotionPrice::getSettings(),
        ]);

        return $this->render('index');
    }

    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $output = [];
        parse_str(Yii::$app->request->get('filter', ''), $output);

        $responseList = [];
        if ($length == -1)
            $length = 10000;
        $recordsTotal = 0;
        
        $platform_id = $output['platform_id'];
        if (!$platform_id) {
            $platform_id = \common\classes\platform::defaultId();
        }

        $promo = Promotions::find()->where(['platform_id' => $platform_id]);

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $promo->andWhere(['like', 'promo_label', $_GET['search']['value']]);
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $promo->orderBy('promo_label ' . $_GET['order'][0]['dir']);
                    break;
                default:
                    $promo->orderBy('promo_label ');
                    break;
            }
        } else {
            $promo->orderBy('promo_priority');
        }

        $recordsTotal = $promo->count();
        $promo->limit($length)->offset($start);
        $rows = $promo->all();
        $service = new PromotionService;
        if (is_array($rows)) foreach ($rows as $key => $promo) {
            $responseList[] = array(
                '<div class="handle_cat_list click_double" data-click-double ="' . Url::toRoute(['promotions/edit', 'platform_id' => $platform_id, 'promo_id' => $promo->promo_id]) . '"><span class="handle" title="Priority"><i class="icon-hand-paper-o"></i></span><div class="cat_name">' . $promo->getAttribute('promo_label') . '</div><input class="cell_identify" type="hidden" value="' . $promo->getAttribute('promo_id') . '"></div>',
                '<div class="click_double" data-click-double ="' . Url::toRoute(['promotions/edit', 'platform_id' => $platform_id, 'promo_id' => $promo->promo_id]) . '" >' . $service($promo->promo_class)->getDescription() . '</div>',
                '<input type="checkbox" value="' . $promo->promo_id . '" name="promo_status" class="check_on_off" ' . ($promo->promo_status ? 'checked="checked" ' : '') . '>',
                $promo->getSets()->count(),
            );
        }

        $response = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $responseList,
        ];
        echo json_encode($response);
    }

    public function actionView() {
        $promo_id = Yii::$app->request->post('promo_id', 0);
        $promo = Promotions::find()->where(['promo_id' => $promo_id])->with('sets')->one();
        return $this->renderAjax('view',[
            'promo' => $promo
        ]);
    }
    
    public function actionPersonalize(){
        //$platform_id = Yii::$app->request->get('platform_id');
        $promo_id = Yii::$app->request->get('promo_id');
        if ($promo_id){
            $gOwners = \yii\helpers\ArrayHelper::getColumn(PromotionsAssignement::getPromoOwners($promo_id, PromotionsAssignement::OWNER_GROUP), 'promo_owner');
            $cOwners = \yii\helpers\ArrayHelper::getColumn(PromotionsAssignement::getPromoOwners($promo_id, PromotionsAssignement::OWNER_CUSTOMER), 'promo_owner');
            if (Yii::$app->request->isPost){
                $groups = Yii::$app->request->post('promo_groups');
                if (!$groups){
                    PromotionsAssignement::deletePromoOwners($promo_id, PromotionsAssignement::OWNER_GROUP);
                } else {
                    $add = array_diff($groups, $gOwners);
                    if ($add){
                        PromotionsAssignement::addPromoOwners($promo_id, PromotionsAssignement::OWNER_GROUP, $add);
                    }
                    $del = array_diff($gOwners, $groups);
                    if ($del){
                        PromotionsAssignement::deletePromoOwners($promo_id, PromotionsAssignement::OWNER_GROUP, $del);
                    }
                }
                $customers = Yii::$app->request->post('promo_customers');
                if (!$customers){
                    PromotionsAssignement::deletePromoOwners($promo_id, PromotionsAssignement::OWNER_CUSTOMER);
                } else {
                    $customers = array_unique($customers);
                    $add = array_diff($customers, $cOwners);
                    if ($add){
                        PromotionsAssignement::addPromoOwners($promo_id, PromotionsAssignement::OWNER_CUSTOMER, $add);
                    }
                    $del = array_diff($cOwners, $customers);
                    if ($del){
                        PromotionsAssignement::deletePromoOwners($promo_id, PromotionsAssignement::OWNER_CUSTOMER, $del);
                    }
                }
                echo json_encode([]);
            } else {
                $code = '';
                /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
                if ($ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
                  if ($ext::allowed()) {
                    $code = 'promotions';
                  }
                }
                $groups = [
                    'full' => \common\helpers\Group::get_customer_groups_list($code),
                    'selected' => $gOwners
                ];
                $customers = [];
                foreach (\common\models\Customers::find()->where(['in', 'customers_id', $cOwners])->all() as $customer) {
                    $customers[] = ['id' => $customer->customers_id, 'text' => $customer->customers_firstname . ' ' . $customer->customers_lastname . ' (' . $customer->customers_email_address . ')'];
                }
                return $this->renderAjax('personalization', [
                    'groups' => $groups,
                    'promo_id' => $promo_id,
                    'customers' => $customers,
                ]);
            }
        }
        exit();
    }
    
    public function actionSearchCustomer(){
        $search = Yii::$app->request->post('search');
        $customers = [];
        if (!empty($search)){
            $cRep = new \common\models\repositories\CustomersRepository();
            foreach ($cRep->search($search)->all() as $customer) {
                $customers[] = ['id' => $customer->customers_id, 'text' => $customer->customers_firstname . ' ' . $customer->customers_lastname . ' (' . $customer->customers_email_address . ')'];
            }
        }
        echo json_encode($customers);
        exit();
    }

    public function actionProductSearch() {
        $q = Yii::$app->request->get('q');
        $platform_id = Yii::$app->request->post('platform_id', 0);
        $tree = [
            [
                'id' => 0,
                'text' => 'Top',
                'parent_id' => 0,
                'category' => 1,
                'status' => 1,
            ]
        ];
        $tree = Categories::get_full_category_tree(0, '', '', $tree, true, $platform_id, false);
        //echo '<pre>';print_r($tree);
        return $this->renderAjax('tree', [
                    'tree' => $tree,
        ]);
    }
    
    public function actionObserve(){
        $action = Yii::$app->request->post('action');
        $promo_class = Yii::$app->request->post('promo_class');
        $request = Yii::$app->request->post('request', 'json');
        $params = Yii::$app->request->post('params', []);        
        $service = new PromotionService();
        $promo = $service($promo_class);
        if (method_exists($promo, $action)){
            $promo->useTranslation();
            if ($request == 'json'){
                echo json_encode($promo->$action($params));
            } else {
                return $promo->$action($params);
            }            
        }
        exit();
    }
        
    public function actionSettings(){
        $promo_class = Yii::$app->request->post('promo_class');
        $platform_id = Yii::$app->request->post('platform_id', 0);
        $promo_id =  Yii::$app->request->post('promo_id', 0);
        if ($promo_class){
            $service = new PromotionService();
            $promo = $service($promo_class);
            $promo->useTranslation();
            $promo->loadSettings(['platform_id' => $platform_id, 'promo_id' => $promo_id]);
            return $this->renderAjax($promo->getSettingsTemplate(),[
                'promo' => $promo,
                'promo_class' => $promo_class,
                'promo_description' => $promo->getPromoFullDescription(),
            ]);
        }
        return false;
    }

    public function actionEdit() {

        $this->selectedMenu = array('marketing', 'promotions-box', 'promotions');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('promotions/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $platform_id = Yii::$app->request->get('platform_id', 0);
        if (!$platform_id) {
            return $this->redirect('promotions/index');
        }

        $promo_id = Yii::$app->request->get('promo_id', 0);
        $promo = Promotions::find()->where(['promo_id' => $promo_id])->one();
        $service = null;
        if (!$promo) {
            $promo = new Promotions();            
            $services = PromotionService::getList(['key' => 'description']);
        } else {
            $service = new PromotionService();
            $services = $service($promo->promo_class)->getDescription();
        }
        //echo '<pre>';print_r($promo);die;
        return $this->render('edit', [
            'promo' => $promo,
            'services' => $services,
            'platform_id' => $platform_id,
            'max_width' => self::MAX_ICON_WIDTH,
            'max_height' => self::MAX_ICON_HEIGHT,
            'path' => Images::getWSCatalogImagesPath() . 'promo_icons' . DIRECTORY_SEPARATOR,
        ]);
    }
    
    public function actionSave(){
        
        $promo_class = Yii::$app->request->post('promo_class');
        $promo_id = Yii::$app->request->post('promo_id', 0);
        $promo_status = Yii::$app->request->post('promo_status', 0);
        $promo_type = Yii::$app->request->post('promo_type', 0);
        
        $messages = [];
        
        $service = new PromotionService();
        $promo = $service($promo_class);
        
        if ($promo->load(Yii::$app->request->post())){
            if (($result = $promo->savePromotions($promo_id)) !== false){
                
                if ($promo_id){
                    $promotions = Promotions::find()->where(['promo_id' => $promo_id])->with('sets')->one();
                } 
                if (!$promotions){
                    $promotions = new Promotions();
                    $priority = Promotions::find()->max('promo_priority') + 1;
                    $promotions->setAttribute('promo_priority', $priority);
                }
                
                $params = Yii::$app->request->post();
                $params['promo_status'] = $promo_status;
                                
                if ($promotions->load($params, '') && $promotions->validate()){
                    
                    if (!$promotions->hasErrors()){
                        PromotionsSets::deleteAll(['promo_id' => $promo_id]);
                        PromotionsSetsConditions::deleteAll(['promo_id' => $promo_id]);
                        $promotions->save(false);
                        
                        if ($result){
                            if (is_array($result)){
                                
                                foreach($result as $type => $ids){ //$type: 0 - products, 1 - categories, 2 - brands
                                    if (is_array($ids)){
                                        foreach($ids as $id){
                                            if (!is_array($id)){
                                                $set = new PromotionsSets();
                                                $set->setAttribute('promo_slave_id', $id);
                                                $set->setAttribute('promo_id', $promotions->promo_id);
                                                $set->setAttribute('promo_slave_type', $type);
                                                $set->save(false);
                                                if (method_exists($promo, 'saveSetsConditions') && !$set->hasErrors()){
                                                    $promo->saveSetsConditions($set);
                                                }
                                            } else {
                                                $qty = $id['quantity'];
                                                $value = $id['id'];
                                                $hash = $id['hash'];
                                                $qindex = (int)$id['qindex'];
                                                $nindex = (int)$id['nindex'];
                                                $set = new PromotionsSets();
                                                if ($set->validate()){
                                                    $set->setAttribute('promo_slave_id', $value);
                                                    if ($qty)
                                                        $set->setAttribute('promo_quantity', $qty);
                                                    if ($hash)
                                                        $set->setAttribute('promo_hash', $hash);
                                                    $set->setAttribute('promo_id', $promotions->promo_id);
                                                    $set->setAttribute('promo_slave_type', $type);
                                                    $set->setAttribute('promo_qindex', $qindex);
                                                    $set->setAttribute('promo_nindex', $nindex);
                                                    $set->save(false);
                                                }
                                                if (method_exists($promo, 'saveSetsConditions') && !$set->hasErrors()){
                                                    $promo->saveSetsConditions($set);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if ($promo->hasConditions()){
                            PromotionsConditions::deleteAll(['promo_id' => $promo_id]);
                            if (method_exists($promo, 'saveConditions') && false){
                                $promo->saveConditions($promo_id);
                            } else {
                                $condtitions = $promo->getConditions();

                                if (is_array($condtitions)){
                                    foreach ($condtitions as $values){
                                        $condition = new PromotionsConditions();
                                        if ($condition->load($values, '')){
                                            $condition->setAttribute('promo_id',  $promotions->promo_id);
                                            $condition->save(false);
                                        }
                                    }
                                }
                            }
                        }
                        if (!$promo_id){
                            $messages['success'] = TEXT_MESSEAGE_SUCCESS_ADDED;
                        } else {
                            $messages['success'] = TEXT_MESSEAGE_SUCCESS;
                        }
                    } else {
                        $messages['error'] = 'Error saving';
                    }
                } else {
                    $messages['error'] = 'Error saving';
                }       
                //echo '<pre>';print_r($promotions);
            }
            
        }
        if (!$promo_id){
            echo json_encode(['messages'=> $messages, 'promo_id' => $promotions->promo_id]);
        } else {        
            echo json_encode(['messages'=> $messages]);
        }
        exit();
    }
    
    public function actionIcons(){
        $ws_path = Images::getWSCatalogImagesPath() . 'promo_icons' . DIRECTORY_SEPARATOR;
        $dir = Images::getFSCatalogImagesPath() . 'promo_icons' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) mkdir($dir, 0777);
        if (Yii::$app->request->isPost){
            $response = [];
            $file = Yii::$app->request->post('file','');
            if (!empty($file)){
                if (($new_file = \backend\design\Uploads::move($file, 'images' . DIRECTORY_SEPARATOR . 'promo_icons' . DIRECTORY_SEPARATOR, false)) !== false){
                    $response = ['message' => 'Moved', 'messageType' => 'success', 'filepath' => $ws_path . $new_file, 'file' => $new_file];
                } else {
                    $response = ['message' => 'Not Moved', 'messageType' => 'error'];
                }
            }
            echo json_encode($response);
            exit();
        } else {
            $gallery = [];
            foreach(glob($dir .'*') as $image){
                    $gallery[] = basename($image);
            }
            
            return $this->renderAjax('icons',[
                'max_width' => self::MAX_ICON_WIDTH,
                'max_height' => self::MAX_ICON_HEIGHT,
                'wspath' => $ws_path,
                'gallery' => $gallery,
            ]);
        }
    }
    
    public function actionDelete(){
        $promo_id = Yii::$app->request->post('promo_id', 0);
        $response = ['message' => 'Promotion not deleted', 'messageType' => 'alert-danger'];
        if ($promo_id){
            $promo = Promotions::find()->where('promo_id =:promo_id',[':promo_id' => $promo_id])->one();
            if ($promo){
                $promo->delete();
                $response = ['message' => 'Promotion deleted', 'messageType' => 'alert-success'];
            }
        }
        echo json_encode($response);
        exit();
    }
    
    public function actionSort(){
        $promo_order = Yii::$app->request->post('promo_order', []);
        if (is_array($promo_order) && count($promo_order)){
            array_unique($promo_order);
            foreach($promo_order as $sort => $id){
                $promo = Promotions::findOne($id);
                if ($promo){
                    $promo->setAttribute('promo_priority', $sort);
                    $promo->save(false);
                }
            }
        }
    }
    
    public function actionSwitchStatus(){
        $promo_id = Yii::$app->request->post('id', 0);
        $status = Yii::$app->request->post('status', 'false');
        if ($status == 'false'){
            $status = 0;
        } else {
            $status = 1;
        }
        if ($promo_id){
            $promo = Promotions::findOne($promo_id);
            if ($promo){
                $promo->setAttribute('promo_status', $status);
                $promo->save(false);
            }
        }
        echo 'ok';
        exit();
    }
    
    public function actionSaveSettings(){
        $to = Yii::$app->request->post('to', '');
        if ($to){
            $current = tep_db_fetch_array(tep_db_query("select configuration_id, configuration_key from " . TABLE_CONFIGURATION . " where configuration_key like 'PROMOTION_APPLY_" . strtoupper($to) . "%' "));
            if ($current){
                tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = 'false' where configuration_key like 'PROMOTION_APPLY_TO_%_PRICE'");
                tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = 'true' where configuration_id = '" . $current['configuration_id'] . "'");
            }
        }
        echo 'ok';
        exit();
    }
    
    public function actionSaveIconSettings(){
        $use = Yii::$app->request->post('instead', '0');
        $use = ($use == '1'? 'true': 'false');
        \common\models\Configuration::updateByKey('PROMOTION_ICON_INSTEAD_SALE', $use);
        echo 'ok';
        exit();
    }
    
    public function actionSavePropertySettings(){
        $use = Yii::$app->request->post('instead', '0');
        $use = ($use == '1'? 'true': 'false');
        \common\models\Configuration::updateByKey('PROPERTIES_IN_PROMOTIONS', $use);
        echo 'ok';
        exit();
    }
    
    public function actionProgramStatus(){
        $use = Yii::$app->request->post('status', 'true');
        if ($use != 'true'){
            $use = 'false';
        }
        \common\models\Configuration::updateByKey('BONUS_ACTION_PROGRAM_STATUS', $use);
        echo 'ok';
        exit();
    }
    
    public function actionGeneratePromoCode(){
        $code = PromotionService::generatePromoCode();
        echo json_encode(['code' => $code]);
        exit;
    }
    
    public function actionActions() {
        $this->acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_PROMOTIONS', 'BOX_PROMOTIONS_ACTIONS'];
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('promotions/actions'), 'title' => HEADING_TITLE_ACTIONS);
        $this->selectedMenu = array('marketing', 'promotions-box', 'promotions/actions');
        
        $bonusesService = new PromotionsBonusService();
        $groups = $bonusesService->getAllGroups();
        
        $languages = \common\helpers\Language::get_languages();
        
        if (Yii::$app->request->isPost){
            $points_title = Yii::$app->request->post('points_title', []);
            $group_title  = Yii::$app->request->post('group_title', []);
            if (is_array($groups)){
                foreach($groups as $group_code => $group){
                    $bGroup = PromotionsBonusGroups::create($group_code);
                    if ($bGroup->validate()){
                        if ($bGroup->save()){
                            if (is_array($languages)){
                                foreach($languages as $language){
                                    $bGroupDesc = PromotionsBonusGroupsDescription::create($bGroup, $language['id']);
                                    if ($bGroupDesc){
                                        $bGroupDesc->setAttribute('bonus_group_title', $group_title[$language['id']][$group_code]);
                                        if ($bGroupDesc->validate()){
                                            $bGroupDesc->save();
                                        }
                                    }
                                }
                            }
                            if (!$bGroup->hasErrors() && is_array($group['items'])){
                                foreach ($group['items'] as $code => $item){
                                    if (is_object($item)){
                                        $item->bonus_groups_id = $bGroup->bonus_groups_id;
                                        if ($item->load(Yii::$app->request->post()) && $item->validate()){
                                            if ($item->save()){
                                                if (is_array($languages)){
                                                    foreach($languages as $language){
                                                        $itemDescription = PromotionsBonusPointsDescription::create($item, $language['id']);
                                                        if ($itemDescription){
                                                            $itemDescription->setAttribute('points_title', $points_title[$language['id']][$code]);
                                                            if ($itemDescription->validate()){
                                                                $itemDescription->save();
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            Yii::$app->session->setFlash('success', TEXT_MESSEAGE_SUCCESS, FALSE);
            $this->redirect(\yii\helpers\Url::to('actions'));
        }
        
        $messages = Yii::$app->session->getFlash('success');
        $languages_id = \Yii::$app->settings->get('languages_id');
        return $this->render('actions',[
            'groups' => $groups,
            'languages' => $languages,
            'default_language' => $languages_id,
            'messages' => $messages
        ]);
    }

}


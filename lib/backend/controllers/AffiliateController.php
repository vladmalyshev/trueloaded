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

class AffiliateController extends Sceleton {

    public $acl = ['BOX_AFFILIATE_AFFILIATE'];

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('admin/affiliate');
        \common\helpers\Translation::init('affiliate');
        parent::__construct($id, $module);
    }

    public function actionIndex() {
        $this->acl[] = 'BOX_AFFILIATES';
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('affiliate/'), 'title' => BOX_AFFILIATES);
        $this->view->headingTitle = BOX_AFFILIATES;
        
        $this->view->catalogTable = array(
            array(
                'title' => 'Affiliate ID',
                'not_important' => 0
            ),
            array(
                'title' => 'Last Name',
                'not_important' => 0
            ),
            array(
                'title' => 'First Name',
                'not_important' => 0
            ),
            array(
                'title' => 'Commission',
                'not_important' => 0
            ),
            array(
                'title' => 'Homepage',
                'not_important' => 0
            ),
        );
        $this->view->row_id = (int) Yii::$app->request->get('row');
        return $this->render('index');
    }
    
    public function actionList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        if( $length == -1 ) $length = 10000;
        
        $query_numrows = 0;
        $responseList = [];
        
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " where (affiliate_lastname like '%" . $keywords . "%' or affiliate_firstname like '%" . $keywords . "%' or affiliate_homepage like '%" . $keywords . "%') ";
        } else {
            $search_condition = " where 1 ";
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "affiliate_id " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "affiliate_lastname " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 2:
                    $orderBy = "affiliate_firstname " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "affiliate_id desc";
                    break;
            }
        } else {
            $orderBy = "coupon_id desc";
        }

        $cc_query_raw = "select * from affiliate_affiliate $search_condition order by $orderBy ";

        $current_page_number = ( $start / $length ) + 1;
        $_split = new \splitPageResults($current_page_number, $length, $cc_query_raw, $query_numrows, 'affiliate_id');
        $cc_query = tep_db_query($cc_query_raw);
        while ($cc_list = tep_db_fetch_array($cc_query)) {
            $before = '';
            $after = '';
            if ($before. $cc_list['platform_id'] == 0) {
                $before = '<b>';
                $after = '</b>';
            }
            $responseList[] = [
                $before. $cc_list['affiliate_id'] . $after . '<input class="cell_identify" type="hidden" value="' . $cc_list['affiliate_id'] . '">',
                $before. $cc_list['affiliate_lastname'] . $after,
                $before. $cc_list['affiliate_firstname'] . $after,
                $before. $cc_list['affiliate_commission_percent'] . '%' . $after,
                $before. $cc_list['affiliate_homepage'] . $after,
            ];
        }
        
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }

    public function actionActions() {
        $item_id = (int) Yii::$app->request->post('item_id');
        $cc_query = tep_db_query("select * from affiliate_affiliate where affiliate_id = '" . (int) $item_id . "'");
        $cc_list = tep_db_fetch_array($cc_query);
        if (!is_array($cc_list)) {
            die();
        }
        $this->layout = false;
        return $this->render('actions', $cc_list);
    }
    
    public function actionActivate() {
        $item_id = (int) Yii::$app->request->post('item_id');
        $cc_query = tep_db_query("select * from affiliate_affiliate where affiliate_id = '" . $item_id . "'");
        $cc_list = tep_db_fetch_array($cc_query);
        if (!is_array($cc_list)) {
            die();
        }
        $this->layout = false;
        if ($cc_list['platform_id'] == 0) {
            $defaultPlatform = \common\models\Platforms::findOne(\common\classes\platform::defaultId());
            if ($defaultPlatform) {
                //create virtual platform
                $platform = new \common\models\Platforms();
                $platform->platform_owner = $cc_list['affiliate_firstname'] . ' ' . $cc_list['affiliate_lastname'];
                $platform->platform_name = $cc_list['affiliate_homepage'];
                $platform->date_added = date('Y-m-d H:i:s');
                $platform->is_virtual = 1;
                $platform->is_default_address = 1;
                $platform->is_default_contact = 1;
                $platform->status = 1;
                $platform->defined_languages = $defaultPlatform->defined_languages;
                $platform->defined_currencies = $defaultPlatform->defined_currencies;
                $platform->platform_code = \common\helpers\Password::create_random_value(6);
                $platform->sattelite_platform_id = $defaultPlatform->platform_id;
                $platform->save();
                
                tep_db_query ("update affiliate_affiliate set platform_id = '" . $platform->platform_id . "' where affiliate_id = '" . $item_id . "' ");
           
                $parameterArray = array();
                $parameterArray['STORE_NAME'] = STORE_NAME;
                $parameterArray['CUSTOMER_FIRSTNAME'] = $cc_list['affiliate_firstname'];
                $parameterArray['AFFILIATE_LINK'] = \Yii::$app->urlManager->createAbsoluteUrl(["affiliate/"]);
                list($emailSubject, $emailMessage) = \common\helpers\Mail::get_parsed_email_template('Affiliate Activation', $parameterArray);
                \common\helpers\Mail::send($cc_list['affiliate_firstname'] . ' ' . $cc_list['affiliate_lastname'], $cc_list['affiliate_email_address'], $emailSubject, $emailMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $parameterArray);
          
            }
        }
    }
    
    public function actionConfirmItemDelete() {
        $this->layout = false;
        $affiliate_id = (int) Yii::$app->request->post('item_id');
        $model = \common\models\AffiliateAffiliate::findOne($affiliate_id);
        if (is_object($model)) {
            echo tep_draw_form('item_delete', 'affiliate', \common\helpers\Output::get_all_get_params(array('action')), 'post', 'id="item_delete" onSubmit="return deleteItem();"');
            echo '<div class="or_box_head">' . TEXT_HEADING_DELETE_AFFILIATE . '</div>';
            echo TEXT_DELETE_AFFILIATE_INTRO . '<br><br><b>' . $model->affiliate_firstname . ' ' . $model->affiliate_lastname . '</b><br>' . $model->affiliate_homepage;
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement()">' . IMAGE_CANCEL . '</button>';
            echo tep_draw_hidden_field('affiliate_id', $model->affiliate_id);
            echo '</div></form>';
        }
    }

    public function actionItemDelete() {
        $affiliate_id = (int) Yii::$app->request->post('affiliate_id');
        if ($affiliate_id > 0) {
            $model = \common\models\AffiliateAffiliate::findOne($affiliate_id);
            if (is_object($model)) {
                // platform_id
                if ($model->platform_id > 0) {
                    $item_id = $model->platform_id;
                    //--- COPIED FROM PLATFORM DELETE
                    $check_is_default = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS c FROM " . TABLE_PLATFORMS . " WHERE is_default=1 AND  platform_id = '" . (int) $item_id . "'"));
                    if ($check_is_default['c']) {
                        // do nothing
                    } else {

                        tep_db_query("delete from " . TABLE_PLATFORMS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_ADDRESS_BOOK . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_OPEN_HOURS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_TO_THEMES . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_CATEGORIES . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_PRODUCTS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_INFORMATION . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_BANNERS_TO_PLATFORM . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_BANNERS_LANGUAGES . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_CONFIGURATION . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_CUT_OFF_TIMES . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORM_FORMATS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_HOLIDAYS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_PLATFORMS_WATERMARK . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from " . TABLE_META_TAGS . " where platform_id = '" . (int) $item_id . "'");
                        tep_db_query("delete from platforms_price_settings where platform_id = '" . (int) $item_id . "'");
                        PlatformsSettings::updateAll(['use_owner_prices' => \common\classes\platform::defaultId()], ['use_owner_prices' => $item_id]);
                        PlatformsSettings::updateAll(['use_owner_descriptions' => \common\classes\platform::defaultId()], ['use_owner_descriptions' => $item_id]);
                        \common\classes\Images::cacheKeyInvalidateByPlatformId((int) $item_id);
                        //\common\models\EmailTemplatesToDesignTemplate::findAll(['platform_id' => (int)$item_id])->delete();
                        \common\models\EmailTemplatesToDesignTemplate::deleteAll(['platform_id' => (int) $item_id]);
                    }
                    //---
                }
                $model->delete();
            }
        }
        echo 'reset';
    }
    
    public function actionEdit() {
        $this->acl[] = 'BOX_AFFILIATES';
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('affiliate/'), 'title' => TEXT_AFFILIATE_EDIT);
        $this->view->headingTitle = TEXT_AFFILIATE_EDIT;
        
        $affiliate_id = (int) Yii::$app->request->get('affiliate_id');
        
        if (Yii::$app->request->isPost) {
            $message_stack = Yii::$container->get('message_stack');
            $affiliate_id = (int) Yii::$app->request->post('affiliate_id');
            $model = \common\models\AffiliateAffiliate::findOne($affiliate_id);
            if (is_object($model)) {
                $model->setAttributes(Yii::$app->request->post(), false);
                $model->save();
                $message_stack->add_session(TEXT_AFFILIATE_SUCCESS, 'header', 'success');
            } else {
                $message_stack->add_session(TEXT_AFFILIATE_ERROR, 'header', 'error');
            }
            return $this->redirect(['edit', 'affiliate_id' => $affiliate_id]);
        }
        
        $model = \common\models\AffiliateAffiliate::findOne($affiliate_id);
  
        if ($model->platform_id > 0) {
            $plid =  \common\models\Platforms::findOne($model->platform_id);
            if($plid->sattelite_platform_id > 0) {
                $platform = \common\models\Platforms::findOne($plid->sattelite_platform_id);
                if (is_object($platform)) {
                    $platformUrl = ($platform->ssl_enabled ? 'https://' : 'http://') .  $platform->platform_url .'/?code=' . $plid->platform_code;
                    $platformUrl = '<a target="_blank" href="'.$platformUrl.'">'.$platformUrl.'</a>';
                }
            }
        }
        
        return $this->render('edit', [
            'model' => $model,
            'platformUrl' => $platformUrl,
        ]);
    }
    
    public function actionPayment() {
        $this->acl[] = 'BOX_AFFILIATE_PAYMENT';
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('affiliate/payment'), 'title' => BOX_AFFILIATE_PAYMENT);
        $this->topButtons[] = '<a href="' . \Yii::$app->urlManager->createUrl(['affiliate/generate-payment']) . '" class="create_item">' . TEXT_GENERATE_PAYMENTS . '</a>';
        $this->view->headingTitle = BOX_AFFILIATE_PAYMENT;
        
        $this->view->catalogTable = array(
            array(
                'title' => 'Affiliate',
                'not_important' => 0
            ),
            array(
                'title' => 'Payment (excl.)',
                'not_important' => 0
            ),
            array(
                'title' => 'Affiliate Earnings',
                'not_important' => 0
            ),
            array(
                'title' => 'Date Billed',
                'not_important' => 0
            ),
            array(
                'title' => 'Payment Status',
                'not_important' => 0
            ),
        );
        $this->view->row_id = (int) Yii::$app->request->get('row');
        return $this->render('payment');
    }
    
    public function actionPaymentList() {
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        if( $length == -1 ) $length = 10000;
        
        $query_numrows = 0;
        $responseList = [];
        
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " where (affiliate_lastname like '%" . $keywords . "%' or affiliate_firstname like '%" . $keywords . "%' or affiliate_homepage like '%" . $keywords . "%') ";
        } else {
            $search_condition = " where 1 ";
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "affiliate_id " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 1:
                    $orderBy = "affiliate_lastname " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 2:
                    $orderBy = "affiliate_firstname " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                case 3:
                    $orderBy = "affiliate_payment_date " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "affiliate_id DESC";
                    break;
            }
        } else {
            $orderBy = "coupon_id desc";
        }
        
        $payment_status_name = array_column(\common\models\AffiliatePaymentStatus::find()->select(['affiliate_payment_status_id', 'affiliate_payment_status_name'])->where(['affiliate_language_id' => \Yii::$app->settings->get('languages_id')])->asArray()->all(), 'affiliate_payment_status_name', 'affiliate_payment_status_id');

        $cc_query_raw = "select * from affiliate_payment $search_condition order by $orderBy ";

        $current_page_number = ( $start / $length ) + 1;
        $_split = new \splitPageResults($current_page_number, $length, $cc_query_raw, $query_numrows, 'affiliate_id');
        $cc_query = tep_db_query($cc_query_raw);
        while ($cc_list = tep_db_fetch_array($cc_query)) {
            $before = '';
            $after = '';
            if ($before. $cc_list['affiliate_payment_status'] == 0) {
                $before = '<b>';
                $after = '</b>';
            }
            $responseList[] = [
                $before. $cc_list['affiliate_firstname'] . ' ' . $cc_list['affiliate_lastname'] . $after . '<input class="cell_identify" type="hidden" value="' . $cc_list['affiliate_payment_id'] . '">',
                $before. $cc_list['affiliate_payment_total'] . $after,
                $before. $cc_list['affiliate_payment_total'] . $after,
                $before. $cc_list['affiliate_payment_date'] . $after,
                $before. $payment_status_name[$cc_list['affiliate_payment_status']] . $after,
            ];
        }
        
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }
    
    public function actionPaymentActions() {
        $item_id = (int) Yii::$app->request->post('item_id');
        $cc_query = tep_db_query("select * from affiliate_payment where affiliate_payment_id = '" . (int) $item_id . "'");
        $cc_list = tep_db_fetch_array($cc_query);
        if (!is_array($cc_list)) {
            die();
        }
        $this->layout = false;
        return $this->render('payment-actions', $cc_list);
    }
    
    public function actionConfirmPaymentDelete() {
        $this->layout = false;
        $affiliate_payment_id = (int) Yii::$app->request->post('item_id');
        $model = \common\models\AffiliatePayment::findOne($affiliate_payment_id);
        if (is_object($model)) {
            echo tep_draw_form('item_delete', 'affiliate', \common\helpers\Output::get_all_get_params(array('action')), 'post', 'id="item_delete" onSubmit="return deleteItem();"');
            echo '<div class="or_box_head">' . TEXT_HEADING_DELETE_AFFILIATE_PAYMENT . '</div>';
            echo TEXT_DELETE_AFFILIATE_PAYMENT_INTRO . '<br><br><b>' . $model->affiliate_firstname . ' ' . $model->affiliate_lastname . '</b>';
            echo '<div class="btn-toolbar btn-toolbar-order">';
            echo '<button type="submit" class="btn btn-primary btn-no-margin">' . IMAGE_CONFIRM . '</button>';
            echo '<button class="btn btn-cancel" onClick="return resetStatement()">' . IMAGE_CANCEL . '</button>';
            echo tep_draw_hidden_field('affiliate_payment_id', $model->affiliate_payment_id);
            echo '</div></form>';
        }
    }
    
    public function actionPaymentDelete() {
        $affiliate_payment_id = (int) Yii::$app->request->post('affiliate_payment_id');
        if ($affiliate_payment_id > 0) {
            $model = \common\models\AffiliatePayment::findOne($affiliate_payment_id);
            if (is_object($model)) {
                $model->delete();
            }
        }
        echo 'reset';
    }
    
    public function actionPaymentEdit() {
        $this->acl[] = 'BOX_AFFILIATE_PAYMENT';
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('affiliate/payment'), 'title' => TEXT_AFFILIATE_PAYMENT_EDIT);
        $this->view->headingTitle = TEXT_AFFILIATE_PAYMENT_EDIT;
        
        $affiliate_payment_id = (int) Yii::$app->request->get('affiliate_payment_id');
        
        if (Yii::$app->request->isPost) {
            $message_stack = Yii::$container->get('message_stack');
            $status = (int) Yii::$app->request->post('status');
            $notify = (int) Yii::$app->request->post('notify');
            
            $model = \common\models\AffiliatePayment::findOne($affiliate_payment_id);
            if ($model) {
                if ($model->affiliate_payment_status != $status) {
                    
                    $StatusHistory = new \common\models\AffiliatePaymentStatusHistory();
                    $StatusHistory->affiliate_payment_id = $model->affiliate_payment_id;
                    $StatusHistory->affiliate_new_value = $status;
                    $StatusHistory->affiliate_old_value = $model->affiliate_payment_status;
                    $StatusHistory->affiliate_date_added = date('Y-m-d H:i:s');
                    
                    $model->affiliate_payment_status = $status;
                    $model->affiliate_last_modified = date('Y-m-d H:i:s');
                    $model->save();
                    
                    $affiliate_notified = 0;
                    if ($notify) {
                        $payment_status_name = array_column(\common\models\AffiliatePaymentStatus::find()->select(['affiliate_payment_status_id', 'affiliate_payment_status_name'])->where(['affiliate_language_id' => \Yii::$app->settings->get('languages_id'), 'affiliate_payment_status_id' => $status])->asArray()->all(), 'affiliate_payment_status_name', 'affiliate_payment_status_id');
                        
                        $affiliate = \common\models\AffiliateAffiliate::findOne($model->affiliate_id);
                        $parameterArray = array();
                        $parameterArray['STORE_NAME'] = STORE_NAME;
                        $parameterArray['CUSTOMER_FIRSTNAME'] = $affiliate->affiliate_firstname;
                        $parameterArray['PAYMENT_ID'] = $affiliate_payment_id;
                        $parameterArray['PAYMENT_DATE'] = \common\helpers\Date::datetime_short($model->affiliate_last_modified);
                        $parameterArray['PAYMENT_STATUS'] = (isset($payment_status_name[$status]) ? $payment_status_name[$status] : '--');
                        list($emailSubject, $emailMessage) = \common\helpers\Mail::get_parsed_email_template('Affiliate Payment Notify', $parameterArray);
                        \common\helpers\Mail::send($affiliate->affiliate_firstname . ' ' . $affiliate->affiliate_lastname, $affiliate->affiliate_email_address, $emailSubject, $emailMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $parameterArray);
        
                        $affiliate_notified = 1;
                    }
                    
                    $StatusHistory->affiliate_notified = $affiliate_notified;
                    $StatusHistory->save();
                    
                    $message_stack->add_session(TEXT_AFFILIATE_PAYMENT_SUCCESS, 'header', 'success');
                }
            }
            
            
            
            
            return $this->redirect(['payment-edit', 'affiliate_payment_id' => $affiliate_payment_id]);
        }
        
        $model = \common\models\AffiliatePayment::findOne($affiliate_payment_id);
  
        $address = [
            'firstname' => $model->affiliate_firstname,
            'lastname' => $model->affiliate_lastname,
            'street_address' => $model->affiliate_street_address,
            'suburb' => $model->affiliate_suburb,
            'city' => $model->affiliate_city,
            'postcode' => $model->affiliate_postcode,
            'state' => $model->affiliate_state,
            'zone_id' => $model->affiliate_firstname,
            'country' => $model->affiliate_country,
            'company'  => $model->affiliate_company,
        ];
        $affiliateLink = \common\helpers\Address::address_format($model->affiliate_address_format_id, $address, 1, '', '<br>');
        
        switch ($model->affiliate_payment_currency) {
            case 'USD':
                $prefixClass = 'global-currency-usd';
                break;
            case 'GBP':
                $prefixClass = 'global-currency-gbp';
                break;
            case 'EUR':
                $prefixClass = 'global-currency-eur';
                break;
            default:
                $prefixClass = '';
                break;
        }
        
        $currencies = Yii::$container->get('currencies');
        $model->affiliate_payment_total = $currencies->format($model->affiliate_payment_total, false, $model->affiliate_payment_currency);
        
        $model->affiliate_payment_date = \common\helpers\Date::datetime_short($model->affiliate_payment_date);
        
        $affiliate = \common\models\AffiliateAffiliate::findOne($model->affiliate_id);
                
        $ordersStatuses = array_column(\common\models\AffiliatePaymentStatus::find()->select(['affiliate_payment_status_id', 'affiliate_payment_status_name'])->where(['affiliate_language_id' => \Yii::$app->settings->get('languages_id')])->asArray()->all(), 'affiliate_payment_status_name', 'affiliate_payment_status_id');
        
        return $this->render('payment-edit', [
            'model' => $model,
            'affiliate' => $affiliate,
            'affiliateLink' => $affiliateLink,
            'ordersStatuses' => $ordersStatuses,
        ]);
    }
    
    public function actionPaymentStatusHistory() {
        
        $affiliate_payment_id = (int) Yii::$app->request->get('affiliate_payment_id');
        $draw = (int) Yii::$app->request->get('draw', 1);
        
        $payment_status_name = array_column(\common\models\AffiliatePaymentStatus::find()->select(['affiliate_payment_status_id', 'affiliate_payment_status_name'])->where(['affiliate_language_id' => \Yii::$app->settings->get('languages_id')])->asArray()->all(), 'affiliate_payment_status_name', 'affiliate_payment_status_id');
        
        $query_numrows = 0;
        $responseList = [];
        
        foreach (\common\models\AffiliatePaymentStatusHistory::find()->where(['affiliate_payment_id' => $affiliate_payment_id])->asArray()->all() as $record) {
            $query_numrows++;
            $responseList[] = [
                $payment_status_name[$record['affiliate_new_value']],
                $payment_status_name[$record['affiliate_old_value']],
                \common\helpers\Date::datetime_short($record['affiliate_date_added']),
                ($record['affiliate_notified'] ? 'Yes' : 'No'),
            ];
        }
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }
    
    public function actionGeneratePayment() {

        define ('SUCCESS_BILLING','Generated successfully');
        define ('AFFILIATE_NOTIFY_AFTER_BILLING','true'); // Nofify affiliate if he got a new invoice
        define ('AFFILIATE_TAX_ID','1');  // Tax Rates used for billing the affiliates 
        
        set_time_limit(0);
        // We are only billing orders which are AFFILIATE_BILLING_TIME days old
        $time = mktime(0, 0, 0, date("m"), date("d") - AFFILIATE_BILLING_TIME + 1, date("Y"));
        $oldday = date("Y-m-d", $time);

        // Select all affiliates who earned enough money since last payment
        $sql = "
        SELECT a.affiliate_id, sum(a.affiliate_payment) 
          FROM affiliate_sales a, " . TABLE_ORDERS . " o 
          WHERE a.affiliate_billing_status != 1 and a.affiliate_orders_id = o.orders_id and o.orders_status in (" . AFFILIATE_PAYMENT_ORDER_MIN_STATUS . ") and a.affiliate_date <= '" . $oldday . "' 
          GROUP by a.affiliate_id 
          having sum(a.affiliate_payment) >= '" . AFFILIATE_THRESHOLD . "'
        ";
        $affiliate_payment_query = tep_db_query($sql);

        // Start Billing:
        while ($affiliate_payment = tep_db_fetch_array($affiliate_payment_query)) {

            // Get all orders which are AFFILIATE_BILLING_TIME days old
            $sql = "SELECT a.affiliate_orders_id 
              FROM affiliate_sales a, " . TABLE_ORDERS . " o 
              WHERE a.affiliate_billing_status!=1 and a.affiliate_orders_id=o.orders_id and o.orders_status in (" . AFFILIATE_PAYMENT_ORDER_MIN_STATUS . ") and a.affiliate_id='" . $affiliate_payment['affiliate_id'] . "' and a.affiliate_date <= '" . $oldday . "'
            ";
            $affiliate_orders_query = tep_db_query($sql);
            $orders_id = "(";
            while ($affiliate_orders = tep_db_fetch_array($affiliate_orders_query)) {
                $orders_id .= $affiliate_orders['affiliate_orders_id'] . ",";
            }
            $orders_id = substr($orders_id, 0, -1) . ")";

            // Set the Sales to Temp State (it may happen that an order happend while billing)
            $sql = "UPDATE affiliate_sales 
            set affiliate_billing_status=99 
              where affiliate_id='" . $affiliate_payment['affiliate_id'] . "' 
              and affiliate_orders_id in " . $orders_id . " 
            ";
            tep_db_query($sql);

            // Get Sum of payment (Could have changed since last selects);
            $sql = "SELECT sum(affiliate_payment) as affiliate_payment
              FROM affiliate_sales 
              WHERE affiliate_id='" . $affiliate_payment['affiliate_id'] . "' and  affiliate_billing_status=99 
            ";
            $affiliate_billing_query = tep_db_query($sql);
            $affiliate_billing = tep_db_fetch_array($affiliate_billing_query);
            // Get affiliate Informations
            $sql = "SELECT a.*, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id 
              from affiliate_affiliate a 
              left join " . TABLE_ZONES . " z on (a.affiliate_zone_id  = z.zone_id) 
              left join " . TABLE_COUNTRIES . " c on (a.affiliate_country_id = c.countries_id)
              WHERE affiliate_id = '" . $affiliate_payment['affiliate_id'] . "' and c.language_id = '" . (int) \Yii::$app->settings->get('languages_id') . "' 
            ";
            $affiliate_query = tep_db_query($sql);
            $affiliate = tep_db_fetch_array($affiliate_query);
            
            // Get need tax informations for the affiliate
            $affiliate_tax_rate = \common\helpers\Tax::get_tax_rate(AFFILIATE_TAX_ID, $affiliate['affiliate_country_id'], $affiliate['affiliate_zone_id']);
            $affiliate_tax = round(($affiliate_billing['affiliate_payment'] * $affiliate_tax_rate / 100), 2); // Netto-Provision
            $affiliate_payment_total = $affiliate_billing['affiliate_payment'] + $affiliate_tax;
            // Bill the order
            $affiliate['affiliate_state'] = \common\helpers\Zones::get_zone_code($affiliate['affiliate_country_id'], $affiliate['affiliate_zone_id'], $affiliate['affiliate_state']);
            $sql_data_array = array(
                'affiliate_id' => $affiliate_payment['affiliate_id'],
                'affiliate_payment' => $affiliate_billing['affiliate_payment'],
                'affiliate_payment_tax' => $affiliate_tax,
                'affiliate_payment_total' => $affiliate_payment_total,
                'affiliate_payment_date' => 'now()',
                'affiliate_payment_status' => '0',
                'affiliate_firstname' => $affiliate['affiliate_firstname'],
                'affiliate_lastname' => $affiliate['affiliate_lastname'],
                'affiliate_street_address' => $affiliate['affiliate_street_address'],
                'affiliate_suburb' => $affiliate['affiliate_suburb'],
                'affiliate_city' => $affiliate['affiliate_city'],
                'affiliate_country' => $affiliate['countries_name'],
                'affiliate_postcode' => $affiliate['affiliate_postcode'],
                'affiliate_company' => $affiliate['affiliate_company'],
                'affiliate_state' => $affiliate['affiliate_state'],
                'affiliate_payment_currency' => \Yii::$app->settings->get('currency'),
                'affiliate_address_format_id' => $affiliate['address_format_id']);

            tep_db_perform('affiliate_payment', $sql_data_array);
            $insert_id = tep_db_insert_id(); 
/*          TODO
            $AffiliatePayment = new \common\models\AffiliatePayment();
            $AffiliatePayment->setAttributes($sql_data_array, false);
            $AffiliatePayment->save();
            $insert_id = $AffiliatePayment->affiliate_payment_id;
*/
            // Set the Sales to Final State 
            tep_db_query("update affiliate_sales set affiliate_payment_id = '" . $insert_id . "', affiliate_billing_status = 1, affiliate_payment_date = now() where affiliate_id = '" . $affiliate_payment['affiliate_id'] . "' and affiliate_billing_status = 99");

            // Notify Affiliate
            /* if (AFFILIATE_NOTIFY_AFTER_BILLING == 'true') {
              $check_status_query = tep_db_query("select af.affiliate_email_address, ap.affiliate_lastname, ap.affiliate_firstname, ap.affiliate_payment_status, ap.affiliate_payment_date, ap.affiliate_payment_date from affiliate_payment ap, affiliate_affiliate af where affiliate_payment_id  = '" . $insert_id . "' and af.affiliate_id = ap.affiliate_id ");
              $check_status = tep_db_fetch_array($check_status_query);
              $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_AFFILIATE_PAYMENT_NUMBER . ' ' . $insert_id . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_AFFILIATE_INVOICE, 'pID=' . $insert_id, 'SSL') . "\n" . EMAIL_TEXT_PAYMENT_BILLED . ' ' . tep_date_long($check_status['affiliate_payment_date']) . "\n\n" . EMAIL_TEXT_NEW_PAYMENT;
              tep_mail($check_status['affiliate_firstname'] . ' ' . $check_status['affiliate_lastname'], $check_status['affiliate_email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, AFFILIATE_EMAIL_ADDRESS);
              } */
        }

        $message_stack = Yii::$container->get('message_stack');
        $message_stack->add_session(SUCCESS_BILLING, 'success');

        $this->redirect(Yii::$app->urlManager->createAbsoluteUrl('affiliate/payment'));
    }

}

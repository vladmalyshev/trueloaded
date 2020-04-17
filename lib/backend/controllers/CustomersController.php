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

use common\helpers\Acl;
use common\helpers\Html;
use common\models\Customers;
use common\services\BonusPointsService\DTO\TransferData;
use Yii;
use common\models\repositories\CustomersRepository;
use common\components\Customer;
use frontend\forms\registration\CustomerRegistration;
use common\forms\AddressForm;

/**
 * default controller to handle user requests.
 */
class CustomersController extends Sceleton {

    public $acl = ['BOX_HEADING_CUSTOMERS', 'BOX_CUSTOMERS_CUSTOMERS'];

    /**
     * Index action is the default action in a controller.
     */
    public function actionIndex() {
        $this->selectedMenu = array('customers', 'customers');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('customers/index'), 'title' => HEADING_TITLE);
        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['editor/order-edit', 'back' => 'customers']) . '" class="create_item"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_OREDER . '</a><a href="' . Yii::$app->urlManager->createUrl('customers/customeredit') . '" class="create_item add_new_cus_item"><i class="icon-user-plus"></i>' . TEXT_ADD_NEW_CUSTOMER . '</a>';
        if (defined('ACCOUNT_GDPR') && ACCOUNT_GDPR == 'true') {
        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['customers/gdpr-check']) . '" onclick="return confirm(\'' . GDPR_CHECK_NOTICE . '\');" class="create_item">' . TEXT_GDPR_CHECK . '</a>';
        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['customers/gdpr-cleanup']) . '" onclick="return confirm(\'' . GDPR_CLEANUP_NOTICE . '\');" class="create_item">' . TEXT_GDPR_CLEANUP . '</a>';
        }
        $this->view->headingTitle = HEADING_TITLE;
        $this->view->customersTable = array(
            array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
            ),
            array(
                'title' => ENTRY_LAST_NAME,
                'not_important' => 0
            ),
            array(
                'title' => ENTRY_FIRST_NAME,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_EMAIL . '/' . ( defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True ? TABLE_HEADING_DEPARTMENT : TABLE_HEADING_PLATFORM),
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_ACCOUNT_CREATED,
                'not_important' => 1
            ),
            array(
                'title' => TABLE_HEADING_LOCATION,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_ORDER_COUNT,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_TOTAL_ORDERED,
                'not_important' => 0
            ),
            array(
                'title' => TABLE_HEADING_DATE_LAST_ORDER,
                'not_important' => 0
            ),
                /* array(
                  'title' => TABLE_HEADING_ACTION,
                  'not_important' => 0
                  ), */
        );

        $this->view->filters = new \stdClass();

        $by = [
            [
                'name' => TEXT_ANY,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => ENTRY_FIRST_NAME,
                'value' => 'firstname',
                'selected' => '',
            ],
            [
                'name' => ENTRY_LAST_NAME,
                'value' => 'lastname',
                'selected' => '',
            ],
            [
                'name' => TEXT_EMAIL,
                'value' => 'email',
                'selected' => '',
            ],
            [
                'name' => ENTRY_COMPANY,
                'value' => 'companyname',
                'selected' => '',
            ],
            [
                'name' => ENTRY_TELEPHONE_NUMBER,
                'value' => 'phone',
                'selected' => '',
            ],
            [
                'name' => TEXT_ZIP_CODE,
                'value' => 'postcode',
                'selected' => '',
            ],
        ];
        foreach ($by as $key => $value) {
            if (isset($_GET['by']) && $value['value'] == $_GET['by']) {
                $by[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->by = $by;

        $search = '';
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $this->view->filters->search = $search;

        $this->view->filters->showGroup = (CUSTOMERS_GROUPS_ENABLE == 'True');
        $group = '';
        if (isset($_GET['group'])) {
            $group = $_GET['group'];
        }
        $this->view->filters->group = $group;

        $country = '';
        if (isset($_GET['country'])) {
            $country = $_GET['country'];
        }
        $this->view->filters->country = $country;

        $state = '';
        if (ACCOUNT_STATE == 'required' || ACCOUNT_STATE == 'visible') {
            $this->view->showState = true;
        } else {
            $this->view->showState = false;
        }
        if (isset($_GET['state'])) {
            $state = $_GET['state'];
        }
        $this->view->filters->state = $state;

        $city = '';
        if (isset($_GET['city'])) {
            $city = $_GET['city'];
        }
        $this->view->filters->city = $city;

        $company = '';
        if (isset($_GET['company'])) {
            $company = $_GET['company'];
        }
        $this->view->filters->company = $company;

        $guest = [
            [
                'name' => TEXT_ALL_CUSTOMERS,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_BTN_YES,
                'value' => 'y',
                'selected' => '',
            ],
            [
                'name' => TEXT_BTN_NO,
                'value' => 'n',
                'selected' => '',
            ],
        ];
        foreach ($guest as $key => $value) {
            if (isset($_GET['guest']) && $value['value'] == $_GET['guest']) {
                $guest[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->guest = $guest;

        $newsletter = [
            [
                'name' => TEXT_ANY,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_SUBSCRIBED,
                'value' => 's',
                'selected' => '',
            ],
            [
                'name' => TEXT_NOT_SUBSCRIBED,
                'value' => 'ns',
                'selected' => '',
            ],
        ];
        foreach ($newsletter as $key => $value) {
            if (isset($_GET['newsletter']) && $value['value'] == $_GET['newsletter']) {
                $newsletter[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->newsletter = $newsletter;

        $status = [
            [
                'name' => TEXT_ALL,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_ACTIVE,
                'value' => 'y',
                'selected' => '',
            ],
            [
                'name' => TEXT_NOT_ACTIVE,
                'value' => 'n',
                'selected' => '',
            ],
        ];
        foreach ($status as $key => $value) {
            if (isset($_GET['status']) && $value['value'] == $_GET['status']) {
                $status[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->status = $status;

        $title = [
            [
                'name' => TEXT_ALL,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => T_MR,
                'value' => 'm',
                'selected' => '',
            ],
            [
                'name' => T_MRS,
                'value' => 'f',
                'selected' => '',
            ],
            [
                'name' => T_MISS,
                'value' => 's',
                'selected' => '',
            ],
        ];
        foreach ($title as $key => $value) {
            if (isset($_GET['title']) && $value['value'] == $_GET['title']) {
                $title[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->title = $title;

        if (isset($_GET['date']) && $_GET['date'] == 'exact') {
            $this->view->filters->presel = false;
            $this->view->filters->exact = true;
        } else {
            $this->view->filters->presel = true;
            $this->view->filters->exact = false;
        }

        $interval = [
            [
                'name' => TEXT_ALL,
                'value' => '',
                'selected' => '',
            ],
            [
                'name' => TEXT_TODAY,
                'value' => '1',
                'selected' => '',
            ],
            [
                'name' => TEXT_WEEK,
                'value' => 'week',
                'selected' => '',
            ],
            [
                'name' => TEXT_THIS_MONTH,
                'value' => 'month',
                'selected' => '',
            ],
            [
                'name' => TEXT_THIS_YEAR,
                'value' => 'year',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_THREE_DAYS,
                'value' => '3',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_SEVEN_DAYS,
                'value' => '7',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_FOURTEEN_DAYS,
                'value' => '14',
                'selected' => '',
            ],
            [
                'name' => TEXT_LAST_THIRTY_DAYS,
                'value' => '30',
                'selected' => '',
            ],
        ];
        foreach ($interval as $key => $value) {
            if (isset($_GET['interval']) && $value['value'] == $_GET['interval']) {
                $interval[$key]['selected'] = 'selected';
            }
        }
        $this->view->filters->interval = $interval;

        $from = '';
        if (isset($_GET['from'])) {
            $from = $_GET['from'];
        }
        $this->view->filters->from = $from;

        $to = '';
        if (isset($_GET['to'])) {
            $to = $_GET['to'];
        }
        $this->view->filters->to = $to;

        $this->view->filters->platform = array();
        if (isset($_GET['platform']) && is_array($_GET['platform'])) {
            foreach ($_GET['platform'] as $_platform_id)
                if ((int) $_platform_id > 0)
                    $this->view->filters->platform[] = (int) $_platform_id;
        }

        $departments = false;
        if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True) {
            $this->view->filters->departments = [];
            if ( isset($_GET['departments']) && is_array($_GET['departments']) ){
                foreach( $_GET['departments'] as $_department_id ) if ( (int)$_department_id>0 ) $this->view->filters->departments[] = (int)$_department_id;
            }
            $departments = \common\classes\department::getList(false);
        }
        
        $this->view->filters->row = (int) $_GET['row'];

        return $this->render('index', [
                    'isMultiPlatform' => \common\classes\platform::isMulti(),
                    'platforms' => \common\classes\platform::getList(),
                    'departments' => $departments,
        ]);
    }

    public function actionCustomerlist() {
        global $login_id;
        $languages_id = \Yii::$app->settings->get('languages_id');

        $draw = Yii::$app->request->get('draw');
        $start = Yii::$app->request->get('start');
        $length = Yii::$app->request->get('length');

        if ($length == -1)
            $length = 10000;

        $currencies = Yii::$container->get('currencies');

        if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True) {
            $departments = [];
            $departmentsList = \common\classes\department::getList(false);
            foreach ($departmentsList as $department) {
                $departments[$department['departments_id']] = $department['departments_store_name'];
            }
        }


        $customersQuery = \common\models\Customers::find()
            ->alias('c')
            ->where('1');

        $_join_address_book = false;
        $_join_zones = false;
        $_join_customer_info = false;

        $search = '';
        if (isset($_GET['search']) && tep_not_null($_GET['search']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $customersQuery->andWhere("(c.customers_lastname like '%" . $keywords . "%' or c.customers_firstname like '%" . $keywords . "%' or c.customers_email_address like '%" . $keywords . "%')");
        }

        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);

        $filter = '';

        if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True) {
            $filter_by_departments = array();
            if ( isset($output['departments']) && is_array($output['departments']) ){
                foreach( $output['departments'] as $_department_id ) if ( (int)$_department_id>0 ) $filter_by_departments[] = (int)$_department_id;
            }

            if ( count($filter_by_departments)>0 ) {
                $customersQuery->andWhere("c.departments_id IN ('" . implode("', '",$filter_by_departments). "')");
            }
        }
        
        $filter_by_platform = array();
        if (isset($output['platform']) && is_array($output['platform'])) {
            foreach ($output['platform'] as $_platform_id)
                if ((int) $_platform_id > 0)
                    $filter_by_platform[] = (int) $_platform_id;
        } elseif (false === \common\helpers\Acl::rule(['BOX_HEADING_FRONENDS'])) {
            $platforms = \common\models\AdminPlatforms::find()->where(['admin_id' => $login_id])->asArray()->all();
            foreach ($platforms as $platform) {
                $filter_by_platform[] = $platform['platform_id'];
            }
            $filter_by_platform[] = 0;
        }

        if (count($filter_by_platform) > 0) {
            $customersQuery->andWhere("c.platform_id IN ('" . implode("', '", $filter_by_platform) . "')");
        }

        if (tep_not_null($output['search'])) {
            $search = tep_db_prepare_input($output['search']);
            switch ($output['by']) {
                case 'firstname':
                    $customersQuery->andWhere("c.customers_firstname like '%" . tep_db_input($search) . "%'");
                    break;
                case 'lastname':
                    $customersQuery->andWhere("c.customers_lastname like '%" . tep_db_input($search) . "%'");
                    break;
                case 'email': default:
                    $customersQuery->andWhere("c.customers_email_address like '%" . tep_db_input($search) . "%'");
                    break;
                case 'companyname':
                    $_join_address_book = true;
                    $customersQuery->andWhere(" a.entry_company like '%" . tep_db_input($search) . "%' ");
                    break;
                case 'phone':
                    $customersQuery->andWhere("c.customers_telephone like '%" . tep_db_input($search) . "%'");
                    break;
                case 'postcode':
                    $_join_address_book = true;
                    $customersQuery->andWhere("a.entry_postcode like '%" . tep_db_input($search) . "%' ");
                    break;
                case '':
                case 'any':
                    $_join_address_book = true;
                    $search_keywords = explode(" ", $search);
                    if (is_array($search_keywords) && count($search_keywords) > 1) {
                        foreach ($search_keywords as $key => $keyword) {
                            $customersQuery->andWhere(
                                    "(".
                                    " c.customers_firstname like '%" . tep_db_input($keyword) . "%' ".
                                    " or c.customers_lastname like '%" . tep_db_input($keyword) . "%' ".
                                    " or c.customers_email_address like '%" . tep_db_input($keyword) . "%' ".
                                    " or a.entry_company like '%" . tep_db_input($keyword) . "%' ".
                                    " or c.customers_telephone like '%" . tep_db_input($keyword) . "%' ".
                                    " or a.entry_postcode like '%" . tep_db_input($keyword) . "%' ".
                                    ") "
                            );
                        }
                    } else {
                        $customersQuery->andWhere(
                                " (".
                                " c.customers_firstname like '%" . tep_db_input($search) . "%' ".
                                " or c.customers_lastname like '%" . tep_db_input($search) . "%' ".
                                " or c.customers_email_address like '%" . tep_db_input($search) . "%' ".
                                " or a.entry_company like '%" . tep_db_input($search) . "%' ".
                                " or c.customers_telephone like '%" . tep_db_input($search) . "%' ".
                                " or a.entry_postcode like '%" . tep_db_input($search) . "%' ".
                                ") "
                        );
                    }
                    break;
            }
        }

        if (tep_not_null($output['group'])) {
            $_filter_group_ids = \yii\helpers\ArrayHelper::map(\common\models\Groups::find()
                ->select(['groups_id'])
                ->where(['LIKE','groups_name',$output['group']])
                ->asArray()
                ->all(),'groups_id','groups_id');

            $filterGroup = "(c.groups_id IN('".implode("','",$_filter_group_ids)."') ";
            /** @var \common\extensions\ExtraGroups\ExtraGroups $extraGroups */
            if ($extraGroups = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
              if ($extraGroups::allowed()) {
                  $filterGroup .= " or exists (select * from customer_extra_groups ceg, groups g1 where g1.groups_name like '%" . tep_db_input($output['group']) . "%' and ceg.group_id=g1.groups_id and c.customers_id=ceg.customer_id)";
        }
            }
            $filterGroup .=  ")";
            $customersQuery->andWhere($filterGroup);
        }

        if (tep_not_null($output['country'])) {
            $_join_address_book = true;

            $_need_countries = \yii\helpers\ArrayHelper::map(\common\models\Countries::find()
                ->select('countries_id')
                ->distinct()
                ->where(['like', 'countries_name', $output['country']])
                ->asArray()
                ->all(),'countries_id','countries_id');
            if ( count($_need_countries)==0 ) $_need_countries = [-111];
            $customersQuery->andWhere(['IN', 'a.entry_country_id', $_need_countries]);
        }
        if (tep_not_null($output['state'])) {
            $_join_zones = true;
            $_join_address_book = true;
            $customersQuery->andWhere("(a.entry_state like '%" . tep_db_input($output['state']) . "%' or z.zone_name like '%" . tep_db_input($output['state']) . "%')");
        }
        if (tep_not_null($output['city'])) {
            $_join_address_book = true;
            $customersQuery->andWhere("a.entry_city like '%" . tep_db_input($output['city']) . "%'");
        }

        if (tep_not_null($output['company'])) {
            $_join_address_book = true;
            $customersQuery->andWhere("a.entry_company like '%" . tep_db_input($output['company']) . "%'");
        }

        if (tep_not_null($output['newsletter'])) {
            switch ($output['newsletter']) {
                case 's':
                    $customersQuery->andWhere("c.customers_newsletter='1'");
                    break;
                case 'ns':
                    $customersQuery->andWhere("c.customers_newsletter='0'");
                    break;
                default:
                    break;
            }
        }

        if (tep_not_null($output['status'])) {
            switch ($output['status']) {
                case 'y':
                    $customersQuery->andWhere("c.customers_status = '1'");
                    break;
                case 'n':
                    $customersQuery->andWhere("c.customers_status = '0'");
                    break;
                default:
                    break;
            }
        }
        if (tep_not_null($output['guest'])) {
            switch ($output['guest']) {
                case 'y':
                    $customersQuery->andWhere("c.opc_temp_account = '1'");
                    break;
                case 'n':
                    $customersQuery->andWhere("c.opc_temp_account = '0'");
                    break;
                default:
                    break;
            }
        }

        if (tep_not_null($output['date'])) {
            switch ($output['date']) {
                case 'exact':
                    if (tep_not_null($output['from'])) {
                        $from = tep_db_prepare_input($output['from']);
                        $_join_customer_info = true;
                        $customersQuery->andWhere("to_days(ci.customers_info_date_account_created) >= to_days('" . \common\helpers\Date::prepareInputDate($from) . "')");
                    }
                    if (tep_not_null($output['to'])) {
                        $to = tep_db_prepare_input($output['to']);
                        $_join_customer_info = true;
                        $customersQuery->andWhere(" to_days(ci.customers_info_date_account_created) <= to_days('" . \common\helpers\Date::prepareInputDate($to) . "')");
                    }
                    break;
                case 'presel':
                    if (tep_not_null($output['interval'])) {
                        switch ($output['interval']) {
                            case 'week':
                                $customersQuery->andWhere("ci.customers_info_date_account_created >= '" . date('Y-m-d', strtotime('monday this week')) . "'");
                                $_join_customer_info = true;
                                break;
                            case 'month':
                                $customersQuery->andWhere("ci.customers_info_date_account_created >= '" . date('Y-m-d', strtotime('first day of this month')) . "'");
                                $_join_customer_info = true;
                                break;
                            case 'year':
                                $customersQuery->andWhere("ci.customers_info_date_account_created >= '" . date("Y") . "-01-01" . "'");
                                $_join_customer_info = true;
                                break;
                            case '1':
                                $customersQuery->andWhere("ci.customers_info_date_account_created >= '" . date('Y-m-d') . "'");
                                $_join_customer_info = true;
                                break;
                            case '3':
                            case '7':
                            case '14':
                            case '30':
                                $customersQuery->andWhere("ci.customers_info_date_account_created >= date_sub(now(), interval " . (int) $output['interval'] . " day)");
                                $_join_customer_info = true;
                                break;
                        }
                    }
                    break;
            }
        }

        if (tep_not_null($output['title'])) {
            switch ($output['title']) {
                case 'm':
                case 'f':
                case 's':
                    $customersQuery->andWhere("c.customers_gender = '" . tep_db_input($output['title']) . "'");
                    break;
                default:
                    break;
            }
        }

        if ( $_join_address_book || $_join_zones ) {
            $customersQuery->leftJoin('address_book a', 'a.customers_id=c.customers_id');
            $customersQuery->groupBy('c.customers_id');
        }
        if ($_join_zones){
            $customersQuery->leftJoin(TABLE_ZONES . " z", "z.zone_country_id=a.entry_country_id and a.entry_zone_id=z.zone_id");
        }
        if ($_join_customer_info){
            $customersQuery->leftJoin(['ci'=>TABLE_CUSTOMERS_INFO], 'c.customers_id=ci.customers_info_id');
        }

        $customersQuery->orderBy(['c.customers_lastname' => SORT_ASC, 'c.customers_firstname' => SORT_ASC]);
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $customersQuery->orderBy(["c.customers_lastname"=>(strtolower($_GET['order'][0]['dir'])=='desc'?SORT_DESC:SORT_ASC)]);
                    break;
                case 1:
                    $customersQuery->orderBy(["c.customers_firstname"=>(strtolower($_GET['order'][0]['dir'])=='desc'?SORT_DESC:SORT_ASC)]);
                    break;
                case 2:
                    $customersQuery->orderBy(["c.customers_email_address"=>(strtolower($_GET['order'][0]['dir'])=='desc'?SORT_DESC:SORT_ASC)]);
                    break;
            }
        }

        $customersQuery->select(['c.customers_id', 'c.platform_id', 'c.departments_id', 'c.customers_default_address_id']);

        //echo $customersQuery->createCommand()->getRawSql()."\n\n";
        $customers_query_numrows = $customersQuery->count();

        $customersQuery->limit($length)->offset($start);
        $customersAll = $customersQuery->asArray()->all();

        $current_page_number = ($start / $length) + 1;

        // {{ attach page info
        if ( count($customersAll)>0 ) {
            $_pageCustomerIds = array_map(function ($row) {
                return $row['customers_id'];
            }, $customersAll);
            $_pageCustomerIdToIdx = array_flip($_pageCustomerIds);

            $_fill_in_data = \common\models\Customers::find()
                ->select([
                    'customers_id', 'customers_gender', 'customers_lastname', 'customers_firstname', 'customers_email_address', 'customers_status',
                    'c.groups_id', 'g.groups_name', 'opc_temp_account',
                    'date_account_created' => 'ci.customers_info_date_account_created',
                ])
                ->alias('c')
                ->leftJoin(['ci' => TABLE_CUSTOMERS_INFO], 'c.customers_id=ci.customers_info_id')
                ->leftJoin(['g' => TABLE_GROUPS], 'c.groups_id=g.groups_id')
                ->where(['IN', 'customers_id', array_keys($_pageCustomerIdToIdx)])
                ->asArray()
                ->all();
            foreach ( $_fill_in_data as $_fill_in_row ){
                $__idx = $_pageCustomerIdToIdx[$_fill_in_row['customers_id']];
                $customersAll[$__idx] = array_merge($customersAll[$__idx], $_fill_in_row);
            }

            $_pageCustomerDefaultAbIds = array_map(function ($row) {
                return $row['customers_default_address_id'];
            }, $customersAll);
            $_pageCustomerDefaultAbIds = array_flip($_pageCustomerDefaultAbIds);

            foreach( \common\models\AddressBook::find()
                ->select([
                        'address_book_id',
                        'entry_country_id', 'entry_postcode', 'entry_firstname', 'entry_lastname', 'entry_street_address',
                        'entry_city',
                        'state' => new \yii\db\Expression('IF(LENGTH(a.entry_state), a.entry_state, z.zone_name)'),
                        'country' => 'cn.countries_name',
                ])
                ->alias('a')
                ->leftJoin(['cn' => TABLE_COUNTRIES], "a.entry_country_id=cn.countries_id  and cn.language_id = '" . (int) $languages_id . "'")
                ->leftJoin(['z' => TABLE_ZONES], "z.zone_country_id=a.entry_country_id and a.entry_zone_id=z.zone_id" )
                ->where(['IN', 'address_book_id', array_keys($_pageCustomerDefaultAbIds)])
                ->asArray()
                ->all()
                as $defaultAddress
            ){
                $__idx = $_pageCustomerDefaultAbIds[$defaultAddress['address_book_id']];
                $customersAll[$__idx] = array_merge($customersAll[$__idx], $defaultAddress);
            }

            // order stat

            $info_query = tep_db_query(
                "select o.customers_id, count(*) as total_orders, max(o.date_purchased) as last_purchased, ".
                "  sum(ot.value) as total_sum, ot.class ".
                "from " . TABLE_ORDERS . " o ".
                "  left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) ".
                "where " . (USE_MARKET_PRICES == 'True' ? "o.currency = '" . tep_db_input($_GET['currency'] ? $_GET['currency'] : DEFAULT_CURRENCY) . "'" : '1') . " ".
                "  and ot.class='ot_total' and o.customers_id IN ('".implode("','",array_keys($_pageCustomerIdToIdx))."') ".
                "group by o.customers_id"
            );
            if ( tep_db_num_rows($info_query)>0 ){
                while( $info = tep_db_fetch_array($info_query) ){
                    $__idx = $_pageCustomerIdToIdx[$info['customers_id']];
                    $customersAll[$__idx]['statInfo'] = $info;
                }
            }

        }
        // }} attach page info

        $responseList = array();
        //while ($customers = tep_db_fetch_array($customers_query)) {
        foreach ($customersAll as $customers){
            $customers['groups_name'] = $customers['groups_id']?\common\helpers\Group::get_user_group_name($customers['groups_id']):'';

            $info = isset($customers['statInfo'])?$customers['statInfo']:['total_orders'=>0, 'total_sum'=>0];

            if (trim($search) != '') {
                $hilite_function = function ($search, $text) {
                    $w = preg_quote(trim($search), '/');
                    $regexp = "/($w)(?![^<]+>)/i";
                    $replacement = '<b style="color:#ff0000">\\1</b>';
                    return preg_replace($regexp, $replacement, $text);
                };
            } else {
                $hilite_function = function ($search, $text) {
                    return $text;
                };
            }
            //------
            if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED == True) {
                $departmentInfo = ($customers['departments_id'] > 0 ? '<b>'.TABLE_HEADING_DEPARTMENT . ':</b>&nbsp;' . $departments[$customers['departments_id']] : '');
            } else {
                $departmentInfo = (\common\classes\platform::isMulti() >= 1 ? '<b>' . TABLE_HEADING_PLATFORM . ':</b>&nbsp;' . \common\classes\platform::name($customers['platform_id']) : '');
            }
            
            $departmentInfo = '<b class="customer-group" ' . (strlen($customers['groups_name']) > 30 ? ' title="' . $customers['groups_name'] . '"' : '') . '>' . substr($customers['groups_name'], 0, 30) . (strlen($customers['groups_name']) > 30 ? '...' : '') . '</b></br>' . $departmentInfo;
            
            $responseList[] = array(
                '<input type="checkbox" class="uniform">' . '<input class="cell_identify" type="hidden" value="' . $customers['customers_id'] . '">',
                ($customers['opc_temp_account'] == 1 ? '<i style="color: #03a2a0;">' . TEXT_GUEST . '</i><br>' : '') . '<div class="c-list-name ord-gender click_double ord-gender-' . $customers['customers_gender'] . '" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . $hilite_function($search, $customers['customers_lastname']) . '<input class="cell_identify" type="hidden" value="' . $customers['customers_id'] . '"></div>',
                '<div class="c-list-name click_double"  data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . $hilite_function($search, $customers['customers_firstname']) . '</div>',
                '<div class="click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '"><a class="ord-name-email" href="mailto:' . $customers['customers_email_address'] . '"><b' . (strlen($customers['customers_email_address']) > 30 ? ' title="' . Html::encode($customers['customers_email_address']) . '"' : '') . '>' . $hilite_function($search, substr($customers['customers_email_address'], 0, 30)) . (strlen($customers['customers_email_address']) > 30 ? '...' : '') . '</b></a><br>' . $departmentInfo . '</div>',
                '<div class="click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . \common\helpers\Date::date_short($customers['date_account_created']) . '</div>',
                '<div class="ord-location click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . $hilite_function($search, $customers['entry_postcode']) . '<div class="ord-total-info ord-location-info"><div class="ord-box-img"></div><b>' . Html::encode($customers['entry_firstname'] . ' ' . $customers['entry_lastname']) . '</b>' . Html::encode($customers['entry_street_address']) . '<br>' . Html::encode($customers['entry_city'] . ', ' . $customers['state']) . '&nbsp;' . Html::encode($customers['entry_postcode']) . '<br>' . $customers['country'] . '</div></div>',
                '<div class="c-list-count click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . $info['total_orders'] . '</div>',
                '<div class="c-list-total click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '">' . $currencies->format($info['total_sum']) . '</div>',
                '<div class="c-list-date-last click_double" data-click-double="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $customers['customers_id']]) . '"><span>' . \common\helpers\Date::datetime_short($info['last_purchased']) . '</span>' . \common\helpers\Date::getDateRange(date('Y-m-d'), $info['last_purchased']) . '</div>',
                    //'<input type="button" class="btn btn-primary pull-right" value="Edit" onClick="return editCustomer(' . $customers['customers_id'] . ')">'.'<input class="cell_identify" type="hidden" value="' . $customers['customers_id'] . '">'
            );
        }
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $customers_query_numrows,
            'recordsFiltered' => $customers_query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
        //die();
    }

    public function actionCustomeractions() {

        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/customers');
        $messageStack = \Yii::$container->get('message_stack');
        $currencies = Yii::$container->get('currencies');

        $this->layout = false;

        $customers_id = Yii::$app->request->post('customers_id');
        $customers = \common\models\Customers::find()
            ->andWhere(['customers_id' => $customers_id])
            ->with(['defaultAddress', 'info', 'group'])
            ->asArray()->one();

        
        if (!is_array($customers)) {
            die("Wrong customer data.");
        }

        $orders_query = tep_db_query("select count(*) as total_orders, max(o.date_purchased) as last_purchased, sum(ot.value) as total_sum, ot.class from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where " . (USE_MARKET_PRICES == 'True' ? "o.currency = '" . tep_db_input($_GET['currency'] ? $_GET['currency'] : DEFAULT_CURRENCY) . "'" : '1') . " and ot.class='ot_total' and o.customers_id = " . $customers['customers_id']);
        $orders = tep_db_fetch_array($orders_query);
        if (!is_array($orders))
            $orders = [];

        $reviews_query = tep_db_query("select count(*) as number_of_reviews from " . TABLE_REVIEWS . " where customers_id = '" . (int) $customers['customers_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        if (!is_array($reviews))
            $reviews = [];

        $customer_info = array_merge($reviews, $orders);
        $cInfo_array = array_merge($customers, $customer_info);
        $cInfo = json_decode(json_encode($cInfo_array));
//echo "#### <PRE>" .print_r($cInfo, 1) ."</PRE>"; die;

        if ($messageStack->size() > 0) {
            if ($_GET['read'] == 'only') {

            } else {
                echo $messageStack->output();
            }
        }

        echo '<div class="or_box_head">' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</div>';
        if (!empty($cInfo->customers_company) ||
          !empty($cInfo->defaultAddress->entry_company) ) {
          echo '<div class="row_or_wrap text-center strong">' . $cInfo->defaultAddress->entry_company . (empty($cInfo->defaultAddress->entry_company)?' ' . $cInfo->customers_company:'') . '</div>';
        }

        echo '<div class="row_or_wrapp">';
        
        if (!empty($cInfo->group->groups_name)  ) {
          echo '<div class="row_or"><div>' . ENTRY_GROUP . '</div><div>' . $cInfo->group->groups_name . '</div></div>';
        }
        echo '<div class="row_or"><div>' . TEXT_TOTAL_ORDERED . '</div><div>' . $currencies->format($cInfo->total_sum) . '</div></div>';
        echo '<div class="row_or"><div>' . TEXT_ORDER_COUNT . '</div><div>' . $cInfo->total_orders . '</div></div>';
        echo '<div class="row_or">
					<div>' . TEXT_DATE_ACCOUNT_CREATED . '</div>
					<div>' . \common\helpers\Date::date_short($cInfo->info->customers_info_date_account_created) . '</div>
				</div>';
        /* echo '<div class="update_password">
          <div class="update_password_title">Update customers password:</div>
          <div class="update_password_content"><form name="passw_form" action="' . tep_href_link(FILENAME_CUSTOMERS, \common\helpers\Output::get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=password') . '" method="post" onsubmit="return check_passw_form('.(int)ENTRY_PASSWORD_MIN_LENGTH.');"><input type="hidden" name="cID" value="'.$cInfo->customers_id.'"><input type="text" name="change_pass" class="form-control" size="16" placeholder="New password"><input type="submit" value="Update Password" class="btn"></form></div>
          </div>'; */
        echo '<div class="row_or">
					<div>' . TEXT_DATE_ACCOUNT_LAST_MODIFIED . '</div>
					<div>' . \common\helpers\Date::date_short($cInfo->info->customers_info_date_account_last_modified) . '</div>
				</div>';
        echo '<div class="row_or">
					<div>' . TEXT_INFO_DATE_LAST_LOGON . '</div>
					<div>' . \common\helpers\Date::date_short($cInfo->info->customers_info_date_of_last_logon) . '</div>
				</div>';
        echo '<div class="row_or">
					<div>' . TEXT_INFO_COUNTRY . '</div>
					<div>' . $cInfo->defaultAddress->country->countries_name . '</div>
				</div>';
        echo '<div class="row_or">
					<div>' . TEXT_INFO_NUMBER_OF_LOGONS . '</div>
					<div>' . $cInfo->info->customers_info_number_of_logons . '</div>
				</div>';
        echo '<div class="row_or">
					<div>' . TEXT_INFO_NUMBER_OF_REVIEWS . '</div>
					<div>' . $cInfo->number_of_reviews . '</div>
				</div>';
        echo '</div>';
        echo '<div class="btn-toolbar btn-toolbar-order">
                <a href="' . \Yii::$app->urlManager->createUrl(['editor/create-order', 'customers_id' => $cInfo->customers_id, 'back' => 'customers']) . '" class="btn btn-primary btn-process-order btn-process-order-cus">' . TEXT_CREATE_NEW_OREDER . '</a>
                <a class="btn btn-edit btn-no-margin" href="' . \Yii::$app->urlManager->createUrl(['customers/customeredit', 'customers_id' => $cInfo->customers_id]) . '">' . IMAGE_EDIT . '</a>' . (!tep_session_is_registered('login_affiliate') ? '<button class="btn btn-delete" onclick="confirmDeleteCustomer(' . $cInfo->customers_id . ')">' . IMAGE_DELETE . '</button>' : '') . '<a class="btn btn-no-margin btn-ord-cus" href="' . \Yii::$app->urlManager->createUrl(['orders/', 'by' => 'cID', 'search' => $cInfo->customers_id]) . '">' . IMAGE_ORDERS . '</a><a class="btn btn-email-cus" href="mailto:' . $cInfo->customers_email_address . '">' . IMAGE_EMAIL . '</a><a href="' . \Yii::$app->urlManager->createUrl(['gv_mail/index', 'type' => 'C', 'customer' => $cInfo->customers_email_address, 'only' => $cInfo->customers_id]) . '" class="btn btn-no-margin btn-coup-cus popup">' . T_SEND_COUPON . '</a>';

        if ($ext = \common\helpers\Acl::checkExtension('MergeCustomers', 'actionCustomeractions')) {
            $ext::actionCustomeractions($cInfo->customers_id);
        } else {
            echo '<a href="javascript:void(0)" class="btn btn-no-margin btn-coup-cus dis_module">' . TEXT_MERGE_CUSTOMER . '</a>';
        }

        if (ENABLE_TRADE_FORM == 'True') {
            echo '<a href="' . \Yii::$app->urlManager->createUrl(['customers/customer-additional-fields', 'customers_id' => $cInfo->customers_id]) . '" class="btn btn-no-margin btn-coup-cus">' . TRADE_FORM . '</a>';
        }

        $check_dev_admin = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS c FROM ".TABLE_ADMIN." WHERE admin_id='".(int)$_SESSION['login_id']."' AND admin_email_address LIKE '%@holbi%'"));
        if ( extension_loaded('openssl') && $check_dev_admin['c']>0 ) {
            $sc = new \yii\base\Security();
            $aup = base64_encode($sc->encryptByKey($cInfo->customers_id."\t".$cInfo->customers_email_address, date('\s\me\c\rYkd\ey')));
            $_activePlatformId = Yii::$app->get('platform')->config()->getId();
            $perPlatformLoginList = [];
            foreach(\common\classes\platform::getList(false) as $platform){
                Yii::$app->get('platform')->config($platform['id']);
                $perPlatformLoginList[] = [
                    'href' => tep_catalog_href_link('account/login-me', 'aup='.$aup),
                    'name' => $platform['text'],
                ];
            }
            Yii::$app->get('platform')->config($_activePlatformId);
            $superLoginButton = '';
            if ( count($perPlatformLoginList)>0 ) {
                if (count($perPlatformLoginList)==1){
                    $superLoginButton = Html::a('Super login',$perPlatformLoginList[0]['href'],['target'=>'_blank','class'=>'btn btn-no-margin btn-coup-cus']);
                }else{
                    $superLoginButton = '<div class="dropdown"><button class="btn btn-pass-cus dropdown-toggle" type="button" id="customerSuperLoginMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Super login <span class="icon-caret-down"></span></button>';
                    $superLoginButton .= '<ul class="dropdown-menu" aria-labelledby="customerSuperLoginMenu">';
                    foreach ($perPlatformLoginList as $perPlatformLogin){
                        $superLoginButton .= '<li>'.Html::a($perPlatformLogin['name'], $perPlatformLogin['href'], ['target'=>'_blank']).'</li>';
                    }
                    $superLoginButton .= '</ul>';
                    $superLoginButton .= '</div>';
                }
            }
            echo $superLoginButton;
        }

        echo '</div>';
        echo '<div class="btn-toolbar btn-toolbar-order btn-toolbar-pass"><span class="btn btn-pass-cus js-update-customer-pass">'.T_UPDATE_PASS.'</span>
                                <script>
                                $(document).ready(function() { 
                                $("a.popup").popUp();
                                $(".js-update-customer-pass").on("click", function(){
                                    alertMessage("<div class=\"popup-heading popup-heading-pass\">' . TEXT_UPDATE_PASSWORD_FOR . ' ' . $cInfo->customers_firstname . '&nbsp;' . $cInfo->customers_lastname . '</div><div class=\"popup-content popup-content-pass\"><form name=\"passw_form\" action=\"' . tep_href_link(FILENAME_CUSTOMERS, \common\helpers\Output::get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->customers_id . '&action=password') . '\" method=\"post\" onsubmit=\"return check_passw_form(' . (int) ENTRY_PASSWORD_MIN_LENGTH . ');\"><label>' . T_NEW_PASS . ':</label><input type=\"hidden\" name=\"cID\" value=\"' . $cInfo->customers_id . '\"><input type=\"password\" name=\"change_pass\" class=\"form-control\" size=\"16\"><div class=\"btn-bar\" style=\"padding-bottom: 0;\"><div class=\"btn-left\"><span class=\"btn btn-cancel\">' . IMAGE_CANCEL . '</span></div><div class=\"btn-right\"><input type=\"submit\" value=\"' . IMAGE_UPDATE . '\" class=\"btn btn-primary\"></div></div></form></div>");
                                });
                                });
                                </script>
                                </div>';


        //die();
        //$this->view->cInfo = $cInfo;
        //$this->render('customeractions');
    }

    public function actionCustomeredit() {

        \common\helpers\Translation::init('admin/customers');

        $currencies = Yii::$container->get('currencies');
        $messageStack = \Yii::$container->get('message_stack');

        if (Yii::$app->request->isPost) {
            $customers_id = Yii::$app->request->post('customers_id');
        } else {
            $customers_id = Yii::$app->request->get('customers_id');
        }

        $customerForm = new CustomerRegistration(['scenario' => CustomerRegistration::SCENARIO_EDIT, 'shortName' => CustomerRegistration::SCENARIO_EDIT]);
        $customerForm->useExtending = true;
        $myPromos = [];
        if ($customers_id && $cInfo = Customer::findOne($customers_id)) {
            $exclude_order_statuses_array = \common\helpers\Order::extractStatuses(DASHBOARD_EXCLUDE_ORDER_STATUSES);
            $orders_query = tep_db_query("select count(*) as total_orders, max(o.date_purchased) as last_purchased, sum(ot.value) as total_sum, ot.class from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where " . (USE_MARKET_PRICES == 'True' ? "o.currency = '" . tep_db_input($_GET['currency'] ? $_GET['currency'] : DEFAULT_CURRENCY) . "'" : '1') . " and ot.class='ot_total' and o.customers_id = " . (int) $customers_id
                ."  AND o.orders_status not in ('" . implode("','", $exclude_order_statuses_array) . "') ");

            $orders = tep_db_fetch_array($orders_query);

            $reviews = tep_db_fetch_array(tep_db_query("select count(*) as total_reviews from reviews where customers_id=" . $cInfo->customers_id));
            $cInfo->set('total_reviews', $reviews['total_reviews']);
            
            $cInfo->set('total_orders', $orders['total_orders']);
            $cInfo->set('last_purchased', \common\helpers\Date::date_short($orders['last_purchased']));
            $cInfo->set('last_purchased_days', \common\helpers\Date::getDateRange(date('Y-m-d'), $orders['last_purchased']));
            $cInfo->set('total_sum', $currencies->format($orders['total_sum']));

            $str_full = strlen($cInfo->customers_firstname . '&nbsp;' . $cInfo->customers_lastname);
            if ($str_full > 22) {
                $st_full_name = mb_substr($cInfo->customers_firstname . '&nbsp;' . $cInfo->customers_lastname, 0, 22);
                $st_full_name .= '...';
                $st_full_name_view = '<span title="' . $cInfo->customers_firstname . '&nbsp;' . $cInfo->customers_lastname . '">' . $st_full_name . '</span>';
            } else {
                $st_full_name_view = $cInfo->customers_firstname . '&nbsp;' . $cInfo->customers_lastname;
            }
            
            $myPromos = \common\models\promotions\PromotionsAssignement::getOwnerPromo(\common\models\promotions\PromotionsAssignement::OWNER_CUSTOMER, $customers_id);
            
            $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('customers/customeredit'), 'title' => T_EDITING_CUS . '&nbsp;"' . $st_full_name_view . '"');
            $this->view->headingTitle = T_EDITING_CUS;
            $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['gv_mail/index', 'type' => 'C', 'customer' => $cInfo->customers_email_address, 'only' => $cInfo->customers_id]) . '" class="create_item popup"><i class="icon-ticket"></i>' . T_SEND_COUPON . '</a><a href="' . Yii::$app->urlManager->createUrl(['editor/create-order', 'customers_id' => $cInfo->customers_id, 'back' => 'orders']) . '" class="create_item"><i class="icon-file-text"></i>' . TEXT_CREATE_NEW_OREDER . '</a>';
        } else {
            $cInfo = new Customer();
            $cInfo->customers_status = 1;
            $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('customers/customeredit'), 'title' => TEXT_ADD_NEW_CUSTOMER);
            $this->view->headingTitle = TEXT_ADD_NEW_CUSTOMER;
        }

        $customerForm->preloadCustomersData($cInfo);

        $cInfo->set('view_credit_amount', $currencies->format($cInfo->credit_amount));
        $cInfo->set('credit_amount_mask', $currencies->format(0));
        $discount = \common\helpers\Customer::get_additional_discount($cInfo->groups_id, $cInfo->customers_id);
        $group = \common\models\Groups::findOne($cInfo->groups_id);
        if ($group) {
            $discount += $group->groups_discount;
        }
        $cInfo->set('discount', $discount);

        $addresses = [];
        foreach ($cInfo->getAddressBooks() as $aBook) {
            $form = new AddressForm(['scenario' => AddressForm::CUSTOM_ADDRESS]);
            $form->preload($aBook);
            $addresses[$aBook->address_book_id] = $form;
        }

        if (count($addresses) < MAX_ADDRESS_BOOK_ENTRIES) {
            $addresses[0] = new AddressForm(['scenario' => AddressForm::CUSTOM_ADDRESS]);
        }

        if (Yii::$app->request->isPost) {
            $customerForm->load(Yii::$app->request->post());
            $customerForm->validate();
            $customerForm->checkPin('pin', $cInfo->customers_id);
            $customerForm->emailUnique('email_address', ['customers_id' => $cInfo->customers_id]);
            $cValid = !$customerForm->hasErrors();
            $hasErrors = !$cValid;
            if ($cValid) {
                $cInfo->updateCustomer($customerForm->getAttributesByScenario());
                $cInfo->addCustomersInfo();                
            } else {
                foreach ($customerForm->getErrors() as $error) {
                    $messageStack->add((is_array($error) ? implode("<br>", $error) : $error), 'account', 'danger');
                }
            }

            if ($addresses) {
                $remove = [];
                $customers_default_address_id = Yii::$app->request->post('customers_default_address_id', null);
                foreach ($addresses as $aBookId => $address) {
                    $data = Yii::$app->request->post($address->formName());
                    if (isset($data[$aBookId])) {
                        $address->load($data, $aBookId);
                        if ($address->notEmpty()) {
                            $address->validate();
                            if (!$address->hasErrors() && $cInfo->customers_id) {
                                $attributes = $cInfo->getAddressFromModel($address);
                                if ($aBookId) {
                                    $aBook = $cInfo->updateAddress($aBookId, $attributes);
                                } else {
                                    $aBook = $cInfo->addAddress($attributes);
                                    if (!is_null($customers_default_address_id) && !$customers_default_address_id) {
                                        $customers_default_address_id = $aBook->address_book_id;
                                    }
                                }
                            } else {
                                $hasErrors = true;
                                foreach ($address->getErrors() as $error) {
                                    $messageStack->add((is_array($error) ? implode("<br>", $error) : $error), 'account', 'danger');
                                }
                            }
                        }
                    } else {
                        $remove[] = $aBookId;
                    }
                }
                if ($remove) {
                    foreach ($remove as $abId) {
                        $cInfo->removeAddress($abId);
                    }
                }
            }

            $abIds = \yii\helpers\ArrayHelper::getColumn($cInfo->getAddressBooks(), 'address_book_id');
            if ($customers_default_address_id && in_array($customers_default_address_id, $abIds) && $abIds) {
                $cInfo->customers_default_address_id = $customers_default_address_id;
                $cInfo->save(false);
            }
            if (!in_array($cInfo->customers_default_address_id, $abIds) && $abIds) {
                $cInfo->customers_default_address_id = $abIds[0];
                $cInfo->save(false);
            }

            if ($cInfo->customers_id) {
                $platform_config = \Yii::$app->get('platform')->config($cInfo->platform_id);
                $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
                $STORE_OWNER = $platform_config->const_value('STORE_OWNER');

                $credit_amount = number_format(floatval(tep_db_prepare_input($_POST['credit_amount'])), 5, '.', '');
                if ($credit_amount > 0) {
                    $currencies = Yii::$container->get('currencies');

                    $credit_prefix = tep_db_prepare_input($_POST['credit_prefix']);
                    $comments = tep_db_prepare_input($_POST['comments']);
                    $customer_notified = '0';
                    if (isset($_POST['notify']) && ($_POST['notify'] == 'on')) {
                        $customer_notified = '1';
                        $email_params['STORE_NAME'] = $STORE_OWNER;
                        $email_params['CUSTOMER_FIRSTNAME'] = $cInfo->customers_firstname;
                        $email_params['CUSTOMER_LASTNAME']= $cInfo->customers_lastname;
                        $email_params['CREDIT_AMOUNT'] = $credit_prefix . $currencies->format($credit_amount, true, DEFAULT_CURRENCY, $currencies->currencies[DEFAULT_CURRENCY]['value']);
                        $email_params['CREDIT_AMOUNT_COMMENTS'] = $comments;

                        [$emailSubject, $emailContent] = \common\helpers\Mail::get_parsed_email_template('Credit amount notification', $email_params, $cInfo->language_id, $cInfo->platform_id);
                        \common\helpers\Mail::send($cInfo->customers_firstname . ' ' . $cInfo->customers_lastname, $cInfo->customers_email_address, $emailSubject, $emailContent, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS, [], '', '', ['add_br' => 'no']);
                    }
                    $cInfo->saveCreditHistory($cInfo->customers_id, $credit_amount, $credit_prefix, DEFAULT_CURRENCY, $currencies->currencies[DEFAULT_CURRENCY]['value'], $comments, 0, $customer_notified);
                    tep_db_query("update " . TABLE_CUSTOMERS . " set credit_amount = credit_amount " . $credit_prefix . " " . $credit_amount . " where customers_id =" . (int) $customers_id);
                }

                $bonus_points = number_format(floatval(tep_db_prepare_input($_POST['bonus_points'])), 5, '.', '');
                if ($bonus_points > 0) {

                    $bonus_prefix = tep_db_prepare_input($_POST['bonus_prefix']);
                    $comments = tep_db_prepare_input($_POST['bonus_comments']);
                    $customer_notified = '0';
                    if (isset($_POST['bonus_notify']) && ($_POST['bonus_notify'] == 'on')) {
                        $customer_notified = '1';

                        $email_params['BONUS_POINTS'] = $bonus_prefix . number_format($bonus_points, 2);
                        $email_params['BONUS_POINTS_COMMENTS'] = $comments;

                        [$emailSubject, $emailContent] = \common\helpers\Mail::get_parsed_email_template('Bonus points notification', $email_params, $cInfo->language_id, $cInfo->platform_id);

                        \common\helpers\Mail::send($cInfo->customers_firstname . ' ' . $cInfo->customers_lastname, $cInfo->customers_email_address, $emailSubject, $emailContent, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS, [], '', '', ['add_br' => 'no']);
                    }

                    $cInfo->updateBonusPoints($cInfo->customers_id, $bonus_points, $bonus_prefix);
                    $cInfo->saveCreditHistory($cInfo->customers_id, $bonus_points, $bonus_prefix, '', 1, $comments, 1, $customer_notified);
                }

                if ($TrustpilotClass = Acl::checkExtension('Trustpilot', 'onCustomerUpdate')) {
                    $TrustpilotClass::onCustomerUpdate((int) $cInfo->customers_id);
                }

                /* @var $CustomerProducts \common\extensions\CustomerProducts\CustomerProducts */
                if ($CustomerProducts = Acl::checkExtension('CustomerProducts', 'saveCustomer')) {
                    $CustomerProducts::saveCustomer((int) $cInfo->customers_id);
                }
                
                /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
                if ($ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
                  $ext::saveCustomer((int) $cInfo->customers_id);
                }

                /** @var \common\extensions\CustomerModules\CustomerModules $CustomerModules */
                if ($CustomerModules = \common\helpers\Acl::checkExtension('CustomerModules', 'saveCustomer')) {
                  $CustomerModules::saveCustomer((int) $cInfo->customers_id);
                }

                /** @var \common\extensions\CustomersMultiEmails\CustomersMultiEmails $CustomersMultiEmails */
                if ($CustomersMultiEmails = \common\helpers\Acl::checkExtension('CustomersMultiEmails', 'saveCustomer')) {
                  $ret = $CustomersMultiEmails::saveCustomer((int) $cInfo->customers_id, $hasErrors);
                  if (!$ret && !$hasErrors) {
                    $hasErrors = true;
                  }
                }


            }

            if (!$hasErrors) {
                $messageStack->add_session(TEXT_MESSEAGE_SUCCESS, 'account', 'success');
                return $this->redirect(['customers/customeredit', 'customers_id' => $cInfo->customers_id]);
            }
        }
        $messages = [];
        if ($messageStack->size('account') > 0) {
            $messages = $messageStack->asArray('account');
        }

        if ($customerForm->erp_customer_id == 0) {
            $customerForm->erp_customer_id = '';
        }

        $this->selectedMenu = array('customers', 'customers');


        $this->view->showGroup = (CUSTOMERS_GROUPS_ENABLE == 'True');
        if ($this->view->showGroup) {
          /** @var \common\extensions\ExtraGroups\ExtraGroups $ext */
          $ext = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed');
          if ($ext::allowed()) {
            $this->view->groupStatusArray = $ext::getMainGroups();
            $this->view->groupExtraArrays = $ext::getOtherGroups();
            $this->view->showOtherGroups = true;
            $this->view->groupExtraSelected = $ext::getOtherGroupsSelected($cInfo->customers_id);
          } else {
            $this->view->groupStatusArray = \common\models\Groups::find()->asArray()->select('groups_name')->indexBy('groups_id')->column();
        }
        }

        $guestStatusArray = [
            1 => TEXT_BTN_YES,
            0 => TEXT_BTN_NO,
        ];
        $this->view->guestStatusArray = $guestStatusArray;

        $this->view->showDOB = in_array(ACCOUNT_DOB, ['required', 'required_register', 'visible', 'visible_register']);
        $this->view->showState = in_array(ACCOUNT_STATE, ['required', 'required_register', 'visible', 'visible_register']);

        $platform_variants = array();
        foreach (\common\classes\platform::getList(false) as $_p) {
            $platform_variants[$_p['id']] = $_p['text'];
        }
        $languages = array_column(\common\classes\language::get_all(), 'name', 'id');
        $currency = \Yii::$app->settings->get('currency');
        switch ($currency) {
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
        
        return $this->render('edit', [
            'cInfo' => $cInfo,
            'addresses' => $addresses,
            'platforms' => $platform_variants,
            'admins' => [0 => ''] + \yii\helpers\ArrayHelper::map(\common\helpers\Admin::getList(), 'admin_id', 'listTitle'),
            'customerForm' => $customerForm,
            'myPromos' => $myPromos,
            'messages' => $messages,
            'prefix' => $prefixClass,
            'languages' => $languages,
        ]);
    }
    
    public function actionDropPromo(){
        $customers_id = Yii::$app->request->post('customers_id');
        $promo_id = Yii::$app->request->post('promo_id');
        if ($promo_id && $customers_id){
            \common\models\promotions\PromotionsAssignement::deletePromoOwners($promo_id, \common\models\promotions\PromotionsAssignement::OWNER_CUSTOMER, [$customers_id]);
        }
        exit();
    }

    public function actionCustomerdelete() {
        $this->layout = false;
        $customers_id = Yii::$app->request->post('customers_id');
        \common\helpers\Customer::deleteCustomer($customers_id, false);
    }

    public function actionCustomersdelete() {
        $this->layout = false;
        $selected_ids = Yii::$app->request->post('selected_ids');
        foreach ($selected_ids as $customers_id) {
            \common\helpers\Customer::deleteCustomer($customers_id, false);
        }
    }

    public function actionConfirmcustomerdelete() {

        $languages_id = \Yii::$app->settings->get('languages_id');

        \common\helpers\Translation::init('admin/customers');

        $this->layout = false;

        $customers_id = Yii::$app->request->post('customers_id');

        $customers_query = tep_db_query("select distinct(c.customers_id), c.last_xml_export, c.customers_lastname, c.customers_firstname, c.customers_email_address, c.customers_status, c.groups_id, a.entry_country_id, c.admin_id from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADDRESS_BOOK . " a on  a.address_book_id = c.customers_default_address_id left join " . TABLE_ADMIN . " ad on ad.admin_id=c.admin_id where c.customers_id = '" . (int) $customers_id . "'");
        $customers = tep_db_fetch_array($customers_query);

        if (!is_array($customers)) {
            die("Wrong customer data.");
        }

        $info_query = tep_db_query("select customers_info_date_account_created as date_account_created, customers_info_date_account_last_modified as date_account_last_modified, customers_info_date_of_last_logon as date_last_logon, customers_info_number_of_logons as number_of_logons from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . $customers['customers_id'] . "'");
        $info = tep_db_fetch_array($info_query);

        $country_query = tep_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_id = '" . (int) $customers['entry_country_id'] . "' and language_id = '" . (int) $languages_id . "'");
        $country = tep_db_fetch_array($country_query);

        $reviews_query = tep_db_query("select count(*) as number_of_reviews from " . TABLE_REVIEWS . " where customers_id = '" . (int) $customers['customers_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);

        $customer_info = array_merge($country, $info, $reviews);
        $cInfo_array = array_merge($customers, $customer_info);
        $cInfo = new \objectInfo($cInfo_array);

        echo tep_draw_form('customers', FILENAME_CUSTOMERS, \common\helpers\Output::get_all_get_params(array('action')) . 'action=update', 'post', 'id="customers_edit" onSubmit="return deleteCustomer();"');
        echo '<div class="or_box_head">' . TEXT_INFO_HEADING_DELETE_CUSTOMER . '</div>';
        echo '<div class="col_desc">' . TEXT_DELETE_INTRO . '</div>';
        echo '<div class="col_desc">' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</div>';
        if (isset($cInfo->number_of_reviews) && ($cInfo->number_of_reviews) > 0) {
            echo '<div class="main_row">';
            echo '<div class="main_title">' . sprintf(TEXT_DELETE_REVIEWS, $cInfo->number_of_reviews) . '</div>';
            echo '<div class="main_value">' . tep_draw_checkbox_field('delete_reviews', 'on', true) . '</div>';
            echo '</div>';
        }
        ?>
        <p class="btn-toolbar">
        <?php
        echo '<input type="submit" class="btn btn-primary" value="' . IMAGE_DELETE . '" >';
        echo '<input type="button" class="btn btn-cancel" value="' . IMAGE_CANCEL . '" onClick="return resetStatement()">';

        echo tep_draw_hidden_field('customers_id', $cInfo->customers_id);
        ?>
        </p>
        </form>
        <?php
    }

    public function actionGeneratepassword() {
        $messageStack = \Yii::$container->get('message_stack');

        \common\helpers\Translation::init('admin/customers');

        $customers_id = tep_db_prepare_input($_POST['cID']);
        $check_customer_query = tep_db_query("select customers_firstname, customers_lastname, customers_password, customers_id ,customers_email_address, platform_id from " . TABLE_CUSTOMERS . " where customers_id = '" . $customers_id . "'");
        if (tep_db_num_rows($check_customer_query)) {
            $check_customer = tep_db_fetch_array($check_customer_query);
            if (trim($_POST['change_pass']) == '') {
                $new_password = \common\helpers\Password::create_random_value(ENTRY_PASSWORD_MIN_LENGTH);
            } else {
                $new_password = $_POST['change_pass'];
            }
            $crypted_password = \common\helpers\Password::encrypt_password($new_password);
            tep_db_query("update " . TABLE_CUSTOMERS . " set customers_password = '" . tep_db_input($crypted_password) . "' where customers_id = '" . (int) $check_customer['customers_id'] . "'");

            $platform_config = Yii::$app->get('platform')->config($check_customer['platform_id']);

            $eMail_store = $platform_config->const_value('STORE_NAME');
            $eMail_address = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
            $eMail_store_owner = $platform_config->const_value('STORE_OWNER');



            $email_params = array();
            $email_params['STORE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link(''));
            $email_params['CUSTOMER_FIRSTNAME'] = $check_customer['customers_firstname'];
            $email_params['CUSTOMER_LASTNAME'] = $check_customer['customers_lastname'];
            $email_params['NEW_PASSWORD'] = $new_password;
            $email_params['STORE_NAME'] = $eMail_store;

            list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Account update', $email_params);

            //$email_text = sprintf(TEXT_EMAIL_ACCOUNT_UPDATE, $check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], HTTP_CATALOG_SERVER . DIR_WS_CATALOG, $new_password, $eMail_store);

            \common\helpers\Mail::send($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $check_customer['customers_email_address'], $email_subject, $email_text, $eMail_store_owner, $eMail_address, [], '', '', ['add_br' => 'no']);
            $messageStack->add_session(PASSWORD_SENT_MESSAGE, 'header', 'success');
        }
        //$this->redirect(array('customers/customeractions', 'customers_id'=>  $customers_id));
        echo json_encode(array('customers_id' => $customers_id));
    }

    public function actionSendCoupon() {
        $messageStack = \Yii::$container->get('message_stack');
        $this->layout = false;
        if (Yii::$app->request->isPost) {
            $customers_id = Yii::$app->request->post('customers_id', 0);
        } else {
            $customers_id = Yii::$app->request->get('customers_id', 0);
        }

        if ($customers_id) {

            \common\helpers\Translation::init('admin/coupon_admin');

            $customers_query = tep_db_query("select c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_email_address from " . TABLE_CUSTOMERS . " c left join " . TABLE_ADMIN . " ad on ad.admin_id=c.admin_id where c.customers_id = '" . (int) $customers_id . "' " . (tep_session_is_registered("login_affiliate") ? " and c.affiliate_id = '" . $login_id . "'" : ''));
            $customers = tep_db_fetch_array($customers_query);
            if (Yii::$app->request->isPost) {

                $currentPlatformId = \Yii::$app->get('platform')->config()->getId();
                $platform_config = \Yii::$app->get('platform')->config($currentPlatformId);

                $STORE_NAME = $platform_config->const_value('STORE_NAME');
                $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
                $STORE_OWNER = $platform_config->const_value('STORE_OWNER');

                $email_text = TEXT_VOUCHER_IS . ' ' . $_POST['coupon_code'] . "\n" .
                        TEXT_TO_REDEEM . "\n" .
                        TEXT_REMEMBER . "\n";

                if (tep_not_null($_POST['coupon_message'])) {
                    $email_text .= "\n" . strip_tags($_POST['coupon_message']);
                }
                $subject = (tep_not_null($_POST['coupon_subject']) ? $_POST['coupon_subject'] : sprintf(TEXT_SUBJECT_CODE, $STORE_NAME));

                \common\helpers\Mail::send($customers['customers_firstname'] . ' ' . $customers['customers_lastname'], $customers['customers_email_address'], $subject, $email_text, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS);

                $messageStack->add_session(MESSAGE_COUPON_SENT, 'header', 'success');

                echo json_encode(array('customers_id' => $customers_id));

                exit();
            }
        }

        return $this->render('send-coupon.tpl', ['customers' => $customers]);
    }

    /**
     * Autocomplete - filter by group
     */
    public function actionGroup() {
        $term = tep_db_prepare_input(Yii::$app->request->get('term'));

        $q = \common\models\Groups::find()->select('groups_name')->distinct();

        /** @var \common\extensions\ExtraGroups\ExtraGroups $ExtraGroups */
        if ($ExtraGroups = \common\helpers\Acl::checkExtension('ExtraGroups', 'allowed')) {
          if ($ExtraGroups::allowed()) {
            $q->orderBy('groups_type_id');
          }
        }

        if (!empty($term)) {
            $q->andWhere(['like', 'groups_name', tep_db_input($term)]);
        }
        $groups = $q->addOrderBy('groups_name')->column();

        echo json_encode($groups);
    }

    public function actionCountries() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $term = tep_db_prepare_input(Yii::$app->request->get('term'));

        $search = "1";
        if (!empty($term)) {
            $search = "c.countries_name like '%" . tep_db_input($term) . "%'";
        }

        $countries = array();
        $address_query = tep_db_query("select c.countries_name as country from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_COUNTRIES . " c on ab.entry_country_id=c.countries_id  and c.language_id = '" . (int) $languages_id . "' left join " . TABLE_ZONES . " z on z.zone_country_id=c.countries_id and ab.entry_zone_id=z.zone_id where " . $search . " group by c.countries_name order by c.countries_name");
        while ($response = tep_db_fetch_array($address_query)) {
            if (!empty($response['country'])) {
                $countries[] = $response['country'];
            }
        }
        echo json_encode($countries);
    }

    public function actionState() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $term = tep_db_prepare_input(Yii::$app->request->get('term'));
        $country = tep_db_prepare_input(Yii::$app->request->get('country'));

        $search = "1";
        if (!empty($country)) {
            $search = "c.countries_name like '%" . tep_db_input($country) . "%'";
        }
        if (!empty($term)) {
            $search .= " and (ab.entry_state like '%" . tep_db_input($term) . "%' or z.zone_name like '%" . tep_db_input($term) . "%')";
        }

        $states = array();
        $address_query = tep_db_query("select if (LENGTH(ab.entry_state), ab.entry_state, z.zone_name) as state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_COUNTRIES . " c on ab.entry_country_id=c.countries_id  and c.language_id = '" . (int) $languages_id . "' left join " . TABLE_ZONES . " z on z.zone_country_id=c.countries_id and ab.entry_zone_id=z.zone_id where " . $search . " group by state order by state");
        while ($response = tep_db_fetch_array($address_query)) {
            if (!empty($response['state'])) {
                $states[] = $response['state'];
            }
        }
        echo json_encode($states);
    }

    public function actionCity() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $term = tep_db_prepare_input(Yii::$app->request->get('term'));
        $country = tep_db_prepare_input(Yii::$app->request->get('country'));
        $state = tep_db_prepare_input(Yii::$app->request->get('state'));

        $search = "1";
        if (!empty($country)) {
            $search = "c.countries_name like '%" . tep_db_input($country) . "%'";
        }
        if (!empty($state)) {
            $search .= " and (ab.entry_state like '%" . tep_db_input($state) . "%' or z.zone_name like '%" . tep_db_input($state) . "%')";
        }
        if (!empty($term)) {
            $search = "ab.entry_city like '%" . tep_db_input($term) . "%'";
        }

        $cities = array();
        $address_query = tep_db_query("select ab.entry_city as city from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_COUNTRIES . " c on ab.entry_country_id=c.countries_id  and c.language_id = '" . (int) $languages_id . "' left join " . TABLE_ZONES . " z on z.zone_country_id=c.countries_id and ab.entry_zone_id=z.zone_id where " . $search . " group by city order by city");
        while ($response = tep_db_fetch_array($address_query)) {
            if (!empty($response['city'])) {
                $cities[] = $response['city'];
            }
        }

        echo json_encode($cities);
    }

    public function actionCompany() {
        $term = tep_db_prepare_input(Yii::$app->request->get('term'));

        $search = "1";
        if (!empty($term)) {
            $search = "entry_company like '%" . tep_db_input($term) . "%'";
        }

        $companies = array();
        $address_query = tep_db_query("select entry_company from " . TABLE_ADDRESS_BOOK . " where " . $search . " group by entry_company order by entry_company");
        while ($response = tep_db_fetch_array($address_query)) {
            if (!empty($response['entry_company'])) {
                $companies[] = $response['entry_company'];
            }
        }
        echo json_encode($companies);
    }

    public function actionStates() {
        $term = tep_db_prepare_input(Yii::$app->request->get('term'));
        $country = (int) Yii::$app->request->get('country');

        $search = "1";
        if ($country > 0) {
            $search = "zone_country_id = '" . $country . "'";
        }
        if (!empty($term)) {
            $search .= " and zone_name like '%" . tep_db_input($term) . "%'";
        }

        $states = array();
        $address_query = tep_db_query("SELECT zone_name FROM " . TABLE_ZONES . " where " . $search . " group by zone_name order by zone_name");
        while ($response = tep_db_fetch_array($address_query)) {
            if (!empty($response['zone_name'])) {
                $states[] = $response['zone_name'];
            }
        }
        echo json_encode($states);
    }

    public function actionCredithistory() {
        $customers_id = (int) Yii::$app->request->get('customers_id');
        $type = Yii::$app->request->get('type', 'credit');
        $type = (($type == 'credit' ) ? 0 : 1 );

        \common\helpers\Translation::init('admin/customers');
        $this->view->headingTitle = HEADING_TITLE;

        $this->layout = false;

        $currencies = Yii::$container->get('currencies');

        $history = [];
        $customer_history_query = tep_db_query("select * from " . TABLE_CUSTOMERS_CREDIT_HISTORY . " where customers_id='" . $customers_id . "' and credit_type = '{$type}' order by customers_credit_history_id DESC ");
        while ($customer_history = tep_db_fetch_array($customer_history_query)) {
            $admin = '';
            if ($customer_history['admin_id'] > 0) {
                $check_admin_query = tep_db_query("select * from " . TABLE_ADMIN . " where admin_id = '" . (int) $customer_history['admin_id'] . "'");
                $check_admin = tep_db_fetch_array($check_admin_query);
                if (is_array($check_admin)) {
                    $admin = $check_admin['admin_firstname'] . ' ' . $check_admin['admin_lastname'];
                }
            }
            $history[] = [
                'date' => ($type ? \common\helpers\Date::datepicker_date($customer_history['date_added']) : \common\helpers\Date::datetime_short($customer_history['date_added'])),
                'credit' => $customer_history['credit_prefix'] . ($customer_history['credit_type'] == '0' ? $currencies->format($customer_history['credit_amount'], true, $customer_history['currency'], $customer_history['currency_value']) : $customer_history['credit_amount']),
                'notified' => $customer_history['customer_notified'],
                'comments' => $customer_history['comments'],
                'admin' => $admin,
            ];
        }

        if ($type) {
            if (defined('BONUS_ACTION_PROGRAM_STATUS') && BONUS_ACTION_PROGRAM_STATUS == 'true') {
                $_history = \common\models\promotions\PromotionsBonusHistory::find()->where('customer_id = :id', [':id' => (int) $customers_id])->asArray()->orderBy(['promotions_bonus_history_id' => SORT_DESC])->all();
                if ($_history) {
                    $titles = [];
                    foreach ($_history as $h) {
                        if (!isset($titles[$h['bonus_points_id']])) {
                            $titles[$h['bonus_points_id']] = \common\models\promotions\PromotionsBonusPoints::find()->where('bonus_points_id = ' . (int) $h['bonus_points_id'])->with('description')->one();
                        }
                        $history[] = [
                            'date' => \common\helpers\Date::datepicker_date($h['action_date']),
                            'credit' => '+' . $h['bonus_points_award'],
                            'notified' => 1,
                            'comments' => $titles[$h['bonus_points_id']]->description->points_title,
                            'admin' => '',
                        ];
                    }
                    //\yii\helpers\ArrayHelper::multisort($history, 'date');
                }
            }
        }

        return $this->render('credithistory', ['history' => $history]);
    }

    public function actionCustomermerge() {
        if ($ext = \common\helpers\Acl::checkExtension('MergeCustomers', 'actionCustomermerge')) {
            return $ext::actionCustomermerge();
        }
        return $this->redirect(Yii::$app->urlManager->createUrl(['customers/']));
    }

    public function actionCustomerMergeInfo() {
        if ($ext = \common\helpers\Acl::checkExtension('MergeCustomers', 'actionCustomerMergeInfo')) {
            return $ext::actionCustomerMergeInfo();
        }
    }

    public function actionDoCustomerMerge() {
        if ($ext = \common\helpers\Acl::checkExtension('MergeCustomers', 'actionDoCustomerMerge')) {
            return $ext::actionDoCustomerMerge();
        }
    }

    public function actionCustomerAdditionalFields() {
        \common\helpers\Translation::init('admin/customers');

        $this->selectedMenu = array('customers', 'customers');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('customers/index'), 'title' => TRADE_FORM);

        $customers_id = intval(Yii::$app->request->get('customers_id'));

        $additionalFields = \common\helpers\Customer::get_additional_fields_tree($customers_id);
        $addresses = \common\helpers\Customer::get_address_book_data($customers_id);

        $customer = tep_db_fetch_array(tep_db_query("select customers_firstname, customers_lastname, platform_id, customers_email_address, customers_telephone, customers_company, customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_id = '" . $customers_id . "'"));


        foreach ($addresses as $key => $item) {
            //$addresses[$key]['address'] = $item['street_address'] . ($item['street_address'] ? ', ' : '') . $item['city'] . ($item['city'] ? ', ' : '') . $item['state'] . ($item['state'] ? ', ' : '') . $item['suburb'] . ($item['suburb'] ? ', ' : '') . $item['country'];

            $addresses[$key]['address'] = \common\helpers\Address::address_format(\common\helpers\Address::get_address_format_id($item['country_id']), $item, 1, ' ', ',');
        }

        $countries = \common\helpers\Country::get_countries();
        $fields = array();
        foreach ($additionalFields as $group) {
            foreach ($group['child'] as $item) {
                $item['group'] = $group['name'];
                $fields[$item['code']] = $item;
            }
        }

        define('THEME_NAME', 'theme-1');

        return $this->render('customer-additional-fields', [
                    'customers_id' => $customers_id,
                    'additionalFields' => $additionalFields,
                    'addresses' => $addresses,
                    'customer' => $customer,
                    'fields' => $fields,
                    'countries' => $countries
        ]);
    }

    public function actionCustomerAdditionalFieldsSubmit() {
        $customers_id = Yii::$app->request->post('customers_id');
        $fields = tep_db_prepare_input(Yii::$app->request->post('field'));

        \common\helpers\Translation::init('admin/customers');
        $messageType = 'success';
        $message = SUCCESS_CUSTOMERUPDATED;

        foreach ($fields as $id => $value) {
            $check_query = tep_db_query("SELECT value FROM " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS . " WHERE customers_id = '" . (int) $customers_id . "' AND additional_fields_id = '" . (int) $id . "'");
            if (tep_db_num_rows($check_query) > 0) {
                tep_db_query("update " . TABLE_CUSTOMERS_ADDITIONAL_FIELDS . " set value = '" . tep_db_input($value) . "' where customers_id = '" . (int) $customers_id . "' AND additional_fields_id = '" . (int) $id . "'");
            } else {
                $sql_data_array = [
                    'additional_fields_id' => (int) $id,
                    'customers_id' => (int) $customers_id,
                    'value' => $value,
                ];
                tep_db_perform(TABLE_CUSTOMERS_ADDITIONAL_FIELDS, $sql_data_array);
            }
        }
        ?>
        <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?= $messageType ?>">
        <?= $message ?>
                    </div>
                </div>
                <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK; ?></span></div>
                </div>
            </div>
            <script>
                //$('body').scrollTop(0);
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function () {
                    $(this).parents('.pop-mess').remove();
                });
                $('.popup-box-wrap.pop-mess').css('top', $(window).scrollTop() + 200);
                setTimeout(function () {
                    $('.popup-box-wrap.pop-mess').remove();
                }, 1000)
            </script>
        </div>
        <?php
        //echo '<script> window.location.href="'. Yii::$app->urlManager->createUrl(['customers/customer-additional-fields', 'customers_id' => $customers_id]) .'";</script>';
    }

    public function actionTradeAcc() {

        $customers_id = Yii::$app->request->get('customers_id');

        $customerData = \common\helpers\Customer::getCustomerData($customers_id);

        $platform_id = $customerData['platform_id'] ?? 1;
        $theme_name = \backend\design\Theme::getThemeName($platform_id);
        $pages = [];
        $pages[] = ['name' => 'trade_form_pdf',
            'params' => [
                'platform_id' => $platform_id,
                'theme_name' => $theme_name,
                'customers_id' => $customers_id,
            ]
        ];

        \backend\design\PDFBlock::widget([
            'pages' => $pages,
            'params' => [
                'theme_name' => $theme_name,
                'document_name' => 'trade_form.pdf',
            ]
        ]);

        //\common\helpers\TradeForm::printPdf($get['customers_id']);
    }

    public function actionGdprCheck() {
        if (in_array(ACCOUNT_DOB, ['required_register', 'visible_register', 'required', 'visible'])) {//dob present
            $currentPlatformId = \Yii::$app->get('platform')->config()->getId();
            $platform_config = \Yii::$app->get('platform')->config($currentPlatformId);
            $STORE_NAME = $platform_config->const_value('STORE_NAME');
            $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
            $STORE_OWNER = $platform_config->const_value('STORE_OWNER');

            $check_customer_query = tep_db_query("select customers_id, customers_dob, customers_firstname, customers_lastname, customers_email_address, opc_temp_account, customers_status from " . TABLE_CUSTOMERS . " where opc_temp_account = 0 and dob_flag = 0 and customers_dob > '" . date('Y-m-d', strtotime('-13 years')) . "' || customers_dob = '0000-00-00 00:00:00'");
            while ($check_customer = tep_db_fetch_array($check_customer_query)) {
                if (/* $check_customer['opc_temp_account'] == 1 || */ $check_customer['customers_status'] == 0) {
                    \common\helpers\Customer::deleteCustomer($check_customer['customers_id'], false); //delete without notification
                } else {

                    $gdpr_check_query = tep_db_query("select * from gdpr_check where customers_id = '" . (int) $check_customer['customers_id'] . "'");
                    if (tep_db_num_rows($gdpr_check_query) == 0) {
                        do {
                            $new_token = \common\helpers\Password::create_random_value(32);
                            $token_check_query = tep_db_query("select token from gdpr_check where token = '" . $new_token . "'");
                        } while (tep_db_num_rows($token_check_query) > 0);
                        $sql_data_array = [
                            'customers_id' => (int) $check_customer['customers_id'],
                            'email' => $check_customer['customers_email_address'],
                            'date_send' => 'now()',
                            'token' => $new_token,
                        ];
                        tep_db_perform('gdpr_check', $sql_data_array);
                        //send email

                        $email_params = array();
                        $email_params['STORE_NAME'] = $STORE_NAME;
                        $email_params['STORE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('', '', 'NONSSL'/* , $store['store_url'] */));
                        $email_params['CUSTOMER_FIRSTNAME'] = $check_customer['customers_firstname'];
                        $email_params['STORE_OWNER_EMAIL_ADDRESS'] = $STORE_OWNER_EMAIL_ADDRESS;
                        $email_params['HTTP_HOST'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('account/update', 'token=' . $new_token, 'SSL'));
                        ;
                        list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('GDPR update request', $email_params);

                        \common\helpers\Mail::send($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $check_customer['customers_email_address'], $email_subject, $email_text, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS);
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->urlManager->createUrl(['customers/']));
    }

    public function actionGdprCleanup() {
        if (in_array(ACCOUNT_DOB, ['required_register', 'visible_register', 'required', 'visible'])) {//dob present
            $check_customer_query = tep_db_query("select customers_id, customers_dob, customers_firstname, customers_lastname, customers_email_address, opc_temp_account, customers_status from " . TABLE_CUSTOMERS . " where opc_temp_account = 0 and dob_flag = 0 and customers_dob > '" . date('Y-m-d', strtotime('-13 years')) . "' || customers_dob = '0000-00-00 00:00:00'");
            while ($check_customer = tep_db_fetch_array($check_customer_query)) {
                if (/* $check_customer['opc_temp_account'] == 1 || */ $check_customer['customers_status'] == 0) {
                    \common\helpers\Customer::deleteCustomer($check_customer['customers_id'], false); //delete without notification
                } else {
                    \common\helpers\Customer::deleteCustomer($check_customer['customers_id']); //delete with notification
                }
            }
        }
        return $this->redirect(Yii::$app->urlManager->createUrl(['customers/']));
    }

    public function actionCustomerProductsSave() {
      $ret = '';
      $cId = intval(\Yii::$app->request->get('customers_id', 0));
      if ($cId > 0 ) {
        /** @var \common\extensions\CustomerProducts\CustomerProducts $ext */
        if ($ext = \common\helpers\Acl::checkExtension('CustomerProducts', 'saveCustomerProducts')) {
          if ($ext::allowed()) {

            $products = array_map('intval', \Yii::$app->request->post('customer_products', []));

            $ret = $ext::saveCustomerProducts($cId, $products);
          }
        }
      }
      return $ret;
    }

    public function actionCustomerProducts() {
      $ret = '';
      
      /** @var \common\extensions\CustomerProducts\CustomerProducts $ext */
      if ($ext = \common\helpers\Acl::checkExtension('CustomerProducts', 'viewCustomerProducts')) {

        $cInfo = new \objectInfo(['customers_id' => intval(\Yii::$app->request->get('customers_id'))]);

        $ret = $ext::viewCustomerProducts($cInfo);
      }
      return $ret;
    }

    public function actionSearchAjax() {
      $ret = '';
      $prod_restricted = (int)\Yii::$app->request->get('prod_restricted', 0);
      $q = \Yii::$app->request->get('q');

      $cQ = (new \yii\db\Query())->select('customers_id, customers_firstname, customers_lastname, customers_email_address, customers_alt_email_address, customers_status')
          ->from(TABLE_CUSTOMERS)
          ->andWhere([
            'or',
            ['like', 'customers_firstname', tep_db_input($q)],
            ['like', 'customers_lastname', tep_db_input($q)],
            ['like', 'customers_email_address', tep_db_input($q)],
            ['like', 'customers_alt_email_address', tep_db_input($q)]
          ])
          ->orderBy('customers_status desc, customers_lastname, customers_firstname, customers_email_address')
          ->limit(20)
          ;

      /** @var \common\extensions\CustomerProducts\CustomerProducts $ext  */
      if ($prod_restricted > 0 && $ext = \common\helpers\Acl::checkExtension('CustomerProducts', 'allowed')) {
        if($ext::allowed() ) {
          $cQ->andWhere('restrict_products=1');
        }
      }
      //echo $cQ->createCommand()->rawSql;
      $customers = $cQ->all();
      if (is_array($customers) && !empty($customers)) {
        foreach ($customers as $c) {
          $option = '';
          if ($c['customers_status'] == 0) {
            $option .= ' class="dis_mod"';
          }
          $ret .= '<a data-id="' . $c['customers_id'] . '" ' . $option . '>' . implode(' ', [$c['customers_lastname'], $c['customers_firstname'], $c['customers_email_address']]) . '</a><br />';
        }
      }
      return $ret;
    }

    public function actionMoveBonusPointsToAmount()
    {
        $result = true;
        try {
            /** @var \common\classes\Currencies $currencies */
            $currencies = \Yii::$container->get('currencies');
            /** @var \common\classes\MessageStack $messageStack */
            $messageStack = \Yii::$container->get('message_stack');
            $transferPoints = (int)\Yii::$app->request->post('bonus', 0);
            $customerId = (int)\Yii::$app->request->post('customerId', 0);
            $notifyBonus = \Yii::$app->request->post('notifyBonus', '') === 'on';
            $notifyAmount = \Yii::$app->request->post('notifyAmount', '') === 'on';
            /** @var \common\services\CustomersService $customerService */
            $customersService = \Yii::createObject(\common\services\CustomersService::class);
            /** @var Customer $customer */
            $customer = $customersService->getIdentityById($customerId);
            /** @var \common\services\BonusPointsService\BonusPointsService $bonusPointsService */
            $bonusPointsService = \Yii::createObject(\common\services\BonusPointsService\BonusPointsService::class);
            if ($customer === false) {
                throw new \DomainException('Operation not allowed');
            }
            $bonusPointsCosts = \common\helpers\Points::getCurrencyCoefficient($customer->groups_id);
            if ($bonusPointsCosts === false) {
                throw new \DomainException('Operation not allowed');
            }
            if ($transferPoints < 1 || $transferPoints > $customer->customers_bonus_points) {
                $transferPoints = $customer->customers_bonus_points;
            }
            $transfer = TransferData::create($customer, $bonusPointsCosts, $transferPoints, $notifyBonus, $notifyAmount);
            $amount = $customersService->bonusPointsToAmount($transfer);
            $platform_config = \Yii::$app->get('platform')->config($customer->platform_id);
            $STORE_OWNER_EMAIL_ADDRESS = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
            $STORE_OWNER = $platform_config->const_value('STORE_OWNER');
            $email_params['STORE_NAME'] = $STORE_OWNER;
            $email_params['CUSTOMER_FIRSTNAME'] = $customer->customers_firstname;
            $email_params['CUSTOMER_LASTNAME']= $customer->customers_lastname;
            if ($notifyAmount) {
                $email_params['CREDIT_AMOUNT'] = '+' . $currencies->format($amount, false);
                $email_params['CREDIT_AMOUNT_COMMENTS'] = TEXT_CONVERT_FROM_BONUS_POINTS;
                [$emailSubject, $emailContent] = \common\helpers\Mail::get_parsed_email_template('Credit amount notification', $email_params, $customer->language_id, $customer->platform_id);
                \common\helpers\Mail::send($customer->customers_firstname . ' ' . $customer->customers_lastname, $customer->customers_email_address, $emailSubject, $emailContent, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS, [], '', '', ['add_br' => 'no']);
            }

            if ($notifyBonus) {
                $email_params['BONUS_POINTS'] = '-' . $transferPoints;
                $email_params['BONUS_POINTS_COMMENTS'] = TEXT_CONVERT_TO_AMOUNT;
                [$emailSubject, $emailContent] = \common\helpers\Mail::get_parsed_email_template('Bonus points notification', $email_params, $customer->language_id, $customer->platform_id);
                \common\helpers\Mail::send($customer->customers_firstname . ' ' . $customer->customers_lastname, $customer->customers_email_address, $emailSubject, $emailContent, $STORE_OWNER, $STORE_OWNER_EMAIL_ADDRESS, [], '', '', ['add_br' => 'no']);
            }
            $messageStack->add_session(sprintf(TRANSFER_BONUS_SUCCESS, $transfer->getBonusPoints(), $currencies->format($amount, false)), 'account', 'success');
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }
        $this->asJson([
            'result' => $result,
        ]);
    }
}

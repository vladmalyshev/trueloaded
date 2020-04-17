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

class CompareReportController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_COMPARE'];

    public function __construct($id, $module = null) {
        \common\helpers\Translation::init('ordertotal');
        \common\helpers\Translation::init('admin/compare-report');
        parent::__construct($id, $module);
    }

    public function actionIndex() {
        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('reports', 'compare-report');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('compare-report/index'), 'title' => BOX_REPORTS_COMPARE);
        $this->view->headingTitle = BOX_REPORTS_COMPARE;

        $this->view->filter = new \stdClass();

        $this->view->filter->period = [
            'daily' => DAILY,
            'weekly' => WEEKLY,
            'monthly' => MONTHLY,
        ];
        $period = Yii::$app->request->get('period');
        if (!isset($this->view->filter->period[$period])) {
           
            $period = 'daily';
        }
        $this->view->filter->period_selected = $period;

        $range = (int)Yii::$app->request->get('range');
        if ($range == 0) {
            $range = 5;
        }
        $this->view->filter->range = [
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            '11' => '11',
            '12' => '12',
        ];
        $this->view->filter->range_selected = $range;

        switch ($period) {
            case 'daily':
                $day = Yii::$app->request->get('day');
                if (empty($day)) {
                    $day = date('d/m/Y');
                }
                $options = Yii::$app->controller->renderAjax('day', ['day' => $day]);
                break;
            case 'weekly':
                $week = Yii::$app->request->get('week');
                if (empty($week)) {
                    $week = date('d/m/Y', strtotime('monday this week')) . '-' . date('d/m/Y', strtotime('sunday this week'));
                }
                $options = Yii::$app->controller->renderAjax('week', ['week' => $week]);
                break;
            case 'monthly':
                $month = Yii::$app->request->get('month');
                if (empty($month)) {
                    $month = date('m/Y');
                }
                $options = Yii::$app->controller->renderAjax('month', ['month' => $month]);
                break;
            default:
                $options = '';
                break;
        }
        
        $this->view->filter->platform = array();
        if (isset($_GET['platform']) && is_array($_GET['platform'])) {
            foreach ($_GET['platform'] as $_platform_id)
                if ((int) $_platform_id > 0)
                    $this->view->filter->platform[] = (int) $_platform_id;
        }

        return $this->render('index', [
            'options' => $options,
            'isMultiPlatform' => \common\classes\platform::isMulti(),
            'platforms' => \common\classes\platform::getList(),
            ]);
    }

    public function actionLoadOptions() {
        $type = Yii::$app->request->get('type');
        switch ($type) {
            case 'daily':
                $options = Yii::$app->controller->renderAjax('day', ['day' => date('d/m/Y')]);
                break;
            case 'weekly':
                $options = Yii::$app->controller->renderAjax('week', ['week' => date('d/m/Y', strtotime('monday this week')) . '-' . date('d/m/Y', strtotime('sunday this week'))]);
                break;
            case 'monthly':
                $options = Yii::$app->controller->renderAjax('month', ['month' => date('m/Y')]);
                break;
            default:
                $options = '';
                break;
        }

        echo json_encode(['options' => $options]);
    }

    private function getOrderStatistic($date_from, $date_to, $statusCompleteIds, $search = '') {
        $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value_inc_tax * ot.currency_value) as total_sum_inc, sum(ot.value_exc_vat * ot.currency_value) as total_sum_exc from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input($date_from) . "' and o.date_purchased <= '" . tep_db_input($date_to) . "' and ot.class = 'ot_total'  and o.orders_status IN (" . $statusCompleteIds . ") " . $search));
        return $orders_amount;
    }

    public function actionGenerate() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $period = Yii::$app->request->post('period');
        $range = (int) Yii::$app->request->post('range');
        $export = Yii::$app->request->get('export', '');
        $platform = Yii::$app->request->post('platform');

        $search = '';
        if (is_array($platform) && count($platform) > 0) {
            $search .= " and o.platform_id IN ('" . implode("', '", $platform) . "') ";
        }
        //---
        $platform_config = new \common\classes\platform_config(\common\classes\platform::defaultId());
        $platform_config->constant_up();
        //---
        $completeStatuses = \common\models\OrdersStatus::find()
                ->where(['orders_status_groups_id' => [\common\models\OrdersStatusGroups::PROCESSING_GROUP, \common\models\OrdersStatusGroups::COMPLETE_GROUP]])
                ->andWhere(['language_id' => $languages_id])
                ->all();
        $statusCompleteIds = \yii\helpers\ArrayHelper::getColumn($completeStatuses, 'orders_status_id');
        $statusCompleteIds = implode(',', $statusCompleteIds);
        //---
        $payment_methods = [];
        $payment_methods_query = tep_db_query("select distinct payment_class from " . TABLE_ORDERS . " where 1 order by payment_class");
        $manager = \common\services\OrderManager::loadManager();
        if (tep_db_num_rows($payment_methods_query)) {
            $payment_modules = $manager->getPaymentCollection();
            while ($row = tep_db_fetch_array($payment_methods_query)) {
                $_payment = $row['payment_class'];
                if (empty($_payment))
                    continue;
                $module = $payment_modules->getModule($_payment);
                if (!is_object($module)){
                    list($pmodule, $method) = explode('_', $_payment);
                    $module = $payment_modules->getModule($pmodule);
                }
                if ($module){
                    if (method_exists($module, 'getTitle')) {
                        $payment_methods[$_payment] = $module->getTitle($_payment);
                    } else {
                        $payment_methods[$_payment] = $module->title;
                    }
                } else{
                    $payment_methods[$_payment] = $_payment;
                }
            }
        }
        //---
        /* $taxClasses = [];
          $classes_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
          if ( tep_db_num_rows($classes_query)>0 ) {
          while ($classes = tep_db_fetch_array($classes_query)) {
          $taxClasses[$classes['tax_class_id']] = $classes['tax_class_title'];
          }
          } */
        $taxClasses = [
            'Tax:' => 'VAT IRL 0%',
            'VAT @ 13.5%:' => 'VAT IRL 13.5%',
            'VAT @ 23%:' => 'VAT IRL 23%',
        ];
        //---

        $currencies = Yii::$container->get('currencies');

        $tableSales = [];
        $tableTax = [];
        $tablePayment = [];

        switch ($period) {
            case 'daily':
                $day = Yii::$app->request->post('day');

                $ex = explode('/', $day);
                $startDay = $ex[0];
                $startMonth = $ex[1];
                $startYear = $ex[2];

                $rowDate = date('Y', mktime(0, 0, 0, $startMonth, $startDay - $range, $startYear));
                if ($rowDate != $startYear) {
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $rowDateTmp = date('Y', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear));
                        if ($rowDateTmp == $startYear) {
                            $range = $i + 1;
                            break;
                        }
                    }
                }
                
                //---

                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row1[] = 'Sales';
                $row2[] = 'Tax';
                $row3[] = 'Payment Type';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $rowDate = date('M j', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear));
                    $row1[] = $rowDate;
                    $row2[] = $rowDate;
                    $row3[] = $rowDate;
                }
                $tableSales[] = $row1;
                unset($row1);
                $tableTax[] = $row2;
                unset($row2);
                $tablePayment[] = $row3;
                unset($row3);

                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row4 = [];
                $row5 = [];
                $row6 = [];
                $row7 = [];
                $row8 = [];
                $row1[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' EX VAT';
                $row2[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' Inc VAT';
                $row3[] = 'Last Week';
                $row4[] = '% Change';
                $row5[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                $row6[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear - 1));
                $row7[] = '% Change';
                $row8[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear)), $statusCompleteIds, $search);
                    $row1[] = $currencies->format($orders_amount['total_sum_exc']);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $orders_amount_last_week = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i - 7, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i - 7, $startYear)), $statusCompleteIds, $search);
                    $row3[] = $currencies->format($orders_amount_last_week['total_sum_inc']);
                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_week['total_sum_inc'] == 0) {
                        $row4[] = '-';
                    } else {
                        $row4[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_week['total_sum_inc'], 2)) / round($orders_amount_last_week['total_sum_inc'], 2), 2) . '%';
                    }
                    $row5[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_week['total_sum_inc'], 2));

                    $orders_amount_last_year = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear - 1)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear - 1)), $statusCompleteIds, $search);
                    $row6[] = $currencies->format($orders_amount_last_year['total_sum_inc']);

                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_year['total_sum_inc'] == 0) {
                        $row7[] = '-';
                    } else {
                        $row7[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2)) / round($orders_amount_last_year['total_sum_inc'], 2), 2) . '%';
                    }
                    $row8[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2));
                }
                $tableTax[] = $row1;
                $tableSales[] = $row2;
                $tableSales[] = $row3;
                $tableSales[] = $row4;
                $tableSales[] = $row5;
                $tableSales[] = $row6;
                $tableSales[] = $row7;
                $tableSales[] = $row8;
                unset($row1);
                unset($row2);
                unset($row3);
                unset($row4);
                unset($row5);
                unset($row6);
                unset($row7);
                unset($row8);

                //---

                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($taxClasses as $tax_class_id => $tax_class_title) {
                    $row1 = [];
                    $row1[] = $tax_class_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        //'and ot.tax_class_id="'.$tax_class_id.'"'
                        $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title="' . $tax_class_id . '"' . $search));
                        $row1[] = $currencies->format($orders_amount['total_sum']);
                        $totalSum[$i] += round($orders_amount['total_sum'], 2);
                    }
                    $tableTax[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    //'and ot.tax_class_id NOT IN ("'.implode('","', array_keys($taxClasses)).'")'
                    $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title NOT IN ("' . implode('","', array_keys($taxClasses)) . '")' . $search));
                    $row2[] = $currencies->format($orders_amount['total_sum']);
                    $totalSum[$i] += round($orders_amount['total_sum'], 2);
                }
                $tableTax[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tableTax[] = $row3;
                unset($row3);

                //---

                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($payment_methods as $payment_class => $payment_title) {
                    $row1 = [];
                    $row1[] = $payment_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear)), $statusCompleteIds, 'and o.payment_class="' . $payment_class . '"' . $search);
                        $row1[] = $currencies->format($orders_amount['total_sum_inc']);
                        $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                    }
                    $tablePayment[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth, $startDay - $i, $startYear)), $statusCompleteIds, 'and o.payment_class NOT IN ("' . implode('","', array_keys($payment_methods)) . '")' . $search);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                }
                $tablePayment[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tablePayment[] = $row3;
                unset($row3);
                //---

                break;
            case 'weekly':
                $week = Yii::$app->request->post('week');
                
                list($firstDay, $lastDay) = explode('-', $week);
                $ex = explode('/', $firstDay);
                $startDay = $ex[0];
                $startMonth = $ex[1];
                $startYear = $ex[2];
                
                $ex = explode('/', $lastDay);
                $endDay = $ex[0];
                $endMonth = $ex[1];
                $endYear = $ex[2];
                
                $rowDate = date('Y', mktime(0, 0, 0, $startMonth, $startDay - $range * 7, $startYear));
                if ($rowDate != $startYear) {
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $rowDateTmp = date('Y', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear));
                        if ($rowDateTmp == $startYear) {
                            $range = $i + 1;
                            break;
                        }
                    }
                }
                
                //---
                
                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row1[] = 'Sales';
                $row2[] = 'Tax';
                $row3[] = 'Payment Type';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $rowDate = 'Week ' . date('W', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear));
                    $row1[] = $rowDate;
                    $row2[] = $rowDate;
                    $row3[] = $rowDate;
                }
                $tableSales[] = $row1;
                unset($row1);
                $tableTax[] = $row2;
                unset($row2);
                $tablePayment[] = $row3;
                unset($row3);
                
                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row4 = [];
                $row5 = [];
                $row6 = [];
                $row7 = [];
                $row8 = [];
                $row1[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' EX VAT';
                $row2[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' Inc VAT';
                $row3[] = 'Last Week';
                $row4[] = '% Change';
                $row5[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                $row6[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear - 1));
                $row7[] = '% Change';
                $row8[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear)), $statusCompleteIds, $search);
                    $row1[] = $currencies->format($orders_amount['total_sum_exc']);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $orders_amount_last_week = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7 - 7, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7 - 7, $endYear)), $statusCompleteIds, $search);
                    $row3[] = $currencies->format($orders_amount_last_week['total_sum_inc']);
                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_week['total_sum_inc'] == 0) {
                        $row4[] = '-';
                    } else {
                        $row4[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_week['total_sum_inc'], 2)) / round($orders_amount_last_week['total_sum_inc'], 2), 2) . '%';
                    }
                    $row5[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_week['total_sum_inc'], 2));

                    $orders_amount_last_year = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear - 1)), date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear - 1)), $statusCompleteIds, $search);
                    $row6[] = $currencies->format($orders_amount_last_year['total_sum_inc']);

                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_year['total_sum_inc'] == 0) {
                        $row7[] = '-';
                    } else {
                        $row7[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2)) / round($orders_amount_last_year['total_sum_inc'], 2), 2) . '%';
                    }
                    $row8[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2));
                }
                $tableTax[] = $row1;
                $tableSales[] = $row2;
                $tableSales[] = $row3;
                $tableSales[] = $row4;
                $tableSales[] = $row5;
                $tableSales[] = $row6;
                $tableSales[] = $row7;
                $tableSales[] = $row8;
                unset($row1);
                unset($row2);
                unset($row3);
                unset($row4);
                unset($row5);
                unset($row6);
                unset($row7);
                unset($row8);

                //---
                
                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($taxClasses as $tax_class_id => $tax_class_title) {
                    $row1 = [];
                    $row1[] = $tax_class_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        //'and ot.tax_class_id="'.$tax_class_id.'"'
                        $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title="' . $tax_class_id . '"' . $search));
                        $row1[] = $currencies->format($orders_amount['total_sum']);
                        $totalSum[$i] += round($orders_amount['total_sum'], 2);
                    }
                    $tableTax[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    //'and ot.tax_class_id NOT IN ("'.implode('","', array_keys($taxClasses)).'")'
                    $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title NOT IN ("' . implode('","', array_keys($taxClasses)) . '")' . $search));
                    $row2[] = $currencies->format($orders_amount['total_sum']);
                    $totalSum[$i] += round($orders_amount['total_sum'], 2);
                }
                $tableTax[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tableTax[] = $row3;
                unset($row3);

                //---
                
                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($payment_methods as $payment_class => $payment_title) {
                    $row1 = [];
                    $row1[] = $payment_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear)), $statusCompleteIds, 'and o.payment_class="' . $payment_class . '"' . $search);
                        $row1[] = $currencies->format($orders_amount['total_sum_inc']);
                        $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                    }
                    $tablePayment[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth, $startDay - $i * 7, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $endMonth, $endDay - $i * 7, $endYear)), $statusCompleteIds, 'and o.payment_class NOT IN ("' . implode('","', array_keys($payment_methods)) . '")' . $search);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                }
                $tablePayment[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tablePayment[] = $row3;
                unset($row3);
                
                //---
                break;
            case 'monthly':
                $month = Yii::$app->request->post('month');

                $ex = explode('/', $month);
                $startDay = 1;
                $startMonth = $ex[0];
                $startYear = $ex[1];
                
                $rowDate = date('Y', mktime(0, 0, 0, $startMonth - $range, $startDay, $startYear));
                if ($rowDate != $startYear) {
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $rowDateTmp = date('Y', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear));
                        if ($rowDateTmp == $startYear) {
                            $range = $i + 1;
                            break;
                        }
                    }
                }
                
                //---
                
                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row1[] = 'Sales';
                $row2[] = 'Tax';
                $row3[] = 'Payment Type';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $rowDate = date('M', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear));
                    $row1[] = $rowDate;
                    $row2[] = $rowDate;
                    $row3[] = $rowDate;
                }
                $tableSales[] = $row1;
                unset($row1);
                $tableTax[] = $row2;
                unset($row2);
                $tablePayment[] = $row3;
                unset($row3);

                $row1 = [];
                $row2 = [];
                $row3 = [];
                $row4 = [];
                $row5 = [];
                $row6 = [];
                $row7 = [];
                $row8 = [];
                $row1[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' EX VAT';
                $row2[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear)) . ' Inc VAT';
                $row3[] = 'Last Month';
                $row4[] = '% Change';
                $row5[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                $row6[] = date('Y', mktime(0, 0, 0, $startMonth, $startDay, $startYear - 1));
                $row7[] = '% Change';
                $row8[] = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'] . ' Change ' . $currencies->currencies[DEFAULT_CURRENCY]['symbol_right'];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear)), $statusCompleteIds, $search);
                    $row1[] = $currencies->format($orders_amount['total_sum_exc']);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $orders_amount_last_month = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i - 1, $startDay, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i, 0, $startYear)), $statusCompleteIds, $search);
                    $row3[] = $currencies->format($orders_amount_last_month['total_sum_inc']);
                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_month['total_sum_inc'] == 0) {
                        $row4[] = '-';
                    } else {
                        $row4[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_month['total_sum_inc'], 2)) / round($orders_amount_last_month['total_sum_inc'], 2), 2) . '%';
                    }
                    $row5[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_month['total_sum_inc'], 2));

                    $orders_amount_last_year = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear - 1)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear - 1)), $statusCompleteIds, $search);
                    $row6[] = $currencies->format($orders_amount_last_year['total_sum_inc']);

                    if ($orders_amount['total_sum_inc'] == 0 || $orders_amount_last_year['total_sum_inc'] == 0) {
                        $row7[] = '-';
                    } else {
                        $row7[] = round(100 * (round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2)) / round($orders_amount_last_year['total_sum_inc'], 2), 2) . '%';
                    }
                    $row8[] = $currencies->format(round($orders_amount['total_sum_inc'], 2) - round($orders_amount_last_year['total_sum_inc'], 2));
                }
                $tableTax[] = $row1;
                $tableSales[] = $row2;
                $tableSales[] = $row3;
                $tableSales[] = $row4;
                $tableSales[] = $row5;
                $tableSales[] = $row6;
                $tableSales[] = $row7;
                $tableSales[] = $row8;
                unset($row1);
                unset($row2);
                unset($row3);
                unset($row4);
                unset($row5);
                unset($row6);
                unset($row7);
                unset($row8);

                //---
                
                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($taxClasses as $tax_class_id => $tax_class_title) {
                    $row1 = [];
                    $row1[] = $tax_class_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        //'and ot.tax_class_id="'.$tax_class_id.'"'
                        $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title="' . $tax_class_id . '"' . $search));
                        $row1[] = $currencies->format($orders_amount['total_sum']);
                        $totalSum[$i] += round($orders_amount['total_sum'], 2);
                    }
                    $tableTax[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    //'and ot.tax_class_id NOT IN ("'.implode('","', array_keys($taxClasses)).'")'
                    $orders_amount = tep_db_fetch_array(tep_db_query("select sum(ot.value * ot.currency_value) as total_sum from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id) where o.date_purchased >= '" . tep_db_input(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear))) . "' and o.date_purchased <= '" . tep_db_input(date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear))) . "' and ot.class = 'ot_tax'  and o.orders_status IN (" . $statusCompleteIds . ") " . 'and ot.title NOT IN ("' . implode('","', array_keys($taxClasses)) . '")' . $search));
                    $row2[] = $currencies->format($orders_amount['total_sum']);
                    $totalSum[$i] += round($orders_amount['total_sum'], 2);
                }
                $tableTax[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tableTax[] = $row3;
                unset($row3);

                //---
                $totalSum = [];
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $totalSum[$i] = 0;
                }
                foreach ($payment_methods as $payment_class => $payment_title) {
                    $row1 = [];
                    $row1[] = $payment_title;
                    for ($i = ($range - 1); $i >= 0; $i--) {
                        $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear)), $statusCompleteIds, 'and o.payment_class="' . $payment_class . '"' . $search);
                        $row1[] = $currencies->format($orders_amount['total_sum_inc']);
                        $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                    }
                    $tablePayment[] = $row1;
                    unset($row1);
                }

                $row2 = [];
                $row2[] = 'Other';
                for ($i = ($range - 1); $i >= 0; $i--) {
                    $orders_amount = $this->getOrderStatistic(date('Y-m-d H:i:s', mktime(0, 0, 0, $startMonth - $i, $startDay, $startYear)), date('Y-m-d H:i:s', mktime(23, 59, 59, $startMonth - $i + 1, 0, $startYear)), $statusCompleteIds, 'and o.payment_class NOT IN ("' . implode('","', array_keys($payment_methods)) . '")' . $search);
                    $row2[] = $currencies->format($orders_amount['total_sum_inc']);
                    $totalSum[$i] += round($orders_amount['total_sum_inc'], 2);
                }
                $tablePayment[] = $row2;
                unset($row2);
                $row3 = [];
                $row3[] = 'Total';
                foreach ($totalSum as $totalRow) {
                    $row3[] = $currencies->format($totalRow);
                }
                $tablePayment[] = $row3;
                unset($row3);
                
                //---
                break;
            default:
                break;
        }

        if ($export == 'CSV') {
            $filename = 'compare_report_' . strftime('%Y%b%d_%H%M') . '.csv';
            $CSV = new \backend\models\EP\Formatter\CSV('write', array(), $filename);
            
            header('Content-Filename: ' . $filename);

            foreach ($tableSales as $row) {
                $CSV->write_array($row);
            }
            $CSV->write_array([]);
            foreach ($tableTax as $row) {
                $CSV->write_array($row);
            }
            $CSV->write_array([]);
            foreach ($tablePayment as $row) {
                $CSV->write_array($row);
            }
            
            exit;
        } elseif ($export == 'XLS') {
            
        }
        
        $platformTitle = 'All';
        if (is_array($platform) && count($platform) == 1) {
            $platformTitle = '';
            $platformList = \common\classes\platform::getList();
            foreach ($platformList as $item) {
                if (in_array($item['id'], $platform)) {
                    $platformTitle .= $item['text'];
                }
            }
        }
                
        return $this->renderAjax('generate', ['tables' => [$tableSales, $tableTax, $tablePayment], 'platformTitle' => $platformTitle]);
    }

}

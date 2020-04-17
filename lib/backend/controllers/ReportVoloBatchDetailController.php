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
use common\classes\platform;

class ReportVoloBatchDetailController extends Sceleton
{

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORT_VOLO_BATCH_DETAIL'];

    public function __construct($id, $module = null)
    {
        \common\helpers\Translation::init('admin/report-volo-batch-detail');
        parent::__construct($id, $module);
    }

    public $start_date;
    public $end_date;

    public function actionIndex()
    {
        $this->selectedMenu = array('reports', 'report-volo-batch-detail');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('report-volo-batch-detail/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $platforms = platform::getList(true);
        $this->view->filters = new \stdClass();
        $this->start_date = isset($_GET['start_date']) ? tep_db_input(trim($_GET['start_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
        $this->end_date = isset($_GET['end_date']) ? tep_db_input(trim($_GET['end_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-d'))));
        /*$by = [
            BOX_ => [
                [
                'label' => TEXT_,
                'name' => 'name',
                'selected' => '',
                'type' => 'text'
                ]
            ]
        ];
        foreach ($by as $label => $items) {
            foreach($items as $key => $item) {
                if (isset($_GET[$item['name']])) {
                    if ($by[$label][$key]['type'] == 'text') {
                        $by[$label][$key]['value'] = $_GET[$item['name']];
                    } elseif($by[$label][$key]['type'] == 'dropdown') {
                        $by[$label][$key]['selected'] = $_GET[$item['name']];
                    }
                }
            }
        }
        $this->view->filters->by = $by;*/
        $this->view->filters->row = (int)$_GET['row'];
        $this->view->reportTable = $this->getTable();
        return $this->render('index', [
            'platforms' => $platforms,
            'default_platform_id' => platform::defaultId(),
            'isMultiPlatforms' => platform::isMulti()
        ]);
    }

    function getOrderCompleteStatus()
    {
        if (defined('ORDER_COMPLETE_STATUSES')) {
            $statuses = preg_split("/[, ]/", ORDER_COMPLETE_STATUSES);
            $statuses = is_array($statuses) ? $statuses : array();
            foreach ($statuses as $key => $value) {
                $value = trim($value);
                if (empty($value)) {
                    unset($statuses[$key]);
                } else {
                    $statuses[$key] = $value;
                }
            }
            return $statuses;
        }
        return array();
    }

    public function getTable()
    {
        return array(
            array(
                'title' => TEXT_VBDR_DATE,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_ORDER_ID,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_NETT,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_ZERO,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_VAT,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_GROSS,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_SHIPPING,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_INSURANCE,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDR_BANKING,
                'not_important' => 0,
            )
        );
    }

    public function build($full = false)
    {
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        if ($length == -1) {
            $length = 10000;
        }
        $output = [];
        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);
        if (is_null($formFilter) && count($_GET)) {
            foreach ($_GET as $key => $value) {
                $output[$key] = $value;
            }
        }
        $itemQuery = "select o.`orders_id`, o.`date_purchased`"
            . " from `" . TABLE_ORDERS . "` o"
            . " where o.`orders_status` in ('" . implode("','", $this->getOrderCompleteStatus()) . "')";
        if ($output['start_date'] != '') {
            $start_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['start_date']));
            $itemQuery .= " and o.`date_purchased` >= '" . $start_date . " 00:00:00'";
        }
        if ($output['end_date'] != '') {
            $end_date = \common\helpers\Date::prepareInputDate(tep_db_prepare_input($output['end_date']));
            $itemQuery .= " and o.`date_purchased` <= '" . $end_date . " 23:59:59'";
        }
        $filter_by_platform = array();
        if (isset($output['platform']) && is_array($output['platform'])) {
            foreach ($output['platform'] as $_platform_id) {
                if ((int) $_platform_id > 0) {
                    $filter_by_platform[] = (int) $_platform_id;
                }
            }
        }
        if (count($filter_by_platform) > 0) {
            $itemQuery .= " and o.platform_id IN ('" . implode("', '", $filter_by_platform) . "') ";
        }
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = " o.date_purchased " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                break;
                case 1:
                    $orderBy = " o.orders_id " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                break;
                default:
                    $orderBy = " o.date_purchased asc ";
                break;
            }
        } else {
            $orderBy = " o.date_purchased asc ";
        }
        $itemQuery .= " order by" . $orderBy;
        $current_page_number = ($start / $length) + 1;
        $itemQuery_numrows = 0;
        if (!$full) {
            new \splitPageResults($current_page_number, $length, $itemQuery, $itemQuery_numrows);
        }
        $itemQuery = tep_db_query($itemQuery);
        $responseList = array(
            'total' => [
                0, 0, 0, 0, 0, 0, 0
            ]
        );
        $currencies = Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        while ($row = tep_db_fetch_array($itemQuery)) {
            $totalQuery = "SELECT ot.currency AS currency, ot.value_inc_tax AS gross, ot.value_exc_vat AS nett,"
                    . " otv.value_inc_tax AS vat, ots.value_inc_tax AS shipping, otp.value_inc_tax AS paid, otr.value_inc_tax AS refund"
                . " FROM " . TABLE_ORDERS_TOTAL . " ot"
                . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otv ON (ot.orders_id = otv.orders_id AND otv.class = 'ot_tax')"
                . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " ots ON (ot.orders_id = ots.orders_id AND ots.class = 'ot_shipping')"
                . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otp ON (ot.orders_id = otp.orders_id AND otp.class = 'ot_paid')"
                . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otr ON (ot.orders_id = otr.orders_id AND otr.class = 'ot_refund')"
                . " WHERE ot.orders_id = " . (int)$row['orders_id'] . " AND ot.class = 'ot_total'";
            if ($totalQuery = tep_db_query($totalQuery)) {
                $totalQuery = tep_db_fetch_array($totalQuery);
            }
            $totalQuery = (is_array($totalQuery) ? $totalQuery : array());
            $rate = 1;
            if (isset($totalQuery['currency']) AND trim($totalQuery['currency']) != '') {
                $rate = $currencies->get_market_price_rate($totalQuery['currency'], $currency);
            }
            $totalQuery['nett'] = (float)(isset($totalQuery['nett']) ? $totalQuery['nett'] : 0) * $rate;
            $totalQuery['zero'] = 0;
            $totalQuery['vat'] = (float)(isset($totalQuery['vat']) ? $totalQuery['vat'] : 0) * $rate;
            if ($totalQuery['vat'] == 0) {
                $totalQuery['zero'] = $totalQuery['nett'];
                $totalQuery['nett'] = 0;
            }
            $totalQuery['gross'] = (float)(isset($totalQuery['gross']) ? $totalQuery['gross'] : 0) * $rate;
            $totalQuery['shipping'] = (float)(isset($totalQuery['shipping']) ? $totalQuery['shipping'] : 0) * $rate;
            $totalQuery['insurance'] = (float)(isset($totalQuery['insurance']) ? $totalQuery['insurance'] : 0) * $rate;
            $totalQuery['paid'] = (float)(isset($totalQuery['paid']) ? $totalQuery['paid'] : 0);
            $totalQuery['refund'] = (float)(isset($totalQuery['refund']) ? $totalQuery['refund'] : 0);
            $totalQuery['banking'] = ($totalQuery['paid'] - $totalQuery['refund']) * $rate;
            $responseList['total'][0] += $totalQuery['nett'];
            $responseList['total'][1] += $totalQuery['zero'];
            $responseList['total'][2] += $totalQuery['vat'];
            $responseList['total'][3] += $totalQuery['gross'];
            $responseList['total'][4] += $totalQuery['shipping'];
            $responseList['total'][5] += $totalQuery['insurance'];
            $responseList['total'][6] += $totalQuery['banking'];
            $responseList[] = array(
                \common\helpers\Date::date_short($row['date_purchased']),
                '<a target="_blank" href="' . Yii::$app->urlManager->createUrl('orders/process-order?orders_id=' . $row['orders_id']) . '">' . $row['orders_id'] . '</a>',
                $currencies->format($totalQuery['nett']),
                $currencies->format($totalQuery['zero']),
                $currencies->format($totalQuery['vat']),
                $currencies->format($totalQuery['gross']),
                $currencies->format($totalQuery['shipping']),
                $currencies->format($totalQuery['insurance']),
                $currencies->format($totalQuery['banking']),
            );
            unset($totalQuery);
        }
        if ($itemQuery) {
            tep_db_free_result($itemQuery);
            unset($itemQuery);
        }
        $responseList[] = array(
            TEXT_AMOUNT,
            '',
            $currencies->format($responseList['total'][0]),
            $currencies->format($responseList['total'][1]),
            $currencies->format($responseList['total'][2]),
            $currencies->format($responseList['total'][3]),
            $currencies->format($responseList['total'][4]),
            $currencies->format($responseList['total'][5]),
            $currencies->format($responseList['total'][6])
        );
        unset($responseList['total']);
        return ['responseList' => $responseList, 'count' => $itemQuery_numrows];
    }

    public function actionList()
    {
        $draw = Yii::$app->request->get('draw', 1);
        $data = $this->build();
        $responseList = $data['responseList'];
        $response = array(
            'draw' => $draw,
            'recordsTotal' => (int)$data['count'],
            'recordsFiltered' => (int)$data['count'],
            'data' => $responseList
        );
        echo json_encode($response);
    }

    public function actionExport()
    {
        $data = $this->build(true);
        $head = $this->getTable();
        $writer = new \backend\models\EP\Formatter\CSV('write', array(), 'expo.csv');
        $a = [];
        foreach ($head as $m) {
            $a[] = $m['title'];
        }
        $writer->write_array($a);
        foreach ($data['responseList'] as $row) {
            $newArray = array_map(function($v) {
                $vv = trim(strip_tags($v));
                $vv = str_replace(['&nbsp;&raquo;&nbsp;', '&nbsp;',], [' / ', '',], $vv);
                return $vv;
            }, $row);
            $writer->write_array($newArray);
        }
        exit();
    }
}
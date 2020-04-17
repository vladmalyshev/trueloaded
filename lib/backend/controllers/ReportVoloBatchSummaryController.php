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

class ReportVoloBatchSummaryController extends Sceleton
{

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORT_VOLO_BATCH_SUMMARY'];

    public function __construct($id, $module = null)
    {
        \common\helpers\Translation::init('admin/report-volo-batch-summary');
        parent::__construct($id, $module);
    }

    public $start_date;
    public $end_date;

    public function actionIndex()
    {
        $this->selectedMenu = array('reports', 'report-volo-batch-summary');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('report-volo-batch-summary/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        $platforms = platform::getList(true);
        $this->view->filters = new \stdClass();
        $this->start_date = isset($_GET['start_date']) ? tep_db_input(trim($_GET['start_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-01'))));
        $this->end_date = isset($_GET['end_date']) ? tep_db_input(trim($_GET['end_date'])) : date(DATE_FORMAT_DATEPICKER_PHP, strtotime((date('Y-m-d'))));
        $by = [
            TEXT_VBDS_ORDER_BOX => [
                [
                'label' => TEXT_VBDS_ORDER,
                'name' => 'orders_id',
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
        $this->view->filters->by = $by;
        $this->view->filters->row = (int)$_GET['row'];
        $this->view->reportTable = $this->getTable();
        return $this->render('index', [
            'platforms' => $platforms,
            'default_platform_id' => platform::defaultId(),
            'isMultiPlatforms' => platform::isMulti()
        ]);
    }

    function actionJournalStatus()
    {
        $this->layout = false;
        $orderId = (int)Yii::$app->request->get('orders_id', 0);
        if ($orderId > 0) {
            $data = $this->build();
            $return = ['status' => ''];
            if (isset($data['responseListValue']['total'])) {
                if ($data['responseListValue']['total']['debit'] > 0 OR $data['responseListValue']['total']['credit'] > 0) {
                    if ($data['responseListValue']['total']['debit'] == $data['responseListValue']['total']['credit']) {
                        $return = ['status' => 'ok'];
                    } else {
                        $return = ['status' => 'error'];
                    }
                }
            }
            echo json_encode($return);
        }
        die();
    }

    function actionPopup()
    {
        $this->layout = false;
        $orderId = (int)Yii::$app->request->get('orders_id', 0);
        if ($orderId > 0) {
            $data = $this->build();
            $this->view->reportTable = $this->getTable();
            $this->view->reportData = $data['responseList'];
            return $this->render('popup');
        }
        die();
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
                'title' => TEXT_VBDS_DATE,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDS_NOMINAL,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDS_DESCRIPTION,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDS_DEBIT,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDS_CREDIT,
                'not_important' => 0,
            ),
            array(
                'title' => TEXT_VBDS_TAX_CODE,
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
        $itemQuery = "select date_format(o.`date_purchased`, '%Y-%m-%d') as date_purchased, group_concat(o.`orders_id`) as orders_id_array"
            . " from `" . TABLE_ORDERS . "` o"
            . " where o.`orders_status` in ('" . implode("','", $this->getOrderCompleteStatus()) . "')";
        if ((int)$output['orders_id'] > 0) {
            $itemQuery .= " and o.`orders_id` = '" . (int)$output['orders_id'] . "'";
        } else {
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
                    default:
                        $orderBy = " o.date_purchased asc ";
                    break;
                }
            } else {
                $orderBy = " o.date_purchased asc ";
            }
            $itemQuery .= " group by date_format(o.`date_purchased`, '%Y%m%d')";
            $itemQuery .= " order by" . $orderBy;
        }
        $itemQuery_numrows = 0;
        if (!$full) {
            $count = tep_db_fetch_array(tep_db_query('SELECT COUNT(*) AS `count` FROM (' . $itemQuery . ') A'));
            $itemQuery_numrows = ($count['count'] ?? 0);
            $current_page_number = ($start / $length) + 1;
            if ($current_page_number < 0 || ($current_page_number > ceil($itemQuery_numrows / $length))){
                $current_page_number = 1;
            }
            $offset = ($length * ($current_page_number - 1));
            if ($length > 0) {
                $itemQuery .= " limit " . max($offset, 0) . ", " . $length;
            }
        }
        $itemQuery = tep_db_query($itemQuery);
        $responseList = array(
            'date' => [],
            'total' => [
                0, 0
            ]
        );
        $currencies = Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        while ($row = tep_db_fetch_array($itemQuery)) {
            $datePurchase = $row['date_purchased'];
            if (trim($datePurchase) !== date('Y-m-d', strtotime($datePurchase))) {
                continue;
            }
            foreach (explode(',', $row['orders_id_array']) as $orderId) {
                if ((int)$orderId <= 0) {
                    continue;
                }
                $totalQuery = "SELECT ot.currency AS currency, ot.value_inc_tax AS total, otu.value_exc_vat AS nett,"
                        . " otv.value_inc_tax AS vat, ots.value_exc_vat AS carriage, otp.value_inc_tax AS paid, otr.value_inc_tax AS refund"
                    . " FROM " . TABLE_ORDERS_TOTAL . " ot"
                    . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otu ON (ot.orders_id = otu.orders_id AND otu.class = 'ot_subtotal')"
                    . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otv ON (ot.orders_id = otv.orders_id AND otv.class = 'ot_tax')"
                    . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " ots ON (ot.orders_id = ots.orders_id AND ots.class = 'ot_shipping')"
                    . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otp ON (ot.orders_id = otp.orders_id AND otp.class = 'ot_paid')"
                    . " LEFT JOIN " . TABLE_ORDERS_TOTAL . " otr ON (ot.orders_id = otr.orders_id AND otr.class = 'ot_refund')"
                    . " WHERE ot.orders_id = " . (int)$orderId . " AND ot.class = 'ot_total'";
                if ($totalQuery = tep_db_query($totalQuery)) {
                    $totalQuery = tep_db_fetch_array($totalQuery);
                }
                $totalQuery = (is_array($totalQuery) ? $totalQuery : array());
                if (!isset($totalQuery['total']) OR (float)$totalQuery['total'] <= 0) {
                    continue;
                }
                $rate = 1;
                if (isset($totalQuery['currency']) AND trim($totalQuery['currency']) != '') {
                    $rate = $currencies->get_market_price_rate($totalQuery['currency'], $currency);
                }
                $totalQuery['total'] = (float)(isset($totalQuery['total']) ? $totalQuery['total'] : 0) * $rate;
                $totalQuery['nett'] = (float)(isset($totalQuery['nett']) ? $totalQuery['nett'] : 0) * $rate;
                $totalQuery['vat'] = (float)(isset($totalQuery['vat']) ? $totalQuery['vat'] : 0) * $rate;
                $totalQuery['carriage'] = (float)(isset($totalQuery['carriage']) ? $totalQuery['carriage'] : 0) * $rate;
                $totalQuery['paid'] = (float)(isset($totalQuery['paid']) ? $totalQuery['paid'] : 0);
                $totalQuery['refund'] = (float)(isset($totalQuery['refund']) ? $totalQuery['refund'] : 0);
                $totalQuery['banking'] = ($totalQuery['paid'] - $totalQuery['refund']) * $rate;
                $orderPaymentRecordArray = \common\helpers\OrderPayment::getArrayByOrderId($orderId);
                if (count($orderPaymentRecordArray) == 0 AND (round($totalQuery['total'], 2) <= round($totalQuery['banking'], 2))) {
                    $transactionArray = [
                        'nominal' => 1230,
                        'decription' => TEXT_VBDS_PAYMENT_CASH,
                        'debit' => $totalQuery['banking'],
                        'credit' => 0,
                        'tax_code' => 'T9'
                    ];
                    $responseList['date'][$datePurchase]['total'] = $responseList['date'][$datePurchase]['total'] ?? [0, 0];
                    $responseList['date'][$datePurchase][] = $transactionArray;
                    $responseList['date'][$datePurchase]['total'][0] += $totalQuery['banking'];
                    $responseList['total'][0] += $totalQuery['banking'];
                    unset($transactionArray);
                    /*if (round($totalQuery['total'], 2) < round($totalQuery['banking'], 2)) {
                            $transactionArray = [
                            'nominal' => 2107,
                            'decription' => TEXT_VBDS_PAYMENT_CREDITORS,
                            'debit' => 0,
                            'credit' => ($totalQuery['banking'] - $totalQuery['total']),
                            'tax_code' => 'T9'
                        ];
                        $responseList['date'][$datePurchase]['total'] = $responseList['date'][$datePurchase]['total'] ?? [0, 0];
                        $responseList['date'][$datePurchase][] = $transactionArray;
                        $responseList['date'][$datePurchase]['total'][1] += ($totalQuery['banking'] - $totalQuery['total']);
                        $responseList['total'][1] += ($totalQuery['banking'] - $totalQuery['total']);
                        unset($transactionArray);
                    }*/
                } elseif (round($totalQuery['total'], 2) > round($totalQuery['banking'], 2)) {
                    $transactionArray = [
                        'nominal' => 1107,
                        'decription' => TEXT_VBDS_PAYMENT_DEBTORS,
                        'debit' => ($totalQuery['total'] - $totalQuery['banking']),
                        'credit' => 0,
                        'tax_code' => 'T9'
                    ];
                    $responseList['date'][$datePurchase]['total'] = $responseList['date'][$datePurchase]['total'] ?? [0, 0];
                    $responseList['date'][$datePurchase][] = $transactionArray;
                    $responseList['date'][$datePurchase]['total'][0] += ($totalQuery['total'] - $totalQuery['banking']);
                    $responseList['total'][0] += ($totalQuery['total'] - $totalQuery['banking']);
                    unset($transactionArray);
                }
                foreach ($orderPaymentRecordArray as $orderPaymentRecord) {
                    if (!in_array($orderPaymentRecord['orders_payment_status'], [
                        \common\helpers\OrderPayment::OPYS_SUCCESSFUL,
                        \common\helpers\OrderPayment::OPYS_REFUNDED,
                        \common\helpers\OrderPayment::OPYS_DISCOUNTED
                    ]) OR $orderPaymentRecord['orders_payment_amount'] <= 0) {
                        continue;
                    }
                    $isCredit = $orderPaymentRecord['orders_payment_is_credit'];
                    if ($isCredit == 0) {
                        $nominal = 1102;
                        $decription = TEXT_VBDS_PAYMENT_OTHER_DEBTOR;
                    } else {
                        $nominal = 2102;
                        $decription = TEXT_VBDS_PAYMENT_OTHER_CREDITOR;
                    }
                    if (stripos($orderPaymentRecord['orders_payment_module'], 'paypal') !== false) {
                        $nominal = 1210;
                        $decription = TEXT_VBDS_PAYMENT_PAYPAL;
                    } elseif (stripos($orderPaymentRecord['orders_payment_module'], 'braintree') !== false) {
                        $nominal = 1215;
                        $decription = TEXT_VBDS_PAYMENT_BRAINTREE;
                    }
                    $transactionArray = [
                        'nominal' => $nominal,
                        'decription' => $decription,
                        'debit' => 0,
                        'credit' => 0,
                        'tax_code' => 'T9'
                    ];
                    $responseList['date'][$datePurchase]['total'] = $responseList['date'][$datePurchase]['total'] ?? [0, 0];
                    $amount = ($orderPaymentRecord['orders_payment_amount'] * $orderPaymentRecord['orders_payment_currency_rate']);
                    $transactionArray[$isCredit ? 'credit' : 'debit'] = $amount;
                    $responseList['date'][$datePurchase][] = $transactionArray;
                    $responseList['date'][$datePurchase]['total'][$isCredit] += $amount;
                    $responseList['total'][$isCredit] += $amount;
                    unset($transactionArray);
                }
                $responseList['date'][$datePurchase][] = [
                    'nominal' => 2200,
                    'decription' => TEXT_VBDS_VAT,
                    'debit' => 0,
                    'credit' => $totalQuery['vat'],
                    'tax_code' => 'T1'
                ];
                $responseList['date'][$datePurchase][] = [
                    'nominal' => 4003,
                    'decription' => TEXT_VBDS_SALE_WEBSITE,
                    'debit' => 0,
                    'credit' => $totalQuery['nett'],
                    'tax_code' => ($totalQuery['vat'] > 0 ? 'T1' : 'T4')
                ];
                $responseList['date'][$datePurchase][] = [
                    'nominal' => 4906,
                    'decription' => TEXT_VBDS_CARRIAGE,
                    'debit' => 0,
                    'credit' => $totalQuery['carriage'],
                    'tax_code' => ($totalQuery['vat'] > 0 ? 'T1' : 'T4')
                ];
                $responseList['date'][$datePurchase]['total'] = $responseList['date'][$datePurchase]['total'] ?? [0, 0];
                $responseList['date'][$datePurchase]['total'][1] += ($totalQuery['vat'] + $totalQuery['nett'] + $totalQuery['carriage']);
                $responseList['total'][1] += ($totalQuery['vat'] + $totalQuery['nett'] + $totalQuery['carriage']);
                unset($totalQuery);
            }
        }
        if ($itemQuery) {
            tep_db_free_result($itemQuery);
            unset($itemQuery);
        }
        $responseListValue = [];
        foreach ($responseList['date'] as $datePurchase => $dateArray) {
            $isDateDisplay = true;
            $dateTotalArray = $dateArray['total'];
            if ($dateTotalArray[0] <= 0 AND $dateTotalArray[1] <= 0) {
                continue;
            }
            unset($dateArray['total']);
            usort($dateArray, function($left, $right) {
                return ($left['nominal'] - $right['nominal']);
            });
            foreach ($dateArray as $movementArray) {
                if ($movementArray['debit'] <= 0 AND $movementArray['credit'] <= 0) {
                    continue;
                }
                $responseListValue[$datePurchase][] = $movementArray;
                $responseList[] = array(
                    ($isDateDisplay ? \common\helpers\Date::date_short($datePurchase) : ''),
                    $movementArray['nominal'],
                    $movementArray['decription'],
                    $currencies->format($movementArray['debit']),
                    $currencies->format($movementArray['credit']),
                    $movementArray['tax_code']
                );
                $isDateDisplay = false;
            }
            $responseListValue[$datePurchase]['total'] = ['debit' => $dateTotalArray[0], 'credit' => $dateTotalArray[1]];
            $responseList[] = array(
                (\common\helpers\Date::date_short($datePurchase) . ' ' . TEXT_VBDS_TOTALS),
                '',
                '',
                $currencies->format($dateTotalArray[0]),
                $currencies->format($dateTotalArray[1]),
                ''
            );
            $responseList[] = array('', '', '', '', '', '');
        }
        unset($responseList['date']);
        $responseListValue['total'] = ['debit' => $responseList['total'][0], 'credit' => $responseList['total'][1]];
        $responseList[] = array(
            TEXT_AMOUNT,
            '',
            '',
            $currencies->format($responseList['total'][0]),
            $currencies->format($responseList['total'][1]),
            ''
        );
        unset($responseList['total']);
        return ['responseList' => $responseList, 'count' => $itemQuery_numrows, 'responseListValue' => $responseListValue];
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
<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\label;

use common\classes\modules\ModuleLabel;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use Yii;

require_once 'dhl/DhlApi.php';

class dhl extends ModuleLabel {

    public $title;
    public $description;
    public $code = 'dhl';
    public $can_update_shipment = false;
    public $can_cancel_shipment = true;
    private $_API = null;

    public function __construct() {
        $this->title = 'DHL'; //MODULE_LABEL_DHL_TEXT_TITLE;
        $this->description = 'DHL'; //MODULE_LABEL_DHL_TEXT_DESCRIPTION;
        $this->_API = new \DhlApi();
        $this->selectDhlAccount();
    }

    public function configure_keys() {
        return array(
            'MODULE_LABEL_DHL_STATUS' =>
            array(
                'title' => 'Enable DHL Labels',
                'value' => 'True',
                'description' => 'Do you want to offer DHL labels?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_LABEL_DHL_MODE' =>
            array(
                'title' => 'Mode',
                'value' => 'staging',
                'description' => 'Mode',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'staging\', \'production\'), ',
            ),
            'MODULE_LABEL_DHL_SITE_ID' =>
            array(
                'title' => 'Site ID',
                'value' => '',
                'description' => 'Site ID',
                'sort_order' => '1',
            ),
            'MODULE_LABEL_DHL_PASSWORD' =>
            array(
                'title' => 'Password',
                'value' => '',
                'description' => 'Password',
                'sort_order' => '2',
            ),
            'MODULE_LABEL_DHL_ACCOUNT_NUMBER' =>
              array (
                'title' => 'Account Number',
                'value' => '',
                'description' => 'Account Number',
                'sort_order' => '3',
              ),
            'MODULE_LABEL_DHL_LABEL_INSURED_FROM' => array(
                'title' => 'Insure',
                'value' => '-1',
                'description' => 'Insure packages over what amount? (set to -1 to disable)',
                'sort_order' => '3',
            ),
            'MODULE_LABEL_DHL_REQUEST_ARCHIVE_DOC' =>
            array(
                'title' => 'Request Archive Doc',
                'value' => 'True',
                'description' => 'Request Archive Doc',
                'sort_order' => '4',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_LABEL_DHL_LABEL_TEMPLATE' =>
            array(
                'title' => 'Label Template',
                'value' => '8X4_A4_PDF',
                'description' => 'Label Template',
                'sort_order' => '4',
                'set_function' => 'tep_cfg_select_option(array(\'8X4_A4_PDF\', \'8X4_A4_TC_PDF\', \'8X4_CI_PDF\', \'6X4_A4_PDF\', \'6X4_PDF\', \'8X4_PDF\'), ',
            ),
            'MODULE_LABEL_DHL_LABEL_RESOLUTION' =>
            array(
                'title' => 'Label Resolution',
                'value' => '200',
                'description' => 'Label Resolution',
                'sort_order' => '4',
                'set_function' => 'tep_cfg_select_option(array(\'200\', \'300\'), ',
            ),
            'MODULE_LABEL_DHL_SORT_ORDER' =>
            array(
                'title' => 'DHL Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '10',
            ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_LABEL_DHL_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_LABEL_DHL_SORT_ORDER');
    }

    private function selectDhlAccount() {
        $config = [];
        if (defined('MODULE_LABEL_DHL_MODE')) {
           $config['mode'] = MODULE_LABEL_DHL_MODE;
        }
        if (defined('MODULE_LABEL_DHL_SITE_ID')) {
           $config['site_id'] = MODULE_LABEL_DHL_SITE_ID;
        }
        if (defined('MODULE_LABEL_DHL_PASSWORD')) {
           $config['password'] = MODULE_LABEL_DHL_PASSWORD;
        }
        if (defined('MODULE_LABEL_DHL_ACCOUNT_NUMBER')) {
           $config['account_number'] = MODULE_LABEL_DHL_ACCOUNT_NUMBER;
        }
        if (defined('MODULE_LABEL_DHL_REQUEST_ARCHIVE_DOC')) {
           $config['request_archive_doc'] = MODULE_LABEL_DHL_REQUEST_ARCHIVE_DOC;
        }
        if (defined('MODULE_LABEL_DHL_LABEL_TEMPLATE')) {
           $config['label_template'] = MODULE_LABEL_DHL_LABEL_TEMPLATE;
        }
        if (defined('MODULE_LABEL_DHL_LABEL_RESOLUTION')) {
           $config['label_resolution'] = MODULE_LABEL_DHL_LABEL_RESOLUTION;
        }
        if (defined('MODULE_LABEL_DHL_LABEL_INSURED_FROM')) {
            $config['insured_from'] = MODULE_LABEL_DHL_LABEL_INSURED_FROM;
        }
        if (count($config) > 0) {
            $this->_API->setConfig($config);
        }
    }

    function extra_params() {
        $method_action = \Yii::$app->request->post('method_action', '');
        $method_value = \Yii::$app->request->post('method_value', '');
        if (!empty($method_action) && !empty($method_value)) {
            switch ($method_action) {
                case 'add':
                    Yii::$app->db->createCommand()->update('dhl_global_product_codes', ['global_product_status' => '1'], ['global_product_code' => $method_value])->execute();
                    break;
                case 'del':
                    Yii::$app->db->createCommand()->update('dhl_global_product_codes', ['global_product_status' => '0'], ['global_product_code' => $method_value])->execute();
                    break;
                default:
                    break;
            }
        }

        $global_product_title = \Yii::$app->request->post('global_product_title', array());
        if (is_array($global_product_title)) {
            foreach ($global_product_title as $key => $value) {
                Yii::$app->db->createCommand()->update('dhl_global_product_codes', ['global_product_title' => $value], ['global_product_code' => $key])->execute();
            }
        }

        $html = '';
        if (!Yii::$app->request->isAjax) {
            $html .= '<div id="modules_extra_params">';
        }

        $html .= '<h2 style="margin: 0">' . TEXT_SELECTED_METHODS . '</h2>';
        $html .= '<table width="100%" class="selected-methods">';
        $html .= '<tr><th width="5%">' . TABLE_HEADING_ACTION . '</th><th>' . TABLE_TEXT_NAME . '</th><th>' . TABLE_HEADING_TITLE . '</th></tr>';
        foreach ((new \Yii\db\Query())->select('global_product_code, global_product_name, product_content_code, doc_indicator, global_product_title')->from('dhl_global_product_codes')->where('global_product_status = 1')->orderBy('global_product_title, global_product_name, doc_indicator desc, product_content_code')->all() as $methods) {
            $html .= '<tr><td><span class="delMethod" onclick="delMethod(\'' . $methods['global_product_code'] . '\')"></span></td><td>' . ($methods['global_product_name'] . ($methods['doc_indicator'] == 'Y' ? ' - Doc' : ($methods['doc_indicator'] == 'N' ? ' - Non Doc' : '')) . ' (' . $methods['product_content_code'] . ') [' . $methods['global_product_code'] . ']') . '</td>';
            $html .= '<td><input type="text" name="global_product_title[' . $methods['global_product_code'] . ']" value="' . $methods['global_product_title'] . '" style="width:350px;"></td>';
            $html .= '</tr>';
        }
        $html .= '</table><br><br>';

        $html .= '<h2 style="margin: 0">' . TEXT_AVAILABLE_METHODS . '</h2>';

        $html .= '<input type="hidden" name="method_action" value=""><input type="hidden" name="method_value" value="">';

        $html .= tep_draw_hidden_field('set', $_GET['set']) . tep_draw_hidden_field('module', $_GET['module']);

        $methods_array = array();
        foreach ((new \Yii\db\Query())->select('global_product_code, global_product_name, product_content_code, doc_indicator, global_product_title')->from('dhl_global_product_codes')->where('global_product_status = 0')->orderBy('global_product_title, global_product_name, doc_indicator desc, product_content_code')->all() as $methods) {
            $methods_array[$methods['global_product_code']] = ($methods['global_product_title'] ? $methods['global_product_title'] : $methods['global_product_name'] . ($methods['doc_indicator'] == 'Y' ? ' - Doc' : ($methods['doc_indicator'] == 'N' ? ' - Non Doc' : '')) . ' (' . $methods['product_content_code'] . ') [' . $methods['global_product_code'] . ']');
        }
        if (count($methods_array) > 0) {
            $html .= '<div class="addMethod-table" style="width:100%"><table width="100%">';
            $html .= '<tr><th width="5%">' . TABLE_HEADING_ACTION . '</th><th>' . TABLE_TEXT_NAME . '</th></tr>';
            foreach ($methods_array as $key => $value) {
                $html .= '<tr><td><span class="addMethod" onclick="addMethod(\'' . $key . '\')"></span></td><td>' . $value . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        $html .= '<script type="text/javascript">
function filterMethods() {
    $.post("' . tep_href_link('modules/extra-params') . '", $(\'form[name=modules]\').serialize(), function(data, status) {
        if (status == "success") {
            $(\'#modules_extra_params\').html(data);
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function delMethod(code) {
    $(\'input[name="method_action"]\').val("del");
    $(\'input[name="method_value"]\').val(code);
    return filterMethods();
}
function addMethod(code) {
    $(\'input[name="method_action"]\').val("add");
    $(\'input[name="method_value"]\').val(code);
    return filterMethods();
}
</script>';

        if (!Yii::$app->request->isAjax) {
            $html .= '</div>';
        }

        return $html;
    }

    public function possibleMethods()
    {
        $possibleMethods = array();
        foreach ((new \Yii\db\Query())->select('global_product_code, global_product_name, product_content_code, doc_indicator, global_product_title')->from('dhl_global_product_codes')->where('global_product_status = 1')->orderBy('global_product_title, global_product_name, doc_indicator desc, product_content_code')->all() as $methods) {
            $possibleMethods[$this->code . '_' . $methods['global_product_code']] = ($methods['global_product_title'] ? $methods['global_product_title'] : $methods['global_product_name'] . ($methods['doc_indicator'] == 'Y' ? ' - Doc' : ($methods['doc_indicator'] == 'N' ? ' - Non Doc' : '')) . ' (' . $methods['product_content_code'] . ') [' . $methods['global_product_code'] . ']');
        }
        return $possibleMethods;
    }

    function get_methods($country_iso_code_2, $method = '', $shipping_weight = 0, $num_of_sheets = 0) {
        $methods_array = array();
        foreach ((new \Yii\db\Query())->select('global_product_code, global_product_name, product_content_code, doc_indicator, global_product_title')->from('dhl_global_product_codes')->where('global_product_status = 1')->orderBy('global_product_title, global_product_name, doc_indicator desc, product_content_code')->all() as $methods) {
            $methods_array[$this->code . '_' . $methods['global_product_code']] = ($methods['global_product_title'] ? $methods['global_product_title'] : $methods['global_product_name'] . ($methods['doc_indicator'] == 'Y' ? ' - Doc' : ($methods['doc_indicator'] == 'N' ? ' - Non Doc' : '')) . ' (' . $methods['product_content_code'] . ') [' . $methods['global_product_code'] . ']');
        }
        return $methods_array;
    }

    function create_shipment($order_id, $orders_label_id, $method = '') {
        \common\helpers\Translation::init('admin/orders');

        $manager = \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
        Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
        $manager->set('platform_id', $order->info['platform_id']);

        $return = array();
        $oLabel = \common\models\OrdersLabel::findOne(['orders_id' => $order_id, 'orders_label_id' => $orders_label_id]);
        if (tep_not_null($oLabel->tracking_number) || tep_not_null($oLabel->parcel_label_pdf)) {
            if (tep_not_null($oLabel->tracking_number)) {
                $tracking_number = $oLabel->tracking_number;
            }
            if (tep_not_null($oLabel->parcel_label_pdf)) {
                $parcel_label_pdf = base64_decode($oLabel->parcel_label_pdf);
            }
        } else {
            $result = $this->_API->_create_shipment($order, $method, $this->shipment_weight($order_id, $orders_label_id), $this->shipment_total($order_id, $orders_label_id));
            if (is_object($result)) {
                $tracking_number = $result->AirwayBillNumber;
                if (is_object($result->LabelImage)) {
                    $parcel_label_pdf = base64_decode($result->LabelImage->OutputImage);
                }
            }
            if (tep_not_null($tracking_number) || tep_not_null($parcel_label_pdf)) {
                if (tep_not_null($tracking_number)) {
                    $addTracking = \common\classes\OrderTrackingNumber::instanceFromString($tracking_number, $order_id);
                    $addTracking->setOrderProducts($oLabel->getOrdersLabelProducts());
                    $order->info['tracking_number'][] = $addTracking;
                    $order->saveTrackingNumbers();

                    $oLabel->tracking_number = $tracking_number;
                    $oLabel->tracking_numbers_id = $addTracking->tracking_numbers_id;
                }
                if (tep_not_null($parcel_label_pdf)) {
                    $oLabel->parcel_label_pdf = base64_encode($parcel_label_pdf);
                }
                $oLabel->save();
            } else {
                $return = $this->parse_errors($result);
            }
        }
        if (tep_not_null($tracking_number)) {
            $return['tracking_number'] = $tracking_number;
        }
        if (tep_not_null($parcel_label_pdf)) {
            $return['parcel_label'] = $parcel_label_pdf;
        }
        return $return;
    }

    public function cancel_shipment($order_id, $orders_label_id) {
        \common\helpers\Translation::init('admin/orders');

        $manager = \common\services\OrderManager::loadManager();
        $order = $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
        Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
        $manager->set('platform_id', $order->info['platform_id']);

        $return = array();
        $oLabel = \common\models\OrdersLabel::findOne(['orders_id' => $order_id, 'orders_label_id' => $orders_label_id]);
        if (tep_not_null($oLabel->tracking_number)) {
            $notify_comments = 'Cancelled - ' . TEXT_TRACKING_NUMBER . ' ' . $oLabel->tracking_number;

            $order->removeTrackingNumber($oLabel->tracking_numbers_id);
            $oLabel->delete();

            global $login_id;
            Yii::$app->db->createCommand()->insert(TABLE_ORDERS_STATUS_HISTORY, ['orders_id' => $order_id, 'orders_status_id' => $order->info['order_status'], 'date_added' => new Yii\db\Expression('NOW()'), 'customer_notified' => '0', 'comments' => $notify_comments, 'admin_id' => $login_id])->execute();

            $return['success'] = 'DHL: ' . $notify_comments;
        }
        return $return;
    }

    public function parse_errors($result) {
        $return = array();
        if (tep_not_null($result['error'])) {
            $return['errors'][] = 'DHL: ' . $result['error'];
        }
        return $return;
    }

    public function install($platform_id) {

        $migration = new \yii\db\Migration();
        if ($migration) {
            if (Yii::$app->db->schema->getTableSchema('dhl_global_product_codes') === null) {
                $migration->createTable('dhl_global_product_codes', [
                    'global_product_code' => $migration->char(1)->notNull(),
                    'global_product_name' => $migration->string(32)->notNull(),
                    'product_content_code' => $migration->char(3)->notNull(),
                    'doc_indicator' => $migration->char(1)->notNull(),
                    'global_product_title' => $migration->string(128)->notNull(),
                    'global_product_status' => $migration->tinyInteger(1)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'dhl_global_product_codes', ['global_product_code']);

                $migration->batchInsert('dhl_global_product_codes', ['global_product_code', 'global_product_name', 'product_content_code', 'doc_indicator', 'global_product_title', 'global_product_status'], [
                    ['0', 'LOGISTICS SERVICES', 'LOG', 'A', '', 0],
                    ['1', 'DOMESTIC EXPRESS 12:00', 'DOT', 'Y', '', 0],
                    ['2', 'B2C', 'BTC', 'Y', '', 0],
                    ['3', 'B2C', 'B2C', 'N', '', 0],
                    ['4', 'JETLINE', 'NFO', 'N', '', 0],
                    ['5', 'SPRINTLINE', 'SPL', 'Y', '', 0],
                    ['7', 'EXPRESS EASY', 'XED', 'Y', '', 0],
                    ['8', 'EXPRESS EASY', 'XEP', 'N', '', 0],
                    ['9', 'EUROPACK', 'EPA', 'Y', '', 0],
                    ['A', 'AUTO REVERSALS', 'N/A', 'A', '', 0],
                    ['B', 'BREAKBULK EXPRESS', 'BBX', 'Y', '', 0],
                    ['C', 'MEDICAL EXPRESS', 'CMX', 'Y', '', 0],
                    ['D', 'EXPRESS WORLDWIDE', 'DOX', 'Y', '', 0],
                    ['E', 'EXPRESS 9:00', 'TDE', 'N', '', 0],
                    ['F', 'FREIGHT WORLDWIDE', 'FRT', 'N', '', 0],
                    ['G', 'DOMESTIC ECONOMY SELECT', 'DES', 'Y', '', 0],
                    ['H', 'ECONOMY SELECT', 'ESI', 'N', '', 0],
                    ['I', 'DOMESTIC EXPRESS 9:00', 'DOK', 'Y', '', 0],
                    ['J', 'JUMBO BOX', 'JBX', 'N', '', 0],
                    ['K', 'EXPRESS 9:00', 'TDK', 'Y', '', 0],
                    ['L', 'EXPRESS 10:30', 'TDL', 'Y', '', 0],
                    ['M', 'EXPRESS 10:30', 'TDM', 'N', '', 0],
                    ['N', 'DOMESTIC EXPRESS', 'DOM', 'Y', '', 0],
                    ['O', 'DOMESTIC EXPRESS 10:30', 'DOL', 'Y', '', 0],
                    ['P', 'EXPRESS WORLDWIDE', 'WPX', 'N', '', 0],
                    ['Q', 'MEDICAL EXPRESS', 'WMX', 'N', '', 0],
                    ['R', 'GLOBALMAIL BUSINESS', 'GMB', 'Y', '', 0],
                    ['S', 'SAME DAY', 'SDX', 'Y', '', 0],
                    ['T', 'EXPRESS 12:00', 'TDT', 'Y', '', 0],
                    ['U', 'EXPRESS WORLDWIDE', 'ECX', 'Y', '', 0],
                    ['V', 'EUROPACK', 'EPP', 'N', '', 0],
                    ['W', 'ECONOMY SELECT', 'ESU', 'Y', '', 0],
                    ['X', 'EXPRESS ENVELOPE', 'XPD', 'Y', '', 0],
                    ['Y', 'EXPRESS 12:00', 'TDY', 'N', '', 0],
                    ['Z', 'Destination Charges', 'N/A', 'A', '', 0],
                ]);
            }
        }

        return parent::install($platform_id);
    }
}

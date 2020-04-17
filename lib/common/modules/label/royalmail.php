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

use Yii;
use common\classes\modules\ModuleLabel;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

require_once 'royalmail/RoyalMailApi.php';

class royalmail extends ModuleLabel {

    public $title;
    public $description;
    public $code = 'royalmail';
    public $can_update_shipment = true;
    public $can_cancel_shipment = true;
    private $_API = null;

    public function __construct() {
        $this->title = 'Royal Mail'; //MODULE_LABEL_ROYALMAIL_TEXT_TITLE;
        $this->description = 'Royal Mail'; //MODULE_LABEL_ROYALMAIL_TEXT_DESCRIPTION;
        $this->_API = new \RoyalMailApi();
        $this->selectRoyalMailAccount();
    }

    public function configure_keys() {
        return array(
            'MODULE_LABEL_ROYALMAIL_STATUS' =>
            array(
                'title' => 'Enable RoyalMail Labels',
                'value' => 'True',
                'description' => 'Do you want to offer RoyalMail labels?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_LABEL_ROYALMAIL_USERNAME' =>
            array(
                'title' => 'Username',
                'value' => '',
                'description' => 'Username',
                'sort_order' => '1',
            ),
            'MODULE_LABEL_ROYALMAIL_PASSWORD' =>
            array(
                'title' => 'Password SHA-1',
                'value' => '',
                'description' => 'Password SHA-1',
                'sort_order' => '2',
            ),
            'MODULE_LABEL_ROYALMAIL_CLIENT_ID' =>
            array(
                'title' => 'Client ID',
                'value' => '',
                'description' => 'Client ID',
                'sort_order' => '3',
            ),
            'MODULE_LABEL_ROYALMAIL_CLIENT_SECRET' =>
            array(
                'title' => 'Client Secret',
                'value' => '',
                'description' => 'Client Secret',
                'sort_order' => '4',
            ),
            'MODULE_LABEL_ROYALMAIL_SORT_ORDER' =>
            array(
                'title' => 'RoyalMail Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '10',
            ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_LABEL_ROYALMAIL_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_LABEL_ROYALMAIL_SORT_ORDER');
    }

    private function selectRoyalMailAccount() {
        $config = [];
        if (defined('MODULE_LABEL_ROYALMAIL_USERNAME')) {
           $config['username'] = MODULE_LABEL_ROYALMAIL_USERNAME;
        }
        if (defined('MODULE_LABEL_ROYALMAIL_PASSWORD')) {
           $config['password'] = MODULE_LABEL_ROYALMAIL_PASSWORD;
        }
        if (defined('MODULE_LABEL_ROYALMAIL_CLIENT_ID')) {
           $config['client_id'] = MODULE_LABEL_ROYALMAIL_CLIENT_ID;
        }
        if (defined('MODULE_LABEL_ROYALMAIL_CLIENT_SECRET')) {
           $config['client_secret'] = MODULE_LABEL_ROYALMAIL_CLIENT_SECRET;
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
                    $method_keys = explode("-", $method_value);
                    Yii::$app->db->createCommand()->update('royal_mail_service_matrix', ['service_matrix_status' => '1'], ['service_types_code' => $method_keys[0], 'service_offerings_code' => $method_keys[1], 'service_formats_code' => $method_keys[2], 'enhancement_types_code' => $method_keys[3]])->execute();
                    break;
                case 'del':
                    $method_keys = explode("-", $method_value);
                    Yii::$app->db->createCommand()->update('royal_mail_service_matrix', ['service_matrix_status' => '0'], ['service_types_code' => $method_keys[0], 'service_offerings_code' => $method_keys[1], 'service_formats_code' => $method_keys[2], 'enhancement_types_code' => $method_keys[3]])->execute();
                    break;
                default:
                    break;
            }
        }

        $service_matrix_name = \Yii::$app->request->post('service_matrix_name', array());
        if (is_array($service_matrix_name)) {
            foreach ($service_matrix_name as $key => $value) {
                $method_keys = explode("-", $key);
                Yii::$app->db->createCommand()->update('royal_mail_service_matrix', ['service_matrix_name' => $value], ['service_types_code' => $method_keys[0], 'service_offerings_code' => $method_keys[1], 'service_formats_code' => $method_keys[2], 'enhancement_types_code' => $method_keys[3]])->execute();
            }
        }

        $params_service_types = \Yii::$app->request->post('service_types', array());
        $params_service_offerings = \Yii::$app->request->post('service_offerings', array());
        $params_service_formats = \Yii::$app->request->post('service_formats', array());
        $params_enhancement_types = \Yii::$app->request->post('enhancement_types', array());

        $html = '';
        if (!Yii::$app->request->isAjax) {
            $html .= '<div id="modules_extra_params">';
        }

        $html .= '<h2 style="margin: 0">' . TEXT_SELECTED_METHODS . '</h2>';
        $html .= '<table width="100%" class="selected-methods">';
        $html .= '<tr><th width="5%">' . TABLE_HEADING_ACTION . '</th><th>' . TABLE_TEXT_NAME . '</th><th>' . TABLE_HEADING_TITLE . '</th></tr>';
        
        $methods_query = (new \Yii\db\Query())->select('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code, sm.service_matrix_name, sm.service_matrix_status, st.service_types_name, so.service_offerings_name, sf.service_formats_name, et.enhancement_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('service_matrix_status = 1');
        foreach ($methods_query->orderBy('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code')->all() as $methods) {
            //$html .= '<tr><td><span onclick="delMethod(\'' . $methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code'] . '\')">-</span></td><td>' . trim(tep_not_null($methods['service_matrix_name']) ? $methods['service_matrix_name'] : $methods['service_types_name'] . ', ' . $methods['service_offerings_name'] . ', ' . $methods['service_formats_name'] . ', ' . $methods['enhancement_types_name'], ", \t\n\r\0\x0B") . '</td>';
            $html .= '<tr><td><span class="delMethod" onclick="delMethod(\'' . $methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code'] . '\')"></span></td><td>' . trim($methods['service_types_name'] . ', ' . $methods['service_offerings_name'] . ', ' . $methods['service_formats_name'] . ', ' . $methods['enhancement_types_name'], ", \t\n\r\0\x0B") . '</td>';
            $html .= '<td><input type="text" name="service_matrix_name[' . $methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code'] . ']" value="' . $methods['service_matrix_name'] . '"></td>';
            $html .= '</tr>';
        }
        $html .= '</table><br><br>';

        $html .= '<h2 style="margin: 0">' . TEXT_AVAILABLE_METHODS . '</h2>';

        $html .= '<input type="hidden" name="method_action" value=""><input type="hidden" name="method_value" value="">';

        $active_tab = \Yii::$app->request->post('active_tab', 'tab_rm_0');
        $html .= tep_draw_hidden_field('active_tab', $active_tab);


        $html .= tep_draw_hidden_field('set', $_GET['set']) . tep_draw_hidden_field('module', $_GET['module']) . '<div class="tabbable tabbable-custom tabbable-methods"><ul class="nav nav-tabs"><li' . ($active_tab == 'tab_rm_0' ? ' class="active"' : '') . '><a href="#tab_rm_0" data-toggle="tab"><span>Service Types</span></a></li><li' . ($active_tab == 'tab_rm_2' ? ' class="active"' : '') . '><a href="#tab_rm_2" data-toggle="tab"><span>Service Formats</span></a></li><li' . ($active_tab == 'tab_rm_3' ? ' class="active"' : '') . '><a href="#tab_rm_3" data-toggle="tab"><span>Enhancement Types</span></a></li><li' . ($active_tab == 'tab_rm_1' ? ' class="active"' : '') . '><a href="#tab_rm_1" data-toggle="tab"><span>Service Offerings</span></a></li></ul><div class="tab-content tab-content-vertical"><div class="tab-pane' . ($active_tab == 'tab_rm_0' ? ' active' : '') . '" id="tab_rm_0">';

        $service_types_query = (new \Yii\db\Query())->select('st.service_types_code, st.service_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('1');
        if (count($params_service_offerings) > 0) {
            $service_types_query->andWhere(['in', 'sm.service_offerings_code', $params_service_offerings]);
        }
        if (count($params_service_formats) > 0) {
            $service_types_query->andWhere(['in', 'sm.service_formats_code', $params_service_formats]);
        }
        if (count($params_enhancement_types) > 0) {
            $service_types_query->andWhere(['in', 'sm.enhancement_types_code', $params_enhancement_types]);
        }
        foreach ($service_types_query->groupBy('st.service_types_code, st.service_types_name')->orderBy('st.service_types_code, st.service_types_name')->all() as $service_types) {
            $html .= '<label>' . tep_draw_checkbox_field('service_types[]', $service_types['service_types_code'], in_array($service_types['service_types_code'], $params_service_types), '', 'onchange="filterMethods();"') . ' ' . $service_types['service_types_name'] . '</label><br>';
        }

        $html .= '</div><div class="tab-pane' . ($active_tab == 'tab_rm_2' ? ' active' : '') . '" id="tab_rm_2">';

        $service_formats_query = (new \Yii\db\Query())->select('sf.service_formats_code, sf.service_formats_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('1');
        if (count($params_service_types) > 0) {
            $service_formats_query->andWhere(['in', 'sm.service_types_code', $params_service_types]);
        }
        if (count($params_service_offerings) > 0) {
            $service_formats_query->andWhere(['in', 'sm.service_offerings_code', $params_service_offerings]);
        }
        if (count($params_enhancement_types) > 0) {
            $service_formats_query->andWhere(['in', 'sm.enhancement_types_code', $params_enhancement_types]);
        }
        foreach ($service_formats_query->groupBy('sf.service_formats_code, sf.service_formats_name')->orderBy('sf.service_formats_code, sf.service_formats_name')->all() as $service_formats) {
            $html .= '<label>' . tep_draw_checkbox_field('service_formats[]', $service_formats['service_formats_code'], in_array($service_formats['service_formats_code'], $params_service_formats), '', 'onchange="filterMethods();"') . ' ' . $service_formats['service_formats_name'] . '</label><br>';
        }

        $html .= '</div><div class="tab-pane' . ($active_tab == 'tab_rm_3' ? ' active' : '') . '" id="tab_rm_3">';

        $enhancement_types_query = (new \Yii\db\Query())->select('et.enhancement_types_code, et.enhancement_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->innerJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('1');
        if (count($params_service_types) > 0) {
            $enhancement_types_query->andWhere(['in', 'sm.service_types_code', $params_service_types]);
        }
        if (count($params_service_offerings) > 0) {
            $enhancement_types_query->andWhere(['in', 'sm.service_offerings_code', $params_service_offerings]);
        }
        if (count($params_service_formats) > 0) {
            $enhancement_types_query->andWhere(['in', 'sm.service_formats_code', $params_service_formats]);
        }
        foreach ($enhancement_types_query->groupBy('et.enhancement_types_code, et.enhancement_types_name')->orderBy('et.enhancement_types_code, et.enhancement_types_name')->all() as $enhancement_types) {
            $html .= '<label>' . tep_draw_checkbox_field('enhancement_types[]', $enhancement_types['enhancement_types_code'], in_array($enhancement_types['enhancement_types_code'], $params_enhancement_types), '', 'onchange="filterMethods();"') . ' ' . $enhancement_types['enhancement_types_name'] . '</label><br>';
        }

        $html .= '</div><div class="tab-pane' . ($active_tab == 'tab_rm_1' ? ' active' : '') . '" id="tab_rm_1">';

        $service_offerings_query = (new \Yii\db\Query())->select('so.service_offerings_code, so.service_offerings_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('1');
        if (count($params_service_types) > 0) {
            $service_offerings_query->andWhere(['in', 'sm.service_types_code', $params_service_types]);
        }
        if (count($params_service_formats) > 0) {
            $service_offerings_query->andWhere(['in', 'sm.service_formats_code', $params_service_formats]);
        }
        if (count($params_enhancement_types) > 0) {
            $service_offerings_query->andWhere(['in', 'sm.enhancement_types_code', $params_enhancement_types]);
        }
        foreach ($service_offerings_query->groupBy('so.service_offerings_code, so.service_offerings_name')->orderBy('so.service_offerings_code, so.service_offerings_name')->all() as $service_offerings) {
            $html .= '<label>' . tep_draw_checkbox_field('service_offerings[]', $service_offerings['service_offerings_code'], in_array($service_offerings['service_offerings_code'], $params_service_offerings), '', 'onchange="filterMethods();"') . ' ' . $service_offerings['service_offerings_name'] . '</label><br>';
        }

        $html .= '</div></div></div>';

        if (count($params_service_types) > 0 || count($params_service_offerings) > 0 || count($params_service_formats) > 0 || count($params_enhancement_types) > 0) {
            $methods_array = array();
            $methods_query = (new \Yii\db\Query())->select('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code, sm.service_matrix_name, sm.service_matrix_status, st.service_types_name, so.service_offerings_name, sf.service_formats_name, et.enhancement_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('service_matrix_status = 0');
            if (count($params_service_types) > 0) {
                $methods_query->andWhere(['in', 'sm.service_types_code', $params_service_types]);
            }
            if (count($params_service_offerings) > 0) {
                $methods_query->andWhere(['in', 'sm.service_offerings_code', $params_service_offerings]);
            }
            if (count($params_service_formats) > 0) {
                $methods_query->andWhere(['in', 'sm.service_formats_code', $params_service_formats]);
            }
            if (count($params_enhancement_types) > 0) {
                $methods_query->andWhere(['in', 'sm.enhancement_types_code', $params_enhancement_types]);
            }
            foreach ($methods_query->orderBy('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code')->all() as $methods) {
                $methods_array[$methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code']] = trim(tep_not_null($methods['service_matrix_name']) ? $methods['service_matrix_name'] : $methods['service_types_name'] . ', ' . $methods['service_offerings_name'] . ', ' . $methods['service_formats_name'] . ', ' . $methods['enhancement_types_name'], ", \t\n\r\0\x0B");
            }
            if (count($methods_array) > 0) {
                $html .= '<div class="addMethod-table"><table width="100%">';
                $html .= '<tr><th>' . TABLE_HEADING_ACTION . '</th><th>' . TABLE_TEXT_NAME . '</th></tr>';
                foreach ($methods_array as $key => $value) {
                    $html .= '<tr><td><span class="addMethod" onclick="addMethod(\'' . $key . '\')"></span></td><td>' . $value . '</td></tr>';
                }
                $html .= '</table></div>';
            }
        }

        $html .= '<script type="text/javascript">
function filterMethods() {
    $(\'input[name="active_tab"]\').val($("div.tab-pane.active").attr("id"));
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
        $methods_array = array();
        $methods_query = (new \Yii\db\Query())->select('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code, sm.service_matrix_name, st.service_types_name, so.service_offerings_name, sf.service_formats_name, et.enhancement_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('sm.service_matrix_status = 1');
        foreach ($methods_query->orderBy('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code')->all() as $methods) {
            $methods_array[$this->code . '_' . $methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code']] =
                trim(tep_not_null($methods['service_matrix_name']) ? $methods['service_matrix_name'] : $methods['service_types_name'] . ', ' . $methods['service_offerings_name'] . ', ' . $methods['service_formats_name'] . ', ' . $methods['enhancement_types_name'], ", \t\n\r\0\x0B");
        }
        return $methods_array;
    }

    function get_methods($country_iso_code_2, $method = '', $shipping_weight = 0, $num_of_sheets = 0) {
        $methods_array = array();
        $methods_query = (new \Yii\db\Query())->select('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code, sm.service_matrix_name, st.service_types_name, so.service_offerings_name, sf.service_formats_name, et.enhancement_types_name')->from(['sm' => 'royal_mail_service_matrix'])->innerJoin(['st' => 'royal_mail_service_types'], 'st.service_types_code = sm.service_types_code')->innerJoin(['so' => 'royal_mail_service_offerings'], 'so.service_offerings_code = sm.service_offerings_code')->innerJoin(['sf' => 'royal_mail_service_formats'], 'sf.service_types_code = sm.service_types_code and sf.service_formats_code = sm.service_formats_code')->leftJoin(['et' => 'royal_mail_enhancement_types'], 'et.enhancement_types_code = sm.enhancement_types_code')->where('sm.service_matrix_status = 1');
        if ($country_iso_code_2 == 'GB') {
            $methods_query->andWhere(['not', ['sm.service_types_code' => 'I']]);
        } else {
            $methods_query->andWhere(['sm.service_types_code' => 'I']);
        }
        foreach ($methods_query->orderBy('sm.service_types_code, sm.service_offerings_code, sm.service_formats_code, sm.enhancement_types_code')->all() as $methods) {
            $methods_array[$this->code . '_' . $methods['service_types_code'] . '-' . $methods['service_offerings_code'] . '-' . $methods['service_formats_code'] . '-' . $methods['enhancement_types_code']] = trim(tep_not_null($methods['service_matrix_name']) ? $methods['service_matrix_name'] : $methods['service_types_name'] . ', ' . $methods['service_offerings_name'] . ', ' . $methods['service_formats_name'] . ', ' . $methods['enhancement_types_name'], ", \t\n\r\0\x0B");
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
        if (tep_not_null($oLabel->tracking_number)) {
            $tracking_number = $oLabel->tracking_number;
        } else {
            $result = $this->_API->_create_shipment($order, $method, $this->shipment_weight($order_id, $orders_label_id));
            if (is_array($result['completedShipments']) && is_array($result['completedShipments'][0]['shipmentItems'])) {
                $tracking_number = $result['completedShipments'][0]['shipmentItems'][0]['shipmentNumber'];
            }
            if (tep_not_null($tracking_number)) {
                $addTracking = \common\classes\OrderTrackingNumber::instanceFromString($tracking_number, $order_id);
                $addTracking->setOrderProducts($oLabel->getOrdersLabelProducts());
                $order->info['tracking_number'][] = $addTracking;
                $order->saveTrackingNumbers();

                $oLabel->tracking_number = $tracking_number;
                $oLabel->tracking_numbers_id = $addTracking->tracking_numbers_id;
                $oLabel->save();
            } else {
                $return = $this->parse_errors($result);
            }
        }

        if (tep_not_null($tracking_number)) {
            $return['tracking_number'] = $tracking_number;
            if (tep_not_null($oLabel->parcel_label_pdf)) {
                $parcel_label_pdf = base64_decode($oLabel->parcel_label_pdf);
            } else {
                $result = $this->_API->_parcel_label($tracking_number);
                if (tep_not_null($result['label'])) {
                    $parcel_label_pdf = base64_decode($result['label']);
                }
                if (tep_not_null($parcel_label_pdf)) {
                    $oLabel->parcel_label_pdf = base64_encode($parcel_label_pdf);
                    $oLabel->save();
                } else {
                    $return = $this->parse_errors($result);
                }
            }
            if (tep_not_null($parcel_label_pdf)) {
                $return['parcel_label'] = $parcel_label_pdf;
            }
        }

        return $return;
    }

    public function update_shipment($order_id, $orders_label_id) {
        $return = array();
        $oLabel = \common\models\OrdersLabel::findOne(['orders_id' => $order_id, 'orders_label_id' => $orders_label_id]);
        if (tep_not_null($oLabel->tracking_number)) {
            \common\helpers\Translation::init('admin/orders');

            $manager = \common\services\OrderManager::loadManager();
            $order = $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
            Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
            $manager->set('platform_id', $order->info['platform_id']);

            $result = $this->_API->_update_shipment($order, $oLabel->tracking_number);
            if (tep_not_null($result['shipmentNumber'])) {
                $updated_tracking_number = $result['shipmentNumber'];
            }
            if (tep_not_null($updated_tracking_number) && $updated_tracking_number == $oLabel->tracking_number) {
                $notify_comments = 'Updated - ' . TEXT_TRACKING_NUMBER . $updated_tracking_number;

                $oLabel->parcel_label_pdf = '';
                $oLabel->save();

                global $login_id;
                Yii::$app->db->createCommand()->insert(TABLE_ORDERS_STATUS_HISTORY, ['orders_id' => $order_id, 'orders_status_id' => $order->info['order_status'], 'date_added' => new Yii\db\Expression('NOW()'), 'customer_notified' => '0', 'comments' => $notify_comments, 'admin_id' => $login_id])->execute();

                $result = $this->_API->_parcel_label($updated_tracking_number);
                if (tep_not_null($result['label'])) {
                    $parcel_label_pdf = base64_decode($result['label']);
                }
                if (tep_not_null($parcel_label_pdf)) {
                    $oLabel->parcel_label_pdf = base64_encode($parcel_label_pdf);
                    $oLabel->save();
                    $return['parcel_label'] = $parcel_label_pdf;
                } else {
                    $return = $this->parse_errors($result);
                }
            } else {
                $return = $this->parse_errors($result);
            }
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
            $result = $this->_API->_cancel_shipment($oLabel->tracking_number);
            if (tep_not_null($result['shipmentNumber'])) {
                $cancelled_tracking_number = $result['shipmentNumber'];
            } elseif (is_array($result) && count($result) == 0) { // RoyalMail returns empty array... bug???
                $cancelled_tracking_number = $oLabel->tracking_number;
            }
            if (tep_not_null($cancelled_tracking_number) && $cancelled_tracking_number == $oLabel->tracking_number) {
                $notify_comments = 'Cancelled - ' . TEXT_TRACKING_NUMBER . ' ' . $cancelled_tracking_number;

                $order->removeTrackingNumber($oLabel->tracking_numbers_id);
                $oLabel->delete();

                global $login_id;
                Yii::$app->db->createCommand()->insert(TABLE_ORDERS_STATUS_HISTORY, ['orders_id' => $order_id, 'orders_status_id' => $order->info['order_status'], 'date_added' => new Yii\db\Expression('NOW()'), 'customer_notified' => '0', 'comments' => $notify_comments, 'admin_id' => $login_id])->execute();

                $return['success'] = 'RoyalMail: ' . $notify_comments;
            } else {
                $return = $this->parse_errors($result);
            }
        }
        return $return;
    }

    public function parse_errors($result) {
        $return = array();
        if (tep_not_null($result['httpCode'])) {
            $return['errors'][] = 'Royal Mail: ' . $result['httpCode'] . ' - ' . ($result['moreInformation'] ? $result['moreInformation'] : $result['httpMessage']);
        }
        if (is_array($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $return['errors'][] = $error['errorCode'] . ' - ' . $error['errorDescription'];
            }
        }
        return $return;
    }

    public function install($platform_id) {

        $migration = new \yii\db\Migration();
        if ($migration) {
            if (Yii::$app->db->schema->getTableSchema('royal_mail_enhancement_types') === null) {
                $migration->createTable('royal_mail_enhancement_types', [
                    'enhancement_types_code' => $migration->tinyInteger(1)->notNull(),
                    'enhancement_types_name' => $migration->string(64)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'royal_mail_enhancement_types', ['enhancement_types_code']);

                $migration->batchInsert('royal_mail_enhancement_types', ['enhancement_types_code', 'enhancement_types_name'], [
                    [1, 'Consequential Loss £1000'],
                    [2, 'Consequential Loss £2500'],
                    [3, 'Consequential Loss £5000'],
                    [4, 'Consequential Loss £7500'],
                    [5, 'Consequential Loss £10000'],
                    [6, 'Recorded'],
                    [11, 'Consequential Loss £750'],
                    [12, 'Tracked Signature'],
                    [13, 'SMS Notification'],
                    [14, 'E-Mail Notification'],
                    [16, 'SMS & E-Mail Notification'],
                    [22, 'Local Collect'],
                    [24, 'Saturday Guaranteed'],
                ]);
            }

            if (Yii::$app->db->schema->getTableSchema('royal_mail_service_formats') === null) {
                $migration->createTable('royal_mail_service_formats', [
                    'service_types_code' => $migration->char(1)->notNull(),
                    'service_formats_code' => $migration->char(1)->notNull(),
                    'service_formats_name' => $migration->string(64)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'royal_mail_service_formats', ['service_types_code', 'service_formats_code']);

                $migration->batchInsert('royal_mail_service_formats', ['service_types_code', 'service_formats_code', 'service_formats_name'], [
                    ['1', 'F', 'Inland Large Letter'],
                    ['1', 'L', 'Inland Letter'],
                    ['1', 'N', 'Inland format Not Applicable'],
                    ['1', 'P', 'Inland Parcel'],
                    ['2', 'F', 'Inland Large Letter'],
                    ['2', 'L', 'Inland Letter'],
                    ['2', 'N', 'Inland format Not Applicable'],
                    ['2', 'P', 'Inland Parcel'],
                    ['D', 'F', 'Inland Large Letter'],
                    ['D', 'L', 'Inland Letter'],
                    ['D', 'N', 'Inland format Not Applicable'],
                    ['D', 'P', 'Inland Parcel'],
                    ['H', 'F', 'Inland Large Letter'],
                    ['H', 'L', 'Inland Letter'],
                    ['H', 'N', 'Inland format Not Applicable'],
                    ['H', 'P', 'Inland Parcel'],
                    ['I', 'E', 'International Parcel'],
                    ['I', 'G', 'International Large Letter'],
                    ['I', 'N', 'International Format Not Applicable'],
                    ['I', 'P', 'International Letter'],
                    ['R', 'F', 'Inland Large Letter'],
                    ['R', 'L', 'Inland Letter'],
                    ['R', 'N', 'Inland format Not Applicable'],
                    ['R', 'P', 'Inland Parcel'],
                    ['T', 'F', 'Inland Large Letter'],
                    ['T', 'L', 'Inland Letter'],
                    ['T', 'N', 'Inland format Not Applicable'],
                    ['T', 'P', 'Inland Parcel'],
                ]);
            }

            if (Yii::$app->db->schema->getTableSchema('royal_mail_service_matrix') === null) {
                $migration->createTable('royal_mail_service_matrix', [
                    'service_types_code' => $migration->char(1)->notNull(),
                    'service_offerings_code' => $migration->char(3)->notNull(),
                    'service_formats_code' => $migration->char(1)->notNull(),
                    'enhancement_types_code' => $migration->tinyInteger(1)->notNull(),
                    'service_matrix_name' => $migration->string(128)->notNull(),
                    'service_matrix_status' => $migration->tinyInteger(1)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'royal_mail_service_matrix', ['service_types_code', 'service_offerings_code', 'service_formats_code', 'enhancement_types_code']);

                $migration->batchInsert('royal_mail_service_matrix', ['service_types_code', 'service_offerings_code', 'service_formats_code', 'enhancement_types_code', 'service_matrix_name', 'service_matrix_status'], [
                    ['1', 'CRL', 'F', 0, '', 0],
                    ['1', 'CRL', 'F', 6, '', 0],
                    ['1', 'CRL', 'P', 0, '', 0],
                    ['1', 'CRL', 'P', 6, '', 0],
                    ['1', 'FS1', 'F', 0, '', 0],
                    ['1', 'PK1', 'P', 0, '', 0],
                    ['1', 'PK1', 'P', 6, '', 0],
                    ['1', 'PK3', 'F', 0, '', 0],
                    ['1', 'PK3', 'F', 6, '', 0],
                    ['1', 'PK3', 'P', 0, '', 0],
                    ['1', 'PK3', 'P', 6, '', 0],
                    ['1', 'PK9', 'F', 0, '', 0],
                    ['1', 'PK9', 'F', 6, '', 0],
                    ['1', 'PPF', 'P', 0, '', 0],
                    ['1', 'PPF', 'P', 6, '', 0],
                    ['1', 'PX0', 'A', 0, '', 0],
                    ['1', 'PX0', 'F', 0, '', 0],
                    ['1', 'PX0', 'P', 0, '', 0],
                    ['1', 'PX1', 'A', 0, '', 0],
                    ['1', 'PX1', 'F', 0, '', 0],
                    ['1', 'PX1', 'P', 0, '', 0],
                    ['1', 'PY1', 'F', 0, '', 0],
                    ['1', 'PY3', 'F', 0, '', 0],
                    ['1', 'PZ4', 'A', 0, '', 0],
                    ['1', 'PZ4', 'F', 0, '', 0],
                    ['1', 'PZ4', 'P', 0, '', 0],
                    ['1', 'RM1', 'F', 0, '', 0],
                    ['1', 'RM1', 'F', 6, '', 0],
                    ['1', 'RM2', 'P', 0, '', 0],
                    ['1', 'RM2', 'P', 6, '', 0],
                    ['1', 'RM5', 'F', 0, '', 0],
                    ['1', 'RM5', 'F', 6, '', 0],
                    ['1', 'RM5', 'P', 0, '', 0],
                    ['1', 'RM5', 'P', 6, '', 0],
                    ['1', 'RM7', 'F', 0, '', 0],
                    ['1', 'RM7', 'F', 6, '', 0],
                    ['1', 'RM8', 'P', 0, '', 0],
                    ['1', 'RM8', 'P', 6, '', 0],
                    ['1', 'STL', 'F', 0, '', 0],
                    ['1', 'STL', 'F', 6, '', 0],
                    ['1', 'STL', 'L', 0, '', 0],
                    ['1', 'STL', 'L', 6, '', 0],
                    ['1', 'STL', 'P', 0, '', 0],
                    ['1', 'STL', 'P', 6, '', 0],
                    ['2', 'CRL', 'F', 0, '', 0],
                    ['2', 'CRL', 'F', 6, '', 0],
                    ['2', 'CRL', 'P', 0, '', 0],
                    ['2', 'CRL', 'P', 6, '', 0],
                    ['2', 'FS2', 'F', 0, '', 0],
                    ['2', 'PK0', 'F', 0, '', 0],
                    ['2', 'PK0', 'F', 6, '', 0],
                    ['2', 'PK2', 'P', 0, '', 0],
                    ['2', 'PK2', 'P', 6, '', 0],
                    ['2', 'PK4', 'F', 0, '', 0],
                    ['2', 'PK4', 'F', 6, '', 0],
                    ['2', 'PK4', 'P', 0, '', 0],
                    ['2', 'PK4', 'P', 6, '', 0],
                    ['2', 'PPF', 'P', 0, '', 0],
                    ['2', 'PPF', 'P', 6, '', 0],
                    ['2', 'PX2', 'A', 0, '', 0],
                    ['2', 'PX2', 'F', 0, '', 0],
                    ['2', 'PX2', 'P', 0, '', 0],
                    ['2', 'PY2', 'F', 0, '', 0],
                    ['2', 'PY4', 'F', 0, '', 0],
                    ['2', 'PZ5', 'A', 0, '', 0],
                    ['2', 'PZ5', 'F', 0, '', 0],
                    ['2', 'PZ5', 'P', 0, '', 0],
                    ['2', 'RM0', 'P', 0, '', 0],
                    ['2', 'RM0', 'P', 6, '', 0],
                    ['2', 'RM3', 'F', 0, '', 0],
                    ['2', 'RM3', 'F', 6, '', 0],
                    ['2', 'RM4', 'P', 0, '', 0],
                    ['2', 'RM4', 'P', 6, '', 0],
                    ['2', 'RM6', 'F', 0, '', 0],
                    ['2', 'RM6', 'F', 6, '', 0],
                    ['2', 'RM6', 'P', 0, '', 0],
                    ['2', 'RM6', 'P', 6, '', 0],
                    ['2', 'RM9', 'F', 0, '', 0],
                    ['2', 'RM9', 'F', 6, '', 0],
                    ['2', 'STL', 'F', 0, '', 0],
                    ['2', 'STL', 'F', 6, '', 0],
                    ['2', 'STL', 'L', 0, '', 0],
                    ['2', 'STL', 'L', 6, '', 0],
                    ['2', 'STL', 'P', 0, '', 0],
                    ['2', 'STL', 'P', 6, '', 0],
                    ['D', 'SD1', 'N', 0, '', 0],
                    ['D', 'SD1', 'N', 1, '', 0],
                    ['D', 'SD1', 'N', 2, '', 0],
                    ['D', 'SD1', 'N', 3, '', 0],
                    ['D', 'SD1', 'N', 4, '', 0],
                    ['D', 'SD1', 'N', 5, '', 0],
                    ['D', 'SD1', 'N', 13, '', 0],
                    ['D', 'SD1', 'N', 14, '', 0],
                    ['D', 'SD1', 'N', 16, '', 0],
                    ['D', 'SD1', 'N', 22, '', 0],
                    ['D', 'SD1', 'N', 24, '', 0],
                    ['D', 'SD2', 'N', 0, '', 0],
                    ['D', 'SD2', 'N', 1, '', 0],
                    ['D', 'SD2', 'N', 2, '', 0],
                    ['D', 'SD2', 'N', 3, '', 0],
                    ['D', 'SD2', 'N', 4, '', 0],
                    ['D', 'SD2', 'N', 5, '', 0],
                    ['D', 'SD2', 'N', 13, '', 0],
                    ['D', 'SD2', 'N', 14, '', 0],
                    ['D', 'SD2', 'N', 16, '', 0],
                    ['D', 'SD2', 'N', 22, '', 0],
                    ['D', 'SD2', 'N', 24, '', 0],
                    ['D', 'SD3', 'N', 0, '', 0],
                    ['D', 'SD3', 'N', 1, '', 0],
                    ['D', 'SD3', 'N', 2, '', 0],
                    ['D', 'SD3', 'N', 3, '', 0],
                    ['D', 'SD3', 'N', 4, '', 0],
                    ['D', 'SD3', 'N', 5, '', 0],
                    ['D', 'SD3', 'N', 13, '', 0],
                    ['D', 'SD3', 'N', 14, '', 0],
                    ['D', 'SD3', 'N', 16, '', 0],
                    ['D', 'SD3', 'N', 22, '', 0],
                    ['D', 'SD3', 'N', 24, '', 0],
                    ['D', 'SD4', 'N', 0, '', 0],
                    ['D', 'SD4', 'N', 1, '', 0],
                    ['D', 'SD4', 'N', 2, '', 0],
                    ['D', 'SD4', 'N', 3, '', 0],
                    ['D', 'SD4', 'N', 4, '', 0],
                    ['D', 'SD4', 'N', 5, '', 0],
                    ['D', 'SD4', 'N', 13, '', 0],
                    ['D', 'SD4', 'N', 14, '', 0],
                    ['D', 'SD4', 'N', 16, '', 0],
                    ['D', 'SD4', 'N', 22, '', 0],
                    ['D', 'SD4', 'N', 24, '', 0],
                    ['D', 'SD5', 'N', 0, '', 0],
                    ['D', 'SD5', 'N', 1, '', 0],
                    ['D', 'SD5', 'N', 2, '', 0],
                    ['D', 'SD5', 'N', 3, '', 0],
                    ['D', 'SD5', 'N', 4, '', 0],
                    ['D', 'SD5', 'N', 5, '', 0],
                    ['D', 'SD5', 'N', 13, '', 0],
                    ['D', 'SD5', 'N', 14, '', 0],
                    ['D', 'SD5', 'N', 16, '', 0],
                    ['D', 'SD5', 'N', 22, '', 0],
                    ['D', 'SD5', 'N', 24, '', 0],
                    ['D', 'SD6', 'N', 0, '', 0],
                    ['D', 'SD6', 'N', 1, '', 0],
                    ['D', 'SD6', 'N', 2, '', 0],
                    ['D', 'SD6', 'N', 3, '', 0],
                    ['D', 'SD6', 'N', 4, '', 0],
                    ['D', 'SD6', 'N', 5, '', 0],
                    ['D', 'SD6', 'N', 13, '', 0],
                    ['D', 'SD6', 'N', 14, '', 0],
                    ['D', 'SD6', 'N', 16, '', 0],
                    ['D', 'SD6', 'N', 22, '', 0],
                    ['D', 'SD6', 'N', 24, '', 0],
                    ['H', 'BF1', 'E', 0, '', 0],
                    ['H', 'BF1', 'G', 0, '', 0],
                    ['H', 'BF1', 'P', 0, '', 0],
                    ['H', 'BF2', 'E', 0, '', 0],
                    ['H', 'BF2', 'G', 0, '', 0],
                    ['H', 'BF2', 'P', 0, '', 0],
                    ['H', 'BF7', 'N', 0, '', 0],
                    ['H', 'BF8', 'N', 0, '', 0],
                    ['H', 'BF9', 'N', 0, '', 0],
                    ['I', 'DE1', 'E', 0, '', 0],
                    ['I', 'DE3', 'E', 0, '', 0],
                    ['I', 'DE4', 'E', 0, '', 0],
                    ['I', 'DE6', 'E', 0, '', 0],
                    ['I', 'DG1', 'G', 0, '', 0],
                    ['I', 'DG3', 'G', 0, '', 0],
                    ['I', 'DG4', 'G', 0, '', 0],
                    ['I', 'DG6', 'G', 0, '', 0],
                    ['I', 'DW1', 'E', 0, '', 0],
                    ['I', 'IE1', 'E', 0, '', 0],
                    ['I', 'IE3', 'E', 0, '', 0],
                    ['I', 'IG1', 'G', 0, '', 0],
                    ['I', 'IG3', 'G', 0, '', 0],
                    ['I', 'IG4', 'G', 0, '', 0],
                    ['I', 'IG6', 'G', 0, '', 0],
                    ['I', 'MB1', 'E', 0, '', 0],
                    ['I', 'MB1', 'N', 0, '', 0],
                    ['I', 'MB2', 'N', 0, '', 0],
                    ['I', 'MB3', 'N', 0, '', 0],
                    ['I', 'MP0', 'E', 0, '', 0],
                    ['I', 'MP1', 'E', 0, '', 0],
                    ['I', 'MP4', 'E', 0, '', 0],
                    ['I', 'MP5', 'E', 0, '', 0],
                    ['I', 'MP6', 'E', 0, '', 0],
                    ['I', 'MP7', 'E', 0, '', 0],
                    ['I', 'MP8', 'E', 0, '', 0],
                    ['I', 'MP9', 'E', 0, '', 0],
                    ['I', 'MTA', 'E', 0, '', 0],
                    ['I', 'MTB', 'E', 0, '', 0],
                    ['I', 'MTC', 'G', 0, '', 0],
                    ['I', 'MTC', 'P', 0, '', 0],
                    ['I', 'MTD', 'G', 0, '', 0],
                    ['I', 'MTD', 'P', 0, '', 0],
                    ['I', 'MTE', 'E', 0, '', 0],
                    ['I', 'MTF', 'E', 0, '', 0],
                    ['I', 'MTG', 'G', 0, '', 0],
                    ['I', 'MTH', 'G', 0, '', 0],
                    ['I', 'MTI', 'G', 0, '', 0],
                    ['I', 'MTI', 'P', 0, '', 0],
                    ['I', 'MTJ', 'G', 0, '', 0],
                    ['I', 'MTJ', 'P', 0, '', 0],
                    ['I', 'MTK', 'G', 0, '', 0],
                    ['I', 'MTL', 'G', 0, '', 0],
                    ['I', 'MTM', 'G', 0, '', 0],
                    ['I', 'MTM', 'P', 0, '', 0],
                    ['I', 'MTN', 'G', 0, '', 0],
                    ['I', 'MTN', 'P', 0, '', 0],
                    ['I', 'MTO', 'G', 0, '', 0],
                    ['I', 'MTP', 'G', 0, '', 0],
                    ['I', 'MTQ', 'E', 0, '', 0],
                    ['I', 'MTS', 'E', 0, '', 0],
                    ['I', 'OLA', 'E', 0, '', 0],
                    ['I', 'OLA', 'G', 0, '', 0],
                    ['I', 'OLA', 'N', 0, '', 0],
                    ['I', 'OLA', 'P', 0, '', 0],
                    ['I', 'OLS', 'E', 0, '', 0],
                    ['I', 'OLS', 'G', 0, '', 0],
                    ['I', 'OLS', 'N', 0, '', 0],
                    ['I', 'OLS', 'P', 0, '', 0],
                    ['I', 'OSA', 'E', 0, '', 0],
                    ['I', 'OSA', 'G', 0, '', 0],
                    ['I', 'OSA', 'P', 0, '', 0],
                    ['I', 'OSB', 'E', 0, '', 0],
                    ['I', 'OSB', 'G', 0, '', 0],
                    ['I', 'OSB', 'P', 0, '', 0],
                    ['I', 'OTA', 'E', 0, '', 0],
                    ['I', 'OTA', 'G', 0, '', 0],
                    ['I', 'OTA', 'P', 0, '', 0],
                    ['I', 'OTB', 'E', 0, '', 0],
                    ['I', 'OTB', 'G', 0, '', 0],
                    ['I', 'OTB', 'P', 0, '', 0],
                    ['I', 'OTC', 'E', 0, '', 0],
                    ['I', 'OTC', 'G', 0, '', 0],
                    ['I', 'OTC', 'P', 0, '', 0],
                    ['I', 'OTD', 'E', 0, '', 0],
                    ['I', 'OTD', 'G', 0, '', 0],
                    ['I', 'OTD', 'P', 0, '', 0],
                    ['I', 'OZ1', 'N', 0, '', 0],
                    ['I', 'OZ3', 'N', 0, '', 0],
                    ['I', 'OZ4', 'N', 0, '', 0],
                    ['I', 'OZ6', 'N', 0, '', 0],
                    ['I', 'PS0', 'E', 0, '', 0],
                    ['I', 'PS7', 'G', 0, '', 0],
                    ['I', 'PS8', 'G', 0, '', 0],
                    ['I', 'PS9', 'E', 0, '', 0],
                    ['I', 'PSB', 'G', 0, '', 0],
                    ['I', 'PSC', 'E', 0, '', 0],
                    ['I', 'WE1', 'E', 0, '', 0],
                    ['I', 'WE3', 'E', 0, '', 0],
                    ['I', 'WG1', 'G', 0, '', 0],
                    ['I', 'WG3', 'G', 0, '', 0],
                    ['I', 'WG4', 'G', 0, '', 0],
                    ['I', 'WG6', 'G', 0, '', 0],
                    ['I', 'WW1', 'N', 0, '', 0],
                    ['I', 'WW3', 'N', 0, '', 0],
                    ['I', 'WW4', 'N', 0, '', 0],
                    ['I', 'WW6', 'N', 0, '', 0],
                    ['R', 'PT1', 'N', 0, '', 0],
                    ['R', 'PT2', 'N', 0, '', 0],
                    ['T', 'TPL', 'N', 0, '', 0],
                    ['T', 'TPL', 'N', 13, '', 0],
                    ['T', 'TPL', 'N', 14, '', 0],
                    ['T', 'TPL', 'N', 16, '', 0],
                    ['T', 'TPL', 'N', 22, '', 0],
                    ['T', 'TPM', 'N', 0, '', 0],
                    ['T', 'TPM', 'N', 13, '', 0],
                    ['T', 'TPM', 'N', 14, '', 0],
                    ['T', 'TPM', 'N', 16, '', 0],
                    ['T', 'TPM', 'N', 22, '', 0],
                    ['T', 'TPN', 'N', 0, '', 0],
                    ['T', 'TPN', 'N', 13, '', 0],
                    ['T', 'TPN', 'N', 14, '', 0],
                    ['T', 'TPN', 'N', 16, '', 0],
                    ['T', 'TPN', 'N', 22, '', 0],
                    ['T', 'TPS', 'N', 0, '', 0],
                    ['T', 'TPS', 'N', 13, '', 0],
                    ['T', 'TPS', 'N', 14, '', 0],
                    ['T', 'TPS', 'N', 16, '', 0],
                    ['T', 'TPS', 'N', 22, '', 0],
                    ['T', 'TRL', 'N', 0, '', 0],
                    ['T', 'TRL', 'N', 13, '', 0],
                    ['T', 'TRL', 'N', 14, '', 0],
                    ['T', 'TRL', 'N', 16, '', 0],
                    ['T', 'TRL', 'N', 22, '', 0],
                    ['T', 'TRM', 'N', 0, '', 0],
                    ['T', 'TRM', 'N', 13, '', 0],
                    ['T', 'TRM', 'N', 14, '', 0],
                    ['T', 'TRM', 'N', 16, '', 0],
                    ['T', 'TRM', 'N', 22, '', 0],
                    ['T', 'TRN', 'N', 0, '', 0],
                    ['T', 'TRN', 'N', 13, '', 0],
                    ['T', 'TRN', 'N', 14, '', 0],
                    ['T', 'TRN', 'N', 16, '', 0],
                    ['T', 'TRN', 'N', 22, '', 0],
                    ['T', 'TRS', 'N', 0, '', 0],
                    ['T', 'TRS', 'N', 13, '', 0],
                    ['T', 'TRS', 'N', 14, '', 0],
                    ['T', 'TRS', 'N', 16, '', 0],
                    ['T', 'TRS', 'N', 22, '', 0],
                ]);
            }

            if (Yii::$app->db->schema->getTableSchema('royal_mail_service_offerings') === null) {
                $migration->createTable('royal_mail_service_offerings', [
                    'service_offerings_code' => $migration->char(3)->notNull(),
                    'service_offerings_name' => $migration->string(128)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'royal_mail_service_offerings', ['service_offerings_code']);

                $migration->batchInsert('royal_mail_service_offerings', ['service_offerings_code', 'service_offerings_name'], [
                    ['BF1', 'HM Forces Mail'],
                    ['BF2', 'HM Forces Signed For'],
                    ['BF7', 'HM Forces Special Delivery [£500)'],
                    ['BF8', 'HM Forces Special Delivery [£1000)'],
                    ['BF9', 'HM Forces Special Delivery [£2500)'],
                    ['BPL', 'Royal Mail 1st/2nd Class'],
                    ['BPR', 'Royal Mail 1st/2nd Class Signed For'],
                    ['CRL', 'Royal Mail 24/ Royal Mail 48 Standard/Signed For [Parcel - Daily Rate Service)'],
                    ['DE1', 'International Business Parcels Zero Sort High Volume Priority'],
                    ['DE3', 'International Business Parcels Zero Sort High Vol Economy'],
                    ['DE4', 'International Business Parcels Zero Srt Low Volume Priority'],
                    ['DE6', 'International Business Parcels Zero Sort Low Vol Economy'],
                    ['DG1', 'International Business Mail Large Letter Country Sort High Volume Priority'],
                    ['DG3', 'International Business Mail Large Letter Ctry Sort High Vol Economy'],
                    ['DG4', 'International Business Mail Large Letter Country Sort Low Volume Priority'],
                    ['DG6', 'International Business Mail Large Letter Ctry Sort Low Vol Economy'],
                    ['FS1', 'Royal Mail 24 Standard/Signed For Large Letter [Flat Rate Service)'],
                    ['FS2', 'Royal Mail 48 Standard/Signed For Large Letter [Flat Rate Service)'],
                    ['FS7', 'Royal Mail 24 [Presorted) [Large Letter)'],
                    ['FS8', 'Royal Mail 48 [Presorted) [Large Letter)'],
                    ['IE1', 'International Business Parcels Zone Sort Priority Service'],
                    ['IE3', 'International Business Parcels Zone Sort Economy Service'],
                    ['IG1', 'International Business Mail Large Letter Zone Sort Priority'],
                    ['IG3', 'International Business Mail Large Letter Zone Sort Economy'],
                    ['IG4', 'International Business Mail Large Letter Zone Sort Priority Machine'],
                    ['IG6', 'International Business Mail Large Letter Zone Srt Economy Machine'],
                    ['LA1', 'Special Delivery Guaranteed By 1PM LA [£500)'],
                    ['LA2', 'Special Delivery Guaranteed By 1PM LA [£1000)'],
                    ['LA3', 'Special Delivery Guaranteed By 1PM LA [£2500)'],
                    ['LA4', 'Special Delivery Guaranteed By 9AM LA [£50)'],
                    ['LA5', 'Special Delivery Guaranteed By 9AM LA [£1000)'],
                    ['LA6', 'Special Delivery Guaranteed By 9AM LA [£2500)'],
                    ['MB1', 'INTL BUS PARCELS PRINT DIRECT PRIORITY'],
                    ['MB2', 'INTL BUS PARCELS PRINT DIRECT STANDARD'],
                    ['MB3', 'INTL BUS PARCELS PRINT DIRECT ECONOMY'],
                    ['MP0', 'International Business Parcels Signed Extra Compensation [Country Pricing)'],
                    ['MP1', 'International Business Parcels Tracked [Zonal Pricing)'],
                    ['MP4', 'International Business Parcels Tracked Extra Comp [Zonal Pricing)'],
                    ['MP5', 'International Business Parcels Signed [Zonal Pricing)'],
                    ['MP6', 'International Business Parcels Signed Extra Compensation [Zonal Pricing)'],
                    ['MP7', 'International Business Parcels Tracked [Country Pricing)'],
                    ['MP8', 'International Business Parcels Tracked Extra Comp [Country Pricing)'],
                    ['MP9', 'International Business Parcels Signed [Country Pricing)'],
                    ['MPB', 'International Business Parcel Tracked Boxable Extra Comp [Country Pricing)'],
                    ['MPF', 'International Business Parcel Tracked High Vol. [Country Pricing)'],
                    ['MPG', 'International Business Parcels Tracked & Signed High Vol. [Country Pricing)'],
                    ['MPH', 'International Business Parcel Signed High Vol. [Country Pricing)'],
                    ['MPI', 'International Business Parcel Tracked High Vol. Extra Comp [Country Pricing)'],
                    ['MPJ', 'International Business Parcels Tracked & Signed High Vol. Extra Comp [Country Pricing)'],
                    ['MPK', 'International Business Parcel Signed High Vol. Extra Comp [Country Pricing)'],
                    ['MPL', 'International Business Mail Tracked High Vol. [Country Pricing)'],
                    ['MPM', 'International Business Mail Tracked & Signed High Vol. [Country Pricing)'],
                    ['MPN', 'International Business Mail Signed High Vol. [Country Pricing)'],
                    ['MPO', 'International Business Mail Tracked High Vol. Extra Comp [Country Pricing)'],
                    ['MPP', 'International Business Mail Tracked & Signed High Vol. Extra Comp [Country Pricing)'],
                    ['MPQ', 'International Business Mail Signed High Vol. Extra Comp [Country Pricing)'],
                    ['MPR', 'International Business Parcel Tracked Boxable [Country Pricing)'],
                    ['MPT', 'International Business Parcel Tracked Boxable High Vol. [Country Pricing)'],
                    ['MPU', 'International Business Parcel Tracked Boxable Extra Comp [Country Pricing)'],
                    ['MPV', 'International Business Parcel Zero Sort Boxable Low Vol. Priority'],
                    ['MPW', 'International Business Parcel Zero Sort Boxable Low Vol. Economy'],
                    ['MPX', 'International Business Parcel Zero Sort Boxable High Vol. Priority'],
                    ['MPY', 'International Business Parcel Zero Sort Boxable High Vol. Economy'],
                    ['MTA', 'International Business Parcels Tracked & Signed [Zonal Pricing)'],
                    ['MTB', 'International Business Parcels Tracked & Signed Extra Compensation [Zonal Pricing)'],
                    ['MTC', 'International Business Mail Tracked & Signed [Zonal Pricing)'],
                    ['MTD', 'International Business Mail Tracked & Signed Extra Compensation [Zonal Pricing)'],
                    ['MTE', 'International Business Parcels Tracked & Signed [Country Pricing)'],
                    ['MTF', 'International Business Parcels Tracked & Signed Extra Compensation [Country Pricing)'],
                    ['MTG', 'International Business Mail Tracked & Signed [Country Pricing)'],
                    ['MTH', 'International Business Mail Tracked & Signed Extra Compensation [Country Pricing)'],
                    ['MTI', 'International Business Mail Tracked [Zonal Pricing)'],
                    ['MTJ', 'International Business Mail Tracked Extra Comp [Zonal Pricing)'],
                    ['MTK', 'International Business Mail Tracked [Country Pricing)'],
                    ['MTL', 'International Business Mail Tracked Extra Comp [Country Pricing)'],
                    ['MTM', 'International Business Mail Signed [Zonal Pricing)'],
                    ['MTN', 'International Business Mail Signed Extra Compensation [Zonal Pricing)'],
                    ['MTO', 'International Business Mail Signed [Country Pricing)'],
                    ['MTP', 'International Business Mail Signed Extra Compensation [Country Pricing)'],
                    ['MTQ', 'International Business Parcels Zone Sort Plus Priority'],
                    ['MTS', 'International Business Parcels Zone Sort Plus Economoy'],
                    ['MUA', 'INTL BUS PARCELS BOXABLE ZERO SORT PRI'],
                    ['MUB', 'INTL BUS PARCELS BOXABLE ZERO SORT ECON'],
                    ['MUC', 'INTL BUS PARCELS BOXABLE ZONE SORT PRI'],
                    ['MUD', 'INTL BUS PARCELS BOXABLE ZONE SORT ECON'],
                    ['MUE', 'INTL BUS PRCL TRCKD BOX ZERO SRT XTR CMP'],
                    ['MUF', 'INTL BUS PARCELS TRACKED BOX ZERO SORT'],
                    ['MUG', 'INTL BUS PARCELS TRACKED BOX ZONE SORT'],
                    ['MUH', 'INTL BUS PRCL TRCKD BOX ZONE SRT XTR CMP'],
                    ['MUI', 'INTL BUS PARCELS TRACKED ZERO SORT'],
                    ['MUJ', 'INTL BUS PARCEL TRACKED ZERO SRT XTR CMP'],
                    ['MUK', 'INTL BUS PARCEL TRACKD & SIGNED ZERO SRT'],
                    ['MUL', 'INT BUS PRCL TRCKD & SGND ZRO SRT XT CMP'],
                    ['MUM', 'INTL BUS PARCELS SIGNED ZERO SORT'],
                    ['MUN', 'INTL BUS PARCEL SIGNED ZERO SORT XTR CMP'],
                    ['MUO', 'INTL BUS MAIL TRACKED ZERO SORT'],
                    ['MUP', 'INTL BUS MAIL TRACKED ZERO SORT XTRA CMP'],
                    ['MUQ', 'INTL BUS MAIL TRACKED & SIGNED ZERO SORT'],
                    ['MUR', 'INT BUS MAIL TRCKD & SGND ZRO SRT XT CMP'],
                    ['MUS', 'INTL BUS MAIL SIGNED ZERO SORT'],
                    ['MUT', 'INTL BUS MAIL SIGNED ZERO SORT XTRA COMP'],
                    ['MUU', 'Intlernational Business Parcels Boxable Max Sort Priority'],
                    ['MUV', 'International Buiness Prcls Boxable Max Sort Standard'],
                    ['MUW', 'International Business Parcels Boxable Max Sort Economy'],
                    ['OLA', 'International Standard On Account'],
                    ['OLS', 'International Economy On Account'],
                    ['OSA', 'International Signed On Account [Zonal Pricing)'],
                    ['OSB', 'International Signed On Account Extra Compensation [Zonal Pricing)'],
                    ['OTA', 'International Tracked On Account [Zonal Pricing)'],
                    ['OTB', 'International Tracked On Account Extra Compensation [Zonal Pricing)'],
                    ['OTC', 'International Tracked & Signed On Account [Zonal Pricing)'],
                    ['OTD', 'International Tracked & Signed On Account Extra Compensation [Zonal Pricing)'],
                    ['OZ1', 'International Business Mail Mixed Zone Sort Priority'],
                    ['OZ3', 'International Business Mail Mixed Zone Sort Economy'],
                    ['OZ4', 'International Business Mail Mixed Zone Sort Priority Machine'],
                    ['OZ6', 'International Business Mail Mixed Zone Srt Economy Machine'],
                    ['PK0', 'Royal Mail 48 [LL) Flat Rate'],
                    ['PK1', 'Royal Mail 24 Standard/Signed For [Parcel – Sort8 - Flat Rate Service)'],
                    ['PK2', 'Royal Mail 48 Standard/Signed For [Parcel – Sort8 - Flat Rate Service)'],
                    ['PK3', 'Royal Mail 24 Standard/Signed For [Parcel - Sort8 - Daily Rate Service)'],
                    ['PK4', 'Royal Mail 48 Standard/Signed For [Parcel - Sort8 - Daily Rate Service)'],
                    ['PK7', 'Royal Mail 24 [Presorted) [P)'],
                    ['PK8', 'Royal Mail 48 [Presorted) [P)'],
                    ['PK9', 'Royal Mail 24 [LL) Flat Rate'],
                    ['PKB', 'RM24 [Presorted) [P) Annual Flat Rate'],
                    ['PKD', 'RM48 [Presorted) [P) Annual Flat Rate'],
                    ['PKK', 'RM48 [Presorted) [LL) Annual Flat Rate'],
                    ['PKM', 'RM24 [Presorted)[LL) Annual Flat Rate'],
                    ['PPF', 'Royal Mail 24/48 Standard/Signed For [Packetpost- Flat Rate Service)'],
                    ['PPJ', 'Parcelpost Flat Rate [Annual)'],
                    ['PPS', 'RM24 [LL) Annual Flat Rate'],
                    ['PPT', 'RM48 [LL) Annual Flat Rate'],
                    ['PS0', 'International Business Parcels Max Sort Economy Service'],
                    ['PS7', 'International Business Mail Large Letter Max Sort Priority Service'],
                    ['PS8', 'International Business Mail Large Letter Max Sort Economy Service'],
                    ['PS9', 'International Business Parcels Max Sort Priority Service'],
                    ['PSB', 'International Business Mail Large Letter Max Sort Standard Service'],
                    ['PSC', 'International Business Parcels Max Sort Standard Service'],
                    ['RM0', 'Royal Mail 48 [Sort8)[P) Annual Flat Rate'],
                    ['RM1', 'Royal Mail 24 [LL) Daily Rate'],
                    ['RM2', 'Royal Mail 24 [P) Daily Rate'],
                    ['RM3', 'Royal Mail 48 [LL) Daily Rate'],
                    ['RM4', 'Royal Mail 48 [P) Daily Rate'],
                    ['RM5', 'Royal Mail 24 [P) Annual Flat Rate'],
                    ['RM6', 'Royal Mail 48 [P) Annual Flat Rate'],
                    ['RM7', 'Royal Mail 24 [SORT8) [LL) Annual Flat Rate'],
                    ['RM8', 'Royal Mail 24 [SORT8) [P) Annual Flat Rate'],
                    ['RM9', 'Royal Mail 48 [SORT8) [LL) Annual Flat Rate'],
                    ['SD1', 'Special Delivery Guaranteed By 1PM [£500)'],
                    ['SD2', 'Special Delivery Guaranteed By 1PM [£1000)'],
                    ['SD3', 'Special Delivery Guaranteed By 1PM [£2500)'],
                    ['SD4', 'Special Delivery Guaranteed By 9AM [£50)'],
                    ['SD5', 'Special Delivery Guaranteed By 9AM [£1000)'],
                    ['SD6', 'Special Delivery Guaranteed By 9AM [£2500)'],
                    ['STL', 'Royal Mail 1st Class/ 2nd Class  Standard/Signed For [Letters - Daily Rate service)'],
                    ['TPL', 'Tracked 48 High Volume Signature/ No Signature'],
                    ['TPM', 'Tracked 24 High Volume Signature/ No Signature'],
                    ['TPN', 'Tracked 24 Signature/ No Signature'],
                    ['TPS', 'Tracked 48 Signature/ No Signature'],
                    ['TRL', 'Tracked Letter-Boxable 48 High Volume Signature'],
                    ['TRM', 'Tracked Letter-Boxable 24 High Volume No Signature'],
                    ['TRN', 'Tracked Letter-Boxable 24 No Signature'],
                    ['TRS', 'Tracked Letter-Boxable 48 No Signature'],
                    ['TSN', 'Tracked Returns 24'],
                    ['TSS', 'Tracked Returns 48'],
                    ['WE1', 'International Business Parcels Zero Sort Priority'],
                    ['WE3', 'International Business Parcels Zero Sort Economy'],
                ]);
            }
            
            if (Yii::$app->db->schema->getTableSchema('royal_mail_service_types') === null) {
                $migration->createTable('royal_mail_service_types', [
                    'service_types_code' => $migration->char(1)->notNull(),
                    'service_types_name' => $migration->string(64)->notNull(),
                ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');
                $migration->addPrimaryKey('', 'royal_mail_service_types', ['service_types_code']);

                $migration->batchInsert('royal_mail_service_types', ['service_types_code', 'service_types_name'], [
                    ['1', 'Royal Mail 24 / 1st Class'],
                    ['2', 'Royal Mail 48 / 2nd Class'],
                    ['D', 'Special Delivery Guaranteed'],
                    ['H', 'HM Forces (BFPO)'],
                    ['I', 'International'],
                    ['R', 'Tracked Returns'],
                    ['T', 'Royal Mail Tracked'],
                ]);
            }
        }

        return parent::install($platform_id);
    }
}

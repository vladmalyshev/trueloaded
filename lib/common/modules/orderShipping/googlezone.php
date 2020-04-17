<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\modules\orderShipping;

use Yii;
use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class googlezone extends ModuleShipping {

    var $code,
        $title,
        $description,
        $icon,
        $enabled,
        $zone_id,
        $methods,
        $select_id,
        $shipping_weight,
        $products_qty,
        $total;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_GOOGLEZONE_TEXT_TITLE' => 'Google Zones',
        'MODULE_SHIPPING_GOOGLEZONE_TEXT_DESCRIPTION' => 'Shipping Google Zones. Edit cost based on zones',
        'MODULE_SHIPPING_GOOGLEZONE_TEXT_WAY' => 'Ship to %s',
        'MODULE_SHIPPING_GOOGLEZONE_TEXT_WEIGHT' => 'Weight',
        'MODULE_SHIPPING_GOOGLEZONE_TEXT_AMOUNT' => 'Amount',
        'MODULE_SHIPPING_GOOGLEZONE_NOTE_TEXT' => 'Global Priority shipping with tracking number',
        'MODULE_SHIPPING_GOOGLEZONE_INVALID_ZONE' => 'The requested service is unavailable between the selected locations'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'googlezone';
        $this->title = MODULE_SHIPPING_GOOGLEZONE_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_GOOGLEZONE_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_GOOGLEZONE_STATUS')) {
            $this->enabled = false;
            return false;
        }

        $this->sort_order = MODULE_SHIPPING_GOOGLEZONE_SORT_ORDER;
        $this->tax_class = MODULE_SHIPPING_GOOGLEZONE_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_GOOGLEZONE_STATUS == 'True') ? true : false);
    }

    public function possibleMethods()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $possibleMethods = [];
        $ship_options_query = tep_db_query(
            "select googlezone_id as id, googlezone_title as name ".
            "from googlezone ".
            "where platform_id='" . \Yii::$app->get('platform')->config()->getId() . "' and language_id='" . $languages_id . "' and status=1 ".
            "order by sort_order"
        );
        while ($d = tep_db_fetch_array($ship_options_query)) {
            $possibleMethods[$d['id']] = $d['name'];
        }

        return $possibleMethods;
    }

// class methods
    function quote($method = '') {
        global $languages_id;

        $platform_id = (int)$this->manager->getPlatformId();
        if ($platform_id == 0) {
            $platform_id = PLATFORM_ID;
        }

        if ($method != '' ) {
          $mSql = " and googlezone_id='" . (int)$method . "'";
        } else {
          $mSql = "";
        }

        $methods = [];
        $collection_points_query = tep_db_query("select * from googlezone where platform_id='" . $platform_id . "' and language_id='" . $languages_id . "' and status=1 {$mSql} order by sort_order");
        while ($collection_points = tep_db_fetch_array($collection_points_query)) {

            $map = $mapEnd = '';
            if (!empty($collection_points['googlezone_code'])) {
                $map = '<a class="popup-map-link" href="' . \Yii::$app->urlManager->createUrl(['callback/webhooks', 'set' => 'shipping', 'module' => $this->code, 'action' => 'map', 'mid' => $collection_points['googlezone_code']]) . '">';
                $mapEnd = '</a>';
            }
            $methods[] = array('id' => $collection_points['googlezone_id'],
                'title' => $collection_points['googlezone_title'],
                'description' => sprintf($collection_points['googlezone_description'], $map, $mapEnd),
                'cost' => ($collection_points['price'] + MODULE_SHIPPING_GOOGLEZONE_HANDLING));
        }

        $this->quotes = array('id' => $this->code,
            'module' => MODULE_SHIPPING_GOOGLEZONE_TEXT_TITLE,
            'methods' => $methods);

        if ($this->tax_class > 0) {
            $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
        }

        if (tep_not_null($this->icon))
            $this->quotes['icon'] = tep_image($this->icon, $this->title);

        return $this->quotes;
    }

    public function install($platform_id) {
        tep_db_query("CREATE TABLE IF NOT EXISTS `googlezone` (
            `googlezone_id` int(11) NOT NULL DEFAULT '0',
            `platform_id` int(11) NOT NULL DEFAULT '0',
            `language_id` int(11) NOT NULL DEFAULT '0',
            `googlezone_title` varchar(64) DEFAULT NULL,
            `googlezone_description` VARCHAR(128) NOT NULL,
            `googlezone_code` TEXT DEFAULT NULL,
             `price` DECIMAL(15,6) NOT NULL DEFAULT '0',
            `sort_order` tinyint(1) NOT NULL DEFAULT '0',
            `status` int(1) NOT NULL DEFAULT '1'
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        return parent::install($platform_id);
    }

    public function configure_keys() {
        return array(
            'MODULE_SHIPPING_GOOGLEZONE_STATUS' =>
            array(
                'title' => 'Enable Table Method',
                'value' => 'True',
                'description' => 'Do you want to offer Google Zones rate shipping?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_SHIPPING_GOOGLEZONE_HANDLING' =>
            array(
                'title' => 'Handling Fee',
                'value' => '0',
                'description' => 'Handling fee for this shipping method.',
                'sort_order' => '0',
            ),
            'MODULE_SHIPPING_GOOGLEZONE_TAX_CLASS' =>
            array(
                'title' => 'Tax Class',
                'value' => '0',
                'description' => 'Use the following tax class on the shipping fee.',
                'sort_order' => '0',
                'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
                'set_function' => 'tep_cfg_pull_down_tax_classes(',
            ),
            'MODULE_SHIPPING_GOOGLEZONE_SORT_ORDER' =>
            array(
                'title' => 'Sort Order',
                'value' => '0',
                'description' => 'Sort order of display.',
                'sort_order' => '0',
            ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_SHIPPING_GOOGLEZONE_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_SHIPPING_GOOGLEZONE_SORT_ORDER');
    }

    function extra_params() {

        global $languages_id;
        $languages = \common\helpers\Language::get_languages();

        $platform_id = (int) \Yii::$app->request->get('platform_id');
        if ($platform_id == 0) {
            $platform_id = (int) \Yii::$app->request->post('platform_id');
        }

        $method_action = \Yii::$app->request->post('action', '');
        $method_value = \Yii::$app->request->post('id', '');
        if (!empty($method_action)) {
            switch ($method_action) {
                case 'add':
                    $next_id_query = tep_db_query("select max(googlezone_id) as googlezone_id from googlezone");
                    $next_id = tep_db_fetch_array($next_id_query);
                    $googlezone_id = $next_id['googlezone_id'] + 1;
                    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                        $sql_data_array = array(
                            'googlezone_id' => $googlezone_id,
                            'platform_id' => $platform_id,
                            'language_id' => $languages[$i]['id'],
                            'googlezone_title' => '',
                            'googlezone_description' => '',
                            'googlezone_code' => '',
                            'price' => 0,
                            'sort_order' => $googlezone_id,
                            'status' => 0,
                        );
                        tep_db_perform('googlezone', $sql_data_array);
                    }
                    break;
                case 'del':
                    tep_db_query("delete from googlezone where googlezone_id = '" . (int) $method_value . "'");
                    break;
                default:
                    break;
            }
        }

        $status = \Yii::$app->request->post('status');
        $googlezone_title = \Yii::$app->request->post('googlezone_title');
        $googlezone_description = \Yii::$app->request->post('googlezone_description');
        $googlezone_code = \Yii::$app->request->post('googlezone_code');
        $price = \Yii::$app->request->post('price');
        $sort_order = \Yii::$app->request->post('sort_order');
        $options_query = tep_db_query("select * from googlezone where platform_id='" . $platform_id . "'");
        while ($options = tep_db_fetch_array($options_query)) {
            if (isset($status[$options['googlezone_id']])) {
                tep_db_query("update googlezone set status = '" . (int) $status[$options['googlezone_id']] . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and platform_id='" . $platform_id . "'");
            }
            if (isset($googlezone_title[$options['googlezone_id']][$options['language_id']])) {
                tep_db_query("update googlezone set googlezone_title = '" . tep_db_input($googlezone_title[$options['googlezone_id']][$options['language_id']]) . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and language_id='" . (int) $options['language_id'] . "' and platform_id='" . $platform_id . "'");
            }
            if (isset($googlezone_description[$options['googlezone_id']][$options['language_id']])) {
                tep_db_query("update googlezone set googlezone_description = '" . tep_db_input($googlezone_description[$options['googlezone_id']][$options['language_id']]) . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and language_id='" . (int) $options['language_id'] . "' and platform_id='" . $platform_id . "'");
            }
            if (isset($googlezone_code[$options['googlezone_id']])) {
                tep_db_query("update googlezone set googlezone_code = '" . tep_db_input($googlezone_code[$options['googlezone_id']]) . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and platform_id='" . $platform_id . "'");
            }
            if (isset($price[$options['googlezone_id']])) {
                tep_db_query("update googlezone set price = '" . tep_db_input($price[$options['googlezone_id']]) . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and platform_id='" . $platform_id . "'");
            }
            if (isset($sort_order[$options['googlezone_id']])) {
                tep_db_query("update googlezone set sort_order = '" . (int) $sort_order[$options['googlezone_id']] . "' where googlezone_id = '" . (int) $options['googlezone_id'] . "' and platform_id='" . $platform_id . "'");
            }
        }

        $html = '';
        if (!Yii::$app->request->isAjax) {
            $html .= '<div id="modules_extra_params">';
        }

        $html .= '<table width="100%" class="selected-methods">';
        $html .= '<tr><th width="10%">' . TABLE_HEADING_ACTION . '</th><th width="10%">' . TABLE_HEADING_STATUS . '</th><th width="20%">' . TABLE_HEADING_TITLE . '</th><th width="40%">'.IMAGE_DETAILS.'</th><th width="10%">MID</th><th width="10%">' . TEXT_SORT_ORDER . '</th><th width="10%">' . TEXT_INFO_PRICE . '</th></tr>';
        $options_query = tep_db_query("select * from googlezone where language_id = '" . (int) $languages_id . "' and platform_id='" . $platform_id . "' order by sort_order,googlezone_id");
        while ($options = tep_db_fetch_array($options_query)) {
            $html .= '<tr><td><span class="delMethod" onclick="delPayMethod(\'' . $options['googlezone_id'] . '\')"></span></td><td>';
            $html .= '<input type="checkbox" class="uniform" name="status[' . $options['googlezone_id'] . ']" value="1" ' . ($options['status'] == 1 ? 'checked' : '') . '></td><td>';
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                $options_value_query = tep_db_query("select googlezone_title from googlezone where googlezone_id = '" . $options['googlezone_id'] . "' and language_id = '" . (int) $languages[$i]['id'] . "' and platform_id='" . $platform_id . "'");
                $options_value = tep_db_fetch_array($options_value_query);
                $html .= $languages[$i]['image'] . '&nbsp;<input type="text" name="googlezone_title[' . $options['googlezone_id'] . '][' . $languages[$i]['id'] . ']" value="' . $options_value['googlezone_title'] . '">' . '<br><br>';
            }

            $html .= '</td><td>';
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                $options_value_query = tep_db_query("select googlezone_description from googlezone where googlezone_id = '" . $options['googlezone_id'] . "' and language_id = '" . (int)$languages[$i]['id'] . "' and platform_id='" . $platform_id . "'");
                $options_value = tep_db_fetch_array($options_value_query);
                $html .= $languages[$i]['image'] . '&nbsp;<textarea name="googlezone_description[' . $options['googlezone_id'] . '][' . $languages[$i]['id'] . ']" rows="2" cols="74">' . $options_value['googlezone_description'] . '</textarea><br>';
            }

            $html .= '</td><td><input type="text" name="googlezone_code[' . $options['googlezone_id'] . ']" value="' . $options['googlezone_code'] . '">';

            $html .= '</td><td><input type="text" name="sort_order[' . $options['googlezone_id'] . ']" value="' . $options['sort_order'] . '">';
            $html .= '</td><td><input type="text" name="price[' . $options['googlezone_id'] . ']" value="' . $options['price'] . '">';
            $html .= '</td></tr>';
        }
        $html .= '<tr><td><span class="addMethod" onclick="return addPayMethod();"></span></td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
        $html .= '</table><br><br>';


        if (!Yii::$app->request->isAjax) {
            $html .= '</div>';

            $html .= '<script type="text/javascript">
function delPayMethod(id) {
    $.post("' . tep_href_link('modules/extra-params') . '", {"set": "shipping", "module": "' . $this->code . '", "platform_id": "' . (int) $platform_id . '", "action": "del", "id": id}, function(data, status) {
        if (status == "success") {
            $(\'#modules_extra_params\').html(data);
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
function addPayMethod() {
    $.post("' . tep_href_link('modules/extra-params') . '", {"set": "shipping", "module": "' . $this->code . '", "platform_id": "' . (int) $platform_id . '", "action": "add"}, function(data, status) {
        if (status == "success") {
            $(\'#modules_extra_params\').html(data);
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}
</script>';
        }
        return $html;
    }

    public function call_webhooks() {
        $action = Yii::$app->request->get('action');
        if ($action == 'map') {
            $mid = Yii::$app->request->get('mid');
            echo '<br><br><iframe src="https://www.google.com/maps/d/embed?mid='.$mid.'" width="528" height="488"></iframe>';
        }
    }

}

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

use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class zonetable extends ModuleShipping {

    const TABLE_MODE_WEIGHT = 0;
    const TABLE_MODE_VOLUME = 5;
    const TABLE_MODE_PRICE = 1;
    const TABLE_MODE_QUANTITY = 2;
    const TABLE_MODE_WEIGHT_PRICE = 3;
    const TABLE_MODE_VOLUME_PRICE = 4;

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
        $volume,
        $total;

    private $no_cost = false;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_TITLE' => 'Zone Table',
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_DESCRIPTION' => 'Shipping Zone table Rate. Edit cost table in Shipping Table section',
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_WAY' => 'Ship to %s',
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_WEIGHT' => 'Weight',
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_AMOUNT' => 'Amount',
        'MODULE_SHIPPING_ZONE_TABLE_NOTE_TEXT' => 'Global Priority shipping with tracking number',
        'MODULE_SHIPPING_ZONE_TABLE_INVALID_ZONE' => 'The requested service is unavailable between the selected locations',
        'MODULE_SHIPPING_ZONE_TABLE_INVALID_ZONE_ALLOW' => 'Please call us for a quote',
        'MODULE_SHIPPING_ZONE_TABLE_TEXT_DESCRIPTION' => 'Zone Table',
        'MODULE_SHIPPING_ZONE_TABLE_CHECKOUT_NOTE' => 'Checkout Note',
    ];

    function __construct() {
        parent::__construct();

        global $languages_id;//, $cart,$quote;
        //, $admin_mode;
        $this->code = 'zonetable';
        $this->title = MODULE_SHIPPING_ZONE_TABLE_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_ZONE_TABLE_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_ZONE_TABLE_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_ZONE_TABLE_SORT_ORDER;
        //$this->icon = DIR_WS_ICONS . 'international.png';
        $this->tax_class = MODULE_SHIPPING_ZONE_TABLE_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_ZONE_TABLE_STATUS == 'True') ? true : false);

        if ($this->enabled == true) {
            $check_flag = false;
            $postcode = str_replace(' ', '', $this->delivery['postcode']);
            if ( strlen($postcode)>10 ) $postcode = substr($postcode, 0, 10);

            if ( preg_match('/^([A-Za-z][A-Ha-hJ-Yj-y]?[0-9][A-Za-z0-9]? ?[0-9][A-Za-z]{2}|[Gg][Ii][Rr] ?0[Aa]{2})$/',str_replace(' ','',$postcode)) ){
                $_postcode = $this->search_uk_zip($postcode, $this->delivery['country_id'], '[@@FIELD@@]');
                $search_by_postcode_sql =
                    "and if(length(gz.start_postcode),gz.start_postcode<=substring(" . str_replace('[@@FIELD@@]', 'gz.start_postcode', $_postcode) . ",1,length(gz.start_postcode)),1) ".
                    "and if(length(gz.stop_postcode),gz.stop_postcode>=substring(" . str_replace('[@@FIELD@@]', 'gz.stop_postcode', $_postcode) . ",1,length(gz.stop_postcode)),1) ";
            }else{
                $search_by_postcode_sql =
                    "and if(gz.start_postcode<>'',gz.start_postcode<='" . tep_db_input($postcode) . "',1) ".
                    "and if(gz.stop_postcode<>'',gz.stop_postcode >= '" . tep_db_input($postcode) . "',1) ";
            }
            $check_query = tep_db_query(
                "select count(*) as total ".
                "from " . TABLE_ZONES_TO_SHIP_ZONES . " gz ".
                "where (gz.zone_country_id = '" . $this->delivery['country']['id'] . "' or gz.zone_country_id=0 ) ".
                "  and (gz.zone_id = '" . $this->delivery['zone_id'] . "' or gz.zone_id = 0 ) ".
                "  {$search_by_postcode_sql} ".
                "  and if(gz.city<>'',gz.city = '" . tep_db_input($this->delivery['city']) . "',1) ".
                ""
            );
            $check = tep_db_fetch_array($check_query);
            if ($check['total'] == 0) {
                if ( defined('MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS') && MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS=='True' ){
                    // return method 0
                  $this->no_cost = true;
                }else {
                    $this->enabled = false;
                }
            }
            // check products shipping options
            /*if (!is_object($order) || !is_array($order->products) || !sizeof($order->products)) {
                return;
            }*/

            // 1. create array of products id
            /*$products_array = array(); what for???
            if (is_object($cart)) {
                $tmp = $cart->get_products();
            } else {
                $tmp = $order->products;
            }
            if($this->isQuote(true)){
                if (is_object($quote)) {
                    $tmp = $quote->get_products();
                }
            }

            if (count($tmp) > 0) {
                foreach ($tmp as $products) {
                    $products_array[] = (int) $products['id'];
                }
            }*/


            // 2. select all ship options
            $ship_options = array();
            $ship_options_query = tep_db_query("select ship_options_id as id, ship_options_name as name from " . TABLE_SHIP_OPTIONS . " where language_id='" . $languages_id . "' order by sort_order, ship_options_id");
            while ($d = tep_db_fetch_array($ship_options_query)) {
                $ship_options[] = $d['id'];
            }

            // 3. check product and options all product must have appropriate option enabled
            $this->methods = [];
            foreach ($ship_options as $ship_options_id) {
                // fill available methods array
                $this->methods[] = $ship_options_id;
            }

            if (count($this->methods) == 0) {
                $this->enabled = false;
            }
        }
    }

    public function possibleMethods()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $possibleMethods = [];
        $ship_options_query = tep_db_query(
            "select ship_options_id as id, ship_options_name as name ".
            "from " . TABLE_SHIP_OPTIONS . " ".
            "where language_id='" . $languages_id . "' ".
            " and platform_id='".\Yii::$app->get('platform')->config()->getId()."' ".
            "order by sort_order, ship_options_id"
        );
        while ($d = tep_db_fetch_array($ship_options_query)) {
            $possibleMethods[$d['id']] = $d['name'];
        }

        return $possibleMethods;
    }

// class methods
    function quote($method = '') {
        // Weight per package - SHIPPING_MAX_WEIGHT
        global $languages_id, $inc_methods, $select_id;

        $platform_id = (int)$this->platform_id;

        $all_in_one_mode = false;
        $currencies = \Yii::$container->get('currencies');
        $cart = $this->manager->getCart();

        if (is_object($cart)) {
            $_weight = $cart->show_weight();
            $this->volume = $cart->show_volume();
            if ( $_weight >= 0 ) {
                $this->shipping_weight = $_weight+ SHIPPING_BOX_WEIGHT;
                $this->total = round($cart->show_total() * $currencies->currencies[$cart->currency]['value'], 2);//find another way to get currency value
                $this->products_qty = $cart->count_contents();
            }
        }


        $methods_query = tep_db_query("select ship_options_id, restrict_access from " . TABLE_SHIP_OPTIONS . " where platform_id='" . $platform_id . "' and language_id='" . $languages_id . "' order by sort_order");
        $methods = array();
        $select_id = 0;
        $inc_methods = 0;
        while ($methods_fetch = tep_db_fetch_array($methods_query)) {
          // skip if 1 required
            if (!empty($method) && $method != $methods_fetch['ship_options_id'])
            {
                continue;
            }
            // skip by restriction
            if ($methods_fetch['restrict_access'] != 0 ) {
              if (\Yii::$app->user->isGuest) {
                continue;
              }
              $cId = \Yii::$app->user->getIdentity()->getId();
              $gId = \Yii::$app->user->getIdentity()->groups_id;
              if ($methods_fetch['restrict_access'] == -1) {
                /** @var \common\extensions\CustomerModules\CustomerModules $CustomerModules */
                $CustomerModules = \common\helpers\Acl::checkExtension('CustomerModules', 'checkAllowed');
                if ($CustomerModules && !$CustomerModules::checkAllowed($platform_id, $cId, $this->code, 'shipping', $methods_fetch['ship_options_id'])) {
                  continue;
                }
              } elseif ($methods_fetch['restrict_access'] != $gId) {
                continue;
              }
            }

            $tmp = $this->_quote($methods_fetch['ship_options_id']);
            if (is_array($tmp) && is_numeric($tmp['cost'])) {
                $methods[] = $tmp;
                $inc_methods++;
            }
        }

        $this->quotes = array('id' => $this->code,
            'module' => '<span class = "ship-title">' . $this->title . '</span><span class="shippingExtNote"><span>' . MODULE_SHIPPING_ZONE_TABLE_NOTE_TEXT . '</span></span>',
            'methods' => $methods,
            'tax' => \common\helpers\Tax::get_tax_rate(MODULE_SHIPPING_ZONE_TABLE_TAX_CLASS, $this->delivery['country']['id'], $this->delivery['zone_id'])
        );

        if (sizeof($this->quotes['methods']) == 0) {
            if ( defined('MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS') && MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS=='True' ){
                $this->no_cost = true;
                $this->quotes['methods'][] = array(
                    'id' => 0,
                    'title' => MODULE_SHIPPING_ZONE_TABLE_INVALID_ZONE_ALLOW,
                    'cost' => 0,
                    'cost_f' => $this->costUserCaption()?$this->costUserCaption():0.0,
                    'no_cost' => true,
                );
            }else {
                $this->quotes['error'] = PLEASE_CHECK_DATA_ZONETABLE;
            }
        }

        return $this->quotes;
    }

    /**
     * @return bool|string
     */
    public function costUserCaption()
    {
        try{
            $order = $this->manager->getOrderInstance();
        } catch (\Exception $e) {
            $order = null;
        }
        $selectedModule = $this->manager->getShipping();
        if (!$this->no_cost && $selectedModule['module'] == $this->code && $selectedModule['no_cost'])  {
          $this->no_cost = true;
        }
        
        if (
            !$this->no_cost ||
            (
                is_object($order) &&
                isset($order->info['shipping_cost_exc_tax']) &&
                $order->info['shipping_cost_exc_tax'] > 0
            )
        ) {
            return false;
        }
        return '<span class="shipping_quote_item_method_no_cost"></span>';
    }

    function _quote($method_id) {
        global $languages_id, $min_price, $inc_methods;

        $prefix = 'order';

        if($this->isQuote()) {
            $prefix = 'quote';
        }

        $platform_id = (int)$this->platform_id;

        $postcode = str_replace(' ', '', $this->delivery['postcode']);
        if ( strlen($postcode)>10 ) $postcode = substr($postcode, 0, 10);

        $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES_TO_SHIP_ZONES . " gz where gz.platform_id='" . $platform_id . "' and (gz.zone_country_id = '" . $this->delivery['country']['id'] . "' or gz.zone_country_id=0 ) and (gz.zone_id = '" . $this->delivery['zone_id'] . "' or gz.zone_id = 0 ) and if(gz.start_postcode<>'',gz.start_postcode<='" . tep_db_input($postcode) . "',1) and if(gz.stop_postcode<>'',gz.stop_postcode >= '" . tep_db_input($postcode) . "',1) and if(gz.city<>'',gz.city = '" . tep_db_input($this->delivery['city']) . "',1) order by gz.start_postcode desc");
        $check = tep_db_fetch_array($check_query);

        if ($check['total'] == 0) {
            // error!!!
            if ( defined('MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS') && MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS=='True' ){
                $this->no_cost = true;
                return false;
            }
            $this->quotes = array(
                'id' => $this->code,
                'error' => MODULE_SHIPPING_ZONE_TABLE_INVALID_ZONE,
                'module' => MODULE_SHIPPING_ZONE_TABLE_TEXT_TITLE,
            );
            return $this->quotes;
        }


        if ( preg_match('/^([A-Za-z][A-Ha-hJ-Yj-y]?[0-9][A-Za-z0-9]? ?[0-9][A-Za-z]{2}|[Gg][Ii][Rr] ?0[Aa]{2})$/',str_replace(' ','',$postcode)) ){
            $_postcode = $this->search_uk_zip($postcode, $this->delivery['country_id'], '[@@FIELD@@]');
            $search_by_postcode_sql =
                "and if(length(gz.start_postcode),gz.start_postcode<=substring(" . str_replace('[@@FIELD@@]', 'gz.start_postcode', $_postcode) . ",1,length(gz.start_postcode)),1) ".
                "and if(length(gz.stop_postcode),gz.stop_postcode>=substring(" . str_replace('[@@FIELD@@]', 'gz.stop_postcode', $_postcode) . ",1,length(gz.stop_postcode)),1) ";
            $query_order_by =
                "if(length(gz.start_postcode),length(gz.start_postcode),length(gz.stop_postcode)) desc, gz.zone_country_id desc";
        }else{
            $search_by_postcode_sql =
                "and if(gz.start_postcode<>'',gz.start_postcode<='" . tep_db_input($postcode) . "',1) ".
                "and if(gz.stop_postcode<>'',gz.stop_postcode >= '" . tep_db_input($postcode) . "',1) ";
            $query_order_by =
                "gz.start_postcode desc, gz.zone_country_id desc";
        }

        $sql = "select * from " . TABLE_SHIP_OPTIONS . " so, " . TABLE_ZONE_TABLE . " zt, " . TABLE_ZONES_TO_SHIP_ZONES . " gz, " . TABLE_SHIP_ZONES . " sz
                where so.ship_options_id = zt.ship_options_id
                  and zt.ship_zone_id = gz.ship_zone_id
                  and (gz.zone_id = '" . $this->delivery['zone_id'] . "'
                      or gz.zone_id=0)
                  and (zt.country_id = '" . $this->delivery['country_id'] . "'
                      or zt.country_id=0)
                  and (gz.zone_country_id = '" . $this->delivery['country_id'] . "'
                      or gz.zone_country_id=0)
                  {$search_by_postcode_sql}
                  and if(gz.city<>'',gz.city >= '" . tep_db_input($this->delivery['city']) . "',1)
                  and sz.ship_zone_id = zt.ship_zone_id
                  and zt.ship_options_id='" . $method_id . "'
                  and so.language_id = '" . $languages_id . "'
                  and zt.enabled=1 and zt.type='".$prefix."'
                  and so.platform_id='" . $platform_id . "'
                  and zt.platform_id='" . $platform_id . "'
                  and gz.platform_id='" . $platform_id . "'
                  and sz.platform_id='" . $platform_id . "'
                order by {$query_order_by} ";
//echo $sql."<hr>\n";
        $query = tep_db_query($sql);
        $data = tep_db_fetch_array($query);

        $price = null;
        $shipping_method = $data['ship_options_name'];

        $shipping_value2 = false;
        if (($data['per_kg_price'] > 0) /* && ($data['mode'] == '0') */ && !tep_not_null($data['rate'])) {
          if ($data['mode']==self::TABLE_MODE_VOLUME_PRICE || $data['mode']==self::TABLE_MODE_VOLUME) {
              $shipping_value = $this->volume;
              $price = ($shipping_value ) * $data['per_kg_price'];
          } else {
              $shipping_value = $this->shipping_weight;
              $price = ($shipping_value ) * $data['per_kg_price'];
          }
        } else {
            $price = -1;
            $skipPerKg = false;
            switch ($data['mode']) {
                case self::TABLE_MODE_PRICE:
                    $shipping_value = $this->total;
                    break;
                case self::TABLE_MODE_QUANTITY:
                    $shipping_value = $this->products_qty;
                    break;
                case self::TABLE_MODE_WEIGHT_PRICE:
                    $shipping_value = $this->shipping_weight;
                    $shipping_value2 = round($this->total,2);
                    break;
                case self::TABLE_MODE_VOLUME_PRICE:
                    $shipping_value = $this->volume;
                    $shipping_value2 = round($this->total,2);
                    break;
                case self::TABLE_MODE_VOLUME:
                    $shipping_value = $this->volume;
                    break;
                default:
                case self::TABLE_MODE_WEIGHT:
                    $shipping_value = $this->shipping_weight;
                    break;
            }

            $last_value = 0;
            $rates = explode(';', $data['rate']);
            foreach ($rates as $rate_info) {
                if (empty($rate_info)) {
                    continue;
                }
                $rate_info = explode(':', $rate_info);

                $innerTableArray = array();
                $_startInnerTable = strpos($rate_info[1],'(');
                if ( $_startInnerTable!==false ) {
                    $innerTable = trim(substr($rate_info[1],$_startInnerTable),'()');
                    $rate_info[1] = substr($rate_info[1],0, $_startInnerTable);
                    foreach(explode('|',$innerTable) as $innerRow){
                        list($from, $to, $value) = explode('@',$innerRow, 3);
                        $innerTableArray[] = array(
                            'from' => $from, 'to'=>$to, 'value'=>$value,
                        );
                    }
                }

                if ($shipping_value < $rate_info[0] && $shipping_value >= $last_value) {
                    $price = $rate_info[1];
                    // {{ rewrite in inner
                    if ( $shipping_value2!==false && count($innerTableArray)>0 ) {
                        foreach ($innerTableArray as $idx => $innerData) {
                            $loHit = ( strlen($innerData['from'])==0 || number_format($innerData['from'],6,'.','')<=number_format($shipping_value2,6,'.',''));
                            $hiHit = ( strlen($innerData['to'])==0 || number_format($innerData['to'],6,'.','')>number_format($shipping_value2,6,'.','') );

                            if ( $loHit && $hiHit ) {
                                if ($price <= 0 && $innerData['value']==0 && strlen($innerData['to'])==0) {
                                  $skipPerKg = true;
                                }
                                
                                $price = $innerData['value'];
                                break;
                            } 
                        }
                    }
                    // }} rewrite in inner
                }
                $last_value = $rate_info[0];
            }
            // per kg price and special 0 price
            if ($price == 0 && ($data['per_kg_price'] > 0) && !$skipPerKg) {
                $price = ($shipping_value ) * $data['per_kg_price'];
            }
        }

        if ( $data['except_flag'] ) $price = -1;

        if ($price >= 0) {
            if ($data['handling_price'] > 0) {
                $price += $data['handling_price'];
            }

            if ($inc_methods == 0)
                $min_price = $price;
            if ($price <= $min_price && $price != 0) {
                $this->select_id = (int) $inc_methods;
                $min_price = $price;
            }

            if ($price >= 0) {
                return array('id' => $data['ship_options_id'],
                    'title' => $shipping_method,//'<span class="ship-img">' . tep_image($this->icon, $shipping_method) . '</span>',
                    //'title' => $shipping_method . "\$shipping_value = $shipping_value \$shipping_value2 $shipping_value2 \$price $price",
                    'tax' => MODULE_SHIPPING_ZONE_TABLE_TAX_CLASS,
                    'cost' => $price,
                    'description' => '<div class="shippingNote">'.self::get_checkout_note($data).'</div>',
                    'selected' => 0);
            }
        }
    }

    public function configure_keys() {
        return array(
            'MODULE_SHIPPING_ZONE_TABLE_STATUS' =>
                array(
                    'title' => 'Enable Table Method',
                    'value' => 'True',
                    'description' => 'Do you want to offer Zone Table rate shipping?',
                    'sort_order' => '0',
                    'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                ),
            'MODULE_SHIPPING_ZONE_TABLE_HANDLING' =>
                array(
                    'title' => 'Handling Fee',
                    'value' => '0',
                    'description' => 'Handling fee for this shipping method.',
                    'sort_order' => '0',
                ),
            'MODULE_SHIPPING_ZONE_TABLE_TAX_CLASS' =>
                array(
                    'title' => 'Tax Class',
                    'value' => '0',
                    'description' => 'Use the following tax class on the shipping fee.',
                    'sort_order' => '0',
                    'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
                    'set_function' => 'tep_cfg_pull_down_tax_classes(',
                ),
            'MODULE_SHIPPING_ZONE_TABLE_SORT_ORDER' =>
                array(
                    'title' => 'Sort Order',
                    'value' => '0',
                    'description' => 'Sort order of display.',
                    'sort_order' => '0',
                ),
            'MODULE_SHIPPING_ZONE_ALLOW_ERROR_PASS' =>
                array(
                    'title' => 'If no rates found allow complete order',
                    'value' => 'False',
                    'description' => 'Display "please call us for a quote" and allow complete order.',
                    'sort_order' => '0',
                    'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_SHIPPING_ZONE_TABLE_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_SHIPPING_ZONE_TABLE_SORT_ORDER');
    }

    function extra_params() {
        global $languages_id;

        $platform_id = (int)\Yii::$app->request->get('platform_id');
        if ($platform_id == 0) {
            $platform_id = (int)\Yii::$app->request->post('platform_id');
        }

        $languages = \common\helpers\Language::get_languages();

        $default_postcode_mode = 2;

        $tab = \Yii::$app->request->post('tab', '');
        $action = \Yii::$app->request->post('action', '');
        switch ($action) {
            case 'add_option':
                $next_id_query = tep_db_query("select max(ship_options_id) as ship_options_id from " . TABLE_SHIP_OPTIONS . "");
                $next_id = tep_db_fetch_array($next_id_query);
                $ship_options_id = $next_id['ship_options_id'] + 1;
                for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
                    $sql_data_array = array(
                        'ship_options_id' => $ship_options_id,
                        'language_id' => $languages[$i]['id'],
                        'ship_options_name' => '',
                        'sort_order' => $ship_options_id,
                        'restrict_access' => 0,
                        'platform_id' => $platform_id,
                    );
                    tep_db_perform(TABLE_SHIP_OPTIONS, $sql_data_array);
                }
                break;
            case 'del_option':
                $sID = \Yii::$app->request->post('params');
                tep_db_query("delete from " . TABLE_SHIP_OPTIONS . " where ship_options_id = '" . (int)$sID . "'");
                tep_db_query("delete from " . TABLE_ZONE_TABLE . " where ship_options_id = '" . (int)$sID . "'");
                break;
            case 'add_zone';
                $sql_data_array = array(
                    'ship_zone_name' => '',
                    'ship_zone_description' => '',
                    'date_added' => 'now()',
                    'platform_id' => $platform_id,
                );
                tep_db_perform(TABLE_SHIP_ZONES, $sql_data_array);

                break;
            case 'del_zone':
                $zID = \Yii::$app->request->post('params');
                tep_db_query("delete from " . TABLE_SHIP_ZONES . " where ship_zone_id = '" . (int)$zID . "'");
                tep_db_query("delete from " . TABLE_ZONES_TO_SHIP_ZONES . " where ship_zone_id = '" . (int)$zID . "'");
                break;
            case 'add_ship_zone';
                $zID = \Yii::$app->request->post('params');
                $start_postcode = \Yii::$app->request->post('start_postcode');
                $stop_postcode = \Yii::$app->request->post('stop_postcode');
                $city = \Yii::$app->request->post('city');
                $zone_country_id = \Yii::$app->request->post('zone_country_id');
                $zone_id = \Yii::$app->request->post('zone_id');
                $except_flag = \Yii::$app->request->post('except_flag');
                $postcode_mode = \Yii::$app->request->post('postcode_mode');
                if ( isset($postcode_mode[$zID]) && $postcode_mode[$zID]==1 ){
                    $stop_postcode[$zID] = $start_postcode[$zID];
                    if ($zID == \Yii::$app->request->post('params',-1)) {
                        $default_postcode_mode = 1;
                    }
                }

                $sql_data_array = array(
                    'zone_country_id' => (int)$zone_country_id[$zID],
                    'zone_id' => (int)$zone_id[$zID],
                    'ship_zone_id' => (int)$zID,
                    'date_added' => 'now()',
                    'start_postcode' => $start_postcode[$zID],
                    'stop_postcode' => $stop_postcode[$zID],
                    'city' => $city[$zID],
                    'except_flag' => $except_flag[$zID],
                    'platform_id' => $platform_id,
                );
                if ( !empty($sql_data_array['start_postcode']) && $sql_data_array['start_postcode']==$sql_data_array['stop_postcode'] && preg_match('/[;,]/',$sql_data_array['start_postcode']) ){
                    $batch_postcodes = preg_split('/[;,]/',$sql_data_array['start_postcode'],-1,PREG_SPLIT_NO_EMPTY);
                    foreach ($batch_postcodes as $batch_postcode){
                        $batch_postcode_start = $batch_postcode_end = trim($batch_postcode);
                        if ( strpos($batch_postcode_start, ' - ')!==false ) {
                            list($batch_postcode_start, $batch_postcode_end) = explode(' - ',$batch_postcode_start,2);
                        }
                        $sql_data_array['start_postcode'] = $batch_postcode_start;
                        $sql_data_array['stop_postcode'] = $batch_postcode_end;
                        tep_db_perform(TABLE_ZONES_TO_SHIP_ZONES, $sql_data_array);
                    }
                }else {
                    tep_db_perform(TABLE_ZONES_TO_SHIP_ZONES, $sql_data_array);
                }
                break;
            case 'del_ship_zone';
                $sID = \Yii::$app->request->post('params');
                tep_db_query("delete from " . TABLE_ZONES_TO_SHIP_ZONES . " where association_id = '" . (int)$sID . "'");
                break;
            case 'add_table':
                $ship_zone_id = \Yii::$app->request->post('ship_zone_id_1');
                if(empty($ship_zone_id)) {
                    $ship_zone_id = \Yii::$app->request->post('ship_zone_id_2');
                    if(empty($ship_zone_id)) {
                        $ship_zone_id = \Yii::$app->request->post('ship_zone_id_3');
                    }
                }
                if(empty($ship_zone_id)) {
                    break;
                }
                $type = \Yii::$app->request->post('type');
                $next_id_query = tep_db_query("select max(zone_table_id) as zone_table_id from " . TABLE_ZONE_TABLE . "");
                $next_id = tep_db_fetch_array($next_id_query);
                $zone_table_id = $next_id['zone_table_id'] + 1;

                $ship_options_query = tep_db_query("select ship_options_id as id from " . TABLE_SHIP_OPTIONS . " where platform_id='" . $platform_id . "' and language_id='" . $languages_id . "' order by sort_order");
                while($d = tep_db_fetch_array($ship_options_query)){
                    $sql_data_array = array(
                        'zone_table_id' => $zone_table_id,
                        'ship_zone_id' => $ship_zone_id,
                        'ship_options_id' => $d['id'],
                        'rate' => '',
                        'type' => $type,
                        'platform_id' => $platform_id,
                    );
                    tep_db_perform(TABLE_ZONE_TABLE, $sql_data_array);
                }


                break;
            case 'del_table':
                $zID = \Yii::$app->request->post('params');
                tep_db_query("delete from " . TABLE_ZONE_TABLE . " where zone_table_id = '" . (int)$zID . "'");
                break;
            default:
                break;
        }

        $saveto = \Yii::$app->request->post('saveto', '');
        switch ($saveto) {
            case 'zones':
                $ship_zone_name = \Yii::$app->request->post('ship_zone_name');
                $zones_query = tep_db_query("select * from " . TABLE_SHIP_ZONES . " where platform_id='" . $platform_id . "'");
                while ($zones = tep_db_fetch_array($zones_query)) {
                    if (isset($ship_zone_name[$zones['ship_zone_id']])) {
                        tep_db_query("update " . TABLE_SHIP_ZONES . " set ship_zone_name = '" . tep_db_input($ship_zone_name[$zones['ship_zone_id']]) . "' where ship_zone_id = '" . (int)$zones['ship_zone_id'] . "'");
                    }
                }
                break;
            case 'options':
                $ship_options_name = \Yii::$app->request->post('ship_options_name');
                $sort_order = \Yii::$app->request->post('sort_order');
                $restrict_access = array_map('intval', \Yii::$app->request->post('restrict_access'));
                $options_query = tep_db_query("select * from " . TABLE_SHIP_OPTIONS . " where platform_id='" . $platform_id . "'");
                while ($options = tep_db_fetch_array($options_query)) {
                    if (isset($ship_options_name[$options['ship_options_id']][$options['language_id']])) {
                        tep_db_query("update " . TABLE_SHIP_OPTIONS . " set ship_options_name = '" . tep_db_input($ship_options_name[$options['ship_options_id']][$options['language_id']]) . "' where ship_options_id = '" . (int)$options['ship_options_id'] . "' and language_id='" . (int)$options['language_id'] . "' and platform_id='" . $platform_id . "'");
                    }
                    if (isset($sort_order[$options['ship_options_id']])) {
                        tep_db_query("update " . TABLE_SHIP_OPTIONS . " set sort_order = '" . (int)$sort_order[$options['ship_options_id']] . "', restrict_access='" . $restrict_access[$options['ship_options_id']] . "' where ship_options_id = '" . (int)$options['ship_options_id'] . "' and platform_id='" . $platform_id . "'");
                    }
                }
                break;
            case 'table':
                $enabled = \Yii::$app->request->post('enabled');
                $mode = \Yii::$app->request->post('mode');
                $handling_price = \Yii::$app->request->post('handling_price');
                $per_kg_price = \Yii::$app->request->post('per_kg_price');
                $rate = \Yii::$app->request->post('rate');
                $new_rate = \Yii::$app->request->post('new_rate');
                $price_rate = \Yii::$app->request->post('price_rate');
                $checkout_note = \Yii::$app->request->post('checkout_note', []);

                $table_query = tep_db_query("select zone_table_id, ship_zone_id from " . TABLE_ZONE_TABLE . " where platform_id='" . $platform_id . "' group by zone_table_id");
                while($table = tep_db_fetch_array($table_query)){
                    $zone_table_id = $table['zone_table_id'];
                    $ship_zone_id = $table['ship_zone_id'];
                    //$zones_query = tep_db_query("select ship_zone_id from " . TABLE_SHIP_ZONES . " where 1");
                    //while($zones = tep_db_fetch_array($zones_query)){
                    //$ship_zone_id = $zones['ship_zone_id'];
                    $options_query = tep_db_query("select ship_options_id from " . TABLE_SHIP_OPTIONS . " where language_id = '" . (int)$languages_id . "' and platform_id='" . $platform_id . "'");
                    while ($options = tep_db_fetch_array($options_query)) {
                        $ship_options_id = $options['ship_options_id'];

                        //rate
                        $sql_data_array = [];

                        $sql_data_array['enabled'] = (isset($enabled[$zone_table_id][$ship_options_id]) ? $enabled[$zone_table_id][$ship_options_id] : 0);
                        $sql_data_array['mode'] = (isset($mode[$zone_table_id][$ship_options_id]) ? $mode[$zone_table_id][$ship_options_id] : 0);
                        $sql_data_array['handling_price'] = (isset($handling_price[$zone_table_id][$ship_options_id]) ? $handling_price[$zone_table_id][$ship_options_id] : 0);
                        $sql_data_array['per_kg_price'] = (isset($per_kg_price[$zone_table_id][$ship_options_id]) ? $per_kg_price[$zone_table_id][$ship_options_id] : 0);

                        if (in_array($sql_data_array['mode'], array(3, 4))) {
                            $line_price_rate = (isset($price_rate[$zone_table_id][$ship_options_id]) && is_array($price_rate[$zone_table_id][$ship_options_id])) ? $price_rate[$zone_table_id][$ship_options_id] : array();
                            $value_true = \common\helpers\Zones::stick_shipping_rates($rate[$zone_table_id][$ship_options_id], $line_price_rate);
                            $value_new_add = \common\helpers\Zones::stick_shipping_rates($new_rate[$zone_table_id][$ship_options_id], $line_price_rate);
                        } else {
                            $value_true = \common\helpers\Zones::stick_shipping_rates($rate[$zone_table_id][$ship_options_id]);
                            $value_new_add = \common\helpers\Zones::stick_shipping_rates($new_rate[$zone_table_id][$ship_options_id]);
                        }
                        if (strlen($value_new_add)) $value_true .= $value_new_add;
                        // {{ sort ASC
                        if (preg_match_all('/(([^:]*):([^;]*);)/', $value_true, $_table)) {
                            array_multisort($_table[2], SORT_NUMERIC, $_table[1]);
                            $value_true = implode('', $_table[1]);
                        }
                        // }} sort ASC
                        $sql_data_array['rate'] = $value_true;


                        $tst = tep_db_fetch_array(tep_db_query("SELECT count(*) AS c FROM " . TABLE_ZONE_TABLE . " WHERE zone_table_id = '" . $zone_table_id . "' AND ship_zone_id = '" . $ship_zone_id . "' AND ship_options_id='" . $ship_options_id . "' AND platform_id='" . $platform_id . "'"));
                        if ($tst['c'] == 0) {
                            $sql_data_array['country_id'] = 0;
                            $sql_data_array['zone_table_id'] = $zone_table_id;
                            $sql_data_array['ship_zone_id'] = $ship_zone_id;
                            $sql_data_array['ship_options_id'] = $ship_options_id;
                            $sql_data_array['platform_id'] = $platform_id;
                            tep_db_perform(TABLE_ZONE_TABLE, $sql_data_array);
                        } else {
                            tep_db_perform(TABLE_ZONE_TABLE, $sql_data_array, 'update', "zone_table_id = '" . $zone_table_id . "' and ship_zone_id = '" . $ship_zone_id . "' and ship_options_id='" . $ship_options_id . "' and platform_id='" . $platform_id . "'");
                        }

                        //}
                        $table_note_filter = [
                            'zone_table_id' => $zone_table_id,
                            'ship_zone_id' => $ship_zone_id,
                            'ship_options_id' => $ship_options_id,
                            'platform_id' => $platform_id,
                        ];
                        $db_checkout_note_collection = \common\models\ShippingZoneTableCheckoutNote::find()
                            ->where($table_note_filter)
                            ->all();
                        $db_checkout_note_collection = \yii\helpers\ArrayHelper::index($db_checkout_note_collection, 'language_id');
                        $_note_post_key = implode('_',$table_note_filter);
                        if (isset($checkout_note[$_note_post_key]) && is_array($checkout_note[$_note_post_key])) {
                            foreach ($checkout_note[$_note_post_key] as $_post_lang_id => $checkout_note_string) {
                                if (!isset($db_checkout_note_collection[$_post_lang_id])) {
                                    $updateModel = new \common\models\ShippingZoneTableCheckoutNote(array_merge($table_note_filter,['language_id' => $_post_lang_id]));
                                } else {
                                    $updateModel = $db_checkout_note_collection[$_post_lang_id];
                                    unset($db_checkout_note_collection[$_post_lang_id]);
                                }
                                $updateModel->setAttributes(['checkout_note' => $checkout_note_string], false);
                                $updateModel->save();
                            }
                        }
                        foreach ($db_checkout_note_collection as $notUpdatedModel) {
                            $notUpdatedModel->delete();
                        }
                    }
                }
                break;
            default:
                break;
        }

        if (empty($tab)) {
            $tab = 'table';
        }

        $html = '';
        if (!\Yii::$app->request->isAjax) {
            $html .= '<div id="modules_extra_params">';
        }

        $html .= '<input type="hidden" name="action" value="">';
        $html .= '<input type="hidden" name="type" value="">';
        $html .= '<input type="hidden" name="params" value="">';
        $html .= tep_draw_hidden_field('tab', $tab);
        $html .= tep_draw_hidden_field('saveto', $tab);

        $html .= '<div style="margin-bottom: 20px">';
        $html .= '<a href="javascript:void(0)" onclick="return changeTab(\'table\');" class="btn-tab btn'.($tab == 'table' ?' btn-primary' : '').'">' . TEXT_SHIPPING_TABLE . '</a>';
        $html .= '&nbsp;<a href="javascript:void(0)" onclick="return changeTab(\'zones\');" class="btn-tab btn'.($tab == 'zones' ?' btn-primary' : '').'">' . TEXT_SHIPPING_ZONES . '</a>';
        $html .= '&nbsp;<a href="javascript:void(0)" onclick="return changeTab(\'options\');" class="btn-tab  btn'.($tab == 'options' ?' btn-primary' : '').'">' . TEXT_SHIPPING_OPTIONS . '</a>';
        $html .= '</div>';

        switch ($tab) {
            case 'zones':
                \common\helpers\Translation::init('admin/cities');
                $html .= '<div class="main-tab">';//START OFF BLOCK
                $html .= '<table width="100%" class="selected-methods">';
                $html .= '<tr><th width="70">'.TABLE_HEADING_ACTION.'</th><th width="210">'.TABLE_HEADING_TITLE.'</th><th>'.IMAGE_DETAILS.'</th></tr>';
                $zones_query = tep_db_query("select ship_zone_id, ship_zone_name, ship_zone_description, last_modified, date_added from " . TABLE_SHIP_ZONES . " where platform_id='" . $platform_id . "' order by ship_zone_name");
                while ($zones = tep_db_fetch_array($zones_query)) {
                    $html .= '<tr><td valign="top"><span style="position: sticky;top: 120px;" class="delMethod" onclick="delZoneMethod(\'' . $zones['ship_zone_id'] . '\')"></span></td><td valign="top">';
                    $html .= '<input style="position: sticky;top: 120px;" type="text" name="ship_zone_name[' . $zones['ship_zone_id'] . ']" value="' . $zones['ship_zone_name'] . '">';
                    $html .= '</td><td>';
                    $ship_zones_query = tep_db_query("select a.association_id, a.zone_country_id, a.except_flag, c.countries_name, a.start_postcode, a.stop_postcode, a.city, a.zone_id, a.ship_zone_id, a.last_modified, a.date_added, z.zone_name from " . TABLE_ZONES_TO_SHIP_ZONES . " a left join " . TABLE_COUNTRIES . " c on a.zone_country_id = c.countries_id and c.language_id='" . $languages_id . "' left join " . TABLE_ZONES . " z on a.zone_id = z.zone_id where a.ship_zone_id = " . $zones['ship_zone_id'] . " and a.platform_id='" . $platform_id . "' order by c.countries_name, z.zone_name, a.start_postcode, a.stop_postcode, association_id");
                    $html .= '<table width="100%">';
                    $html .= '<tr><th width="110">'.TEXT_EXCEPT.'</th><th width="210">'.TABLE_HEADING_COUNTRY_NAME.'</th><th width="210">'.TABLE_HEADING_COUNTRY_ZONE.'</th><th>'.TABLE_HEADING_START_POSTCODE.'</th><th>'.TABLE_HEADING_STOP_POSTCODE.'</th><th width="210">'.TABLE_HEADING_CITY_NAME.'</th><th width="55">'.TABLE_HEADING_ACTION.'</th></tr>';
                    while ($ship_zones = tep_db_fetch_array($ship_zones_query)) {
                        $html .= '<tr>';
                        $html .= '<td>' . (($ship_zones['except_flag']) ? TEXT_EXCEPT : '') . '</td>';
                        $html .= '<td>' . (($ship_zones['countries_name']) ? $ship_zones['countries_name'] : TEXT_ALL_COUNTRIES) . '</td>';
                        $html .= '<td>' . (($ship_zones['zone_id']) ? $ship_zones['zone_name'] : TEXT_ALL_ZONES) . '</td>';
                        if (!empty($ship_zones['start_postcode']) && $ship_zones['start_postcode']==$ship_zones['stop_postcode']) {
                            $html .= '<td colspan="2">' . (($ship_zones['start_postcode']) ? $ship_zones['start_postcode'] : '-') . '</td>';
                        }else {
                            $html .= '<td>' . (($ship_zones['start_postcode']) ? $ship_zones['start_postcode'] : '-') . '</td>';
                            $html .= '<td>' . (($ship_zones['stop_postcode']) ? $ship_zones['stop_postcode'] : '-') . '</td>';
                        }

                        $html .= '<td>' . (($ship_zones['city']) ? $ship_zones['city'] : '-') . '</td>';
                        $html .= '<td><span class="delMethod" onclick="delShipZoneMethod(\'' . $ship_zones['association_id'] . '\')"></span></td>';
                        $html .= '</tr>';
                    }
                    $html .= '<tr style="vertical-align: top">';
                    $html .= '<td>' . tep_draw_pull_down_menu('except_flag['.$zones['ship_zone_id'].']', [['id'=>0,'text'=>''],['id'=>1,'text'=>TEXT_EXCEPT]],'', 'style="width:100px"') . '</td>';
                    $html .= '<td>' . tep_draw_pull_down_menu('zone_country_id['.$zones['ship_zone_id'].']', \common\helpers\Country::get_countries('', false, TEXT_ALL_COUNTRIES), '', 'onChange="update_zone(this.form, '.$zones['ship_zone_id'].');"') . '</td>';
                    $html .= '<td>' . tep_draw_pull_down_menu('zone_id['.$zones['ship_zone_id'].']', \common\helpers\Zones::prepare_country_zones_pull_down()) . '</td>';
                    $html .= '<td class="js-postcode1"'.($default_postcode_mode==1?' colspan="2"':'').'>' . tep_draw_input_field('start_postcode['.$zones['ship_zone_id'].']', '', 'size="10"') . '<br>';
                    $html .= '<label><input class="js-postcode-mode" type="radio" name="postcode_mode['.$zones['ship_zone_id'].']" value="2" '.($default_postcode_mode==1?'':'checked').'> Post code Range</label><br>';
                    $html .= '<label><input class="js-postcode-mode" type="radio" name="postcode_mode['.$zones['ship_zone_id'].']" value="1" '.($default_postcode_mode==1?'checked':'').'> Single Post code</label>';
                    $html .= '</td>';
                    $html .= '<td class="js-postcode2"'.($default_postcode_mode==1?' style="display:none"':'').'>' . tep_draw_input_field('stop_postcode['.$zones['ship_zone_id'].']', '', 'size="10"') . '</td>';
                    $html .= '<td><div class="f_country" style="position: relative;">' . tep_draw_input_field('city['.$zones['ship_zone_id'].']', '', 'size="10" code="'.$zones['ship_zone_id'].'" class="ui-autocomplete-input"') . '</div></td>';
                    $html .= '<td><span class="addMethod" onclick="addShipZoneMethod(\'' . $zones['ship_zone_id'] . '\')"></span></td>';
                    $html .= '</tr>';

                    $html .= '</table>';
                    $html .= '</td></tr>';
                }
                $html .= '<tr><td><span class="addMethod" onclick="return addZoneMethod();"></span></td><td>&nbsp;</td><td></td></tr>';
                $html .= '</table><br><br>';
                $html .= '</div>';//END OFF BLOCK
                $html .='
<script type="text/javascript">
(function(){$(function(){
    $(\'#saveModules\').on(\'click\', \'.js-postcode-mode\', function(e){
       var $r = $(e.target); var $rp = $r.parents(\'.js-postcode1\');
       console.log($r.val())
       if ($r.val()==\'1\'){
         $rp.parent().find(\'.js-postcode2\').hide();
         $rp.attr(\'colspan\',\'2\');
       }else{
         $rp.removeAttr(\'colspan\');
         $rp.parent().find(\'.js-postcode2\').show();  
       }
    });
$(\'input[name^="city"]\').autocomplete({
      source: function(request, response) {
        $.getJSON("' . \Yii::$app->urlManager->createUrl('countries/address-city') . '", { term : request.term, country: $(\'select[name="zone_country_id[\'+$(this.element).attr("code")+\']"]\').val() }, response);
      },
      minLength: 0,
      autoFocus: true,
      delay: 0,
      appendTo: \'.f_country\',
      open: function (e, ui) {
        if ($(this).val().length > 0) {
          var acData = $(this).data(\'ui-autocomplete\');
          acData.menu.element.find(\'a\').each(function () {
            var me = $(this);
            var keywords = acData.term.split(\' \').join(\'|\');
            me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), \'<b>$1</b>\'));
          });
        }
      },
      select: function( event, ui ) {
        setTimeout(function(){
          $(\'input[name^="city"]\').trigger(\'change\');
        }, 200)
      }
    }).focus(function () {
      $(this).autocomplete("search");
    });

})})(jQuery)
</script>
';
                break;
            case 'options':
                $html .= '<div class="main-tab">';//START OFF BLOCK
                $html .= '<table width="100%" class="selected-methods">';
                $html .= '<tr><th width="10%">'.TABLE_HEADING_ACTION.'</th><th width="80%">'.TABLE_HEADING_TITLE.'</th><th width="10%">' . TEXT_SORT_ORDER . '</th></tr>';
                $options_query = tep_db_query("select * from " . TABLE_SHIP_OPTIONS . " where language_id = '" . (int)$languages_id . "' and platform_id='" . $platform_id . "' order by sort_order,ship_options_id");
                while ($options = tep_db_fetch_array($options_query)) {
                    $html .= '<tr><td><span class="delMethod" onclick="delOptionMethod(\'' . $options['ship_options_id'] . '\')"></span></td><td>';
                    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                        $html .= $languages[$i]['image'] . '&nbsp;<input type="text" style="width:92%" name="ship_options_name[' . $options['ship_options_id'] . '][' . $languages[$i]['id'] . ']" value="' . static::get_ship_options_name($options['ship_options_id'], $languages[$i]['id']) . '">' . '<br>';
                    }
                    $html .= '</td><td><input type="text" name="sort_order[' . $options['ship_options_id'] . ']" value="' . $options['sort_order'] . '">';
                    $html .= '<br />' . TEXT_RESTRICTIONS . ':<br />';
                    $html .= '' . static::getGroupsRestrictionPulldown('restrict_access[' .  $options['ship_options_id'] . ']', $options['restrict_access']);
                    $html .= '</td></tr>';
                }
                $html .= '<tr><td><span class="addMethod" onclick="return addOptionMethod();"></span></td><td>&nbsp;</td></tr>';
                $html .= '</table><br><br>';
                $html .= '</div>';//END OFF BLOCK
                break;
            case 'table':
            default:

                $html .= '<div class="main-tab">';//START OFF BLOCK
                $html .= '
    <ul class="nav nav-tabs" id="cartTab">
      <li class="active"><a data-toggle="tab" data-id="1" href="#panel1">Order</a></li>';
                if (\common\helpers\Acl::checkExtension('Quotations', 'allowed')) {
                    $html .= '<li><a data-toggle="tab" data-id="2" href="#panel2">Quotation</a></li>';
                }
                if (\common\helpers\Acl::checkExtension('Samples', 'allowed')) {
                    $html .= '<li><a data-toggle="tab" data-id="3" href="#panel3">Sample</a></li>';
                }
                $html .='</ul>

    <div class="tab-content" id="cartPanel">';

                $html .= '<div id="panel1" class="tab-pane fade in active">';
                $html .= static::get_zone_setup_tab($platform_id, 'Order','order', 'ship_zone_id_1');
                $html .= '</div>';
                if (\common\helpers\Acl::checkExtension('Quotations', 'allowed')) {
                    $html .= '<div id="panel2" class="tab-pane fade">';
                    $html .= static::get_zone_setup_tab($platform_id, 'Quotation','quote', 'ship_zone_id_2');
                    $html .= '</div>';
                }
                if (\common\helpers\Acl::checkExtension('Samples', 'allowed')) {
                    $html .= '<div id="panel3" class="tab-pane fade">';
                    $html .= static::get_zone_setup_tab($platform_id, 'Sample','sample', 'ship_zone_id_3');
                    $html .= '</div>';
                }

                $html .= '</div></div>
    <script>
        (function(){$(function(){
            var openPanel = $.cookie("openPanel");
            if(openPanel){
                $(\'#cartTab a[href="#panel\'+openPanel+\'"]\').tab(\'show\');
            }
            $("#cartTab li a").click(function() {
                $.cookie("openPanel",$(this).attr("data-id"));
            });
        })})(jQuery);
        $(document).ready(function(){
             $("#modules_extra_params").on(\'click\', \'.table_mode\', function(event){
                var $radio = $(event.target);
                var $container = $radio.parents(\'.zone-table-box-content\').find(\'.js-table-cell\');
                if ($radio.data(\'mode\')){
                  $container.addClass(\'weight_with_price\');
                }else{
                  $container.removeClass(\'weight_with_price\');
                }
                $("#modules_extra_params").trigger("update_mode_heading",[event.target]);
            });
            $("#modules_extra_params").on("click",".tblPriceRange .js-btn-add",function(event) {
              var $table = $(event.target).parents(".tblPriceRange");
              var newRowHtml = $table.find("tfoot").html();
              newRowHtml = newRowHtml.replace(/data-name/g, "name");
              newRowHtml = newRowHtml.replace(/%%counter%%/g, $table.data("counter"));
              $table.data("counter", parseInt($table.data("counter"))+1);
              $table.find("tbody").append(newRowHtml);
            });
            $(document).on("click",".tblPriceRange .js-btn-delete",function(event) {
               $(event.target).parents("tr").first().remove();
            });
            $("#modules_extra_params").on("update_mode_heading",function(event, radio){
                if ( radio ) {
                   var $radio = $(radio);
                   if ( $radio.data("table-head") ) {
                      $radio.parents(".fieldset-content").find(".js-mode-heading").html($radio.data("table-head"));
                   }
                }else{
                   $(".table_mode:checked").each(function(){
                       var $radio = $(this);
                       if ( $radio.data("table-head") ) {
                          $radio.parents(".fieldset-content").find(".js-mode-heading").html($radio.data("table-head"));
                       }
                   });
               }
            });
            $("#modules_extra_params").trigger("update_mode_heading");
        });
    </script>
    ';

                break;
        }
        if (!\Yii::$app->request->isAjax) {
            $html .= '</div>';

            $html .= '<script type="text/javascript">
function submitForm() {
    $.post("' . tep_href_link('modules/extra-params') . '", $(\'form[name=modules]\').serialize(), function(data, status) {
        if (status == "success") {
            $(\'#modules_extra_params\').html(data);
        } else {
            alert("Request error.");
        }
    },"html");
    return false;
}

function changeTab(tab) {
    $("input[name=\'tab\']").val(tab);
    submitForm();
    return false;
}

function addOptionMethod() {
    $("input[name=\'action\']").val("add_option");
    submitForm();
    return false;
}

function delOptionMethod(id) {
    $("input[name=\'action\']").val("del_option");
    $("input[name=\'params\']").val(id);
    submitForm();
    return false;
}

function addZoneMethod() {
    $("input[name=\'action\']").val("add_zone");
    submitForm();
    return false;
}

function delZoneMethod(id) {
    $("input[name=\'action\']").val("del_zone");
    $("input[name=\'params\']").val(id);
    submitForm();
    return false;
}

function addShipZoneMethod(id) {
    $("input[name=\'action\']").val("add_ship_zone");
    $("input[name=\'params\']").val(id);
    submitForm();
    return false;
}

function delShipZoneMethod(id) {
    $("input[name=\'action\']").val("del_ship_zone");
    $("input[name=\'params\']").val(id);
    submitForm();
    return false;
}

function update_zone(theForm, id) {
  var NumState = theForm.elements[\'zone_id[\'+id+\']\'].options.length;
  var SelectedCountry = "";

  while(NumState > 0) {
    NumState--;
    theForm.elements[\'zone_id[\'+id+\']\'].options[NumState] = null;
  }

  SelectedCountry = theForm.elements[\'zone_country_id[\'+id+\']\'].options[theForm.elements[\'zone_country_id[\'+id+\']\'].selectedIndex].value;

' .  tep_js_zone_list('SelectedCountry', 'theForm', 'elements[\'zone_id[\'+id+\']\']') . '

}

function addTableMethod(cart) {
    $("input[name=\'action\']").val("add_table");
    $("input[name=\'type\']").val(cart);
    submitForm();
    return false;
}

function delTableMethod(id) {
    $("input[name=\'action\']").val("del_table");
    $("input[name=\'params\']").val(id);
    submitForm();
    return false;
}

function add_row_cost(obj_id,new_obj_html){
	var div = document.createElement(\'div\');

	var $attachTo = $("#"+obj_id);
	if ( new_obj_html.indexOf("%%row_count%%")!==-1 ) {
       var count = ""+($attachTo.data("counter")||"");
       new_obj_html = new_obj_html.replace(/%%row_count%%/g,count);
       new_obj_html = new_obj_html.replace(/%%row_count_2%%/g,( count.length>0?(parseInt(count)+1):count ));
       if (count.length>0) { $attachTo.data("counter",parseInt(count)+2); }
    }
	div.innerHTML = new_obj_html;
	document.getElementById(obj_id).appendChild(div);
}

function delete_row_cost($obj){
	$obj.parentNode.parentNode.removeChild($obj.parentNode);
}

function delete_tr_cost($obj){
	$obj.parentNode.parentNode.parentNode.removeChild($obj.parentNode.parentNode);
}

</script>';
        }

        return $html;
    }

    public static function get_zone_setup_tab($platform_id, $headTitle = 'Order', $tableType = 'order', $addNewZonePulldownName = 'ship_zone_id_1')
    {
        global $languages_id;
        $languages = \common\helpers\Language::get_languages();

        $html = '';
        $html .= '
        <h2>'.$headTitle.'</h2>';

        $already_used_ship_zones = [];
        $table_query = tep_db_query("SELECT DISTINCT z.zone_table_id, c1.ship_zone_name, z.ship_zone_id FROM " . TABLE_ZONE_TABLE . " z, " . TABLE_SHIP_ZONES . " c1 WHERE z.type='".$tableType."' AND z.ship_zone_id = c1.ship_zone_id AND z.platform_id='" . $platform_id . "' AND c1.platform_id='" . $platform_id . "' ORDER BY ship_zone_name");
        while ($table = tep_db_fetch_array($table_query)) {
            $already_used_ship_zones[$table['ship_zone_id']] = $table['ship_zone_id'];
            $sql = "SELECT c.countries_name, a.start_postcode, a.stop_postcode, a.except_flag, a.city FROM zones_to_ship_zones a LEFT JOIN countries c ON a.zone_country_id = c.countries_id AND c.language_id='" . $languages_id . "' LEFT JOIN zones z ON a.zone_id = z.zone_id WHERE a.ship_zone_id = '" . $table['ship_zone_id'] . "' AND a.platform_id='" . $platform_id . "'  ORDER BY c.countries_name, association_id";
            $sql = tep_db_query($sql);
            $ship_zone_name = $table['ship_zone_name'];
            $countries = [];
            while ($country = tep_db_fetch_array($sql)) {
                $countries[] = [($country['except_flag'] ? 'Except ' : '') . $country['countries_name'], $country['stop_postcode'], $country['start_postcode'], $country['city']];
            }
            $html .= '
<div class="zone-table-box" id="zone-table-box-' . $table['zone_table_id'] . '">
  <div class="zone-table-box-header">
    <span class="delMethod" onclick="delTableMethod(\'' . $table['zone_table_id'] . '\')"></span><div class="zone-table-box-close"></div>
' . $table['ship_zone_name'] . '
</div>
  <div class="zone-table-box-content">';
            $options_query = tep_db_query("SELECT ship_options_id AS id, rate, mode, handling_price, per_kg_price, enabled FROM " . TABLE_ZONE_TABLE . " WHERE zone_table_id ='" . $table['zone_table_id'] . "' AND platform_id='" . $platform_id . "'");
            $rate_array = array();
            $mode_array = array();
            $enabled_array = array();
            $handling_price_array = array();
            $per_kg_price_array = array();
            while ($d = tep_db_fetch_array($options_query)) {
                $rate_array[$d['id']] = $d['rate'];
                $mode_array[$d['id']] = $d['mode'];
                $handling_price_array[$d['id']] = $d['handling_price'];
                $per_kg_price_array[$d['id']] = $d['per_kg_price'];
                $enabled_array[$d['id']] = $d['enabled'];
            }
            $cInfo = new \objectInfo([]);
            $cInfo->rate = $rate_array;
            $cInfo->mode = $mode_array;
            $cInfo->enabled = $enabled_array;
            $cInfo->handling_price = $handling_price_array;
            $cInfo->per_kg_price = $per_kg_price_array;


            $ship_options_query = tep_db_query("SELECT ship_options_id AS id, ship_options_name AS name FROM " . TABLE_SHIP_OPTIONS . " WHERE platform_id='" . $platform_id . "' AND language_id='" . $languages_id . "' ORDER BY sort_order");
            while ($d = tep_db_fetch_array($ship_options_query)) {
                $id = $d['id'];
                //$ship_options[$d['id']] = $d['name'];
                $html .= '<div class="fieldset"><div class="legend">' . tep_draw_checkbox_field('enabled[' . $table['zone_table_id'] . '][' . $id . ']', '1', ($cInfo->enabled[$id] == 1)) . ' ' . $d['name'] . '</div><div class="fieldset-content"' . (($cInfo->enabled[$id] != 1) ? ' style="display:none"' : '') . '>';


                $html .= '<div class="ztb-col-1 ztb-col-1-0"><strong>' . TEXT_INFO_MODE . '</strong>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_WEIGHT, ($cInfo->mode[$id] == self::TABLE_MODE_WEIGHT), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_WEIGHT) . '"') . ' ' . TEXT_INFO_WEIGHT . '</label>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_VOLUME, ($cInfo->mode[$id] == self::TABLE_MODE_VOLUME), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_VOLUME) . '"') . ' ' . TEXT_INFO_VOLUME . '</label>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_PRICE, ($cInfo->mode[$id] == self::TABLE_MODE_PRICE), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_PRICE) . '"') . ' ' . TEXT_INFO_PRICE . '</label>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_QUANTITY, ($cInfo->mode[$id] == self::TABLE_MODE_QUANTITY), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_QUANTITY) . '"') . ' ' . TEXT_INFO_QUANTITY . '</label>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_WEIGHT_PRICE, ($cInfo->mode[$id] == self::TABLE_MODE_WEIGHT_PRICE), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_WEIGHT) . '" data-mode="mode-weight_price"') . ' ' . TEXT_INFO_WEIGHT . ' + ' . TEXT_INFO_PRICE . '</label>' .
                    '<br><label>' . tep_draw_radio_field('mode[' . $table['zone_table_id'] . '][' . $id . ']', self::TABLE_MODE_VOLUME_PRICE, ($cInfo->mode[$id] == self::TABLE_MODE_VOLUME_PRICE), '', 'class="table_mode" data-table-head="' . \common\helpers\Output::output_string(TEXT_INFO_VOLUME) . '" data-mode="mode-volume_price"') . ' ' . TEXT_INFO_VOLUME . ' + ' . TEXT_INFO_PRICE . '</label>' .
                    '</div>'.
                    '<div class="ztb-col-1">
                              <div><strong>' . TEXT_PRODUCTS_PRICE_INFO . '</strong></div>
                              <div style="float:left; width:150px">' . TEXT_HANDLING_PRICE . '</div>
                              <div>' . TEXT_PER_KG_PRICE . '</div>
                              <div class="setting-row" style="clear:both">
                                <div style="float:left; width:150px">' . tep_draw_input_field('handling_price[' . $table['zone_table_id'] . '][' . $id . ']', $cInfo->handling_price[$id], 'size="5"') . '</div>

' . tep_draw_input_field('per_kg_price[' . $table['zone_table_id'] . '][' . $id . ']', $cInfo->per_kg_price[$id], 'size="5"') . '
</div>
                          </div>' .
                    '<div class="ztb-col-2 js-table-cell' . (in_array($cInfo->mode[$id], array(3, 4)) ? ' weight_with_price' : '') . '"><strong>' . TEXT_INFO_RATE .
                    '</strong><br>' . self::tep_draw_shipping_table_cost($cInfo->rate[$id], $id, $table['zone_table_id']) . '</div><div style="clear: both;"></div>';

                $html .= '<div class="ztb-col-2" style="margin: 8px"><strong>'.MODULE_SHIPPING_ZONE_TABLE_CHECKOUT_NOTE.':</strong>';

                $table_note_filter = [
                    'zone_table_id' => $table['zone_table_id'],
                    'ship_zone_id' => $table['ship_zone_id'],
                    'ship_options_id' => $id,
                    'platform_id' => $platform_id,
                ];
                for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                    $html .= '<br>'.$languages[$i]['image'] . '&nbsp;'.\common\helpers\Html::textInput('checkout_note[' . implode('_',$table_note_filter) . '][' . $languages[$i]['id'] . ']',self::get_checkout_note($table_note_filter, $languages[$i]['id']),['class'=>'form-control','style'=>'width:80%;display:inline-block']);
                }
                $html .= '</div>';

                $html .= '</div></div>';
            }
            $html .= '
  </div>
   <div class="zone-table-box-contry">
    <div class="fieldset-country">';
            $s = '<h3>' . $ship_zone_name . ' ' . BOX_TAXES_COUNTRIES . ':</h3>';
            if (!empty($countries)) {
                $s = '<h3>' . $ship_zone_name . ' ' . BOX_TAXES_COUNTRIES . ':</h3>';
                $s .= '<ul>';
                foreach ($countries as $country) {
                    if ($country[0] === null) {
                        $s .= '<li>' . TEXT_ALL_COUNTRIES . '</li>';
                    } else {
                        $_city = '';
                        if (!empty($country[3])) {
                            $_city = $country[3] . ':';
                        }
                        $_pst = '';
                        if (!empty($country[2])) {
                            $_pst = $country[2];
                        }
                        $_psp = '';
                        if (!empty($country[1])) {
                            $_psp = $country[1];
                        }
                        $addData = '';
                        if (!empty($_city) || !empty($_pst) || !empty($_psp)) {
                            $addData = "($_city $_pst $_psp)";
                        }
                        $s .= '<li>' . $country[0] . $addData . '</li>';
                    }
                }
                $s .= '</ul>';
            }
            $html .= $s . '</div>
  </div>
</div>
<script type="text/javascript">
    (function(){$(function(){
        var ztb_close = $.cookie("ztb_close");
        var ztb_close_i = -1;
        if (ztb_close){
            ztb_close_i = ztb_close.split("a").indexOf("' . $table['zone_table_id'] . '");
  } else {
            ztb_close = "";
        }
        var box = $("#zone-table-box-' . $table['zone_table_id'] . '");
  $(".zone-table-box-close", box).on("click", function(){
      $(this).toggleClass("ztb-opened");
      $(".zone-table-box-content", box).slideToggle();

      ztb_close = $.cookie("ztb_close");
      ztb_close_i = -1;
      if (ztb_close){
          ztb_close_i = ztb_close.split("a").indexOf("' . $table['zone_table_id'] . '");
      } else {
          ztb_close = "";
      }
      if (ztb_close_i == -1){
          $.cookie("ztb_close", ztb_close + "' . $table['zone_table_id'] . '" + "a")
      } else {
          $.cookie("ztb_close", ztb_close.replace("' . $table['zone_table_id'] . 'a", ""))
      }
  });
  if (ztb_close_i != -1){
      $(".zone-table-box-close", box).toggleClass("ztb-opened");
      $(".zone-table-box-content", box).slideToggle(0)
  }

  $(".legend input", box).on(\'click switchChange.bootstrapSwitch\',function(){
    var obj = $(this);
    if (obj.prop("checked") || obj.next("input").prop("checked") || obj.prev("input").prop("checked") ){
        obj.parents(".fieldset").find(".fieldset-content").show()
    } else {
        obj.parents(".fieldset").find(".fieldset-content").hide()
    }
  })
})})(jQuery)
</script>
                ';

        }

        $except_ship_zones = '';
        if ( count($already_used_ship_zones)>0) {
            $except_ship_zones = "AND ship_zone_id NOT IN('".implode("','",$already_used_ship_zones)."')";
        }
        $zones_query = tep_db_query("SELECT ship_zone_id, ship_zone_name FROM " . TABLE_SHIP_ZONES . " WHERE platform_id='" . $platform_id . "' {$except_ship_zones} ORDER BY ship_zone_name");
        if (tep_db_num_rows($zones_query) > 0) {
            $html .= '<div>' . static::ship_zones_pull_down('name="'.$addNewZonePulldownName.'"', '', $platform_id, true, $already_used_ship_zones) . ' <span class="btn" onclick="return addTableMethod(\''.$tableType.'\');">' . TEXT_ADD_SHIPPING_TABLE . '</span></div>';
        }

        return $html;
    }

    public static function tep_draw_shipping_table_cost($shipping_cost_string='',$id, $zone_table_id){

        $shipping_cost_string = trim($shipping_cost_string," ;\t\n\r\0\x0B");
        $shipping_cost = preg_split('/[;:]/',$shipping_cost_string);
        for($i=0;$i<sizeof($shipping_cost);$i+=2){
            $valueData = $shipping_cost[$i+1];
            $priceTableArray = array();
            $_startInnerTable = strpos($valueData,'(');
            if ( $_startInnerTable!==false ) {
                $innerTable = trim(substr($valueData,$_startInnerTable),'()');
                $shipping_cost[$i+1] = substr($valueData,0, $_startInnerTable);
                foreach(explode('|',$innerTable) as $innerRow){
                    list($from, $to, $value) = explode('@',$innerRow, 3);
                    $priceTableArray[] = array(
                        'from' => $from, 'to'=>$to, 'value'=>$value,
                    );
                }
            }
            $priceTableBody = '';
            $tableCount = 1;
            foreach( $priceTableArray as $innerItem) {
                $priceTableBody .=
                    '<tr><td>'.
                    '<input class="shipping_cost" type="text" name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.']['.$tableCount.'][from]" value="'.\common\helpers\Output::output_string($innerItem['from']).'">'.
                    '</td>'.
                    '<td>'.
                    '<input class="shipping_cost" type="text" name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.']['.$tableCount.'][to]" value="'.\common\helpers\Output::output_string($innerItem['to']).'">'.
                    '</td>'.
                    '<td>'.
                    '<input class="shipping_cost" type="text" name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.']['.$tableCount.'][value]" value="'.\common\helpers\Output::output_string($innerItem['value']).'">'.
                    '</td>'.
                    '<td><span class="remove-rate js-btn-delete"></span></td>'.
                    '</tr>';
                $tableCount++;
            }
            //name="price_rate[' . $zone_table_id . '][' . $id . '][][][cost]
            $output .= '<tr>
						<td class="shipping_cost">
						' . tep_draw_input_field('rate[' . $zone_table_id . '][' . $id . ']['.$i.']', $shipping_cost[$i],'size="10" value="99999" class="shipping_cost"') . '
						' . tep_draw_input_field('rate[' . $zone_table_id . '][' . $id . ']['.($i+1).']', $shipping_cost[$i+1],'size="10" value="0" class="shipping_cost"') . '

						<span onClick="delete_tr_cost(this)"  class="remove-rate"></span>
<div class="price_range">
  <table class="table tblPriceRange" data-counter="'.$tableCount.'"><thead><tr><th>Price From (&gt;=)</th><th>Price To (&lt;)</th><th>Cost</th><th><button type="button" class="btn btn-add js-btn-add"></button></th></tr></thead><tbody>'.$priceTableBody.'</tbody><tfoot><tr><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.'][%%counter%%][from]"></td><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.'][%%counter%%][to]"></td><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . ']['.$i.'][%%counter%%][value]"></td><td><span class="remove-rate js-btn-delete"></span></td></tr></tfoot></table>
</div>
						</td>
					</tr>';
        }

        $output = '<div id="id_nodesContent">
				<table border="0" cellspacing="0" cellpadding="0" class="shipping_cost">' .
            '<tr class="shipping_cost"><td class="shipping_cost_heading" style="width:105px"><span class="js-mode-heading">'.TEXT_VALUE.'</span> (&lt;)</td><td class="shipping_cost_heading">'.TEXT_COST.'</td></tr>'.
            '</table>'.
            '<div class="'.((strlen($shipping_cost_string) && ($i/2)>10)?'shipping_cost':'shipping_cost_small').'">'.
            '<table border="0" cellspacing="0" cellpadding="0" class="shipping_cost"  style="width:100%">' .
            $output .
            '</table>
			   </div>'.
            '<div id="rate_cost_' . $zone_table_id . '_'.$id.'" data-counter="'.intval($i).'"></div>'.
            '<div class="shipping_cost_width">'.
            '<input type="button" value="' . TEXT_ADD_MORE . '" onClick="add_row_cost(\'rate_cost_' . $zone_table_id . '_'.$id.'\',\'' .

            htmlspecialchars('<div class="shipping_cost2">'.
                tep_draw_input_field('new_rate[' . $zone_table_id . '][' . $id . '][%%row_count%%]', '','size="10" value="99999" class="new_shipping_cost"') . ' ' .
                tep_draw_input_field('new_rate[' . $zone_table_id . '][' . $id . '][%%row_count_2%%]', '','size="10" value="0" class="new_shipping_cost"') .
                ' <span onClick="delete_row_cost(this)"  class="remove-rate"></span>'.
'<div class="price_range">'.
   '<table class="table tblPriceRange" data-counter="1"><thead><tr><th>Price From (&gt;=)</th><th>Price To (&lt;)</th><th>Cost</th><th><button type="button" class="btn btn-add js-btn-add"></button></th></tr></thead><tbody></tbody><tfoot><tr><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . '][%%row_count%%][%%counter%%][from]"></td><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . '][%%row_count%%][%%counter%%][to]"></td><td><input class="shipping_cost" type="text" data-name="price_rate[' . $zone_table_id . '][' . $id . '][%%row_count%%][%%counter%%][value]"></td><td><span class="remove-rate js-btn-delete"></span></td></tr></tfoot></table>'.
'</div>'.
                '</div>')

            .'\')" class="btn">'.
            '</div>
			   <div id="virtual"></div>
			   </div>'
        ;

        return $output;
    }

    /**
     * @param bool $full
     *
     * @return bool
     */
    private function isQuote($full = false)
    {
        global $quote;
        if((mb_strpos(\Yii::$app->request->url,'quot') !== false)){
            if($full && !is_object($quote)) {
                return false;
            }
            return true;
        }
        return false;
    }

    private function search_uk_zip($zip, $country, $compare)
    {
        // leave only postcode district
        $ret = preg_replace('/[0-9][ABD-HJLNP-UW-Z]{2}$/i', '', $zip);
        if (preg_match('/(\D+)(\d+)/', $ret, $m)) {
            $ret = "CONCAT('" . tep_db_input($m[1]) . "', IF( LENGTH(" . $compare . ")>" . intval(strlen($m[1] . $m[2])) . ", LPAD('" . tep_db_input($m[2]) . "', LENGTH(" . $compare . ")-" . intval(strlen($m[1])) . " ,'0'),'" . tep_db_input($m[2]) . "'))";
        } else {
            $ret = "'" . tep_db_input(substr($ret, 0, 4)) . "'";
        }
        return $ret;
    }

    public static function get_checkout_note($zone_table_filter, $language_id='')
    {
        if ( empty($language_id) ){
            $language_id = \Yii::$app->settings->get('languages_id');
        }
        $filter = [
            'zone_table_id' => $zone_table_filter['zone_table_id'],
            'ship_zone_id' => $zone_table_filter['ship_zone_id'],
            'ship_options_id' => $zone_table_filter['ship_options_id'],
            'platform_id' => $zone_table_filter['platform_id'],
        ];
        $filter['language_id'] = $language_id;
        return \common\models\ShippingZoneTableCheckoutNote::find()
            ->where($filter)
            ->select(['checkout_note'])
            ->scalar();
    }

    public static function get_ship_options_name($ship_options_id, $language_id = '') {
        $languages_id = \Yii::$app->settings->get('languages_id');
        if (!is_numeric($language_id))
            $language_id = $languages_id;
        $status_query = tep_db_query("select ship_options_name from " . TABLE_SHIP_OPTIONS . " where ship_options_id = '" . (int) $ship_options_id . "' and language_id = '" . (int) $language_id . "'");
        $status = tep_db_fetch_array($status_query);
        return $status['ship_options_name'];
    }

    public static function ship_zones_pull_down($parameters, $selected = '', $platform_id = 0,$withDumb = false, $already_used_ship_zones=false) {
        $except_ship_zones = '';
        if ( is_array($already_used_ship_zones) && count($already_used_ship_zones)>0) {
            $except_ship_zones = "AND ship_zone_id NOT IN('".implode("','",$already_used_ship_zones)."')";
        }

        $select_string = '<select ' . $parameters . '>';
        $zones_query = tep_db_query("select ship_zone_id, ship_zone_name from " . TABLE_SHIP_ZONES . " where platform_id='" . (int)$platform_id . "' {$except_ship_zones} order by ship_zone_name");
        if($withDumb) {
            $select_string .= '<option value="">'. PULL_DOWN_DEFAULT .'</option>';
        }
        while ($zones = tep_db_fetch_array($zones_query)) {
            $select_string .= '<option value="' . $zones['ship_zone_id'] . '"';
            if ($selected == $zones['ship_zone_id'])
                $select_string .= ' SELECTED';
            $select_string .= '>' . $zones['ship_zone_name'] . '</option>';
        }
        $select_string .= '</select>';

        return $select_string;
    }

    public static function getGroupsRestrictionPulldown($name, $selected) {

      $arr = [];

      /** @var \common\extensions\CustomerModules\CustomerModules $CustomerModules */
      $CustomerModules = \common\helpers\Acl::checkExtension('CustomerModules', 'allowed');
      if ($CustomerModules::allowed() || $selected=='-1') {
        $arr += [-1 => TEXT_DISALLOW_ALL_ALLOW_BY_CUSTOMER];
      }

      $arr += [0 => ''];

      $tmp = \common\helpers\Group::get_customer_groups_list(0);
      if (is_array($tmp)) {
        $arr += $tmp;
      }
      if (is_array($arr)) {
        return \common\helpers\Html::dropDownList($name, $selected, $arr);
      }
      
    }

/**
 * returns all possible methods (to enable per customer group)
 * @return array [method => method title]
 */
    function getAllMethodsKeys($platform_id = null) {
      global $order, $languages_id;
      if (is_null($platform_id)){
        $platform_id = (int)$order->info['platform_id'];
      }

        $methods_query = tep_db_query("select ship_options_id, ship_options_name from " . TABLE_SHIP_OPTIONS . " where platform_id='" . $platform_id . "' and language_id='" . $languages_id . "' order by sort_order");

        $methods = array();
        while ($methods_fetch = tep_db_fetch_array($methods_query)) {
           $methods[$methods_fetch['ship_options_id']] = $methods_fetch['ship_options_name'];
        }

        return $methods;
    }

}

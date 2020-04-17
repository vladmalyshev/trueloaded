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

  class zonesperitem extends ModuleShipping {
    var $code, $title, $description, $enabled, $num_zonesperitem;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_ZONESPERITEM_TEXT_TITLE' => 'Post and Packing Rates',
        'MODULE_SHIPPING_ZONESPERITEM_TEXT_DESCRIPTION' => 'Post and Packing Rates',
        'MODULE_SHIPPING_ZONESPERITEM_TEXT_STANDARD_DELIVERY' => '%s Standard Delivery',
        'MODULE_SHIPPING_ZONESPERITEM_TEXT_PREMIUM_DELIVERY' => '%s Premium Delivery',
        'MODULE_SHIPPING_ZONESPERITEM_INVALID_ZONE' => 'No shipping available to the selected country'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'zonesperitem';
        $this->title = MODULE_SHIPPING_ZONESPERITEM_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_ZONESPERITEM_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_ZONESPERITEM_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_ZONESPERITEM_SORT_ORDER;
        $this->icon = '';
        $this->tax_class = MODULE_SHIPPING_ZONESPERITEM_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_ZONESPERITEM_STATUS == 'True') ? true : false);

      // CUSTOMIZE THIS SETTING FOR THE NUMBER OF ZONESPERITEM NEEDED
      $this->num_zonesperitem = MODULE_SHIPPING_ZONESPERITEM_NUMBER_OF_ZONES;

      /*
      for ($i = 1; $i <= $this->num_zonesperitem; $i++) {
        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Zone " . $i . " Geo Zone', 'MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_" . $i . "', '0', 'Select Geo Zone of this Zone" . $i . ".', '6', '0', '\common\helpers\Zones::get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        }

        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_INITIAL_ITEM_CHARGE_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Standard Initial Item Charge', 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_INITIAL_ITEM_CHARGE_" . $i . "', '2.50', 'Standard Initial Item Charge for Zone " . $i . " destinations.', '6', '0', now())");
        }
        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_ADDITIONAL_ITEM_CHARGE_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Standard Additional Item Charge', 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_ADDITIONAL_ITEM_CHARGE_" . $i . "', '0.50', 'Standard Additional Item Charge for this shipping zone.', '6', '0', now())");
        }
        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_FREE_DELIVERY_AMOUNT_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Standard Free Delivery Amount', 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_FREE_DELIVERY_AMOUNT_" . $i . "', '20.00', 'Amount of subtotal for free standard delivery for this shipping zone.', '6', '0', now())");
        }

        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_INITIAL_ITEM_CHARGE_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Premium Initial Item Charge', 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_INITIAL_ITEM_CHARGE_" . $i . "', '4.50', 'Premium Initial Item Charge for Zone " . $i . " destinations.', '6', '0', now())");
        }
        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_ADDITIONAL_ITEM_CHARGE_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Premium Additional Item Charge', 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_ADDITIONAL_ITEM_CHARGE_" . $i . "', '0.00', 'Premium Additional Item Charge for this shipping zone.', '6', '0', now())");
        }
        $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_FREE_DELIVERY_AMOUNT_" . $i . "'"));
        if (!$check['key_exists']) {
          tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i . " Premium Free Delivery Amount', 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_FREE_DELIVERY_AMOUNT_" . $i . "', '20.00', 'Amount of subtotal for free premium delivery for this shipping zone.', '6', '0', now())");
        }
      }
      $keys = array();
      for ($i=$this->num_zonesperitem+1; $i<=64; $i++) {
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_INITIAL_ITEM_CHARGE_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_ADDITIONAL_ITEM_CHARGE_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_STANDARD_FREE_DELIVERY_AMOUNT_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_INITIAL_ITEM_CHARGE_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_ADDITIONAL_ITEM_CHARGE_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZONESPERITEM_PREMIUM_FREE_DELIVERY_AMOUNT_' . $i;
      }
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $keys) . "')");
      */
    }

// class methods
    function quote($method = '') {
      $cart = $this->manager->getCart();

      $total_count = $this->manager->get('total_count');
      $currencies = \Yii::$container->get('currencies');
      $dest_zone = 0;
      $error = false;

      for ($i=1; $i<=$this->num_zonesperitem; $i++) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . (int)constant('MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_' . $i) . "' and zone_country_id = '" . (int)$this->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
        }
        if ($check_flag == true) {
          $dest_zone = $i;
          break;
        }
      }

      $intro = false;
      if ($dest_zone == 0) {
        $error = true;
      } else {
        $order_total = $cart->show_total();

        $standard_initial_item_charge = constant('MODULE_SHIPPING_ZONESPERITEM_STANDARD_INITIAL_ITEM_CHARGE_' . $dest_zone);
        $standard_additional_item_charge = constant('MODULE_SHIPPING_ZONESPERITEM_STANDARD_ADDITIONAL_ITEM_CHARGE_' . $dest_zone);
        $standard_free_delivery_amount = constant('MODULE_SHIPPING_ZONESPERITEM_STANDARD_FREE_DELIVERY_AMOUNT_' . $dest_zone);
        if ($order_total > $standard_free_delivery_amount) {
          $standard_shipping_cost = 0;
        } else {
          $intro = 'Spend just ' . $currencies->format($standard_free_delivery_amount - $order_total, 2) . ' more to get free delivery!';
          $standard_shipping_cost = $standard_initial_item_charge + $standard_additional_item_charge * ($total_count - 1);
        }
        $standard_shipping_method = sprintf(MODULE_SHIPPING_ZONESPERITEM_TEXT_STANDARD_DELIVERY, \common\helpers\Zones::get_zone_class_title(constant('MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_' . $i)));

        $premium_initial_item_charge = constant('MODULE_SHIPPING_ZONESPERITEM_PREMIUM_INITIAL_ITEM_CHARGE_' . $dest_zone);
        $premium_additional_item_charge = constant('MODULE_SHIPPING_ZONESPERITEM_PREMIUM_ADDITIONAL_ITEM_CHARGE_' . $dest_zone);
        $premium_free_delivery_amount = constant('MODULE_SHIPPING_ZONESPERITEM_PREMIUM_FREE_DELIVERY_AMOUNT_' . $dest_zone);
        if ($order_total > $premium_free_delivery_amount) {
          $premium_shipping_cost = 0;
        } else {
          $premium_shipping_cost = $premium_initial_item_charge + $premium_additional_item_charge * ($total_count - 1);
        }
        $premium_shipping_method = sprintf(MODULE_SHIPPING_ZONESPERITEM_TEXT_PREMIUM_DELIVERY, \common\helpers\Zones::get_zone_class_title(constant('MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_' . $i)));
      }

      if ($method == 'standard')
      {
        $this->quotes = array('id' => $this->code,
                              'module' => MODULE_SHIPPING_ZONESPERITEM_TEXT_TITLE,
                              'intro' => $intro,
                              'methods' => array(array('id' => 'standard',
                                                       'title' => $standard_shipping_method,
                                                       'cost' => $standard_shipping_cost)));
      }
      elseif ($method == 'premium')
      {
        $this->quotes = array('id' => $this->code,
                              'module' => MODULE_SHIPPING_ZONESPERITEM_TEXT_TITLE,
                              'intro' => $intro,
                              'methods' => array(array('id' => 'premium',
                                                       'title' => $premium_shipping_method,
                                                       'cost' => $premium_shipping_cost)));
      }
      else
      {
          $methods = array();
          $methods[] = array(
                'id' => 'standard',
                'title' => $standard_shipping_method,
                'cost' => $standard_shipping_cost
            );
          //if ($order->delivery['country']['id'] == STORE_COUNTRY) {
          $methods[] = array(
                'id' => 'premium',
                'title' => $premium_shipping_method,
                'cost' => $premium_shipping_cost
            );
          //}
        $this->quotes = array('id' => $this->code,
                              'module' => MODULE_SHIPPING_ZONESPERITEM_TEXT_TITLE,
                              'intro' => $intro,
                              'methods' => $methods );
      }

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      if ($error == true) $this->quotes['error'] = MODULE_SHIPPING_ZONESPERITEM_INVALID_ZONE;

      return $this->quotes;
    }

    protected function get_install_keys($platform_id)
    {
      $keys = $this->configure_keys();

      /*$prefill_values = array(
        'MODULE_SHIPPING_ZONES_COUNTRIES_1' => 'US,CA',
        'MODULE_SHIPPING_ZONES_COST_1' => '3:8.50,7:10.50,99:20.00',
      );

      foreach( $prefill_values as $_key=>$_value ) {
        if ( isset($keys[$_key]) ) {
          $keys[$_key]['value'] = $_value;
        }
      }*/

      return $keys;
    }

    public function configure_keys()
    {
      $config = array (
        'MODULE_SHIPPING_ZONESPERITEM_STATUS' =>
          array (
            'title' => 'Enable Zones Per Item Method',
            'value' => 'True',
            'description' => 'Do you want to offer zone rate shipping?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_SHIPPING_ZONESPERITEM_TAX_CLASS' =>
          array (
            'title' => 'Zones Per Item Tax Class',
            'value' => '0',
            'description' => 'Use the following tax class on the shipping fee.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
            'set_function' => 'tep_cfg_pull_down_tax_classes(',
          ),
        'MODULE_SHIPPING_ZONESPERITEM_SORT_ORDER' =>
          array (
            'title' => 'Zones Per Item Sort Order',
            'value' => '0',
            'description' => 'Sort order of display.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_ZONESPERITEM_NUMBER_OF_ZONES' =>
          array (
            'title' => 'Number of Zones',
            'value' => '0',
            'description' => 'Number of zones needed.',
            'sort_order' => '0',
          ),
      );
      for ($i = 1; $i <= $this->num_zonesperitem; $i ++) {
        $config['MODULE_SHIPPING_ZONESPERITEM_GEO_ZONE_'.$i] = array(
          'title' => 'Zone ' . $i .' Geo Zone',
          'value' => '0',
          'description' => 'Select Geo Zone of this Zone '.$i.'.',
          'sort_order' => '0',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_STANDARD_INITIAL_ITEM_CHARGE_'.$i] = array(
          'title' => 'Zone ' . $i. ' Standard Initial Item Charge',
          'value' => '2.50',
          'description' => 'Standard Initial Item Charge for Zone ' . $i . ' destinations.',
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_STANDARD_ADDITIONAL_ITEM_CHARGE_'.$i] = array(
          'title' => 'Zone ' . $i .' Standard Additional Item Charge',
          'value' => '0.50',
          'description' => 'Standard Additional Item Charge for this shipping zone.',
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_STANDARD_FREE_DELIVERY_AMOUNT_'.$i] = array(
          'title' => 'Zone ' . $i .' Standard Free Delivery Amount',
          'value' => '20.00',
          'description' => 'Amount of subtotal for free standard delivery for this shipping zone.',
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_PREMIUM_INITIAL_ITEM_CHARGE_'.$i] = array(
          'title' => 'Zone ' . $i .' Premium Initial Item Charge',
          'value' => '4.50',
          'description' => 'Premium Initial Item Charge for Zone ' . $i . ' destinations.',
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_PREMIUM_ADDITIONAL_ITEM_CHARGE_'.$i] = array(
          'title' => 'Zone ' . $i .' Premium Additional Item Charge',
          'value' => '0.00',
          'description' => 'Premium Additional Item Charge for this shipping zone.',
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_ZONESPERITEM_PREMIUM_FREE_DELIVERY_AMOUNT_'.$i] = array(
          'title' => 'Zone ' . $i .' Premium Free Delivery Amount',
          'value' => '20.00',
          'description' => 'Amount of subtotal for free premium delivery for this shipping zone.',
          'sort_order' => '0',
        );
      }
      return $config;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_SHIPPING_ZONESPERITEM_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_SHIPPING_ZONESPERITEM_SORT_ORDER');
    }

  }


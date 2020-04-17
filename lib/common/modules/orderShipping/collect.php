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
use common\helpers\Address;

class collect extends ModuleShipping {
    var $code, $title, $description, $icon, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_COLLECT_TEXT_TITLE' => 'Click & Collect',
        'MODULE_SHIPPING_COLLECT_TEXT_DESCRIPTION' => 'Collect from point'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'collect';
        $this->title = MODULE_SHIPPING_COLLECT_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_COLLECT_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_COLLECT_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_COLLECT_SORT_ORDER;
        $this->icon = '';
        $this->tax_class = MODULE_SHIPPING_COLLECT_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_COLLECT_STATUS == 'True') ? true : false);

      if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_COLLECT_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_COLLECT_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    public function getCollectAddress($method_name){
        if ($method_name){
            $list = explode("_", $method_name);
            if ($list[1]){
                $collect = $this->getCollectionsQuery($list[1])->one();
                if ($collect){
                    return $this->getAddress($collect->warehouses_address_book_id);
                }
            }
        }
    }

    private function getAddress($adId = 0){
        if ($adId){
            $aBook = \common\models\WarehousesAddressBook::find()->where(['warehouses_address_book_id' => $adId])->asArray()->one();
            if ($aBook){
                $address_format_id = Address::get_address_format_id($aBook['entry_country_id']);
                $aBook = array_pop(Address::skipEntryKey([$aBook]));
                return Address::address_format($address_format_id, $aBook, true, ' ', '<br>');
            }
        }
        return '';
    }

    private function getCollectionsQuery($method = ''){
        $_collections = \common\models\CollectionPoints::find()->orderBy('sort_order');
        if (!empty($method)){
            $_collections->where(['collection_points_id' => $method]);
        }
        return $_collections;
    }

// class methods
    function quote($method = '') {

        $methods = [];
        foreach($this->getCollectionsQuery($method)->all() as $collection_points){
            $methods[] = array('id' => $collection_points->collection_points_id,
                             'title' => $collection_points->collection_points_text,// . $this->getAddress($collection_points->warehouses_address_book_id),
                             'cost' => (0 + MODULE_SHIPPING_COLLECT_HANDLING));
        }

        $this->quotes = array('id' => $this->code,
                            'module' => MODULE_SHIPPING_COLLECT_TEXT_TITLE,
                            'methods' => $methods);

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      return $this->quotes;
    }

    public function configure_keys()
    {
      return array (
        'MODULE_SHIPPING_COLLECT_STATUS' =>
          array (
            'title' => 'Enable Collect Shipping',
            'value' => 'True',
            'description' => 'Do you want to offer collect shipping?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_SHIPPING_COLLECT_HANDLING' =>
          array (
            'title' => 'Collect Shipping Handling Fee',
            'value' => '0',
            'description' => 'Handling fee for this shipping method.',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_COLLECT_TAX_CLASS' =>
          array (
            'title' => 'Collect Shipping Tax Class',
            'value' => '0',
            'description' => 'Use the following tax class on the shipping fee.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
            'set_function' => 'tep_cfg_pull_down_tax_classes(',
          ),
        'MODULE_SHIPPING_COLLECT_ZONE' =>
          array (
            'title' => 'Collect Shipping Shipping Zone',
            'value' => '0',
            'description' => 'If a zone is selected, only enable this shipping method for that zone.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
            'set_function' => 'tep_cfg_pull_down_zone_classes(',
          ),
        'MODULE_SHIPPING_COLLECT_SORT_ORDER' =>
          array (
            'title' => 'Collect Shipping Sort Order',
            'value' => '0',
            'description' => 'Sort order of display.',
            'sort_order' => '0',
          ),
      );
    }
    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_SHIPPING_COLLECT_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_SHIPPING_COLLECT_SORT_ORDER');
    }

    public function useDelivery(){
        return false;
    }

  }

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

  namespace common\modules\orderPayment;

  use common\classes\modules\ModulePayment;
  use common\classes\modules\ModuleStatus;
  use common\classes\modules\ModuleSortOrder;


class for_collect extends ModulePayment{
    var $code, $title, $description, $enabled,$hidden;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_FOR_COLLECT_TEXT_TITLE' => 'Collect',
        'MODULE_PAYMENT_FOR_COLLECT_TEXT_DESCRIPTION' => 'Pay on Collection'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'for_collect';
        $this->hidden = false;
        $this->title = MODULE_PAYMENT_FOR_COLLECT_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_FOR_COLLECT_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_FOR_COLLECT_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_PAYMENT_FOR_COLLECT_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_FOR_COLLECT_STATUS == 'True') ? true : false);
        $this->online = false;

        if ((int)MODULE_PAYMENT_FOR_COLLECT_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_FOR_COLLECT_ORDER_STATUS_ID;
        }

        $this->update_status();
        //if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_FOR_COLLECT_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_FOR_COLLECT_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

// disable the module if the order only contains virtual products

        if ($this->enabled == true) {
            if ($this->manager ){
                $this->enabled = $this->manager->isShippingNeeded() ? true : false;
                $shipping_class = explode("_", $this->manager->getSelectedShipping());
                if ($this->forCollect() && $shipping_class[0] != 'collect') {
                    $this->enabled = false;
                }
            }
        }
    }

    public function before_process()
    {
        $order = $this->manager->getOrderInstance();
        if(is_object($order)){
            if (defined('MODULE_PAYMENT_FOR_COLLECT_TEXT_TITLE') && $order->info['payment_method']== $order->info['payment_class']) {
                $order->info['payment_method'] = MODULE_PAYMENT_FOR_COLLECT_TEXT_TITLE;
            }
        }
    }

    function selection() {
        $shipping = $this->manager->getShipping();
        $additionalInfo = [];
        if(isset($shipping['id']) && !empty($shipping['id']) ){
            if ($oCollectionsPoint = \common\models\CollectionPoints::isCollect($shipping['id'])) {
                $additionalInfo['name'] = $oCollectionsPoint->collection_points_text;
                $additionalInfo['info'] = $oCollectionsPoint->getAddress('<br />');

            }
        }

        return array('id' => $this->code,
                   'hide_row' => $this->hidden,
                   'iconCss' => 'icon_delivery',
                   'nameBlock' => $this->title,
                   'additionalInfo' => $additionalInfo,
                   'module' => $this->description);


        global $pointto;
        $selection = [
              'id' => $this->code,
              'module' => $this->title,
              'fields' => [],
            ];
        $collectionPointsList = [];
        $collection_points_query = tep_db_query("select * from " . TABLE_COLLECTION_POINTS . " where 1 order by sort_order");
        while ($collection_points = tep_db_fetch_array($collection_points_query)) {
            $collectionPointsList[] = array('id' => $collection_points['collection_points_id'], 'text' => $collection_points['collection_points_text']);
        }
        $selection ['fields'][] = array('title' => '<label for="data_cards">Please choose collection point:</label>',
                                        'field' => tep_draw_pull_down_menu('pointto', $collectionPointsList, $pointto));
        return $selection;
    }

    public function configure_keys(){
      return array(
        'MODULE_PAYMENT_FOR_COLLECT_STATUS' => array (
          'title' => 'FOR_COLLECT Enable Cash On Delivery Module',
          'value' => 'True',
          'description' => 'Do you want to accept Cash On Delevery payments?',
          'sort_order' => '1',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_FOR_COLLECT_ZONE' => array(
          'title' => 'FOR_COLLECT Payment Zone',
          'value' => '0',
          'description' => 'If a zone is selected, only enable this payment method for that zone.',
          'sort_order' => '2',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        ),
        'MODULE_PAYMENT_FOR_COLLECT_ORDER_STATUS_ID' => array (
          'title' => 'FOR_COLLECT Set Order Status',
          'value' => '0',
          'description' => 'Set the status of orders made with this payment module to this value',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_FOR_COLLECT_SORT_ORDER' => array (
          'title' => 'FOR_COLLECT Sort order of  display.',
          'value' => '0',
          'description' => 'Sort order of collect display. Lowest is displayed first.',
          'sort_order' => '0',
        ),
      );
  }

  public function describe_status_key()
  {
    return new ModuleStatus('MODULE_PAYMENT_FOR_COLLECT_STATUS', 'True', 'False');
  }

  public function describe_sort_key()
  {
    return new ModuleSortOrder('MODULE_PAYMENT_FOR_COLLECT_SORT_ORDER');
  }

  function forCollect() {
      return true;
  }

  function forPOS() {
      return true;
  }

}
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
use common\classes\platform_config;
use yii\helpers\Html;

class codpos extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_COD_POS_TEXT_TITLE' => 'COD (POS)',
        'MODULE_PAYMENT_COD_POS_TEXT_DESCRIPTION' => 'Cash On Delivery (POS)'
    ];

    // class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'codpos';
        $this->title = $this->code;
        $this->hidden = true;

        if(defined('MODULE_PAYMENT_COD_POS_TEXT_TITLE')){
            $this->title = MODULE_PAYMENT_COD_POS_TEXT_TITLE;
        }
        $this->description = 'Cash On Delivery (POS)';
        if(defined('MODULE_PAYMENT_COD_POS_TEXT_DESCRIPTION')){
            $this->description = MODULE_PAYMENT_COD_POS_TEXT_DESCRIPTION;
        }

        $this->sort_order = defined('MODULE_PAYMENT_COD_POS_SORT_ORDER') ? MODULE_PAYMENT_COD_POS_SORT_ORDER : 0;
        if(defined('MODULE_PAYMENT_COD_POS_STATUS')){
            $this->enabled = ((MODULE_PAYMENT_COD_POS_STATUS == 'True') ? true : false);
        }else{
            $this->enabled = false;
        }
        $this->online = true;
        if(defined('MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID')){
            if ((int)MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID;
            }
        }else{
            $this->order_status = 1;
        }

        $this->update_status();
    }

    // class methods
    function update_status() {

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_COD_POS_ZONED > 0) ) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_COD_POS_ZONED . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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
            if (is_object($this->manager) && !$this->manager->isShippingNeeded()) {
                $this->enabled = false;
            }
        }
    }

    function selection() {
        $selection = array('id' => $this->code,
                           'module' => $this->title,
                           'hide_row' => $this->hidden,
                           /*'icon' => '<img src="'.tep_href_link('images/account.png').'">',/**/
                           'fields' => [],
        );

        return $selection;
    }


    function before_process()
    {
        global $cart;
        $order = $this->manager->getOrderInstance();

        if (defined('MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID') && MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID > 0) {
            $order->info['order_status'] = MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID;
            if(is_object($cart)){
                $cart->setOrderStatus($order->info['order_status']);
            };
        }

        if (defined('MODULE_PAYMENT_COD_POS_TEXT_DESCRIPTION') && $order->info['payment_method']== $order->info['payment_class']) {
            $order->info['payment_method'] = MODULE_PAYMENT_COD_POS_TEXT_DESCRIPTION;
        }
    }

    public function configure_keys(){
        return array(
            'MODULE_PAYMENT_COD_POS_STATUS' => array (
                'title' => 'COD Enable Cash On Delivery For POS',
                'value' => 'True',
                'description' => 'Do you want to accept COD(POS)?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_COD_POS_ZONED' => array(
                'title' => 'COD(POS) Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '2',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_COD_POS_ORDER_STATUS_ID' => array (
                'title' => 'COD(POS) Set Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_COD_POS_SORT_ORDER' => array (
                'title' => 'COD(POS) Sort order of  display.',
                'value' => '0',
                'description' => 'Sort order of COD(POS) display. Lowest is displayed first.',
                'sort_order' => '0',
            ),
        );
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_PAYMENT_COD_POS_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_PAYMENT_COD_POS_SORT_ORDER');
    }

    function isOnline() {
        return false;
    }

    function forShop() {
        return false;
    }

    function forPOS() {
        return true;
    }
    function forAdmin() {
        return false;
    }

}
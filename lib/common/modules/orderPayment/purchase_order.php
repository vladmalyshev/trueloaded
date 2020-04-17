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


class purchase_order extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PURCHASE_TEXT_TITLE' => 'Purchase order',
        'MODULE_PAYMENT_PURCHASE_TEXT_DESCRIPTION' => 'For Purchase orders',
        'MODULE_PAYMENT_PURCHASE_ORDER_NUMBER_TEXT' => 'Order number',
        'MODULE_PAYMENT_PURCHASE_ORDER_NOTES' => ''
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'purchase_order';
        $this->title = MODULE_PAYMENT_PURCHASE_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PURCHASE_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_PURCHASE_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_PAYMENT_PURCHASE_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PURCHASE_STATUS == 'True') ? true : false);
        $this->online = false;

        if ((int)MODULE_PAYMENT_PURCHASE_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_PURCHASE_ORDER_STATUS_ID;
        }

        $this->update_status();
    }

    function update_status() {

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PURCHASE_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PURCHASE_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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

      if ($this->enabled == true) {
        if ($this->manager ){
            $this->enabled = $this->manager->isShippingNeeded() ? true : false;
        }
      }
    }

    function javascript_validation() {
        $message = MODULE_PAYMENT_PURCHASE_ORDER_NUMBER_TEXT;
        $js = <<<EOD
if (payment_value == "{$this->code}") {
    var purchase_order = $('#purchase_order').val();
    if (purchase_order == "") {
        error_message = error_message + " {$message} " + "\\n";
        error = 1;
    }
}
EOD;
        return $js;
    }

 /*   function getJS(){
return <<<EOD
<script>
function checkPO(){
    if ($('input[name=payment][value="{$this->code}"]').is(':checked')){
        $('.purchase_order_class').closest('.sub-item').show();
    } else {
        $('.purchase_order_class').closest('.sub-item').hide();
    }
    $('.purchase_order_class').css('display', 'inline-block');
    $('.purchase_order_class').prev().css('display', 'inline-block');
}
if (typeof tl == 'function'){
    tl(function(){ checkPO();
        $('input[name=payment]').change(function(){ checkPO(); })
    })
}
</script>
EOD;
    }
*/
    function selection() {
        $order = $this->manager->getOrderInstance();

        $selection = array('id' => $this->code,
                         'module' => $this->title,
                         'fields' => array()
        );
        $selection ['fields'][] = array('title' => '<label for="data_cards">' .MODULE_PAYMENT_PURCHASE_ORDER_NUMBER_TEXT .'</label>',
                                        'field' => tep_draw_input_field('purchase_order', $order->info['purchase_order'],' class="purchase_order_class" id="purchase_order"  ') . $this->getJS());

        if (defined('MODULE_PAYMENT_PURCHASE_ORDER_NOTES') && !empty(MODULE_PAYMENT_PURCHASE_ORDER_NOTES)) {
          $selection['notes'][] = MODULE_PAYMENT_PURCHASE_ORDER_NOTES;
        }
        return $selection;
    }

    function pre_confirmation_check() {
        $order = $this->manager->getOrderInstance();

        $order->info['purchase_order'] = isset($_POST['purchase_order'])?$_POST['purchase_order']:'';

        if(empty($order->info['purchase_order'])){
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . MODULE_PAYMENT_PURCHASE_ORDER_NUMBER_TEXT . ' required&error=1', 'SSL'));
        }
        $_SESSION['purchase_order'] = $order->info['purchase_order'];

    }

    function confirmation() {
      $order = $this->manager->getOrderInstance();
      $confirmation = array(
                            'fields' => array(  array('title' => MODULE_PAYMENT_PURCHASE_ORDER_NUMBER_TEXT,
                                                    'field' =>  $order->info['purchase_order']),
                                               ));

        return $confirmation;
    }

    public function configure_keys(){
      return array(
        'MODULE_PAYMENT_PURCHASE_STATUS' => array (
          'title' => 'Enable Purchase Module',
          'value' => 'True',
          'description' => 'Do you want to accept Purchase payments?',
          'sort_order' => '1',
          'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
        ),
        'MODULE_PAYMENT_PURCHASE_ZONE' => array(
          'title' => 'Payment Zone',
          'value' => '0',
          'description' => 'If a zone is selected, only enable this payment method for that zone.',
          'sort_order' => '2',
          'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
          'set_function' => 'tep_cfg_pull_down_zone_classes(',
        ),
        'MODULE_PAYMENT_PURCHASE_ORDER_STATUS_ID' => array (
          'title' => 'Set Order Status',
          'value' => '0',
          'description' => 'Set the status of orders made with this payment module to this value',
          'sort_order' => '0',
          'set_function' => 'tep_cfg_pull_down_order_statuses(',
          'use_function' => '\\common\\helpers\\Order::get_order_status_name',
        ),
        'MODULE_PAYMENT_PURCHASE_SORT_ORDER' => array (
          'title' => 'Sort order of  display.',
          'value' => '0',
          'description' => 'Sort order of purchase display. Lowest is displayed first.',
          'sort_order' => '0',
        ),
      );
  }

  public function describe_status_key()
  {
    return new ModuleStatus('MODULE_PAYMENT_PURCHASE_STATUS', 'True', 'False');
  }

  public function describe_sort_key()
  {
    return new ModuleSortOrder('MODULE_PAYMENT_PURCHASE_SORT_ORDER');
  }

}
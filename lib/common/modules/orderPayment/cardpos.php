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

class cardpos extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_CARD_POS_TEXT_TITLE' => 'Credit Card (POS)',
        'MODULE_PAYMENT_CARD_POS_TEXT_DESCRIPTION' => 'Credit Card payment (POS)',
        'MODULE_PAYMENT_CARD_POS_TEXT_REF_ID' => 'Reference ID'
    ];

    // class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'cardpos';
        $this->title = $this->code;
        $this->hidden = false;
        $this->runScript = false;

        if(defined('MODULE_PAYMENT_CARD_POS_TEXT_TITLE')){
            $this->title = MODULE_PAYMENT_CARD_POS_TEXT_TITLE;
        }



        $this->reftext = 'Refernce Id';
        if(defined('MODULE_PAYMENT_CARD_POS_TEXT_REF_ID')){
            $this->reftext = MODULE_PAYMENT_CARD_POS_TEXT_REF_ID;
        }

        $this->description = 'Credit Card payment (POS)';
        if(defined('MODULE_PAYMENT_CARD_POS_TEXT_DESCRIPTION')){
            $this->description = MODULE_PAYMENT_CARD_POS_TEXT_DESCRIPTION;
        }

        $this->sort_order = defined('MODULE_PAYMENT_CARD_POS_SORT_ORDER') ? MODULE_PAYMENT_CARD_POS_SORT_ORDER : 0;
        if(defined('MODULE_PAYMENT_CARD_POS_STATUS')){
            $this->enabled = ((MODULE_PAYMENT_CARD_POS_STATUS == 'True') ? true : false);
        }else{
            $this->enabled = false;
        }
        $this->online = true;
        if(defined('MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID')){
            if ((int)MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID;
            }
        }else{
            $this->order_status = 1;
        }

        $this->update_status();
    }

    // class methods
    function update_status() {

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_CARD_POS_ZONED > 0) ) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CARD_POS_ZONED . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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
        if ($this->enabled == true ) {
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
        $selection ['fields'][] = [
            'title' => '',
            'hide_input' => true,
            'field' => Html::input('hidden', 'card_pos_ref','', ['id'=>'card_pos_ref'])
        ];
        return $selection;
    }
    function confirmation()
    {

        $output = <<<EOD
<script type="text/javascript">
            cardPosPopUp("{$this->reftext}");
           function cardPosPopUp(data){
              $("body").append('<div class="popup-box-wrap"><div class="around-pop-up"></div><div class="popup-box" style="height:50%;width: 50%;"><div class="pop-up-close"></div><div class="pop-up-content alert-message"> <div class="pop-up-content alert-message"> <div class="box_12 box_white form_content bottom_2 top_2 border_bottom_grey"> <div class="box_10 left_2 right_2 top_4 text_normal"> '+data+'</div></div><div class="box_12 box_white form_content top_2 border_bottom_grey bottom_2"> <div class="box_10 left_2 right_2 "> <input value="" id="card_pos_ref_id" name="card_pos_ref_id" class="text_normal" type="text"> </div><div class="box_2"><button class="but ok_cod_pos" type="button">OK</button></div> </div> </div> </div></div></div>');

              var d = ($(window).height() - $(".popup-box").height()) / 2;
              if (d < 0) d = 0;
              $(".popup-box-wrap").css("top", $(window).scrollTop() + d);

              $(".pop-up-close, .around-pop-up").click(function(){
                $(".ok_cod_pos").click();
                return false
              });
              $(".popup-box").on("click", ".ok_cod_pos", function(){
                 var card_pos_ref_id = $('#card_pos_ref_id').val();
                 if(card_pos_ref_id.length > 2){
                    $('#card_pos_ref').val(card_pos_ref_id);
                    $(".popup-box-wrap:last").remove();
                    $('#edit_order').submit();
                    return false
                 }
              });
              $('body').on('keypress','#card_pos_ref_id',function (e) {
                if (e.which == 13) {
                    $(this).blur();
                    $('.ok_cod_pos').focus().click();
                    e.preventDefault();
                }
             });
           }
        </script>
EOD;
        $confirmation = ['title' => $output,'runScript'=>$this->runScript];
        return $confirmation;
    }
    function process_button()
    {
        return false;
    }
    function before_process()
    {
        global $cart;
        $order = $this->manager->getOrderInstance();

        $ref_id = Yii::$app->request->post('card_pos_ref', '');

        if (defined('MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID') && MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID > 0) {
            $order->info['order_status'] = MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID;
            if(is_object($cart)){
                $cart->setOrderStatus($order->info['order_status']);
            };
        }

        $order->info['card_reference_id'] = $ref_id;

        if (defined('MODULE_PAYMENT_CARD_POS_TEXT_DESCRIPTION') && $order->info['payment_method']== $order->info['payment_class']) {
            $order->info['payment_method'] = MODULE_PAYMENT_CARD_POS_TEXT_DESCRIPTION;
        }

    }

    public function configure_keys(){
        return array(
            'MODULE_PAYMENT_CARD_POS_STATUS' => array (
                'title' => 'Credit Card Enable For POS',
                'value' => 'True',
                'description' => 'Do you want to accept Credit Card(POS)?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_CARD_POS_ZONED' => array(
                'title' => 'Credit Card(POS) Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '2',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_CARD_POS_ORDER_STATUS_ID' => array (
                'title' => 'Credit Card(POS) Set Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_CARD_POS_SORT_ORDER' => array (
                'title' => 'Credit Card(POS) Sort order of  display.',
                'value' => '0',
                'description' => 'Sort order of Credit Card(POS) display. Lowest is displayed first.',
                'sort_order' => '0',
            ),
        );
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_PAYMENT_CARD_POS_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_PAYMENT_CARD_POS_SORT_ORDER');
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
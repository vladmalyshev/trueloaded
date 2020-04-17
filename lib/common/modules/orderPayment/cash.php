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

class cash extends ModulePayment{
    var $code, $title, $description, $enabled;

    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_CASH_TEXT_TITLE' => 'Cash',
        'MODULE_PAYMENT_CASH_TEXT_BUTTON' => 'Tender',
        'MODULE_PAYMENT_CASH_TEXT_DESCRIPTION' => 'Сash payment',
        'MODULE_PAYMENT_CASH_TEXT_CUSTOM' => 'Custom',
        'MODULE_PAYMENT_CASH_ERROR_MESSAGE' => 'Wrong cash value'
    ];

    // class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'cash';
        $this->title = 'Cash';
        if(defined('MODULE_PAYMENT_CASH_TEXT_TITLE')){
            $this->title = MODULE_PAYMENT_CASH_TEXT_TITLE;
        }
        $this->description = 'Сash payment';
        if(defined('MODULE_PAYMENT_CASH_TEXT_DESCRIPTION')){
            $this->description = MODULE_PAYMENT_CASH_TEXT_DESCRIPTION;
        }

        $this->button = $this->title;
        if(defined('MODULE_PAYMENT_CASH_TEXT_BUTTON')){
            $this->button = MODULE_PAYMENT_CASH_TEXT_BUTTON;
        }

        $this->sort_order = defined('MODULE_PAYMENT_CASH_SORT_ORDER') ? MODULE_PAYMENT_CASH_SORT_ORDER : 0;
        if(defined('MODULE_PAYMENT_CASH_STATUS')){
            $this->enabled = ((MODULE_PAYMENT_CASH_STATUS == 'True') ? true : false);
        }else{
            $this->enabled = false;
        }
        $this->online = true;
        if(defined('MODULE_PAYMENT_CASH_ORDER_STATUS_ID')){
            if ((int)MODULE_PAYMENT_CASH_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_CASH_ORDER_STATUS_ID;
            }
        }else{
            $this->order_status = 1;
        }

        $this->update_status();
    }

    // class methods
    function update_status() {

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_CASH_ZONED > 0) ) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CASH_ZONED . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
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
            if ($order->content_type == 'virtual') {
                $this->enabled = false;
            }
        }
    }

    function selection() {
        $style1 = '
        <style>
        .payment_class_cash{
            position: relative;
        }
        .payment_class_cash .payment_settings{
            padding-right: 1vw;
            padding-bottom: 1vw;
            width: 33.33333%;
            float: right;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        </style>';
        $style2 = '
        <style>
        .payment_class_cash{
            position: relative;
            display:table;
        }
        .payment_class_cash .payment_settings {
            display:table-footer-group;
        }
        </style>';
        $style3 = '
        <style>
        .payment_class_cash{
            position: relative;
        }
        .payment_class_cash .payment_settings{
            padding-right: 1vw;
            padding-bottom: 1vw;
            width: 67%;
            float: right;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        </style>';
        $js = '
            <script>
                var bodySelect = $("body");
                $(bodySelect).on("change input","#cash_val_text",function() {
                    var valInput = $(this).val();
                    if(valInput.length > 0 ){
                        $("input[name=cash_val]").prop("checked", false);
                        $("input[name=payment][value=cash]").prop("checked", true).trigger("change");
                    }
                });
                $(bodySelect).on("click","input[name=cash_val]",function() {
                    if($(this).prop("checked")) {
                        $("#cash_val_text").val("");
                    }
                    var previousValue = $(this).attr("previousValue");
                    var name = $(this).attr("name");
                    if (previousValue == "checked")
                    {
                      $(this).removeAttr("checked");
                      $(this).attr("previousValue", false);
                    }
                    else
                    {
                      $("input[name="+name+"]:radio").attr("previousValue", false);
                      $(this).attr("previousValue", "checked");
                    }
                    $("input[name=payment][value=cash]").prop("checked", true).trigger("change");
                });
            </script>
        ';
        $selection = [
            'id' => $this->code,
            'style' => $style1.$js,
            'iconCss' => 'icon_cash',
            'hide_input' => true,
            'module' => $this->title,
            'nameBlock' => $this->description,
            'button' => $this->button,
            'icon' => '<img src="'.tep_href_link('images/account.png').'">',
            'fields' => []
        ];

        $oCurrency = \common\models\Currencies::find()->where(['currencies_id' => \Yii::$app->settings->get('currency_id')])->limit(1)->one();
        if(!$oCurrency || empty($oCurrency->nominalsVal)){
            $selection ['fields'][] = [
                'title' => defined("TEXT_NO_METHODS_AVAILABLE") ? TEXT_NO_METHODS_AVAILABLE : 'no methods available',
                'field' => ''
            ];
            return $selection;
        }
        sort($oCurrency->nominalsVal);

        $totalCart = intval(ceil($this->getTotalOrder()));
        if($totalCart < 1){
            $selection ['fields'][] = [
                'title' => defined("TEXT_NO_METHODS_AVAILABLE") ? TEXT_NO_METHODS_AVAILABLE : 'no methods available',
                'field' => ''
            ];
            return $selection;
        }
        $bills = $this->roundUp($totalCart,$oCurrency->nominalsVal);

        foreach ($bills as $key => $nominal){
            if($key == 5){
                break;
            }
            $selection ['fields'][] = [
                'title' => '',
                'field' => Html::radio('cash_val', false, ['class' => 'form_radio','id'=>'r'.($nominal),'value' => ($nominal)]).'<label for="r'.($nominal).'"><span class="text_2_bold">'.($nominal).'</span></label>'
            ];

        }

        $selection ['fields'][] = [
            'title' => '',
            'field' => Html::input('number', 'cash_val_text','', ['class' => 'input_text_big','id'=>'cash_val_text','placeholder'=> MODULE_PAYMENT_CASH_TEXT_CUSTOM])
        ];
        if(count($selection ['fields'])  % 3 == 0){
            $selection['style'] = $style2.$js;
        }
        if(count($selection ['fields'])  % 3 == 1){
            $selection['style'] = $style3.$js;
        }
        return $selection;
    }
    private function roundUp($cost,$nominals)
    {
        $result = [];
        foreach ($nominals as $nominal) {
            $result[] = (int)(($cost + $nominal - 1) / $nominal) * $nominal;
        }
        $result = array_unique($result);
        sort($result);
        return $result;
    }

    function before_process() {
        global $order, $cash_val_text,$cash_val,$cart;

        $cashCheck = round((float)Yii::$app->request->post('cash_val_text', 0.00),2);
        if($cashCheck == 0){
            $cashCheck = round((float)Yii::$app->request->post('cash_val', 0.00),2);
        }

        if($cashCheck == 0){
            if(isset($cash_val_text) && $cash_val_text > 0.00){
                $cashCheck = $cash_val_text;
            }elseif(isset($cash_val) && $cash_val > 0.00){
                $cashCheck = $cash_val;
            }
        }
        if($cashCheck == 0){
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error='.urlencode($this->code).'&error=' . urlencode(MODULE_PAYMENT_CASH_ERROR_MESSAGE), 'SSL'));
        }

        if(!isset($platform_id) || !$platform_id){
            $platform_id = \common\classes\platform::currentId();
        }
        $pltformConfig = new platform_config($platform_id);
        $pltformConfig->constant_up();

        $totalCart = $this->getTotalOrder();

        $order->info['cash_data_summ']= $cashCheck;
        $order->info['cash_data_change'] = round(($cashCheck - $totalCart),2);

        if(isset($order->info['order_id']) && $order->info['order_id'] > 0){
            \common\models\Orders::updateAll(['cash_data_summ'=>$order->info['cash_data_summ'],'cash_data_change'=>$order->info['cash_data_change']],['orders_id'=>$order->info['order_id']]);
        }

        if (defined('MODULE_PAYMENT_CASH_ORDER_STATUS_ID') && MODULE_PAYMENT_CASH_ORDER_STATUS_ID > 0) {
            $order->info['order_status'] = MODULE_PAYMENT_CASH_ORDER_STATUS_ID;
            if(is_object($cart)){
                $cart->setOrderStatus($order->info['order_status']);
            };
        }
        if (tep_session_is_registered('cash_val_text')){
            tep_session_unregister('cash_val_text');
        }
        if (tep_session_is_registered('cash_val')){
            tep_session_unregister('cash_val');
        }

        if (defined('MODULE_PAYMENT_CASH_TEXT_DESCRIPTION') && $order->info['payment_method']== $order->info['payment_class']) {
            $order->info['payment_method'] = MODULE_PAYMENT_CASH_TEXT_DESCRIPTION;
        }

    }
    public function getTotalOrder()
    {
        global $order;
        $currencies = \Yii::$container->get('currencies');
        if(defined('USE_MARKET_PRICES')){
            $recalculate = (USE_MARKET_PRICES == 'True' ? false : true);
        }else{
            $recalculate = false;
        }
        return round($currencies->format_clear($currencies->calculate_price_in_order($order->info, $order->info['total']), $recalculate, $order->info['currency']),2);
    }

    public function configure_keys(){
        return array(
            'MODULE_PAYMENT_CASH_STATUS' => array (
                'title' => 'CASH Enable Cash Payment Module',
                'value' => 'True',
                'description' => 'Do you want to accept Cash Payment?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_CASH_ZONED' => array(
                'title' => 'CASH Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '2',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_CASH_ORDER_STATUS_ID' => array (
                'title' => 'CASH Set Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_CASH_SORT_ORDER' => array (
                'title' => 'CASH Sort order of  display.',
                'value' => '0',
                'description' => 'Sort order of CASH display. Lowest is displayed first.',
                'sort_order' => '0',
            ),
        );
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_PAYMENT_CASH_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_PAYMENT_CASH_SORT_ORDER');
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
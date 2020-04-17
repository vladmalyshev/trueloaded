<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use backend\models\EP\Tools;
use common\api\models\Soap\Order\Order;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;
use yii\helpers\ArrayHelper;

class CreateOrderResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var integer
     * @soap
     */
    public $orders_id;

    /**
     * @var \common\classes\order
     */
    protected $order;

    protected $status_history_array = false;

    public $asPurchaseOrder = false;

    protected $afterSaveOrderPatch = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setOrder(Order $orderData)
    {
        $this->afterSaveOrderPatch = [];
        global $cart, $order_total_modules, $order;
        if ( !is_object($cart) ) {
            $cart = new \common\classes\shopping_cart();
        }
        $cart->reset(true);

        $orderData = (array)$orderData;

        $tools = new Tools();

        $manager = \common\services\OrderManager::loadManager();

        $order = $manager->createOrderInstance('\common\classes\Order');
        $order->withDelivery = true;

        if ( isset($orderData['order_id']) && $orderData['order_id'] ) {
            $this->warning('Field \'orders_id\' - read-only');
        }
        $order->order_id = null;

        if (empty($orderData['client_order_id'])) {
            $this->error('Missing \'client_order_id\'');
        }

        $customer = (array)$orderData['customer'];
        if (isset($customer['country_iso2'])) {
            $countryId = $tools->getCountryId($customer['country_iso2']);
            $customer['country_id'] = $countryId;
            $country_info = \common\helpers\Country::get_country_info_by_id($countryId);
            $customer['country'] = [
                'id' => $countryId,
                'title' => $country_info['countries_name'],
                'iso_code_2' => $country_info['countries_iso_code_2'],
                'iso_code_3' => $country_info['countries_iso_code_3'],
            ];
            $customer['format_id'] = \common\helpers\Address::get_address_format_id($countryId);
        }
        if (!empty($customer['country_id']) && !empty($customer['state'])) {
            $customer['zone_id'] = \common\helpers\Zones::get_zone_id($customer['country_id'],$customer['state']);
        }

        if ( !is_array($order->customer) ) $order->customer = [];
        foreach (array_keys($customer/*$order->customer*/) as $key) {
            $order->customer[$key] = isset($customer[$key]) ? $customer[$key] : null;
        }

        if ( $this->asPurchaseOrder ) {
            // pass as 0 now
        }else {
            if (empty($order->customer['customer_id'])) {
                $this->error('Missing \'customer_id\'');
            } else {
                $check_customer_r = tep_db_query(
                    "SELECT customers_email_address " .
                    "FROM " . TABLE_CUSTOMERS . " " .
                    "WHERE customers_id='" . intval($order->customer['customer_id']) . "' " .
                    " " . (ServerSession::get()->getDepartmentId() ? " AND departments_id='" . ServerSession::get()->getDepartmentId() . "' " : '') . " " .
                    "LIMIT 1"
                );
                if (tep_db_num_rows($check_customer_r) > 0) {
                    $check_customer = tep_db_fetch_array($check_customer_r);
                    if (strtolower($order->customer['email_address']) != strtolower($check_customer['customers_email_address'])) {
                        $this->warning('Customer "' . intval($order->customer['customer_id']) . '" email mismatch', 'ERROR_CUSTOMER_EMAIL_MISMATCH');
                    }
                    $manager->assignCustomer($order->customer['customer_id']);
                } else {
                    $this->error('Customer "' . intval($order->customer['customer_id']) . '" not found', 'ERROR_CUSTOMER_NOT_FOUND');
                }
            }
        }

        //if ( !is_array($order->customer) ) $order->customer = [];
        foreach (array_keys($order->customer) as $key) {
            $order->customer[$key] = isset($customer[$key]) ? $customer[$key] : null;
        }

        $billing = (array)$orderData['billing'];
        if (isset($billing['country_iso2'])) {
            $countryId = $tools->getCountryId($billing['country_iso2']);
            $billing['country_id'] = $countryId;

            $country_info = \common\helpers\Country::get_country_info_by_id($countryId);
            $billing['country'] = [
                'id' => $countryId,
                'title' => $country_info['countries_name'],
                'iso_code_2' => $country_info['countries_iso_code_2'],
                'iso_code_3' => $country_info['countries_iso_code_3'],
            ];

            $billing['format_id'] = \common\helpers\Address::get_address_format_id($countryId);
        }
        if (!empty($billing['country_id']) && !empty($billing['state'])) {
            $billing['zone_id'] = \common\helpers\Zones::get_zone_id($billing['country_id'],$billing['state']);
        }

        $manager->set('billto', $billing);
//        if ( !is_array($order->billing) ) $order->billing = [];
//        foreach (array_keys(/*$order->billing*/$billing) as $key) {
//            $order->billing[$key] = isset($billing[$key]) ? $billing[$key] : null;
//        }

        $delivery = (array)$orderData['delivery'];
        if (isset($delivery['country_iso2'])) {
            $countryId = $tools->getCountryId($delivery['country_iso2']);
            $delivery['country_id'] = $countryId;
            $country_info = \common\helpers\Country::get_country_info_by_id($countryId);
            $delivery['country'] = [
                'id' => $countryId,
                'title' => $country_info['countries_name'],
                'iso_code_2' => $country_info['countries_iso_code_2'],
                'iso_code_3' => $country_info['countries_iso_code_3'],
            ];

            $delivery['format_id'] = \common\helpers\Address::get_address_format_id($countryId);
        }
        if (!empty($delivery['country_id']) && !empty($delivery['state'])) {
            $delivery['zone_id'] = \common\helpers\Zones::get_zone_id($delivery['country_id'],$delivery['state']);
        }

        $manager->set('sendto', $delivery);
//        if (!is_array($order->delivery)) $order->delivery = [];
//        foreach (array_keys(/*$order->delivery*/$delivery) as $key) {
//            $order->delivery[$key] = isset($delivery[$key]) ? $delivery[$key] : null;
//        }

        $order->prepareOrderAddresses();

        foreach (array_keys($order->customer) as $key) {
            $order->customer[$key] = isset($customer[$key]) ? $customer[$key] : null;
        }
        $order->customer['id'] = $order->customer['customer_id'];
        foreach (array_keys($order->billing) as $key) {
            $order->billing[$key] = isset($billing[$key]) ? $billing[$key] : null;
        }
        foreach (array_keys($order->delivery) as $key) {
            $order->delivery[$key] = isset($delivery[$key]) ? $delivery[$key] : null;
        }

        // {{ patch/create address book ID
        foreach (['customer','billing','delivery'] as $abKey) {
            $order->{$abKey}['address_book_id'] = $tools->addressBookFind($order->customer['customer_id'], $order->{$abKey});
        }
        // }} patch/create address book ID


        $order->products = [];

        if (isset($orderData['products']) && isset($orderData['products']->product)) {
            $products = $orderData['products']->product;
            if (!is_array($products) || !ArrayHelper::isIndexed($products)) $products = [$products];
            foreach ($products as $product) {
                $product = (array)$product;

                if ( $product['id'] && !SoapHelper::hasProduct($product['id']) ){
                    $this->error('Product "'.$product['id'].'" not found');
                    $product['id'] = 0;
                }
                $ordered_attributes = [];
                if ( isset($product['attributes']) ){
                    $attributes = isset($product['attributes']->attribute)?$product['attributes']->attribute:[];
                    if ( !is_array($attributes) ) $attributes = [$attributes];
                    unset($product['attributes']);

                    foreach( $attributes as $attribute ) {
                        $ordered_attributes[] = [
                            'option' => $attribute->option_name,
                            'value' => $attribute->option_value_name,
                            'option_id' => $attribute->option_id,
                            'value_id' => $attribute->value_id,
                        ];
                    }
                }
                $order->products[] = [
                    'qty' => $product['qty'],
                    //'reserved_qty' => $products[$i]['reserved_qty'],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    //'stock_info' => $products[$i]['stock_info'],
                    //'products_file' => $products[$i]['products_file'],
                    'is_virtual' => isset($product['is_virtual']) ? intval($product['is_virtual']) : 0,
                    'gv_state' => (preg_match('/^GIFT/', $product['model']) ? 'pending' : 'none'),
                    'tax' => $product['tax'], //\common\helpers\Tax::get_tax_rate($products[$i]['tax_class_id'], $this->tax_address['entry_country_id'], $this->tax_address['entry_zone_id']),
                    //'tax_class_id' => $products[$i]['tax_class_id'],
                    //'tax_description' => \common\helpers\Tax::get_tax_description($products[$i]['tax_class_id'], $this->tax_address['entry_country_id'], $this->tax_address['entry_zone_id']),
                    'ga' => isset($product['ga']) ? intval($product['ga']) : 0,
                    'price' => $product['price'],
                    'final_price' => $product['final_price'], //$products[$i]['price'] + $cart->attributes_price($products[$i]['id'], $products[$i]['quantity']),
                    //'weight' => $products[$i]['weight'],
                    'gift_wrap_price' => $product['gift_wrap_price'],
                    'gift_wrapped' => $product['gift_wrapped'],
                    //'gift_wrap_allowed' => $products[$i]['gift_wrap_allowed'],
                    //'virtual_gift_card' => $products[$i]['virtual_gift_card'],
                    'id' => \common\helpers\Inventory::normalize_id($product['id']),
                    //'subscription' => $products[$i]['subscription'],
                    //'subscription_code' => $products[$i]['subscription_code'],
                    //'overwritten' => $products[$i]['overwritten']
                    'attributes' => $ordered_attributes,
                    'packs' => (int)$product['packs'],
                    'units'=> (int)$product['units'],
                    'packagings' => (int)$product['packagings'],
                    'packs_price' => $product['packs_price'],
                    'units_price'=> $product['units_price'],
                    'packagings_price' => $product['packagings_price'],
                ];
            }
        }

        $order->totals = [];
        if (isset($orderData['totals']) && isset($orderData['totals']->total)) {
            $totals = (array)$orderData['totals']->total;
            if (!ArrayHelper::isIndexed($totals)) $totals = [$totals];
            foreach ($totals as $total) {
                $total = (array)$total;
                $total['class'] = $total['code'];
                $order->totals[] = $total;
            }
        }

        $infoData = isset($orderData['info']) ? (array)$orderData['info'] : [];
        if ( isset($infoData['language']) ) {
            $infoData['language_id'] = \common\classes\language::get_id($infoData['language']);
        }
        foreach (array_keys(/*$order->info*/$infoData) as $key) {
            if ( $key=='department_id' && ServerSession::get()->getDepartmentId() ) {
                $infoData[$key] = ServerSession::get()->getDepartmentId();
            }
            $order->info[$key] = isset($infoData[$key]) ? $infoData[$key] : null;
        }
        if ( empty($order->info['language_id']) ) {
            $order->info['language_id'] = \common\classes\language::defaultId();
        }
        if ( empty($order->info['platform_id']) ) {
            $order->info['platform_id'] = ServerSession::get()->getPlatformId();
            if ( ServerSession::get()->acl()->siteAccessPermission() ) {
                if ( !empty($infoData['platform_name']) ) {
                    $platformId = Tools::getInstance()->getPlatformId($infoData['platform_name']);
                    if ( $platformId ) {
                        $order->info['platform_id'] = $platformId;
                    }
                }
            }
        }

        global $order_delivery_date;
        if ( isset($infoData['delivery_date']) && $infoData['delivery_date']>0 ) {
            $order->info['delivery_date'] = date('Y-m-d', strtotime($infoData['delivery_date']));
            $order_delivery_date = $order->info['delivery_date'];
        }

        if ( isset($infoData['date_purchased']) && $infoData['date_purchased']>0 ) {
            $order->info['date_purchased'] = date('Y-m-d H:i:s', strtotime($infoData['date_purchased']));
        }

        if ( ServerSession::get()->getDepartmentId() && isset($infoData['platform_name']) && !empty($infoData['platform_name']) && $order->info['platform_id'] ) {
            \Yii::$app->get('department')->updatePlatformName($order->info['platform_id'], $infoData['platform_name']);
        }

        $order->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;
        if ( isset($infoData['order_status']) && !empty($infoData['order_status']) ){
            $this->warning('Field \'order_status\' - read-only');
        }

        $order_total_modules = new \common\classes\order_total(false, $manager);

        //unset($order->info['last_modified']);
        if( isset($orderData['status_history_array']) && is_object($orderData['status_history_array']) && isset($orderData['status_history_array']->status_history) ) {
            if ( is_array($orderData['status_history_array']->status_history) ) {
                $status_history = $orderData['status_history_array']->status_history;
            }else{
                $status_history = [$orderData['status_history_array']->status_history];
            }
            $this->status_history_array = [];
            foreach ($status_history as $idx=>$history_row) {
                $history_row = (array)$history_row;
                if ( !empty($history_row['orders_status_id']) ) {
                    if ( $idx==0 ) {
                        $order->info['order_status'] = $history_row['orders_status_id'];
                    }
                }else{
                    $history_row['orders_status_id'] = $order->info['order_status'];
                }
                $history_row['comments'] = isset($history_row['comments'])?trim($history_row['comments']):'';
                if ( trim($history_row['comments'])==trim($order->info['comments']) ) continue;

                if ( isset($history_row['date_added']) && $history_row['date_added']>0 ) {
                    $history_row['date_added'] = date('Y-m-d H:i:s', strtotime($history_row['date_added']));
                }else{
                    $history_row['date_added'] = 'now()';
                }
                $this->status_history_array[] = [
                    'orders_status_id' => $history_row['orders_status_id'],
                    'date_added' => $history_row['date_added'],
                    'customer_notified' => (isset($history_row['customer_notified']) && $history_row['customer_notified'])?1:0,
                    'comments' => $history_row['comments'],
                ];
            }
        }

        if ( isset($infoData['sap_export']) && is_numeric($infoData['sap_export']) ) {
            $this->afterSaveOrderPatch['sap_export'] = (int)$infoData['sap_export'];
        }
        if ( isset($infoData['sap_export_mode']) && !empty($infoData['sap_export_mode']) ) {
            $order_sap_export_modes = ['auto','manual'];
            if ( in_array($infoData['sap_export_mode'],$order_sap_export_modes) ) {
                $this->afterSaveOrderPatch['sap_export_mode'] = $infoData['sap_export_mode'];
            }else{
                $this->error('sap_export_mode possible values: '.implode(', ',$order_sap_export_modes));
            }
        }

        if ( isset($orderData['client_order_id']) ) {
            $order->info['api_client_order_id'] = $orderData['client_order_id'];
        }

        if ($order->info['api_client_order_id']) {
            $ownCheck = '';
            if (!ServerSession::get()->acl()->siteAccessPermission()){
                $ownCheck =
                    " ".(ServerSession::get()->getDepartmentId()?"AND department_id='".ServerSession::get()->getDepartmentId()."' ":'').
                    " ".(!ServerSession::get()->getDepartmentId() && ServerSession::get()->getPlatformId()?"AND platform_id='".ServerSession::get()->getPlatformId()."' ":'');
            }
            $check_unique_ref = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS check_double " .
                "FROM " . TABLE_ORDERS . " " .
                "WHERE api_client_order_id='".tep_db_input($order->info['api_client_order_id'])."' ".
                " {$ownCheck}"
            ));
            if ( $check_unique_ref['check_double']>0 ) {
                $this->error('Client order id "'.$order->info['api_client_order_id'].'" already exists');
            }
        }
        $this->order = $order;

    }

    public function build()
    {
        if ( $this->status!='ERROR' ) {
            $this->persistOrder();
        }

        parent::build();
    }

    protected function persistOrder()
    {
        global $cart, $order_totals, $order_total_modules;

        $order_totals = $this->order->totals;

        global $order_delivery_date;
        $order_delivery_date = strval($this->order->info['delivery_date']);

        $insert_id = $this->order->save_order();
        if ( $insert_id ) {
            if ( is_array($this->order->info['tracking_number']) ) {
                $this->afterSaveOrderPatch['tracking_number'] = implode(';',$this->order->info['tracking_number']);
            }
            if ( count($this->afterSaveOrderPatch)>0 ) {
                tep_db_perform(TABLE_ORDERS,$this->afterSaveOrderPatch,'update',"orders_id='".(int)$insert_id."'");
            }

            $order_totals = $this->order->totals;
            $this->order->save_details();

            $this->order->save_products(false);

            if ( is_array($this->status_history_array) && count($this->status_history_array)>0 ) {
                foreach ($this->status_history_array as $status_history) {
                    $status_history['orders_id'] = $insert_id;
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $status_history);
                }

                $history_last_order_status = tep_db_fetch_array(tep_db_query(
                    "SELECT orders_status_id ".
                    "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                    "WHERE orders_id = '".(int)$insert_id."' ".
                    "ORDER BY date_added DESC, orders_status_history_id DESC ".
                    "LIMIT 1"
                ));
                if ( $history_last_order_status['orders_status_id'] ) {
                    $this->order->info['order_status'] = $history_last_order_status['orders_status_id'];
                    tep_db_query(
                        "UPDATE " . TABLE_ORDERS . " " .
                        "SET orders_status='" . (int)$this->order->info['order_status'] . "' " .
                        "WHERE orders_id = '" . (int)$insert_id . "'"
                    );
                }
            }

            $updateOrderAdditional = '';
            $updateOrderAdditional = "api_client_order_id='".tep_db_input($this->order->info['api_client_order_id'])."', ";
            if ($this->asPurchaseOrder){
                $updateOrderAdditional .= "order_type='purchase', ";
                $get_purchase_order_target_id_r = tep_db_query(
                    "SELECT DISTINCT p.created_by_department_id ".
                    "FROM ".TABLE_ORDERS_PRODUCTS." op ".
                    " INNER JOIN ".TABLE_PRODUCTS." p ON p.products_id=op.products_id ".
                    "WHERE op.orders_id='".(int)$insert_id."' "
                );
                if ( tep_db_num_rows($get_purchase_order_target_id_r)==1 ) {
                    $_purchase_order_target_id = tep_db_fetch_array($get_purchase_order_target_id_r);
                    if ($_purchase_order_target_id['created_by_department_id']>0) {
                        $updateOrderAdditional .= "purchase_order_department_id='".(int)$_purchase_order_target_id['created_by_department_id']."', ";
                    }
                }else{
                    tep_db_free_result($get_purchase_order_target_id_r);
                }
            }
            tep_db_query(
                "UPDATE ".TABLE_ORDERS." ".
                "SET {$updateOrderAdditional} _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                "WHERE orders_id='".$insert_id."' "
            );

            $this->orders_id = $insert_id;
        }else{
            $this->error('Create order failure','ERROR_CREATE_ERROR');
        }
    }

}
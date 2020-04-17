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
use common\classes\order_total;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class UpdatePurchaseOrderResponse extends SoapModel
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

    protected $updateOrderId = 0;
    protected $update_status_history = [];

    public $asPurchaseOrder = false;

    protected $afterSaveOrderPatch = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setOrder(Order $orderData)
    {
        //$this->updateOrderId ??

        $this->afterSaveOrderPatch = [];
/*        if ($orderData->client_order_id) {
            $check_unique_ref = tep_db_query(
                "SELECT orders_id " .
                "FROM " . TABLE_ORDERS . " " .
                "WHERE api_client_order_id='".tep_db_input($orderData->client_order_id)."' ".
                (ServerSession::get()->getDepartmentId()?"AND department_id='".ServerSession::get()->getDepartmentId()."' ":'').
                (!ServerSession::get()->getDepartmentId() && ServerSession::get()->getPlatformId()?"AND platform_id='".ServerSession::get()->getPlatformId()."' ":'')
            );
            if ( tep_db_num_rows($check_unique_ref)==1 ) {
                $existing_order = tep_db_fetch_array($check_unique_ref);
                $this->updateOrderId = (int)$existing_order['orders_id'];
            }else{
                $this->error('Client order id "'.$orderData->client_order_id.'" not found');
                return;
            }
        }else{
            $this->error('Missing or empty client_order_id');
            return;
        }*/
        if ( $orderData->order_id ) {
            $check_unique_ref = tep_db_query(
                "SELECT orders_id " .
                "FROM " . TABLE_ORDERS . " " .
                "WHERE orders_id='" . tep_db_input($orderData->order_id) . "' " .
                " AND order_type='purchase' " .
                (ServerSession::get()->getDepartmentId() ? "AND (purchase_order_department_id='" . ServerSession::get()->getDepartmentId() . "' OR department_id='".ServerSession::get()->getDepartmentId()."') " : '')
            );
            if (tep_db_num_rows($check_unique_ref) == 1) {
                $existing_order = tep_db_fetch_array($check_unique_ref);
                $this->updateOrderId = (int)$existing_order['orders_id'];
            } else {
                $this->error('Purchase order id "' . $orderData->order_id . '" not found');
                return;
            }
        }

        if ( empty($this->updateOrderId) ) {
            $this->error('Order not found');
            return;
        }

        // {{ check server modify order
        $check_order = tep_db_fetch_array(tep_db_query(
            "SELECT COUNT(*) AS is_modified ".
            "FROM ".TABLE_ORDERS." ".
            "WHERE orders_id='".$this->updateOrderId."' ".
            " AND _api_order_time_processed!=_api_order_time_modified"
        ));
        if ( $check_order['is_modified'] ) {
            $this->error('Can\'t update. Order has been modified on server.', 'ERROR_ORDER_CHANGED_ON_SERVER');
            return;
        }
        // }} check server modify order


        global $cart, $order_total_modules, $order;
        if ( !is_object($cart) ) {
            $cart = new \common\classes\shopping_cart();
        }
        $cart->reset(true);

        $orderData = (array)$orderData;

        $tools = new Tools();

        $order = new \common\classes\Order($this->updateOrderId);
        $orderOriginal = clone $order;
        if ( ServerSession::get()->getDepartmentId() ) {
            $order->info['department_id'] = ServerSession::get()->getDepartmentId();
        }elseif(ServerSession::get()->getPlatformId()){
            $order->info['platform_id'] = ServerSession::get()->getPlatformId();
        }

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

        foreach (array_keys($order->customer) as $key) {
            $order->customer[$key] = isset($customer[$key]) ? $customer[$key] : null;
        }
        if ( $this->asPurchaseOrder ) {

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
                } else {
                    $this->error('Customer "' . intval($order->customer['customer_id']) . '" not found', 'ERROR_CUSTOMER_NOT_FOUND');
                }
            }
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

        foreach (array_keys($order->billing) as $key) {
            $order->billing[$key] = isset($billing[$key]) ? $billing[$key] : null;
        }

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
        foreach (array_keys($order->info) as $key) {
            if ( $key=='department_id' && ServerSession::get()->getDepartmentId() ) {
                $infoData[$key] = ServerSession::get()->getDepartmentId();
            }
            if ( $key=='tracking_number' ) {
                if (isset($orderOriginal->info['tracking_number']) && !empty($orderOriginal->info['tracking_number'])) {
                    $infoData[$key] = array_unique(array_merge($orderOriginal->info['tracking_number'], is_array($infoData[$key])?$infoData[$key]:[]));
                }
            }
            $order->info[$key] = isset($infoData[$key]) ? $infoData[$key] : $orderOriginal->info[$key];
        }

        if ( empty($order->info['language_id']) ) {
            $order->info['language_id'] = $orderOriginal->info['language_id'];
        }

        global $order_delivery_date;
        if ( isset($infoData['delivery_date']) ) {
            if ( $infoData['delivery_date']>0 ) {
                $order->info['delivery_date'] = date('Y-m-d', strtotime($infoData['delivery_date']));
                $order_delivery_date = $order->info['delivery_date'];
            }
        }else{
            $order_delivery_date = $orderOriginal->info['delivery_date'];
        }

        if ( isset($infoData['date_purchased']) && $infoData['date_purchased']>0 ) {
            $order->info['date_purchased'] = date('Y-m-d H:i:s', strtotime($infoData['date_purchased']));
        }

        if ( isset($infoData['platform_name']) && !empty($infoData['platform_name']) && $order->info['platform_id'] ) {
            \Yii::$app->get('department')->updatePlatformName($order->info['platform_id'], $infoData['platform_name']);
        }

        $order->info['order_status'] = $orderOriginal->info['order_status'];
        if ( isset($infoData['order_status']) && !empty($infoData['order_status']) ){
            $this->warning('Field \'order_status\' - read-only');
        }

        $order_total_modules = new \common\classes\order_total();

        unset($order->info['last_modified']);
        /*
        $order->info['last_modified'] = $orderOriginal->info['last_modified'];
        if ( isset($infoData['last_modified']) && $infoData['last_modified']>0 ) {
            $_last_modified = date('Y-m-d H:i:s', strtotime($infoData['last_modified']));
            if ( $_last_modified>$order->info['last_modified'] )
                $order->info['last_modified'] = $_last_modified;
        }
         */

        if ( isset($orderData['status_history_array']) ){
            $orderData['status_history_array'] = json_decode(json_encode($orderData['status_history_array']),true);
        }
        if ( isset($orderData['status_history_array']) && is_array($orderData['status_history_array']) && isset($orderData['status_history_array']['status_history']) ) {
            $status_history = ArrayHelper::isIndexed($orderData['status_history_array']['status_history'])?$orderData['status_history_array']['status_history']:[$orderData['status_history_array']['status_history']];
            foreach ( $status_history as $idx=>$history_row ) {
                $status_history[$idx]['comments'] = isset($history_row['comments'])?trim($history_row['comments']):'';
                if ( isset($history_row['date_added']) && $history_row['date_added']>0 ) {
                    $status_history[$idx]['date_added'] = date('Y-m-d H:i:s', strtotime($history_row['date_added']));
                }
            }
            $this->update_status_history = $status_history;
        }

        if ( ServerSession::get()->getDepartmentId() ) {
            $order->info['department_id'] = ServerSession::get()->getDepartmentId();
        }elseif ( ServerSession::get()->getPlatformId() ){
            $order->info['platform_id'] = ServerSession::get()->getPlatformId();
        }

        if ( isset($orderData['client_order_id']) && !empty($orderData['client_order_id']) ) {
            $order->info['api_client_order_id'] = $orderData['client_order_id'];
        }else{
            $order->info['api_client_order_id'] = $orderOriginal->info['api_client_order_id'];
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

        $insert_id = $this->updateOrderId;
        if ( $insert_id ) {
            if ( is_array($this->order->info['tracking_number']) ) {
                $this->afterSaveOrderPatch['tracking_number'] = implode(';',$this->order->info['tracking_number']);
            }
            if ( count($this->afterSaveOrderPatch)>0 ) {
                tep_db_perform(TABLE_ORDERS,$this->afterSaveOrderPatch,'update',"orders_id='".(int)$insert_id."'");
            }

            if ( count($this->update_status_history)>0 ) {
                // {{ match status history by date, then status and comment
                $db_status_history = [];
                $get_status_history_r = tep_db_query(
                    "SELECT * ".
                    "FROM ".TABLE_ORDERS_STATUS_HISTORY." ".
                    "WHERE orders_id='".(int)$this->updateOrderId."' ".
                    "ORDER BY date_added, orders_status_history_id"
                );
                $db_pk_first_id = 0;
                $max_order_status_date = '';
                if ( tep_db_num_rows($get_status_history_r)>0 ) {
                    while($db_history = tep_db_fetch_array($get_status_history_r)) {
                        if ( empty($db_pk_first_id) ) $db_pk_first_id = $db_history['orders_status_history_id'];
                        $db_status_history[$db_history['orders_status_history_id']] = $db_history;
                        if ( $db_history['date_added'] > $max_order_status_date ){
                            $max_order_status_date = $db_history['date_added'];
                        }
                    }
                }
                foreach ( $this->update_status_history as $income_history ){
                    if ( !isset($income_history['orders_status_id']) || empty($income_history['orders_status_id']) ) continue;
                    if ( !isset($income_history['date_added']) || empty($income_history['date_added']) ) continue;

                    $skip_income_history = false;
                    foreach ( $db_status_history as $pk_idx=>$db_comment ){
                        if ( $db_comment['date_added']!=$income_history['date_added'] ) continue;
                        if ( $db_pk_first_id==$pk_idx ) {
                            // comment
                            if ( trim($db_comment['comments'])==$income_history['comments'] ) {
                                $skip_income_history = true;
                                unset($db_status_history[$pk_idx]);
                                break;
                            }
                        }else{
                            if ( $income_history['orders_status_id']==$db_comment['orders_status_id'] && trim($db_comment['comments'])==$income_history['comments'] ) {
                                $skip_income_history = true;
                                unset($db_status_history[$pk_idx]);
                                break;
                            }
                        }
                    }

                    if ( $skip_income_history ) continue;

                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY,[
                        'orders_id' => $insert_id,
                        'orders_status_id' => $income_history['orders_status_id'],
                        'date_added' => $income_history['date_added'],
                        'customer_notified' => (isset($income_history['customer_notified']) && $income_history['customer_notified'])?1:0,
                        'comments' => $income_history['comments'],
                    ]);
                    if ( $income_history['date_added']>$max_order_status_date ){
                        tep_db_query(
                            "UPDATE ".TABLE_ORDERS." ".
                            "SET orders_status='".(int)$income_history['orders_status_id']."', last_modified='".tep_db_input($income_history['date_added'])."' ".
                            "WHERE orders_id = '".(int)$insert_id."'"
                        );
                    }
                }
            }
            // }} status history

            $this->orders_id = $insert_id;

            tep_db_query(
                "UPDATE ".TABLE_ORDERS." ".
                "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                "WHERE orders_id='".$this->updateOrderId."' "
            );
        }else{
            $this->error('Update order failure','ERROR_UPDATE_ERROR');
        }
    }
}
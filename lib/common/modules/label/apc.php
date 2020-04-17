<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\label;

use common\classes\modules\ModuleLabel;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use Yii;
use \common\models;
use \common\helpers;

require_once 'apc/ApcApi.php';

class apc extends ModuleLabel
{
    public $title;
    public $description;
    public $code = 'apc';
    
    public $can_update_shipment = false;
    public $can_cancel_shipment = true;
    
    private $_API = null;
    
    function __construct() {
        $this->title = 'APC';//MODULE_LABEL_APC_TEXT_TITLE;
        $this->description = 'APC';//MODULE_LABEL_APC_TEXT_DESCRIPTION;
        $this->_API = new \ApcApi();
    }
    
    public function configure_keys()
    {
        return array (
        'MODULE_LABEL_APC_STATUS' =>
          array (
            'title' => 'Enable APC Labels',
            'value' => 'True',
            'description' => 'Do you want to offer APC labels?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_LABEL_APC_USERNAME' =>
          array (
            'title' => 'Username',
            'value' => '',
            'description' => 'Username',
            'sort_order' => '1',
          ),
        'MODULE_LABEL_APC_PASSWORD' =>
          array (
            'title' => 'Password',
            'value' => '',
            'description' => 'Password',
            'sort_order' => '2',
          ),
        'MODULE_LABEL_APC_SORT_ORDER' =>
          array (
            'title' => 'APC Sort Order',
            'value' => '0',
            'description' => 'Sort order of display.',
            'sort_order' => '10',
          ),
      );
    }
    
    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_LABEL_APC_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_LABEL_APC_SORT_ORDER');
    }
    
    /**
     * create order, get/set tracking
     * @param $orders_id
     * @return array
     */
    public function hypashipTracking($orders_id, $method = '')
    {
        $oOrder = models\Orders::findOne($orders_id);
        if($oOrder->real_tracking_number == '')
        {
            $aHypashipOrder = $this->createHypashipOrder($orders_id, $method);
            if (isset($aHypashipOrder['Orders']['Messages']['Code']) && $aHypashipOrder['Orders']['Messages']['Code'] == 'SUCCESS')
            {
                $tracking_number = $aHypashipOrder['Orders']['Order']['WayBill'];
                $oOrder->real_tracking_number = $tracking_number;
                $oOrder->tracking_number = substr($tracking_number, -7);

                try{
                    $getLabel = json_decode($this->_API->getLabels($oOrder->real_tracking_number));
                    $label = $getLabel->Orders->Order->ShipmentDetails->Items->Item->Label->Content;
                    $oOrder->parcel_label_pdf = '1';
                    $oOrder->save();
                    tep_db_query("delete from " . TABLE_ORDERS_LABEL . " where orders_id = '" . (int)$orders_id . "'");
                    tep_db_query("insert into " . TABLE_ORDERS_LABEL . " set orders_id = '" . (int)$orders_id . "', parcel_label_pdf = '" . tep_db_input(base64_encode($label)) . "'");
                } catch (\Error $e) {
                    echo '<pre>'; print_r($e); echo '</pre>';
                }

                return [
                    'exist' => 0,
                    'tracking_number' => $oOrder->tracking_number,
                    'real_tracking_number' => $oOrder->real_tracking_number,
                    'parcel_label' => base64_decode($oOrder->parcel_label_pdf)
                ];
            }
        }
        elseif($oOrder->parcel_label_pdf == '')
        {
            try{
                $getLabel = json_decode($this->_API->getLabels($oOrder->real_tracking_number));
                $label = $getLabel->Orders->Order->ShipmentDetails->Items->Item->Label->Content;
                $oOrder->parcel_label_pdf = '1';
                $oOrder->save();
                tep_db_query("delete from " . TABLE_ORDERS_LABEL . " where orders_id = '" . (int)$orders_id . "'");
                tep_db_query("insert into " . TABLE_ORDERS_LABEL . " set orders_id = '" . (int)$orders_id . "', parcel_label_pdf = '" . tep_db_input(base64_encode($label)) . "'");
            } catch (\Error $e) {
                echo '<pre>'; print_r($e); echo '</pre>';
            }
        }

        $check_label_order = tep_db_fetch_array(tep_db_query("select parcel_label_pdf from " . TABLE_ORDERS_LABEL . " where orders_id = '" . (int)$orders_id . "'"));
        $parcel_label_pdf = base64_decode($check_label_order['parcel_label_pdf']);
        
        return [
            'exist' => 1,
            'tracking_number' => $oOrder->tracking_number,
            'real_tracking_number' => $oOrder->real_tracking_number,
            'parcel_label' => base64_decode($parcel_label_pdf),
        ];
    }

    /**
     * create APC order
     * @param $orders_id
     * @return array|mixed|object
     */
    public function createHypashipOrder($orders_id, $method = '')
    {
        $oOrder = models\Orders::findOne($orders_id);

        // build Collection data
        $oPlatform = models\Platforms::find()
            ->where(['platform_id' => $oOrder->platform_id])
            ->with('platformsAddressBook', 'platformsCutOffTimes')
            ->one();
        $aCollectionCountry = helpers\Country::get_country_info_by_id($oPlatform->defaultPlatformsAddressBook->entry_country_id);

        // build Delivery data
        $aDeliveryCountry = helpers\Country::get_country_info_by_name($oOrder->delivery_country);
        $PersonName = $oOrder->delivery_name .' '. $oOrder->delivery_lastname;


        $CutOffTime = new \common\classes\CutOffTime();
        $CollectionDate = $CutOffTime->getDeliveryDate($oPlatform, $oOrder, null, false);

        $DeliveryDate = new \DateTime($oOrder->delivery_date ?? Null);
        // same as extract($CutOffTime->getPostalOpenOurs($oPlatform, $CollectionDate));
        $aPostalOpenOurs = $CutOffTime->getPostalOpenOurs($oPlatform, $DeliveryDate);
        if (is_null($aPostalOpenOurs)) {
            $open_time_from = '08:00';
            $open_time_to = '20:00';
        } else {
            $open_time_from = $aPostalOpenOurs['open_time_from'];
            $open_time_to = $aPostalOpenOurs['open_time_to'];
        }

        $CompanyName = ($oOrder->delivery_company != '' && !is_null($oOrder->delivery_company))
                    ? $oOrder->delivery_company
                    : $PersonName;

        $shipping_weight = $oOrder->shipping_weight > 0 ? $oOrder->shipping_weight : 1;
        $xml = "
            <Orders>
                <Order>
                    <Reference>{$orders_id}</Reference>
                    <CollectionDate>{$DeliveryDate->format('d/m/Y')}</CollectionDate>
                    <ReadyAt>{$open_time_from}</ReadyAt>
                    <ClosedAt>{$open_time_to}</ClosedAt>
                    <ProductCode>{$method}</ProductCode>
                    <Delivery>
                        <CompanyName>{$CompanyName}</CompanyName>
                        <AddressLine1>{$oOrder->delivery_street_address}</AddressLine1>
                        <AddressLine2>{$oOrder->delivery_suburb}</AddressLine2>
                        <PostalCode>{$oOrder->delivery_postcode}</PostalCode>
                        <City>{$oOrder->delivery_city}</City>
                        <County>{$oOrder->delivery_country}</County>
                        <CountryCode>{$aDeliveryCountry['iso_code_2']}</CountryCode>
                        <Contact>
                            <PersonName>{$PersonName}</PersonName>
                            <PhoneNumber>{$oOrder->customers_telephone}</PhoneNumber>
                            <Email>{$oOrder->customers_email_address}</Email>
                        </Contact>
                    </Delivery>
                    <Collection>
                        <CompanyName>{$oPlatform->defaultPlatformsAddressBook->entry_company}</CompanyName>
                        <AddressLine1>{$oPlatform->defaultPlatformsAddressBook->entry_street_address}</AddressLine1>
                        <AddressLine2>{$oPlatform->defaultPlatformsAddressBook->entry_suburb}</AddressLine2>
                        <PostalCode>{$oPlatform->defaultPlatformsAddressBook->entry_postcode}</PostalCode>
                        <City>{$oPlatform->defaultPlatformsAddressBook->entry_city}</City>
                        <County>{$aCollectionCountry['text']}</County>
                        <CountryCode>{$aCollectionCountry['countries_iso_code_2']}</CountryCode>
                        <Contact>
                            <PersonName>{$oPlatform->platform_owner}</PersonName>
                            <PhoneNumber>{$oPlatform->platform_telephone}</PhoneNumber>
                            <Email>{$oPlatform->platform_email_address}</Email>
                        </Contact>
                        <instructions/>
                    </Collection>
                    <GoodsInfo>
                        <GoodsValue>1</GoodsValue>
                        <IncreasedLiability>TRUE</IncreasedLiability>
                        <Security>TRUE</Security>
                        <Fragile>False</Fragile>
                        <GoodsDescription>TEST</GoodsDescription>
                    </GoodsInfo>
                    <ShipmentDetails>
                        <NumberOfPieces>1</NumberOfPieces>
                        <TotalWeight>4</TotalWeight>
                        <Items>
                            <Item>
                                <Type>ALL</Type>
                                <Weight>{$shipping_weight}</Weight>
                                <Length>1</Length>
                                <Width>1</Width>
                                <Height>1</Height>
                            </Item>
                        </Items>
                    </ShipmentDetails>
                </Order>
            </Orders>      
        ";
        $json = '';
        try{
            $json = $this->_API->createOrders($xml);
        } catch (\Exception $e) {}
        return json_decode($json, true);
    }
    
    /**
     * deprecated
     * @param $orders_id
     */
    public function getServiceAvailability($orders_id)
    {
        $oOrder = models\Orders::findOne($orders_id);
        $oPlatform = models\Platforms::find()
            ->where(['platform_id' => $oOrder->platform_id])
            ->with('platformsAddressBook', 'platformsCutOffTimes')
            ->one();
        $aCountry = helpers\Country::get_country_info_by_name($oOrder->delivery_country);
        $aCollectionCountry = helpers\Country::get_country_info_by_id($oPlatform->defaultPlatformsAddressBook->entry_country_id);

        $CutOffTime = new \common\classes\CutOffTime();
        $CollectionDate = $CutOffTime->getDeliveryDate($oPlatform, $oOrder, null, false);
        
        $DeliveryDate = new \DateTime($oOrder->delivery_date ?? Null);
        // same as extract($CutOffTime->getPostalOpenOurs($oPlatform, $CollectionDate));
        $aPostalOpenOurs = $CutOffTime->getPostalOpenOurs($oPlatform, $DeliveryDate);
        if (is_null($aPostalOpenOurs)) {
            $open_time_from = '08:00';
            $open_time_to = '20:00';
        } else {
            $open_time_from = $aPostalOpenOurs['open_time_from'];
            $open_time_to = $aPostalOpenOurs['open_time_to'];
        }

        $xml = "
            <Orders>
                <Order>
                    <CollectionDate>{$DeliveryDate->format('d/m/Y')}</CollectionDate>
                    <ReadyAt>{$open_time_from}</ReadyAt>
                    <ClosedAt>{$open_time_to}</ClosedAt>
                    <Delivery>
                        <PostalCode>{$oOrder->delivery_postcode}</PostalCode>
                        <CountryCode>{$aCountry['iso_code_2']}</CountryCode>
                    </Delivery>
                    <Collection>
                        <PostalCode>{$oPlatform->defaultPlatformsAddressBook->entry_postcode}</PostalCode>
                        <CountryCode>{$aCollectionCountry['countries_iso_code_2']}</CountryCode>
                    </Collection>
                    <ShipmentDetails>
                        <NumberOfPieces >1</NumberOfPieces>
                        <Items >
                            <Item>
                                <Weight>1</Weight>
                                <Length>1</Length>
                                <Width>1</Width>
                                <Height>1</Height>
                            </Item>
                        </Items>
                    </ShipmentDetails>
                </Order>
            </Orders>
        ";

        $Services = json_decode($this->_API->serviceAvailability($xml), true);
        $methods = [];
        if (is_array($Services['ServiceAvailability']['Services']['Service'])) {
            foreach ($Services['ServiceAvailability']['Services']['Service'] as $Service) {
                $methods[$this->code . '_' . $Service['ProductCode']] = $Service['ServiceName'];
            }
        }
        return $methods;
    }
    
    /**
     * get methods
     * @return mixed
     */
    function get_methods($country_iso_code_2, $method = '', $shipping_weight = 0, $num_of_sheets = 0) {
        $orders_id = \Yii::$app->request->get('orders_id', 0);
        $methods = $this->getServiceAvailability($orders_id);
        return $methods;
        
        $methods = $this->quote()['methods'];
        if(is_array($methods) && count($methods) > 0)
        {
            $aMethods = [];
            foreach ($methods as $method)
            {
                $aMethods[$method['id']] = $method;
            }
            return $aMethods;
        }
        return $methods;
    }

    /**
     * @param $order_id
     * @param string $method
     * @return array
     */
    function create_shipment ($order_id, $method = '')
    {
        return $this->hypashipTracking($order_id, $method);
    }

    /**
     * cancel APC order
     * @param $orders_id
     * @return array
     */
    function cancel_shipment ($orders_id)
    {
        $oOrder = models\Orders::findOne($orders_id);
        if($oOrder && $oOrder->real_tracking_number != '')
        {
            $xml = "
                <CancelOrder>
                    <Order>
                        <Status>CANCELLED</Status>
                    </Order>
                </CancelOrder>
            ";
            $resultCancel = json_decode($this->_API->cancelOrder($oOrder->real_tracking_number, $xml));

            if($resultCancel->CancelOrder->Messages->Code == 121)
            {
                $oOrder->tracking_number = '';
                $oOrder->real_tracking_number = '';
                $oOrder->parcel_label_pdf = '';
                $oOrder->save();
                tep_db_query("delete from " . TABLE_ORDERS_LABEL . " where orders_id = '" . (int)$orders_id . "'");

                return ['success' => $resultCancel->CancelOrder->Messages->Description];
            }
            else
            {
                return ['errors' => [$resultCancel->CancelOrder->Messages->Description]];
            }

        }
        return ['errors' => [
            'Order cannot be updated'
        ]];
    }

    /**
     * @param $order_id
     * @return array
     */
    function update_shipment ($order_id)
    {
        return ['errors' => [
            'Order cannot be updated'
        ]];
    }
}
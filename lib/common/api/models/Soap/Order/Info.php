<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;


use common\api\models\Soap\SoapModel;
use common\api\SoapServer\ServerSession;

class Info extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    public $currency;

    /**
     * @var double
     * @soap
     */
    public $currency_value;

    /**
     * @var integer
     * @soap
     */
    public $platform_id;

    /**
     * @var string
     * @soap
     */
    public $platform_name;

    /**
     * @var string
     * @soap
     */
    public $language;
    public $language_id;

    /**
     * @var integer
     * @soap
     */
    public $admin_id;

//    /**
//     * @var integer
//     * @soap
//     */
//    public $orders_id;

    /**
     * @var string
     * @soap
     */
    public $payment_method;
    /**
     * @var string
     * @soap
     */
    public $payment_class;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $transaction_id;

    /**
     * @var string
     * @soap
     */
    public $shipping_class;
    /**
     * @var string
     * @soap
     */
    public $shipping_method;

    /**
     * @var string[]
     * @soap
     */
    public $tracking_number;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $orders_status_name;
    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $order_status;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $promotional_description;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $purchase_order;

    /**
     * @var datetime
     * @soap
     */
    public $date_purchased;
    /**
     * @var datetime
     * @soap
     */
    public $last_modified;

    /**
     * @var double
     * @soap
     */
    public $total;

    /**
     * @var double
     * @soap
     */
    public $shipping_cost;
    /**
     * @var double
     * @soap
     */
    public $subtotal;

    /**
     * @var double
     * @soap
     */
    public $subtotal_inc_tax;
    /**
     * @var double
     * @soap
     */
    public $subtotal_exc_tax;
    /**
     * @var double
     * @soap
     */
    public $tax;
    public $tax_groups = array();

    /**
     * @var string
     * @soap
     */
    public $comments;

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $gift_wrap_message;

//    /**
//     * @var integer
//     * @soap
//     */
//    public $basket_id;

    /**
     * @var double
     * @soap
     */
    public $shipping_weight;

    /**
     * @var double
     * @soap
     */
    public $total_paid_inc_tax;
    /**
     * @var double
     * @soap
     */
    public $total_paid_exc_tax;

    /**
     * @var string
     * @soap
     */
    public $delivery_date;

    /**
     * @var \common\api\models\Soap\Order\OrderFlag  {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $flag;

    /**
     * @var \common\api\models\Soap\Order\OrderMarker {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $marker;

    /**
     * @var string {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sap_order_id;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sap_export_date;

    /**
     * @var integer {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sap_export;

    /**
     * @var string  {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sap_export_mode;

    /**
     * @var string[]  {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sap_export_issues;

    public function __construct(array $config = [])
    {
        if ( array_key_exists('tracking_number',$config) ) {
            //if ( is_array($config['tracking_number']) ) $config['tracking_number'] = implode(';',$config['tracking_number']);
        }

        if ( isset($config['tracking_number']) && is_array($config['tracking_number']) ) {
            $config['tracking_number'] = array_map(function($item){
                if(is_object($item)) {
                    return $item->tracking_number;
                }else{
                    return trim($item);
                }
            },$config['tracking_number']);
        }

        if (  isset($config['language_id']) ) {
            $config['language'] = \common\classes\language::get_code($config['language_id']);
        }
        if ( isset($config['platform_id']) ) {
            if ( ServerSession::get()->getDepartmentId() ) {
                $this->platform_name = \Yii::$app->get('department')->getPlatformName($config['platform_id']);
            }else{
                $this->platform_name = \Yii::$app->get('platform')->name($config['platform_id']);
            }
        }
        if ( empty($config['promotional_description']) ) {
            unset($config['promotional_description']);
        }
        if ( empty($config['purchase_order']) ) {
            unset($config['purchase_order']);
        }

        if (isset($config['transaction_id']) && empty($config['transaction_id'])) unset($config['transaction_id']);

        parent::__construct($config);

        if ( !empty($this->date_purchased) ) {
            $this->date_purchased = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_purchased);
        }
        if ( !empty($this->last_modified) ) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }

        if ( $config['markers'] || $config['flags'] ) {
            if ($ext = \common\helpers\Acl::checkExtensionAllowed('OrderMarkers', 'allowed')) {
                if ($config['markers']) {
                    $_name_list = $ext::getMarkerNames();
                    $this->marker = new OrderMarker([
                        'id' => $config['markers'],
                        'text' => isset($_name_list[$config['markers']]) ? $_name_list[$config['markers']] : '',
                    ]);
                }
                if ($config['flags']) {
                    $_name_list = $ext::getFlagNames();
                    $this->flag = new OrderFlag([
                        'id' => $config['flags'],
                        'text' => isset($_name_list[$config['flags']]) ? $_name_list[$config['flags']] : '',
                    ]);
                }
            }
        }

        //{{ SAP part
        if ( empty($this->sap_order_id) ) {
            $this->sap_order_id = null;
        }
        if ( !empty($this->sap_export_date) ) {
            $this->sap_export_date = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->sap_export_date);
        }
        if ( class_exists('\common\classes\SapClient') && (true || $this->sap_export==2) ) {
            $get_issues_r = tep_db_query(
                "SELECT date_added, issue_text FROM ep_sap_order_issues WHERE orders_id='".(int)$config['orders_id']."' ORDER BY date_added"
            );
            if ( tep_db_num_rows($get_issues_r)>0 ) {
                $this->sap_export_issues = [];
                while( $get_issue = tep_db_fetch_array($get_issues_r) ){
                    $this->sap_export_issues[] = \common\api\SoapServer\SoapHelper::soapDateTimeOut($get_issue['date_added']);
                }
            }
        }
        //}} SAP part

    }


}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Quotation;


use common\api\models\Soap\SoapModel;
use common\api\SoapServer\ServerSession;

class QuotationInfo extends SoapModel
{
    /**
     * @var integer {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $child_order_id;

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

    public function __construct(array $config = [])
    {
        if ( empty($config['child_order_id']) ) {
            unset($config['child_order_id']);
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

    }

}
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

class QuotationShortInfo extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $quotation_id;

    /**
     * @var integer {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $child_order_id;

    /**
     * @var integer {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $platform_id;

    /**
     * @var string {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $platform_name;

    /**
     * @var string
     * @soap
     */
    public $orders_status_name;
    /**
     * @var integer
     * @soap
     */
    public $order_status;

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
     * @var double
     * @soap
     */
    public $total;

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
    public $shipping_method;
    /**
     * @var string
     * @soap
     */
    public $shipping_class;

    public function __construct(array $config = [])
    {
        if ( empty($config['child_order_id']) ) {
            unset($config['child_order_id']);
        }
        if ( isset($config['language_id']) ) {
            $config['language'] = \common\classes\language::get_code($config['language_id']);
        }
        if ( isset($config['platform_id']) ) {
            if ( ServerSession::get()->getDepartmentId() ) {
                $this->platform_name = \Yii::$app->get('department')->getPlatformName($config['platform_id']);
            }else{
                $this->platform_name = \Yii::$app->get('platform')->name($config['platform_id']);
            }
        }

        parent::__construct($config);
        if ( !empty($this->date_purchased) ) {
            $this->date_purchased = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_purchased);
        }
        if ( !empty($this->last_modified) ) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }

    }

}
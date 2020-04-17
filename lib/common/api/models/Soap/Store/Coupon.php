<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;


use common\api\models\Soap\SoapModel;
use yii\helpers\ArrayHelper;

class Coupon extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $coupon_id;

    /**
     * @var string
     * @soap
     */
    public $coupon_type;

    /**
     * @var string
     * @soap
     */
    public $coupon_code;


    /**
     * @var \common\api\models\Soap\Store\ArrayOfCouponDescriptions
     * @soap
     */
    public $descriptions;

    /**
     * @var float
     * @soap
     */
    public $coupon_amount;

    /**
     * @var string
     * @soap
     */
    public $coupon_currency;

    /**
     * @var float
     * @soap
     */
    public $coupon_minimum_order;

    /**
     * @var date
     * @soap
     */
    public $coupon_start_date;

    /**
     * @var date
     * @soap
     */
    public $coupon_expire_date;

    /**
     * @var integer
     * @soap
     */
    public $uses_per_coupon;

    /**
     * @var integer
     * @soap
     */
    public $uses_per_user;

    /**
     * @var integer
     * @soap
     */
    public $uses_per_shipping;

    /**
     * @var string
     * @soap
     */
    public $restrict_to_products;

    /**
     * @var string
     * @soap
     */
    public $restrict_to_categories;

    /**
     * @var string
     * @soap
     */
    public $restrict_to_customers;

    /**
     * @var string
     * @soap
     */
    public $coupon_active;

    /**
     * @var datetime
     * @soap
     */
    public $date_created;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_modified;

    /**
     * @var integer
     * @soap
     */
    public $coupon_for_recovery_email;

    /**
     * @var integer
     * @soap
     */
    public $tax_class_id;

    /**
     * @var integer
     * @soap
     */
    public $flag_with_tax;

    protected $_castType = [
        'coupon_start_date' => 'date',
        'coupon_expire_date' => 'date',
        'date_created' => 'datetime',
        'date_modified' => ['datetime',true],
    ];

    public function __construct(array $config = [])
    {
        if ( isset($config['descriptions']) ) {
            $this->descriptions = new ArrayOfCouponDescriptions($config['descriptions']);
            unset($config['descriptions']);
        }else{
            $this->descriptions = new ArrayOfCouponDescriptions();
        }
        parent::__construct($config);
    }

    public static function makeFromAR($model)
    {
        $soapModel = new Coupon($model->toArray());
        $languages = ArrayHelper::getColumn(\common\classes\language::get_all(),'id');
        $descriptionQuery = $model->getDescriptions()->where(['language_id'=>array_values($languages)])->all();

        foreach ($descriptionQuery as $couponDescription) {
            /**
             * @var \common\models\CouponsDescription $couponDescription
             */
            $soapModel->descriptions->description[$couponDescription->language_id] = new CouponDescription($couponDescription->toArray());
        }

        $soapModel->descriptions->description = array_values($soapModel->descriptions->description);
        return $soapModel;
    }

}
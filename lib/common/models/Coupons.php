<?php

namespace app\models;

use Yii;

namespace common\models;
use yii\db\ActiveRecord;
use common\models\queries\CouponsQuery;

/**
 * This is the model class for table "coupons".
 *
 * @property int $coupon_id
 * @property string $coupon_type
 * @property string $coupon_code
 * @property string $coupon_amount
 * @property string $coupon_currency
 * @property string $coupon_minimum_order
 * @property string $coupon_start_date
 * @property string $coupon_expire_date
 * @property int $uses_per_coupon
 * @property int $uses_per_user
 * @property int $uses_per_shipping
 * @property string $restrict_to_products
 * @property string $restrict_to_categories
 * @property string $restrict_to_customers
 * @property string $coupon_active
 * @property string $date_created
 * @property string $date_modified
 * @property int $coupon_for_recovery_email
 * @property int $tax_class_id
 * @property int $flag_with_tax
 * @property string $full_name
 *
 * @property CouponsDescription $description
 *
 */
class Coupons extends ActiveRecord
{
    const STATUS_ACTIVE = 'Y';
    const STATUS_DISABLE = 'N';

	public $full_name;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'coupons';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['coupon_code'], 'required'],
            [['coupon_amount', 'coupon_minimum_order'], 'number'],
            [['coupon_start_date', 'coupon_expire_date', 'date_created', 'date_modified'], 'safe'],
            [['uses_per_coupon', 'uses_per_user', 'uses_per_shipping', 'coupon_for_recovery_email', 'tax_class_id', 'flag_with_tax'], 'integer'],
            [['restrict_to_customers'], 'string'],
            [['coupon_type', 'coupon_active'], 'string', 'max' => 1],
            [['coupon_code'], 'string', 'max' => 32],
            [['coupon_currency'], 'string', 'max' => 3],
            [['restrict_to_products', 'restrict_to_categories', 'restrict_to_countries'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'coupon_id' => 'Coupon ID',
            'coupon_type' => 'Coupon Type',
            'coupon_code' => 'Coupon Code',
            'coupon_amount' => 'Coupon Amount',
            'coupon_currency' => 'Coupon Currency',
            'coupon_minimum_order' => 'Coupon Minimum Order',
            'coupon_start_date' => 'Coupon Start Date',
            'coupon_expire_date' => 'Coupon Expire Date',
            'uses_per_coupon' => 'Uses Per Coupon',
            'uses_per_user' => 'Uses Per User',
            'uses_per_shipping' => 'Uses Per Shipping',
            'restrict_to_products' => 'Restrict To Products',
            'restrict_to_categories' => 'Restrict To Categories',
			'restrict_to_countries' => 'Restrict To Countries',
            'restrict_to_customers' => 'Restrict To Customers',
            'coupon_active' => 'Coupon Active',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
            'coupon_for_recovery_email' => 'Coupon For Recovery Email',
            'tax_class_id' => 'Tax Class ID',
            'flag_with_tax' => 'Flag With Tax',
        ];
    }

    public function afterFind() {
      $currencies = \Yii::$container->get('currencies');
	    $coupon_amount = '';
	    if ($this->coupon_type == 'P') {
		    $coupon_amount =  number_format($this->coupon_amount, 2) . '%';
	    } elseif ($this->coupon_type == 'S') {
		    $coupon_amount =  TEXT_FREE_SHIPPING;
	    } else {
		    $coupon_amount =  $currencies->format($this->coupon_amount, false, $this->coupon_currency);
	    }
	    $this->full_name = $this->description->coupon_name . '  -  ' . $coupon_amount;
    }

    public function getDescription(){
    	$languages_id = \Yii::$app->settings->get('languages_id');
	    return $this->hasOne(CouponsDescription::className(), ['coupon_id' => 'coupon_id'])->where(['language_id' => $languages_id]);
    }

    public function getDescriptions(){
        return $this->hasMany(CouponsDescription::className(), ['coupon_id' => 'coupon_id']);
    }

    /**
     * @inheritdoc
     * @return CouponsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CouponsQuery(get_called_class());
    }
    
    public static function getCouponByCode($code, $onlyActive = false){
        $query = self::find()->select('coupon_id, coupon_code, coupon_type, coupon_amount, coupon_currency')
                    ->where('coupon_code =:code', [':code' => $code])
                    ->with('redeemTrack');
        if ($onlyActive){
            $query->andWhere(['coupon_active' => 'Y']);
        }
        return $query->one();
    }
    
    public function getRedeemTrack(){
        return $this->hasMany(CouponRedeemTrack::className(), ['coupon_id' => 'coupon_id']);
    }
    
    public function addRedeemTrack($customer_id, $order_id = 0){
        
        $track = new CouponRedeemTrack();
        $track->setAttributes([
            'coupon_id' => $this->coupon_id,
            'customer_id' => (int)$customer_id,
            'redeem_ip' => tep_db_input(\common\helpers\System::get_ip_address()),
            'order_id' => $order_id
        ], false);
        $track->save(false);
        
        return $this;
    }
    
    public function isStartDateInvalid(){
        return strtotime($this->coupon_start_date) > strtotime("now");
    }
    
    public function isEndDateExpired(){
        if ($this->coupon_expire_date != '0000-00-00 00:00:00'){
            $_end = str_replace("00:00:00", "23:59:59", $this->coupon_expire_date);
            return strtotime($_end) < strtotime("now");
        }
        return false;
    }
}

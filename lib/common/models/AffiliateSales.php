<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "affiliate_sales".
 *
 * @property int $affiliate_id
 * @property string $affiliate_date
 * @property string $affiliate_browser
 * @property string $affiliate_ipaddress
 * @property int $affiliate_orders_id
 * @property string $affiliate_value
 * @property string $affiliate_payment
 * @property int $affiliate_clickthroughs_id
 * @property int $affiliate_billing_status
 * @property string $affiliate_payment_date
 * @property int $affiliate_payment_id
 * @property string $affiliate_percent
 * @property int $affiliate_salesman
 */
class AffiliateSales extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'affiliate_sales';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['affiliate_id', 'affiliate_browser', 'affiliate_ipaddress', 'affiliate_orders_id'], 'required'],
            [['affiliate_id', 'affiliate_orders_id', 'affiliate_clickthroughs_id', 'affiliate_billing_status', 'affiliate_payment_id', 'affiliate_salesman'], 'integer'],
            [['affiliate_date', 'affiliate_payment_date'], 'safe'],
            [['affiliate_value', 'affiliate_payment', 'affiliate_percent'], 'number'],
            [['affiliate_browser'], 'string', 'max' => 100],
            [['affiliate_ipaddress'], 'string', 'max' => 20],
            [['affiliate_id', 'affiliate_orders_id'], 'unique', 'targetAttribute' => ['affiliate_id', 'affiliate_orders_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'affiliate_id' => 'Affiliate ID',
            'affiliate_date' => 'Affiliate Date',
            'affiliate_browser' => 'Affiliate Browser',
            'affiliate_ipaddress' => 'Affiliate Ipaddress',
            'affiliate_orders_id' => 'Affiliate Orders ID',
            'affiliate_value' => 'Affiliate Value',
            'affiliate_payment' => 'Affiliate Payment',
            'affiliate_clickthroughs_id' => 'Affiliate Clickthroughs ID',
            'affiliate_billing_status' => 'Affiliate Billing Status',
            'affiliate_payment_date' => 'Affiliate Payment Date',
            'affiliate_payment_id' => 'Affiliate Payment ID',
            'affiliate_percent' => 'Affiliate Percent',
            'affiliate_salesman' => 'Affiliate Salesman',
        ];
    }
}

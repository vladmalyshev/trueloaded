<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "affiliate_payment_status".
 *
 * @property int $affiliate_payment_status_id
 * @property int $affiliate_language_id
 * @property string $affiliate_payment_status_name
 */
class AffiliatePaymentStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'affiliate_payment_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['affiliate_payment_status_id', 'affiliate_language_id', 'affiliate_payment_status_name'], 'required'],
            [['affiliate_payment_status_id', 'affiliate_language_id'], 'integer'],
            [['affiliate_payment_status_name'], 'string', 'max' => 32],
            [['affiliate_payment_status_id', 'affiliate_language_id'], 'unique', 'targetAttribute' => ['affiliate_payment_status_id', 'affiliate_language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'affiliate_payment_status_id' => 'Affiliate Payment Status ID',
            'affiliate_language_id' => 'Affiliate Language ID',
            'affiliate_payment_status_name' => 'Affiliate Payment Status Name',
        ];
    }
}

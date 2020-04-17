<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "affiliate_payment_status_history".
 *
 * @property int $affiliate_status_history_id
 * @property int $affiliate_payment_id
 * @property int $affiliate_new_value
 * @property int $affiliate_old_value
 * @property string $affiliate_date_added
 * @property int $affiliate_notified
 */
class AffiliatePaymentStatusHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'affiliate_payment_status_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['affiliate_payment_id', 'affiliate_new_value', 'affiliate_old_value', 'affiliate_notified'], 'integer'],
            [['affiliate_date_added'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'affiliate_status_history_id' => 'Affiliate Status History ID',
            'affiliate_payment_id' => 'Affiliate Payment ID',
            'affiliate_new_value' => 'Affiliate New Value',
            'affiliate_old_value' => 'Affiliate Old Value',
            'affiliate_date_added' => 'Affiliate Date Added',
            'affiliate_notified' => 'Affiliate Notified',
        ];
    }
}

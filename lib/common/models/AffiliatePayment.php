<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "affiliate_payment".
 *
 * @property int $affiliate_payment_id
 * @property int $affiliate_id
 * @property string $affiliate_payment
 * @property string $affiliate_payment_tax
 * @property string $affiliate_payment_total
 * @property string $affiliate_payment_currency
 * @property string $affiliate_payment_date
 * @property string $affiliate_payment_last_modified
 * @property int $affiliate_payment_status
 * @property string $affiliate_firstname
 * @property string $affiliate_lastname
 * @property string $affiliate_street_address
 * @property string $affiliate_suburb
 * @property string $affiliate_city
 * @property string $affiliate_postcode
 * @property string $affiliate_country
 * @property string $affiliate_company
 * @property string $affiliate_state
 * @property int $affiliate_address_format_id
 * @property string $affiliate_last_modified
 */
class AffiliatePayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'affiliate_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['affiliate_id', 'affiliate_payment_status', 'affiliate_address_format_id'], 'integer'],
            [['affiliate_payment', 'affiliate_payment_tax', 'affiliate_payment_total'], 'number'],
            [['affiliate_payment_date', 'affiliate_payment_last_modified', 'affiliate_last_modified'], 'safe'],
            [['affiliate_firstname', 'affiliate_lastname', 'affiliate_street_address', 'affiliate_city', 'affiliate_postcode'], 'required'],
            [['affiliate_payment_currency'], 'string', 'max' => 3],
            [['affiliate_firstname', 'affiliate_lastname', 'affiliate_city', 'affiliate_state'], 'string', 'max' => 32],
            [['affiliate_street_address', 'affiliate_suburb', 'affiliate_country'], 'string', 'max' => 64],
            [['affiliate_postcode'], 'string', 'max' => 10],
            [['affiliate_company'], 'string', 'max' => 60],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'affiliate_payment_id' => 'Affiliate Payment ID',
            'affiliate_id' => 'Affiliate ID',
            'affiliate_payment' => 'Affiliate Payment',
            'affiliate_payment_tax' => 'Affiliate Payment Tax',
            'affiliate_payment_total' => 'Affiliate Payment Total',
            'affiliate_payment_currency' => 'Affiliate Payment Currency',
            'affiliate_payment_date' => 'Affiliate Payment Date',
            'affiliate_payment_last_modified' => 'Affiliate Payment Last Modified',
            'affiliate_payment_status' => 'Affiliate Payment Status',
            'affiliate_firstname' => 'Affiliate Firstname',
            'affiliate_lastname' => 'Affiliate Lastname',
            'affiliate_street_address' => 'Affiliate Street Address',
            'affiliate_suburb' => 'Affiliate Suburb',
            'affiliate_city' => 'Affiliate City',
            'affiliate_postcode' => 'Affiliate Postcode',
            'affiliate_country' => 'Affiliate Country',
            'affiliate_company' => 'Affiliate Company',
            'affiliate_state' => 'Affiliate State',
            'affiliate_address_format_id' => 'Affiliate Address Format ID',
            'affiliate_last_modified' => 'Affiliate Last Modified',
        ];
    }
}

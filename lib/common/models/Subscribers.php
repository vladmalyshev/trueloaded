<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "subscribers".
 *
 * @property int $subscribers_id
 * @property int $platform_id
 * @property string $subscribers_md5hash
 * @property string $subscribers_firstname
 * @property string $subscribers_lastname
 * @property string $subscribers_email_address
 * @property int $subscribers_status
 * @property string $subscribers_datetime
 * @property int $subscribers_sales1
 * @property int $subscribers_sales2
 * @property int $subscribers_sales3
 */
class Subscribers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscribers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_id', 'subscribers_status', 'subscribers_sales1', 'subscribers_sales2', 'subscribers_sales3'], 'integer'],
            [['subscribers_datetime'], 'safe'],
            [['subscribers_md5hash', 'subscribers_firstname', 'subscribers_lastname', 'subscribers_email_address'], 'string', 'max' => 255],
            [['subscribers_email_address'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subscribers_id' => 'Subscribers ID',
            'platform_id' => 'Platform ID',
            'subscribers_md5hash' => 'Subscribers Md5hash',
            'subscribers_firstname' => 'Subscribers Firstname',
            'subscribers_lastname' => 'Subscribers Lastname',
            'subscribers_email_address' => 'Subscribers Email Address',
            'subscribers_status' => 'Subscribers Status',
            'subscribers_datetime' => 'Subscribers Datetime',
            'subscribers_sales1' => 'Subscribers Sales1',
            'subscribers_sales2' => 'Subscribers Sales2',
            'subscribers_sales3' => 'Subscribers Sales3',
        ];
    }
}

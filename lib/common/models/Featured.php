<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "featured".
 *
 * @property int $featured_id
 * @property int $products_id
 * @property string $featured_date_added
 * @property string $featured_last_modified
 * @property string $expires_date
 * @property string $date_status_change
 * @property int $status
 * @property int $affiliate_id
 */
class Featured extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'featured';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['products_id', 'status', 'affiliate_id'], 'integer'],
            [['featured_date_added', 'featured_last_modified', 'expires_date', 'date_status_change'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'featured_id' => 'Featured ID',
            'products_id' => 'Products ID',
            'featured_date_added' => 'Featured Date Added',
            'featured_last_modified' => 'Featured Last Modified',
            'expires_date' => 'Expires Date',
            'date_status_change' => 'Date Status Change',
            'status' => 'Status',
            'affiliate_id' => 'Affiliate ID',
        ];
    }
}

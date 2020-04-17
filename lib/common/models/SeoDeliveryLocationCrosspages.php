<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "seo_delivery_location_crosspages".
 *
 * @property int $id
 * @property int $crosspage_id
 */
class SeoDeliveryLocationCrosspages extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seo_delivery_location_crosspages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['crosspage_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'crosspage_id' => 'Crosspage ID',
        ];
    }
}

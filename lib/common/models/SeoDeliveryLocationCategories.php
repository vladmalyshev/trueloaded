<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "seo_delivery_location_categories".
 *
 * @property int $id
 * @property int $categories_id
 */
class SeoDeliveryLocationCategories extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seo_delivery_location_categories';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'categories_id'], 'required'],
            [['id', 'categories_id'], 'integer'],
            [['id', 'categories_id'], 'unique', 'targetAttribute' => ['id', 'categories_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categories_id' => 'Categories ID',
        ];
    }
}

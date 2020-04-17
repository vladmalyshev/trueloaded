<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "products_options".
 *
 * @property int $products_options_id
 * @property int $language_id
 * @property string $products_options_name
 * @property int $products_options_sort_order
 * @property string $type
 * @property string $products_options_image
 * @property string $products_options_color
 * @property int $is_virtual
 * @property int $display_filter
 * @property int $display_search
 */
class ProductsOptions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'products_options';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['products_options_id', 'language_id', 'products_options_sort_order', 'is_virtual', 'display_filter', 'display_search'], 'integer'],
            [['products_options_name', 'products_options_image'], 'string', 'max' => 32],
            [['type'], 'string', 'max' => 16],
            [['products_options_color'], 'string', 'max' => 8],
            [['products_options_id', 'language_id'], 'unique', 'targetAttribute' => ['products_options_id', 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'products_options_id' => 'Products Options ID',
            'language_id' => 'Language ID',
            'products_options_name' => 'Products Options Name',
            'products_options_sort_order' => 'Products Options Sort Order',
            'type' => 'Type',
            'products_options_image' => 'Products Options Image',
            'products_options_color' => 'Products Options Color',
            'is_virtual' => 'Is Virtual',
            'display_filter' => 'Display Filter',
            'display_search' => 'Display Search',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValues()
    {
        return $this->hasMany(ProductsOptionsValues::class, ['products_options_values_id' => 'products_options_id']);
    }
}

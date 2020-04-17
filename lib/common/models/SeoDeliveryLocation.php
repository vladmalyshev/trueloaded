<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "seo_delivery_location".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property integer $platform_id
 * @property integer $is_international
 * @property integer $show_product_group_id
 * @property integer $international_country_id
 * @property string $old_seo_page_name
 * @property integer $product_set_rule
 * @property integer $status
 * @property string $date_added
 * @property string $date_modified
 * @property string $image_headline
 * @property string $image_listing
 * @property integer $featured
 * @property integer $show_on_index
 */
class SeoDeliveryLocation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'seo_delivery_location';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'platform_id', 'is_international', 'show_product_group_id', 'international_country_id', 'product_set_rule', 'status', 'featured', 'show_on_index'], 'integer'],
            [['platform_id'], 'required'],
            [['date_added', 'date_modified'], 'safe'],
            [['old_seo_page_name', 'image_headline', 'image_listing'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent ID',
            'platform_id' => 'Platform ID',
            'is_international' => 'Is International',
            'show_product_group_id' => 'Show Product Group ID',
            'international_country_id' => 'International Country ID',
            'old_seo_page_name' => 'Old Seo Page Name',
            'product_set_rule' => 'Product Set Rule',
            'status' => 'Status',
            'date_added' => 'Date Added',
            'date_modified' => 'Date Modified',
            'image_headline' => 'Image Headline',
            'image_listing' => 'Image Listing',
            'featured' => 'Featured',
            'show_on_index' => 'Show On Index',
        ];
    }

    public function getLocationText()
    {
        return $this->hasMany(SeoDeliveryLocationText::className(), ['id' => 'id']);
    }
}

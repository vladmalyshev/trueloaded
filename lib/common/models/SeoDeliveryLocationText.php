<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "seo_delivery_location_text".
 *
 * @property integer $id
 * @property integer $language_id
 * @property string $location_name
 * @property string $location_description
 * @property string $location_description_long
 * @property string $meta_title
 * @property string $meta_keyword
 * @property string $meta_description
 * @property string $seo_page_name
 * @property string $location_description_short
 * @property integer $overwrite_head_title_tag
 * @property integer $overwrite_head_desc_tag
 */
class SeoDeliveryLocationText extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'seo_delivery_location_text';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'language_id', 'location_description_short'], 'required'],
            [['id', 'language_id', 'overwrite_head_title_tag', 'overwrite_head_desc_tag'], 'integer'],
            [['location_description', 'location_description_long', 'location_description_short'], 'string'],
            [['location_name'], 'string', 'max' => 128],
            [['meta_title', 'meta_keyword', 'meta_description'], 'string', 'max' => 1024],
            [['seo_page_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'language_id' => 'Language ID',
            'location_name' => 'Location Name',
            'location_description' => 'Location Description',
            'location_description_long' => 'Location Description Long',
            'meta_title' => 'Meta Title',
            'meta_keyword' => 'Meta Keyword',
            'meta_description' => 'Meta Description',
            'seo_page_name' => 'Seo Page Name',
            'location_description_short' => 'Location Description Short',
            'overwrite_head_title_tag' => 'Overwrite Head Title Tag',
            'overwrite_head_desc_tag' => 'Overwrite Head Desc Tag',
        ];
    }

    public function getLocation()
    {
        return $this->hasOne(SeoDeliveryLocation::className(), ['id' => 'id']);
    }
}

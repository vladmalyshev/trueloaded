<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "image_maps_properties".
 *
 * @property integer $maps_properties_id
 * @property integer $maps_id
 * @property integer $languages_id
 * @property string $maps_properties_name
 * @property string $maps_properties_value
 */
class ImageMapsProperties extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'image_maps_properties';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['maps_id', 'languages_id', 'maps_properties_name', 'maps_properties_value'], 'required'],
            [['maps_properties_id', 'maps_id', 'languages_id'], 'integer'],
            [['maps_properties_name'], 'string', 'max' => 64],
            [['maps_properties_value'], 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'maps_properties_id' => 'Maps Properties ID',
            'maps_id' => 'Maps ID',
            'languages_id' => 'Languages ID',
            'maps_properties_name' => 'Maps Properties Name',
            'maps_properties_value' => 'Maps Properties Value',
        ];
    }

    public function getImageMaps()
    {
        return $this->hasOne(ImageMaps::className(), ['maps_id' => 'maps_id']);
    }
}

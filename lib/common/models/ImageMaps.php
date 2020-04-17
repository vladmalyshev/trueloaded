<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "image_maps".
 *
 * @property integer $maps_id
 * @property string $image
 */
class ImageMaps extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'image_maps';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['image'], 'required'],
            [['maps_id'], 'integer'],
            [['image'], 'string', 'max' => 256],
            [['svg_data'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'maps_id' => 'Maps ID',
            'image' => 'Image',
            'svg_data' => 'Svg data',
        ];
    }

    public function getProperties()
    {
        return $this->hasMany(ImageMapsProperties::className(), ['maps_id' => 'maps_id']);
    }

    public function getTitle($languages_id)
    {
        return $this->hasMany(ImageMapsProperties::className(), ['maps_id' => 'maps_id'])
            ->where(['maps_properties_name' => 'title', 'languages_id' => $languages_id])->one()
            ->maps_properties_value;
    }


    public function getTitles() {
      return $this->hasMany(ImageMapsProperties::className(), ['maps_id' => 'maps_id'])
            ->andWhere(['maps_properties_name' => 'title']);
    }

    public function getImageMapTitle()
    {
      $languages_id = \Yii::$app->settings->get('languages_id');
      return
        $this->hasMany(ImageMapsProperties::className(), ['maps_id' => 'maps_id'])
             ->andWhere(['maps_properties_name' => 'title', 'languages_id' => $languages_id])
             ->select(['title' => 'maps_properties_value',
                      'maps_id'
                      ]);
    }
}

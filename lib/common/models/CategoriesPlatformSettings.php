<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

class CategoriesPlatformSettings extends \yii\db\ActiveRecord
{
  
  public static function tableName() {
    return 'categories_platform_settings';
  }
  
  public function rules()
  {
      return [
          [['categories_id', 'platform_id', 'show_on_home', 'maps_id'], 'integer'],
          [['categories_image', 'categories_image_2', 'categories_image_3'], 'string', 'max' => 128],
      ];
  }

  public function getImageMap()
  {
    return $this->hasOne(ImageMaps::className(), ['maps_id' => 'maps_id']);
  }

  public function getImageMapTitle()
  {
    $languages_id = \Yii::$app->settings->get('languages_id');
    return
      $this->hasOne(ImageMapsProperties::className(), ['maps_id' => 'maps_id']) //->via('imageMaps')
           ->andWhere(['maps_properties_name' => 'title', 'languages_id' => $languages_id])
           ->select(['title' => 'maps_properties_value',
                    'maps_id'
                    ])
        ;
  }

}

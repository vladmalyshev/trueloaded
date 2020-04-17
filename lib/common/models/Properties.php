<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\models;

use Yii;

/**
 * This is the model class for table "properties".
 */
class Properties extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'properties';
    }
    
    public function getValues(){
        return $this->hasMany(PropertiesValues::className(), ['properties_id' => 'properties_id']);
    }
    
    public function getDescriptions(){
        return $this->hasMany(PropertiesDescription::className(), ['properties_id' => 'properties_id']);
    }

}

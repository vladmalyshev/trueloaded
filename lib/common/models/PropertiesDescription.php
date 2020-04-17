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
 * This is the model class for table "properties_description".
 */
class PropertiesDescription extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'properties_description';
    }


    public function getPropertiesUnit() {
      return $this->hasOne(PropertiesUnits::className(), ['properties_units_id' => 'properties_units_id'])
          ;
    }

}

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

use yii\db\ActiveRecord;

/**
 * Class OptionsTemplatesAttributes
 * @package common\models
 *
 * @property int $options_templates_attributes_id
 * @property int $options_templates_id
 * @property int $options_id
 * @property int $options_values_id
 *
 */
class OptionsTemplatesAttributes extends ActiveRecord
{

    public static function tableName()
    {
        return 'options_templates_attributes';
    }

    public function getPrices(){
        return $this->hasMany(OptionsTemplatesAttributesPrices::className(),['options_templates_attributes_id' => 'options_templates_attributes_id']);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        OptionsTemplatesAttributesPrices::deleteAll(['options_templates_attributes_id'=>$this->options_templates_attributes_id]);

        return true;
    }


}
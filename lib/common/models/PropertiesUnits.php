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

/**
 * This is the model class for table "properties_units".
 *
 * @property int $properties_units_id
 * @property string $properties_units_title
 */
class PropertiesUnits extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'properties_units';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['properties_units_title'], 'required'],
            [['properties_units_title'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'properties_units_id' => 'Properties Units ID',
            'properties_units_title' => 'Properties Units Title',
        ];
    }
}

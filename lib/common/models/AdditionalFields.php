<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "additional_fields".
 *
 * @property int $additional_fields_id
 * @property string $additional_fields_code
 * @property string $field_type
 * @property int $additional_fields_group_id
 * @property int $sort_order
 */
class AdditionalFields extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'additional_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['additional_fields_code', 'field_type', 'additional_fields_group_id'], 'required'],
            [['additional_fields_group_id', 'sort_order', 'required'], 'integer'],
            [['additional_fields_code'], 'string', 'max' => 32],
            [['field_type'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'additional_fields_id' => 'Additional Fields ID',
            'additional_fields_code' => 'Additional Fields Code',
            'field_type' => 'Field Type',
            'additional_fields_group_id' => 'Additional Fields Group ID',
            'sort_order' => 'Sort Order',
            'required' => 'Required',
        ];
    }
}

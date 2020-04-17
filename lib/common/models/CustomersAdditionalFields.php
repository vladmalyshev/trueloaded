<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "customers_additional_fields".
 *
 * @property int $additional_fields_id
 * @property int $customers_id
 * @property string $value
 */
class CustomersAdditionalFields extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customers_additional_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['additional_fields_id', 'customers_id', 'value'], 'required'],
            [['additional_fields_id', 'customers_id'], 'integer'],
            [['value'], 'string', 'max' => 255],
            [['additional_fields_id', 'customers_id'], 'unique', 'targetAttribute' => ['additional_fields_id', 'customers_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'additional_fields_id' => 'Additional Fields ID',
            'customers_id' => 'Customers ID',
            'value' => 'Value',
        ];
    }
}

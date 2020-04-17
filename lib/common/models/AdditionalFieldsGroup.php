<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "additional_fields_group".
 *
 * @property int $additional_fields_group_id
 * @property int $sort_order
 */
class AdditionalFieldsGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'additional_fields_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sort_order'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'additional_fields_group_id' => 'Additional Fields Group ID',
            'sort_order' => 'Sort Order',
        ];
    }
}

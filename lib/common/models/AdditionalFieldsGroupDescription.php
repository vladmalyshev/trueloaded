<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "additional_fields_group_description".
 *
 * @property int $additional_fields_group_id
 * @property int $language_id
 * @property string $title
 */
class AdditionalFieldsGroupDescription extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'additional_fields_group_description';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['additional_fields_group_id', 'language_id'], 'required'],
            [['additional_fields_group_id', 'language_id'], 'integer'],
            [['title'], 'string', 'max' => 64],
            [['additional_fields_group_id', 'language_id'], 'unique', 'targetAttribute' => ['additional_fields_group_id', 'language_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'additional_fields_group_id' => 'Additional Fields Group ID',
            'language_id' => 'Language ID',
            'title' => 'Title',
        ];
    }
}

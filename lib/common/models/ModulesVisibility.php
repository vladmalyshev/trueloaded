<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "modules_visibility".
 *
 * @property integer $id
 * @property string $code
 * @property string $area
 */
class ModulesVisibility extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'modules_visibility';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['area'], 'string'],
            [['code'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'area' => 'Area',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "modules_countries".
 *
 * @property integer $id
 * @property string $code
 * @property string $countries
 */
class ModulesCountries extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'modules_countries';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['countries'], 'string'],
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
            'countries' => 'Countries',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "emails".
 *
 * @property integer $emails_id
 * @property string $subject
 * @property string $html
 * @property string $data
 * @property string $date_added
 * @property string $date_modified
 */
class Emails extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'emails';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['html', 'data'], 'string'],
            [['date_added', 'date_modified'], 'safe'],
            [['subject', 'theme_name', 'template'], 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'emails_id' => 'Emails ID',
            'subject' => 'Subject',
            'html' => 'Html',
            'data' => 'Data',
            'date_added' => 'Date Added',
            'date_modified' => 'Date Modified',
            'theme_name' => 'Theme Name',
            'template' => 'Template',
        ];
    }
}

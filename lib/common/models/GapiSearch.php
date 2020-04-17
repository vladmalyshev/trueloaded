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
 * This is the model class for table "gapi_search".
 *
 * @property int $gapi_id
 * @property string $gapi_keyword
 * @property int $gapi_views
 * @property int $total_satisfaction
 * @property int $yes_satisfaction
 * @property int $platform_id
 */
class GapiSearch extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gapi_search';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gapi_keyword', 'gapi_views', 'total_satisfaction', 'yes_satisfaction', 'platform_id'], 'required'],
            [['gapi_views', 'total_satisfaction', 'yes_satisfaction', 'platform_id'], 'integer'],
            [['gapi_keyword'], 'string', 'max' => 50],
            [['gapi_keyword'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'gapi_id' => 'Gapi ID',
            'gapi_keyword' => 'Gapi Keyword',
            'gapi_views' => 'Gapi Views',
            'total_satisfaction' => 'Total Satisfaction',
            'yes_satisfaction' => 'Yes Satisfaction',
            'platform_id' => 'Platform ID',
        ];
    }
}

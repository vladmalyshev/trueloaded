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
 * This is the model class for table "gapi_search_to_products".
 *
 * @property int $gapi_id
 * @property int $products_id
 * @property int $sort
 */
class GapiSearchToProducts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gapi_search_to_products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gapi_id', 'products_id', 'sort'], 'required'],
            [['gapi_id', 'products_id', 'sort'], 'integer'],
            [['gapi_id', 'products_id'], 'unique', 'targetAttribute' => ['gapi_id', 'products_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'gapi_id' => 'Gapi ID',
            'products_id' => 'Products ID',
            'sort' => 'Sort',
        ];
    }
}

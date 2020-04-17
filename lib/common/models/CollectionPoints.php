<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "collection_points".
 *
 * @property int $collection_points_id
 * @property string $collection_points_text
 * @property int $sort_order
 * @property string $address1
 * @property string $address2
 * @property string $address3
 */
class CollectionPoints extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collection_points';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sort_order'], 'integer'],
            [['collection_points_text'], 'string', 'max' => 128],
            [['address1', 'address2', 'address3'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'collection_points_id' => 'Collection Points ID',
            'collection_points_text' => 'Collection Points Text',
            'sort_order' => 'Sort Order',
            'address1' => 'Address1',
            'address2' => 'Address2',
            'address3' => 'Address3',
        ];
    }
    public function getAddress($delimiter = "\n")
    {
        return $this->address1.$delimiter.$this->address2.$delimiter.$this->address3;
    }
    public static function isCollect($shippingClass)
    {
        if ( preg_match('/^collect_(\d+)$/', mb_strtolower($shippingClass), $match) ) {
            return self::find()->where(['collection_points_id'=>(int)$match[1]])->limit(1)->one();
        }
        return false;
    }
}

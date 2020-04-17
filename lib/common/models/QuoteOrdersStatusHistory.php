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
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class QuoteOrdersStatusHistory extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'quote_orders_status_history';
    }
    
    public function behaviors() {
        return [
            'date_added' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['date_added'],
                ],
                'value' => new \yii\db\Expression('NOW()'),
            ]
        ];
    }

    /*
     * one-to-one
     * @return object
     */
    public function getOrder()
    {
        return $this->hasOne(QuoteOrders::className(), ['orders_id' => 'orders_id']);
    }
    
    public function getStatus() {
        return $this->hasOne(OrdersStatus::className(), ['orders_status_id' => 'orders_status_id']);
    }
    
    public function getGroup() {
        return $this->hasOne(OrdersStatusGroups::className(), ['orders_status_groups_id' => 'orders_status_groups_id'])->via('status');
    }
}

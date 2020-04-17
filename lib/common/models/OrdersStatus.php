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


/**
 * This is the model class for table "orders_status".
 *
 * @property int $orders_status_id
 * @property int $orders_status_groups_id
 * @property int $language_id
 * @property string $orders_status_name
 * @property string $orders_status_template
 * @property int $comment_template_id
 * @property string $orders_status_template_confirm
 * @property string $orders_status_template_sms
 * @property int $automated
 * @property int $order_evaluation_state_id
 * @property int $orders_status_allocate_allow
 */
class OrdersStatus extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'orders_status';
    }

    public static function create($orders_status_id, $orders_status_groups_id, $language_id, $orders_status_name, $orders_status_template, $automated, $orders_status_template_confirm = null, $order_evaluation_state_id = 0, $orders_status_allocate_allow = 0){
    	$model = new static();
    	$model->orders_status_id = $orders_status_id;
    	$model->orders_status_groups_id = $orders_status_groups_id;
    	$model->language_id = $language_id;
    	$model->orders_status_name = $orders_status_name;
        $model->comment_template_id = 0;
    	$model->orders_status_template = $orders_status_template;
    	$model->orders_status_template_confirm = $orders_status_template_confirm;
    	$model->automated = $automated;
        $model->order_evaluation_state_id = $order_evaluation_state_id;
        $model->orders_status_allocate_allow = $orders_status_allocate_allow;
    	return $model;
    }

    public static function newOrdersStatusId(){
    	return self::find()->max('orders_status_id') + 1;
    }

    public function getOrdersStatusHistory()
    {
        return $this->hasMany(OrdersStatusHistory::className(), ['orders_status_id' => 'orders_status_id']);
    }

    public static function getDefaultByOrderEvaluationState($orderEvaluationStateId = 0, $orderStatusPreferred = 0)
    {
        $return = null;
        foreach (self::find()
            ->where(['order_evaluation_state_id' => (int)$orderEvaluationStateId])
            ->orderBy(['order_evaluation_state_default' => SORT_DESC, 'orders_status_id' => SORT_ASC])
            ->asArray(false)->all() as $osRecord
        ) {
            if (is_null($return)) {
                $return = $osRecord;
            }
            if ((int)$osRecord->orders_status_id == (int)$orderStatusPreferred) {
                $return = $osRecord;
                break;
            }
        }
        unset($osRecord);
        return $return;
    }
}
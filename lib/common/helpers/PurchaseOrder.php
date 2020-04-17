<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class PurchaseOrder {

    use StatusTrait;

    const POES_PENDING = 5;
    const POES_PROCESSING = 15;
    const POES_RECEIVED = 25;
    const POES_CANCELLED = 55;

    public static function getStatusTypeId()
    {
        return 5;
    }

    /**
     * Automatically update Purchase Order Status based on Purchase Order Product statuses
     * @param mixed $orderRecord Purchase Order Id or instance of PurchaseOrders model
     * @return mixed false on error or current Purchase Order Status Id
     */
    public static function evaluate($orderRecord = 0)
    {
        $return = false;
        $orderRecord = self::getRecord($orderRecord);
        if ($orderRecord instanceof \common\models\PurchaseOrders) {
            $orderStatus = $orderRecord->orders_status;
            $orderProductStatusArray = array_fill_keys(array_keys(\common\helpers\OrderProduct::getStatusArray()), 0);
            foreach (\common\models\PurchaseOrdersProducts::findAll(['orders_id' => $orderRecord->orders_id]) as $orderProductRecord) {
                $orderProductStatusArray[$orderProductRecord->orders_products_status] += $orderProductRecord->products_quantity;
            }
            unset($orderProductRecord);
            if ($orderProductStatusArray[\common\helpers\OrderProduct::OPS_CANCELLED] > 0) {
                $return = self::POES_CANCELLED;
            }
            if ($orderProductStatusArray[\common\helpers\OrderProduct::OPS_RECEIVED] > 0) {
                $return = self::POES_RECEIVED;
            }
            if ($orderProductStatusArray[\common\helpers\OrderProduct::OPS_STOCK_PENDING] > 0) {
                $return = self::POES_PROCESSING;
            }
             if ($orderProductStatusArray[\common\helpers\OrderProduct::OPS_STOCK_ORDERED] > 0) {
                $return = self::POES_PROCESSING;
            }
            if ($orderProductStatusArray[\common\helpers\OrderProduct::OPS_QUOTED] > 0) {
                if ($return == false) {
                    $return = self::POES_PENDING;
                } else {
                    $return = self::POES_PROCESSING;
                }
            }
            unset($orderProductStatusArray);

            $orderStatusRecord = \common\models\OrdersStatus::getDefaultByOrderEvaluationState($return);
            $return = $orderStatus;
            if (($orderStatusRecord instanceof \common\models\OrdersStatus) AND $orderStatusRecord->orders_status_id != $return) {
                try {
                    $orderRecord->orders_status = $orderStatusRecord->orders_status_id;
                    $orderRecord->save();
                    $return = $orderRecord->orders_status;
                } catch (\Exception $exc) {}
            }
            unset($orderStatusRecord);
            unset($orderStatus);
        }
        unset($orderRecord);
        return $return;
    }

    /**
     * Automatically update Purchase Order Product Status based on Purchase Order Product Received
     * @param mixed $orderProductRecord Purchase Order Product Id or instance of PurchaseOrdersProducts model
     * @return mixed false on error or current Purchase Order Product Status Id
     */
    public static function evaluateProduct($orderProductRecord = 0)
    {
        $return = false;
        $orderProductRecord = self::getRecordProduct($orderProductRecord);
        if ($orderProductRecord instanceof \common\models\PurchaseOrdersProducts) {
            $orderProductStatus = $orderProductRecord->orders_products_status;
            $return = $orderProductStatus;
            $orderProductQuantityReal = ((int)$orderProductRecord->products_quantity - (int)$orderProductRecord->qty_cnld);
            if ($orderProductQuantityReal <= 0) {
                $return = \common\helpers\OrderProduct::OPS_CANCELLED;
            } else {
                if ($orderProductQuantityReal == (int)$orderProductRecord->qty_rcvd) {
                    $return = \common\helpers\OrderProduct::OPS_RECEIVED;
                } elseif ((int)$orderProductRecord->qty_rcvd > 0) {
                    $return = \common\helpers\OrderProduct::OPS_STOCK_PENDING;
                } else {
                    $return = \common\helpers\OrderProduct::OPS_QUOTED;
                }
            }
            if ($return != $orderProductStatus) {
                $orderProductRecord->orders_products_status = $return;
                try {
                    $orderProductRecord->save();
                } catch (\Exception $exc) {
                    $return = $orderProductStatus;
                }
            }
            unset($orderProductQuantityReal);
            unset($orderProductStatus);
        }
        unset($orderProductRecord);
        return $return;
    }

    /**
     * Get Purchase Order record
     * @param mixed $orderId Purchase Order Id or instance of PurchaseOrders model
     * @return mixed instance of PurchaseOrders model or null
     */
    public static function getRecord($orderId = 0)
    {
        return ($orderId instanceof \common\models\PurchaseOrders
            ? $orderId
            : \common\models\PurchaseOrders::findOne(['orders_id' => (int)$orderId])
        );
    }

    /**
     * Get Purchase Order Product record
     * @param mixed $orderProductId Purchase Order Product Id or instance of PurchaseOrdersProducts model
     * @return mixed instance of PurchaseOrdersProducts model or null
     */
    public static function getRecordProduct($orderProductId = 0)
    {
        return ($orderProductId instanceof \common\models\PurchaseOrdersProducts
            ? $orderProductId
            : \common\models\PurchaseOrdersProducts::findOne(['orders_products_id' => (int)$orderProductId])
        );
    }

    /**
     * Get configuration array of possible automated evaluation states
     * @return array configuration array of possible automated evaluation states
     */
    public static function getEvaluationStateArray()
    {
        return [
            self::POES_PENDING => [
                'long' => 'Pending',
                'short' => 'Pndg',
                'key' => 'POES_PENDING'
            ],
            self::POES_PROCESSING => [
                'long' => 'Processing',
                'short' => 'Proc',
                'key' => 'POES_PROCESSING'
            ],
            self::POES_RECEIVED => [
                'long' => 'Received',
                'short' => 'Rcvd',
                'key' => 'POES_RECEIVED'
            ],
            self::POES_CANCELLED => [
                'long' => 'Cancelled',
                'short' => 'Cnld',
                'key' => 'POES_CANCELLED'
            ]
        ];
    }
    
    public static function getOrderedProduct($ProductId)
    {
        $response = [];
        $orderedProducts = \common\models\PurchaseOrdersProducts::find()
                        ->select(['((op.products_quantity - op.qty_cnld) - op.qty_rcvd) AS qty', 'delivery_date'])
                        ->from(\common\models\PurchaseOrdersProducts::tableName() . ' AS op')
                        ->leftJoin(\common\models\PurchaseOrders::tableName() . ' AS o', 'op.orders_id = o.orders_id')
                        ->where(['products_id' => $ProductId])
                        ->andWhere([
                          'and',
                          '((op.products_quantity - op.qty_cnld) - op.qty_rcvd)>0',
                          ['>', 'o.delivery_date', date('Y-m-d 00:00:00', strtotime('-3 DAYS'))]
                          ])
                        ->asArray();
        foreach ($orderedProducts->all() as $productData) {
            $response[$productData['delivery_date']] += $productData['qty'];
        }
        ksort($response);
        return $response;
    }
}
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

class OrderPayment
{
    CONST OPYS_PENDING = 0;
    CONST OPYS_PROCESSING = 10;
    CONST OPYS_SUCCESSFUL = 20;
    CONST OPYS_REFUSED = 30;
    CONST OPYS_REFUNDED = 40;
    CONST OPYS_CANCELLED = 50;
    CONST OPYS_DISCOUNTED = 100;

    public static function createDebitFromOrder($orderInstance = null, $orderPaymentAmount = false, $ordersPaymentStatus = false, $transactionInformationArray = [])
    {
        $return = false;
        if ($orderInstance instanceof \common\classes\Order) {
            if ($orderPaymentAmount === false) {
                $orderPaymentAmount = $orderInstance->info['total_inc_tax'];
            }
            $orderPaymentAmount = (float)$orderPaymentAmount;
            if ($orderPaymentAmount == 0) {
                return $return;
            }
            $ordersPaymentStatus = (int)(($ordersPaymentStatus === false) ? self::OPYS_PENDING : $ordersPaymentStatus);
            $ordersPaymentStatusList = self::getStatusList();
            $ordersPaymentStatus = (!isset($ordersPaymentStatusList[$ordersPaymentStatus]) ? self::OPYS_PENDING : $ordersPaymentStatus);
            unset($ordersPaymentStatusList);
            $transactionInformationArray = (is_array($transactionInformationArray) ? $transactionInformationArray : []);

            $orderPaymentRecord = new \common\models\OrdersPayment();
            $orderPaymentRecord->orders_payment_id_parent = 0;
            $orderPaymentRecord->orders_payment_order_id = (int)$orderInstance->order_id;
            $orderPaymentRecord->orders_payment_module = trim($orderInstance->info['payment_class']);
            $orderPaymentRecord->orders_payment_module_name = trim($orderInstance->info['payment_method']);
            $orderPaymentRecord->orders_payment_is_credit = 0;
            $orderPaymentRecord->orders_payment_status = $ordersPaymentStatus;
            $orderPaymentRecord->orders_payment_amount = $orderPaymentAmount;
            $orderPaymentRecord->orders_payment_currency = trim($orderInstance->info['currency']);
            $orderPaymentRecord->orders_payment_currency_rate = (float)$orderInstance->info['currency_value'];
            $orderPaymentRecord->orders_payment_snapshot = json_encode(self::getOrderPaymentSnapshot($orderInstance));
            $orderPaymentRecord->orders_payment_transaction_id = trim(isset($transactionInformationArray['id']) ? $transactionInformationArray['id'] : '');
            $orderPaymentRecord->orders_payment_transaction_status = trim(isset($transactionInformationArray['status']) ? $transactionInformationArray['status'] : '');
            $orderPaymentRecord->orders_payment_transaction_commentary = trim(isset($transactionInformationArray['commentary']) ? $transactionInformationArray['commentary'] : '');
            $orderPaymentRecord->orders_payment_transaction_date = trim(isset($transactionInformationArray['date']) ? $transactionInformationArray['date'] : '0000-00-00 00:00:00');
            global $login_id;
            $orderPaymentRecord->orders_payment_admin_create = (int)$login_id;
            unset($login_id);
            $orderPaymentRecord->orders_payment_date_create = date('Y-m-d H:i:s');
            try {
                if ($orderPaymentRecord->save()) {
                    $return = $orderPaymentRecord;
                }
            } catch (\Exception $exc) {}
            unset($orderPaymentRecord);
        }
        unset($transactionInformationArray);
        unset($ordersPaymentStatus);
        unset($orderPaymentAmount);
        unset($orderInstance);
        return $return;
    }

    public static function searchRecord($orderPaymentModule = '', $orderPaymentTransactionId = '')
    {
        $orderPaymentModule = trim($orderPaymentModule);
        $orderPaymentTransactionId = trim($orderPaymentTransactionId);
        if ($orderPaymentModule == '' OR $orderPaymentTransactionId == '') {
            return false;
        }
        $orderPaymentRecord = \common\models\OrdersPayment::find()
            ->where(['orders_payment_module' => $orderPaymentModule])
            ->andWhere(['orders_payment_transaction_id' => $orderPaymentTransactionId])
            ->orderBy(['orders_payment_date_create' => SORT_DESC, 'orders_payment_id' => SORT_DESC])
            ->one();
        if (!($orderPaymentRecord instanceof \common\models\OrdersPayment)) {
            $orderPaymentRecord = new \common\models\OrdersPayment();
            $orderPaymentRecord->orders_payment_module = $orderPaymentModule;
            $orderPaymentRecord->orders_payment_transaction_id = $orderPaymentTransactionId;
            $orderPaymentRecord->orders_payment_status = self::OPYS_PENDING;
            global $login_id;
            $orderPaymentRecord->orders_payment_admin_create = (int)$login_id;
            unset($login_id);
        }
        return $orderPaymentRecord;
    }

    public static function getArrayByOrderId($orderId = 0, $asArray = true)
    {
        $return = [];
        foreach ((\common\models\OrdersPayment::find()
            ->where(['orders_payment_order_id' => (int)$orderId])
            ->orderBy(['orders_payment_date_create' => SORT_ASC])
            ->asArray($asArray)->all())
                as $orderPaymentRecord
        ) {
            $return[] = $orderPaymentRecord;
        }
        unset($orderPaymentRecord);
        return $return;
    }

    public static function getArrayParentByOrderId($orderId = 0, $asArray = true)
    {
        $return = [];
        foreach ((\common\models\OrdersPayment::find()
            ->where(['orders_payment_order_id' => (int)$orderId])
            ->andWhere(['orders_payment_id_parent' => 0])
            ->asArray($asArray)->all())
                as $orderPaymentRecord
        ) {
            $return[] = $orderPaymentRecord;
        }
        unset($orderPaymentRecord);
        return $return;
    }

    public static function getArrayChildByParentId($orderPaymentIdParent = 0, $asArray = true)
    {
        $return = [];
        foreach ((\common\models\OrdersPayment::find()
            ->where(['orders_payment_id_parent' => (int)$orderPaymentIdParent])
            ->asArray($asArray)->all())
                as $orderPaymentRecord
        ) {
            $return[] = $orderPaymentRecord;
        }
        unset($orderPaymentRecord);
        return $return;
    }

    public static function getArrayStatusByOrderIdTotal($orderId = 0, $orderTotal = 0)
    {
        $return = false;
        $orderId = (int)$orderId;
        $orderTotal = (float)$orderTotal;
        if ($orderTotal < 0) {
            $orderTotal = 0;
        }
        $debit = 0;
        $credit = 0;
        $discount = 0;
        foreach (self::getArrayParentByOrderId($orderId) as $orderPaymentParentRecord) {
            $orderPaymentParentRecord['orders_payment_amount'] = (float)(((float)$orderPaymentParentRecord['orders_payment_amount'] <= 0)
                ? 0 : $orderPaymentParentRecord['orders_payment_amount']
            );
            $orderPaymentParentRecord['orders_payment_currency_rate'] = (float)(((float)$orderPaymentParentRecord['orders_payment_currency_rate'] <= 0)
                ? 1 : $orderPaymentParentRecord['orders_payment_currency_rate']
            );
            if (in_array((int)$orderPaymentParentRecord['orders_payment_status'], [self::OPYS_SUCCESSFUL, self::OPYS_REFUNDED, self::OPYS_DISCOUNTED])) {
                if ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_SUCCESSFUL) {
                    $debit += ($orderPaymentParentRecord['orders_payment_amount'] / $orderPaymentParentRecord['orders_payment_currency_rate']);
                } elseif ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_REFUNDED) {
                    $credit += ($orderPaymentParentRecord['orders_payment_amount'] / $orderPaymentParentRecord['orders_payment_currency_rate']);
                } elseif ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_DISCOUNTED) {
                    $discount += ($orderPaymentParentRecord['orders_payment_amount'] / $orderPaymentParentRecord['orders_payment_currency_rate']);
                }
                foreach (self::getArrayChildByParentId($orderPaymentParentRecord['orders_payment_id']) as $orderPaymentChildRecord) {
                    if (in_array((int)$orderPaymentChildRecord['orders_payment_status'], [self::OPYS_REFUNDED, self::OPYS_DISCOUNTED])) {
                        $orderPaymentChildRecord['orders_payment_amount'] = (float)(((float)$orderPaymentChildRecord['orders_payment_amount'] <= 0)
                            ? 0 : $orderPaymentChildRecord['orders_payment_amount']
                        );
                        $orderPaymentChildRecord['orders_payment_currency_rate'] = (float)((float)$orderPaymentChildRecord['orders_payment_currency_rate'] <= 0
                            ? 1 : $orderPaymentChildRecord['orders_payment_currency_rate']
                        );
                        if ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_SUCCESSFUL) {
                            if ((int)$orderPaymentChildRecord['orders_payment_status'] == self::OPYS_DISCOUNTED) {
                                $discount += ($orderPaymentChildRecord['orders_payment_amount'] / $orderPaymentChildRecord['orders_payment_currency_rate']);
                            } elseif ((int)$orderPaymentChildRecord['orders_payment_status'] == self::OPYS_REFUNDED) {
                                $credit += ($orderPaymentChildRecord['orders_payment_amount'] / $orderPaymentChildRecord['orders_payment_currency_rate']);
                            }
                        } elseif ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_REFUNDED) {
                            // ???
                        } elseif ((int)$orderPaymentParentRecord['orders_payment_status'] == self::OPYS_DISCOUNTED) {
                            if ((int)$orderPaymentChildRecord['orders_payment_status'] == self::OPYS_REFUNDED) {
                                $discount -= ($orderPaymentChildRecord['orders_payment_amount'] / $orderPaymentChildRecord['orders_payment_currency_rate']);
                            }
                        }
                    }
                }
                unset($orderPaymentChildRecord);
            } elseif (in_array((int)$orderPaymentParentRecord['orders_payment_status'], [])) {

            }
        }
        unset($orderPaymentParentRecord);
        $discount = self::toAmount($discount);
        $discount = ($discount <= 0 ? 0 : $discount);
        $return = [
            'status' => 0,
            'total' => $orderTotal,
            'debit' => self::toAmount($debit),
            'credit' => self::toAmount($credit),
            'discount' => $discount,
            'paid' => 0,
            'due' => 0,
            'over' => 0
        ];
        $return['paid'] = (($return['debit'] + $return['discount']) - $return['credit']);
        $return['due'] = ($return['total'] - $return['paid']);
        if ($return['due'] > 0) {
            $return['status'] = 1;
        } elseif ($return['due'] < 0) {
            $return['status'] = -1;
            $return['over'] = abs($return['due']);
            $return['due'] = 0;
        }
        unset($credit);
        unset($debit);
        return $return;
    }

    public static function getAmountAvailable($orderPaymentRecord = 0)
    {
        $return = 0;
        $orderPaymentRecord = self::getRecord($orderPaymentRecord);
        if ($orderPaymentRecord instanceof \common\models\OrdersPayment) {
            if ($orderPaymentRecord->orders_payment_id_parent == 0) {
                $return = (float)$orderPaymentRecord->orders_payment_amount;
                foreach (self::getArrayChildByParentId($orderPaymentRecord->orders_payment_id) as $paymentChildRecord) {
                    if (in_array($paymentChildRecord['orders_payment_status'], [
                        self::OPYS_REFUNDED,
                        self::OPYS_DISCOUNTED
                    ])) {
                        $return -= (float)$paymentChildRecord['orders_payment_amount'];
                    }
                }
                unset($paymentChildRecord);
            } else {
                $return = self::getAmountAvailable($orderPaymentRecord->orders_payment_id_parent);
            }
        }
        return $return;
    }

    private static function toAmount($amount = 0)
    {
        return round((float)$amount, 2);
    }

    public static function getRecord($orderPaymentId = 0)
    {
        return ($orderPaymentId instanceof \common\models\OrdersPayment
            ? $orderPaymentId
            : \common\models\OrdersPayment::findOne(['orders_payment_id' => (int)$orderPaymentId])
        );
    }

    public static function getStatusList($forStatus = false, $isCredit = false)
    {
        $return = [
            self::OPYS_PENDING => TEXT_STATUS_OPYS_PENDING,
            self::OPYS_PROCESSING => TEXT_STATUS_OPYS_PROCESSING,
            self::OPYS_SUCCESSFUL => TEXT_STATUS_OPYS_SUCCESSFUL,
            self::OPYS_REFUSED => TEXT_STATUS_OPYS_REFUSED,
            self::OPYS_REFUNDED => TEXT_STATUS_OPYS_REFUNDED,
            self::OPYS_CANCELLED => TEXT_STATUS_OPYS_CANCELLED,
            self::OPYS_DISCOUNTED => TEXT_STATUS_OPYS_DISCOUNTED
        ];
        $isCredit = ((int)$isCredit > 0 ? true : false);
        if ($forStatus !== false) {
            switch ($forStatus) {
                case self::OPYS_PENDING:
                    unset($return[self::OPYS_REFUSED]);
                    unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_DISCOUNTED]);
                break;
                case self::OPYS_PROCESSING:
                    unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_CANCELLED]);
                    unset($return[self::OPYS_DISCOUNTED]);
                break;
                case self::OPYS_SUCCESSFUL:
                    //unset($return[self::OPYS_PENDING]);
                    //unset($return[self::OPYS_PROCESSING]);
                    //unset($return[self::OPYS_REFUSED]);
                    unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_CANCELLED]);
                    unset($return[self::OPYS_DISCOUNTED]);
                break;
                case self::OPYS_REFUSED:
                    unset($return[self::OPYS_PROCESSING]);
                    unset($return[self::OPYS_SUCCESSFUL]);
                    unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_CANCELLED]);
                    unset($return[self::OPYS_DISCOUNTED]);
                break;
                case self::OPYS_REFUNDED:
                    unset($return[self::OPYS_PENDING]);
                    unset($return[self::OPYS_PROCESSING]);
                    unset($return[self::OPYS_SUCCESSFUL]);
                    unset($return[self::OPYS_REFUSED]);
                    unset($return[self::OPYS_CANCELLED]);
                break;
                case self::OPYS_CANCELLED:
                    unset($return[self::OPYS_PROCESSING]);
                    unset($return[self::OPYS_SUCCESSFUL]);
                    unset($return[self::OPYS_REFUSED]);
                    unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_DISCOUNTED]);
                break;
                case self::OPYS_DISCOUNTED:
                    unset($return[self::OPYS_PENDING]);
                    unset($return[self::OPYS_PROCESSING]);
                    unset($return[self::OPYS_SUCCESSFUL]);
                    unset($return[self::OPYS_REFUSED]);
                    //unset($return[self::OPYS_REFUNDED]);
                    unset($return[self::OPYS_CANCELLED]);
                break;
            }
        }
        return $return;
    }

    public static function getOrderPaymentSnapshot($orderInstance = null)
    {
        $return = [
            'product' => [],
            'total' => [
                'subtotal' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'shipping' => [
                    'module' => '',
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'tax' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'discount' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'coupon' => [
                    'id' => 0,
                    'type' => '',
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'total' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'paid' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'due' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ],
                'refund' => [
                    'price_exc' => 0,
                    'price_inc' => 0
                ]
            ]
        ];
        if ($orderInstance instanceof \common\classes\Order) {
            foreach ($orderInstance->products as $orderProduct) {
                $orderProductArray = [
                    'prid' => (int)$orderProduct['id'],
                    'uprid' => \common\helpers\Inventory::normalize_id_excl_virtual($orderProduct['template_uprid']),
                    'model' => trim($orderProduct['model']),
                    'tax_rate' => (float)$orderProduct['tax'],
                    'price_exc' => (float)$orderProduct['final_price'],
                    'price_inc' => (float)$orderProduct['final_price'],
                    'qty' => (int)$orderProduct['qty'],
                    'qty_cnld' => (int)$orderProduct['qty_cnld'],
                    'qty_rcvd' => (int)$orderProduct['qty_rcvd'],
                    'qty_dspd' => (int)$orderProduct['qty_dspd'],
                    'qty_dlvd' => (int)$orderProduct['qty_dlvd']
                ];
                $orderProductArray['price_inc'] = round((float)$orderProductArray['price_exc'] * (1 + $orderProductArray['tax_rate'] / 100), 2);
                $return['product'][] = $orderProductArray;
                unset($orderProductArray);
            }
            unset($orderProduct);
            foreach ($orderInstance->totals as $orderTotal) {
                $code = strtolower(isset($orderTotal['code']) ? substr($orderTotal['code'], 3) : '');
                switch ($code) {
                    case 'subtotal':
                    case 'shipping':
                    case 'tax':
                    case 'total':
                        $orderTotalArray = [
                            'price_exc' => (float)$orderTotal['value_exc_vat'],
                            'price_inc' => (float)$orderTotal['value_inc_tax']
                        ];
                        if ($code == 'shipping') {
                            $orderTotalArray['module'] = trim($orderInstance->info['shipping_class']);
                        } elseif ($code == 'coupon') {
                            $orderTotalArray['id'] = 0; //???
                            $orderTotalArray['type'] = ''; //???
                        }
                        $return['total'][$code] = $orderTotalArray;
                        unset($orderTotalArray);
                    break;
                    case 'paid':
                    case 'due':
                    case 'refund':
                        $orderTotalArray = [
                            'price_exc' => (float)$orderTotal['value_inc_tax'],
                            'price_inc' => (float)$orderTotal['value_inc_tax']
                        ];
                        $return['total'][$code] = $orderTotalArray;
                        unset($orderTotalArray);
                    break;
                }
            }
            unset($orderTotal);
        }
        return $return;
    }
}
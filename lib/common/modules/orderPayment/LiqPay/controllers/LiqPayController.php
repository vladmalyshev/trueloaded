<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace common\modules\orderPayment\LiqPay\controllers;

use backend\services\OrdersService;
use common\modules\orderPayment\liqpay;
use common\modules\orderPayment\LiqPay\services\LiqPayService;
use common\services\OrderManager;
use frontend\controllers\Sceleton;
use common\services\storages\StorageInterface;

class LiqPayController extends Sceleton
{
    public $enableCsrfValidation = false;

    /** @var LiqPayService */
    private $liqPayService;
    /** @var OrdersService */
    private $ordersService;
    /** @var OrderManager */
    private $orderManager;

    public function __construct(
        $id,
        $module = null,
        LiqPayService $liqPayService,
        OrdersService $ordersService,
        array $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->liqPayService = $liqPayService;
        $this->ordersService = $ordersService;
        /** @var StorageInterface $storage */
        $storage = \Yii::$app->get('storage');
        $this->orderManager = new OrderManager($storage);
    }

    public function actionHandleTransaction() {
        $order = null;
        $liqPay = null;
        try {
            $transaction = $this->liqPayService->createTransactionModel(\Yii::$app->request->post());
            if ($transaction->hasErrors()) {
                throw new \RuntimeException(implode("\n", $transaction->getErrors()));
            }
            /** @var liqpay $liqPay */
            $liqPay = $this->orderManager->getPaymentCollection('liqpay')->getSelectedPayment();
            if (!is_object($liqPay)) {
                throw new \RuntimeException('Payment module LiqPay not found.');
            }
            if (!$liqPay->validResponse($transaction->data, $transaction->signature)) {
                throw new \RuntimeException('Wrong Signature.');
            }
            $transactionData = $this->liqPayService->parseTransaction(json_decode(base64_decode($transaction->data), true));
            $order = $this->ordersService->getById($transactionData->getOrderId());
            $response = $this->liqPayService->getTransactionResponse($transactionData, $liqPay->getDefaultOrderStatusId());
        } catch (\Exception $e) {
            $response = [
                'orderStatus' => defined('MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID')
                    ? (int)MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID
                    : (is_object($liqPay) ? $liqPay->getDefaultOrderStatusId() : 1),
                'comment' => explode("\n", $e->getMessage()),
            ];
        } catch (\Throwable $e) {
            $response = [
                'orderStatus' => defined('MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID')
                    ? (int)MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID
                    : (is_object($liqPay) ? $liqPay->getDefaultOrderStatusId() : 1),
                'comment' => explode("\n", $e->getMessage()),
            ];
        }
        try {
            $this->ordersService->changeStatus($order, $response['orderStatus'], implode("\n", $response['comment']));
        } catch (\Exception $e) {
            \Yii::error('LiqPay - Transaction Handle ERROR: '. $e->getMessage());
        } catch (\Throwable $e) {
            \Yii::error('LiqPay - Transaction Handle ERROR: '. $e->getMessage());
        }
    }

    public function getViewPath()
    {
        return \Yii::getAlias('@liq-pay/views');
    }
}

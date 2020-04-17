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


namespace common\modules\orderPayment\LiqPay\services;


use common\modules\orderPayment\LiqPay\DTO\TransactionLiqPay;
use common\modules\orderPayment\LiqPay\lib\LiqPay;
use common\services\PlatformsConfigurationService;
use yii\base\DynamicModel;

class LiqPayService
{
    public static function allowed()
    {
        try {
            if (defined('MODULE_PAYMENT_LIQPAY_STATUS')) {
                return true;
            }
            /** @var PlatformsConfigurationService $platformsConfigurationService */
            $platformsConfigurationService = \Yii::createObject(PlatformsConfigurationService::class);
            return $platformsConfigurationService->existByKey('MODULE_PAYMENT_LIQPAY_STATUS');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $data
     * @return DynamicModel
     * @throws \yii\base\InvalidConfigException
     */
    public function createTransactionModel(array $data)
    {
        /**
         * @property orderId
         */
        $model = DynamicModel::validateData([
            'data' => $data['data'],
            'signature' => $data['signature'],
        ],
            [
                ['data', 'string', 'min' => 1, 'message' => 'Transaction data missed', 'tooShort' => 'Transaction data missed'],
                ['data', 'required', 'message' => 'Transaction data missed'],
                ['signature', 'string', 'min' => 1, 'message' => 'Transaction signature missed', 'tooShort' => 'Transaction signature missed'],
                ['signature', 'required', 'message' => 'Transaction signature missed'],
            ]);
        return $model;
    }

    /**
     * @param array $data
     * @return TransactionLiqPay
     */
    public function parseTransaction(array $data)
    {
        return TransactionLiqPay::create($data);
    }

    /**
     * @param TransactionLiqPay $transactionData
     * @return array
     */
    public function getTransactionResponse(TransactionLiqPay $transactionData, int $defaultOrderStatus = 1): array
    {
        if ($transactionData->isSuccess()) {
            return $this->getSuccessTransactionResponse($transactionData, $defaultOrderStatus);
        }
        return $this->getErrorTransactionResponse($transactionData, $defaultOrderStatus);
    }

    /**
     * @param string $public_key
     * @param string $private_key
     * @param array $params
     * @return string
     */
    public function getCnbForm(string $public_key, string $private_key, array $params)
    {
        return (new LiqPay($public_key, $private_key))->cnb_form($params);
    }

    private function getSuccessTransactionResponse(TransactionLiqPay $transactionData, int $defaultOrderStatus = 1)
    {
        return [
            'orderStatus' => defined('MODULE_PAYMENT_LIQPAY_ORDER_PAID_STATUS_ID') ? (int)MODULE_PAYMENT_LIQPAY_ORDER_PAID_STATUS_ID : $defaultOrderStatus,
            'comment' => [
                'Status: '. $transactionData->getStatus(),
                'Type:' . $transactionData->getType(),
                'Payment Id: ' . $transactionData->getPaymentId(),
                'Transaction Id: ' . $transactionData->getTransactionId(),
                'Pay Type: ' . $transactionData->getPaytype(),
                'Acq Id: ' . $transactionData->getAcqId(),
                'LiqPay Order Id: ' . $transactionData->getLiqPayOrderId(),
                'Amount: ' . $transactionData->getAmount(),
                'Currency: ' . $transactionData->getCurrency(),
                'Sender Commission: ' . $transactionData->getSenderCommission(),
                'Receiver Commission: ' . $transactionData->getReceiverCommission(),
            ],
        ];
    }

    private function getErrorTransactionResponse(TransactionLiqPay $transactionData, int $defaultOrderStatus = 1)
    {
        return [
            'orderStatus' => defined('MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID') ? (int)MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID : $defaultOrderStatus,
            'comment' => [
                'Error Code: '. $transactionData->getErrorCode(),
                'Error Message: ' . $transactionData->getErrorDescription(),
            ],
        ];
    }
}

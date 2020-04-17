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


namespace common\modules\orderPayment\LiqPay\DTO;


final class TransactionLiqPay
{
    /** @var string */
    private $action;
    /** @var string */
    private $errorCode;
    /** @var string */
    private $errorDescription;
    /** @var string */
    private $info;
    /** @var int */
    private $paymentId;
    /** @var string */
    private $status;
    /** @var string */
    private $public_key;
    /** @var string */
    private $type;
    /** @var string */
    private $paytype;
    /** @var int */
    private $acqId;
    /** @var int */
    private $orderId;
    /** @var string */
    private $liqPayOrderId;
    /** @var float */
    private $amount;
    /** @var string */
    private $description;
    /** @var string */
    private $currency;
    /** @var float */
    private $senderCommission;
    /** @var float */
    private $receiverCommission;
    /** @var float */
    private $agentCommission;
    /** @var float */
    private $amountDebit;
    /** @var float */
    private $amountCredit;
    /** @var float */
    private $commissionDebit;
    /** @var float */
    private $commissionCredit;
    /** @var int */
    private $mpiCci;
    /** @var int */
    private $transactionId;

    private function __construct()
    {
    }

    /**
     * @param array $data
     * @return self
     */
    public static function create(array $data): self
    {
        $transaction = new self();
        $transaction->errorCode = $data['err_code'] ?? '';
        $transaction->errorDescription = $data['err_description'] ?? '';
        $transaction->info = $data['info'] ?? '';
        $transaction->action = $data['action'] ?? '';
        $transaction->paymentId = isset($data['payment_id']) ? (int)$data['payment_id'] : 0;
        $transaction->status = $data['status'] ?? '';
        $transaction->public_key = $data['public_key'] ?? '';
        $transaction->type = $data['type'] ?? '';
        $transaction->paytype = $data['paytype'] ?? '';
        $transaction->acqId = isset($data['acq_id']) ? (int)$data['acq_id'] : 0;
        $transaction->orderId = isset($data['order_id']) ? (int)explode('_',$data['order_id'])[0] : 0.00;
        $transaction->liqPayOrderId = $data['liqpay_order_id'] ?? '';
        $transaction->amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        $transaction->description = $data['description'] ?? '';
        $transaction->currency = $data['currency'] ?? '';
        $transaction->senderCommission = isset($data['sender_commission']) ? (float)$data['sender_commission'] : 0.00;
        $transaction->receiverCommission = isset($data['receiver_commission']) ? (float)$data['receiver_commission'] : 0.00;
        $transaction->agentCommission = isset($data['agent_commission']) ? (float)$data['agent_commission'] : 0.00;
        $transaction->amountDebit = isset($data['amount_debit']) ? (float)$data['amount_debit'] : 0.00;
        $transaction->amountCredit = isset($data['amount_credit']) ? (float)$data['amount_credit'] : 0.00;
        $transaction->commissionDebit = isset($data['commission_debit']) ? (float)$data['commission_debit'] : 0.00;
        $transaction->commissionCredit = isset($data['commission_credit']) ? (float)$data['commission_credit'] : 0.00;
        $transaction->mpiCci = isset($data['mpi_eci']) ? (int)$data['mpi_eci'] : 0;
        $transaction->transactionId = isset($data['transaction_id']) ? (int)$data['transaction_id'] : 0;

        return $transaction;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @return int
     */
    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->public_key;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPaytype(): string
    {
        return $this->paytype;
    }

    /**
     * @return int
     */
    public function getAcqId(): int
    {
        return $this->acqId;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getLiqPayOrderId(): string
    {
        return $this->liqPayOrderId;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getSenderCommission(): float
    {
        return $this->senderCommission;
    }

    /**
     * @return float
     */
    public function getReceiverCommission(): float
    {
        return $this->receiverCommission;
    }

    /**
     * @return float
     */
    public function getAgentCommission(): float
    {
        return $this->agentCommission;
    }

    /**
     * @return float
     */
    public function getAmountDebit(): float
    {
        return $this->amountDebit;
    }

    /**
     * @return float
     */
    public function getAmountCredit(): float
    {
        return $this->amountCredit;
    }

    /**
     * @return float
     */
    public function getCommissionDebit(): float
    {
        return $this->commissionDebit;
    }

    /**
     * @return float
     */
    public function getCommissionCredit(): float
    {
        return $this->commissionCredit;
    }

    /**
     * @return int
     */
    public function getMpiCci(): int
    {
        return $this->mpiCci;
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function isSuccess(): bool
    {
        return !in_array($this->status, ['error', 'failure']);
    }
}

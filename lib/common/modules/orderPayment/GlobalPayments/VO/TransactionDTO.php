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


namespace common\modules\orderPayment\GlobalPayments\VO;


final class TransactionDTO
{
    /** @var string */
    private $transactionId;
    /** @var string|null */
    private $parentTransactionId;
    /** @var string */
    private $transactionCode;
    /** @var float */
    private $amount;
    /** @var string */
    private $message;
    /** @var int */
    private $orderId;
    /** @var int */
    private $paymentStatus;
    /** @var string */
    private $orderSnapshot;
    /** @var float */
    private $currencyRate;
    /** @var string */
    private $currency;
    /** @var int */
    private $isCredit;
    /** @var \DateTimeImmutable */
    private $date;

    private function __construct()
    {

    }

    public static function create(
        string $transactionId,
        string $parentTransactionId,
        string $transactionCode,
        float $amount,
        string $message,
        int $orderId,
        int $paymentStatus,
        string $orderSnapshot,
        string $currency,
        float $currencyRate,
        int $isCredit = 0,
        ?\DateTimeImmutable $date = null
    ): self
    {
        $transaction = new self();
        $transaction->transactionId = $transactionId;
        $transaction->parentTransactionId = $parentTransactionId;
        $transaction->transactionCode = $transactionCode;
        $transaction->amount = $amount;
        $transaction->message = $message;
        $transaction->orderId = $orderId;
        $transaction->paymentStatus = $paymentStatus;
        $transaction->orderSnapshot = $orderSnapshot;
        $transaction->currencyRate = $currencyRate;
        $transaction->currency = $currency;
        $transaction->isCredit = $isCredit;
        $transaction->date = $date ?? new \DateTimeImmutable();
        return $transaction;
    }
    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return string|null
     */
    public function getParentTransactionId(): ?string
    {
        return $this->parentTransactionId;
    }

    /**
     * @return string
     */
    public function getTransactionCode(): string
    {
        return $this->transactionCode;
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
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getPaymentStatus(): int
    {
        return $this->paymentStatus;
    }

    /**
     * @return string
     */
    public function getOrderSnapshot(): string
    {
        return $this->orderSnapshot;
    }

    /**
     * @return float
     */
    public function getCurrencyRate(): float
    {
        return $this->currencyRate;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return int
     */
    public function getIsCredit(): int
    {
        return $this->isCredit;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

}

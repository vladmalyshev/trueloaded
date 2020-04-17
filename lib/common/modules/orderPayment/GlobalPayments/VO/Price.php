<?php
declare (strict_types=1);


namespace common\modules\orderPayment\GlobalPayments\VO;


class Price
{
    /** @var string */
    private $currency;
    /** @var float */
    private $amount;
    /** @var int */
    private $decimals;
    private function __construct(float $amount, string $currency, int $decimals = 2)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->decimals = $decimals;
    }

    public static function create(...$args)
    {
        if (!is_float($args[0])) {
            throw new \InvalidArgumentException('Amount wrong type');
        }
        if (!is_string($args[1])) {
            throw new \InvalidArgumentException('Currency wrong type');
        }
        if (isset($args[2])) {
            if (!is_int($args[2])) {
                throw new \InvalidArgumentException('Currency wrong type');
            }
            return new static($args[0], $args[1], $args[2]);
        }
        return new static($args[0], $args[1]);
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return $this->decimals;
    }
    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function getAmountRound(): float
    {
        return round($this->amount, $this->decimals);
    }

    /**
     * @return int
     */
    public function getAmountRoundInt(): int
    {
        $amount = $this->getAmountRound() * (10 ** $this->decimals);
        return (int)$amount;
    }
    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function sameValueAs(self $money): bool
    {
        return $this->amount === $money->getAmount()
            && $this->currency === $money->getCurrency()
            && $this->decimals === $money->getDecimals();
    }

}

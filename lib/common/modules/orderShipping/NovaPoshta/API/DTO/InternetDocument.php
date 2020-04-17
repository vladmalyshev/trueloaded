<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class InternetDocument
{
    /** @var float */
    private $costOnSite;
    /** @var string */
    private $ref;
    /** @var \DateTimeImmutable */
    private $estimatedDeliveryDate;
    /** @var string */
    private $intDocNumber;
    /** @var string */
    private $typeDocument;

    private function __construct()
    {
    }
    public static function create(
        string $ref,
        float $costOnSite,
        \DateTimeImmutable $estimatedDeliveryDate,
        string $intDocNumber,
        string $typeDocument
    ): self
    {
        $document = new self();
        $document->ref = $ref;
        $document->costOnSite = $costOnSite;
        $document->estimatedDeliveryDate = $estimatedDeliveryDate;
        $document->intDocNumber = $intDocNumber;
        $document->typeDocument = $typeDocument;
        return $document;
    }

    /**
     * @return float
     */
    public function getCostOnSite(): float
    {
        return $this->costOnSite;
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->ref;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEstimatedDeliveryDate(): \DateTimeImmutable
    {
        return $this->estimatedDeliveryDate;
    }

    /**
     * @return string
     */
    public function getIntDocNumber(): string
    {
        return $this->intDocNumber;
    }

    /**
     * @return string
     */
    public function getTypeDocument(): string
    {
        return $this->typeDocument;
    }

}

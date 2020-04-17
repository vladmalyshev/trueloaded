<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class Warehouse
{
    /** @var string */
    private $ref;
    /** @var string */
    private $description;
    /** @var string */
    private $citeKey;
    /** @var string */
    private $shortAddress;
    /** @var string */
    private $phone;
    /** @var string */
    private $typeOfWarehouse;
    /** @var string */
    private $number;
    /** @var string */
    private $cityRef;
    /** @var string */
    private $cityDescription;
    /** @var int */
    private $postFinance;
    /** @var int */
    private $bicycleParking;
    /** @var int */
    private $paymentAccess;
    /** @var int */
    private $posTerminal;
    /** var int */
    private $internationalShipping;
    /** @var int */
    private $totalMaxWeightAllowed;
    /** @var int */
    private $placeMaxWeightAllowed;
    /** @var string */
    private $districtCode;
    /** @var string */
    private $warehouseStatus;
    /** @var string */
    private $categoryOfWarehouse;

    private function __construct()
    {
    }

    public static function createDumb(): self
    {
        $warehouse = new self();
        $warehouse->ref = '';
        $warehouse->description = '';
        return $warehouse;
    }
    public static function createSimply(string $ref, string $description = ''): self
    {
        $warehouse = new self();
        $warehouse->ref = $ref;
        $warehouse->description = $description;
        return $warehouse;
    }
    public static function create(
        string $ref,
        string $description,
        string $citeKey,
        string $shortAddress,
        string $phone,
        string $typeOfWarehouse,
        string $number,
        string $cityRef,
        string $cityDescription,
        int $postFinance,
        int $bicycleParking,
        int $paymentAccess,
        int $posTerminal,
        int $internationalShipping,
        int $totalMaxWeightAllowed,
        int $placeMaxWeightAllowed,
        string $districtCode,
        string $warehouseStatus,
        string $categoryOfWarehouse
    ): self
    {
        $warehouse = new self();
        $warehouse->ref = $ref;
        $warehouse->description = $description;
        $warehouse->citeKey = $citeKey;
        $warehouse->shortAddress = $shortAddress;
        $warehouse->phone = $phone;
        $warehouse->typeOfWarehouse = $typeOfWarehouse;
        $warehouse->number = $number;
        $warehouse->cityRef = $cityRef;
        $warehouse->cityDescription = $cityDescription;
        $warehouse->postFinance = $postFinance;
        $warehouse->bicycleParking = $bicycleParking;
        $warehouse->paymentAccess = $paymentAccess;
        $warehouse->posTerminal = $posTerminal;
        $warehouse->internationalShipping = $internationalShipping;
        $warehouse->totalMaxWeightAllowed = $totalMaxWeightAllowed;
        $warehouse->placeMaxWeightAllowed = $placeMaxWeightAllowed;
        $warehouse->districtCode = $districtCode;
        $warehouse->warehouseStatus = $warehouseStatus;
        $warehouse->categoryOfWarehouse = $categoryOfWarehouse;
        return $warehouse;
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->ref;
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
    public function getCiteKey(): string
    {
        return $this->citeKey;
    }

    /**
     * @return string
     */
    public function getShortAddress(): string
    {
        return $this->shortAddress;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getTypeOfWarehouse(): string
    {
        return $this->typeOfWarehouse;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getCityRef(): string
    {
        return $this->cityRef;
    }

    /**
     * @return string
     */
    public function getCityDescription(): string
    {
        return $this->cityDescription;
    }

    /**
     * @return int
     */
    public function getPostFinance(): int
    {
        return $this->postFinance;
    }

    /**
     * @return int
     */
    public function getBicycleParking(): int
    {
        return $this->bicycleParking;
    }

    /**
     * @return int
     */
    public function getPaymentAccess(): int
    {
        return $this->paymentAccess;
    }

    /**
     * @return int
     */
    public function getPosTerminal(): int
    {
        return $this->posTerminal;
    }

    /**
     * @return mixed
     */
    public function getInternationalShipping()
    {
        return $this->internationalShipping;
    }

    /**
     * @return int
     */
    public function getTotalMaxWeightAllowed(): int
    {
        return $this->totalMaxWeightAllowed;
    }

    /**
     * @return int
     */
    public function getPlaceMaxWeightAllowed(): int
    {
        return $this->placeMaxWeightAllowed;
    }

    /**
     * @return string
     */
    public function getDistrictCode(): string
    {
        return $this->districtCode;
    }

    /**
     * @return string
     */
    public function getWarehouseStatus(): string
    {
        return $this->warehouseStatus;
    }

    /**
     * @return string
     */
    public function getCategoryOfWarehouse(): string
    {
        return $this->categoryOfWarehouse;
    }
}

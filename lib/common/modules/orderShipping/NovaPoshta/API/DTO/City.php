<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class City
{
    /** @var string */
    private $ref;
    /** @var string */
    private $areaRef;
    /** @var string */
    private $description;
    /** @var int */
    private $delivery1;
    /** @var int */
    private $delivery2;
    /** @var int */
    private $delivery3;
    /** @var int */
    private $delivery4;
    /** @var int */
    private $delivery5;
    /** @var int */
    private $delivery6;
    /** @var int */
    private $delivery7;
    /** @var string */
    private $settlementType;
    /** @var string */
    private $settlementTypeDescription;
    /** @var int */
    private $cityId;
    /** @var int */
    private $isBranch;
    private $preventEntryNewStreetsUser;
    private $conglomerates;
    /** @var int */
    private $specialCashCheck;

    private function __construct()
    {
    }

    public static function createDumb(): self
    {
        $city = new self();
        $city->ref = '';
        $city->description = '';
        return $city;
    }
    public static function createSimply(string $ref, string $description = ''): self
    {
        $city = new self();
        $city->ref = $ref;
        $city->description = $description;
        return $city;
    }
    /**
     * @param string $ref
     * @param string $description
     * @param string $areaRef
     * @param int $delivery1
     * @param int $delivery2
     * @param int $delivery3
     * @param int $delivery4
     * @param int $delivery5
     * @param int $delivery6
     * @param int $delivery7
     * @param string $settlementType
     * @param string $settlementTypeDescription
     * @param int $cityId
     * @param int $isBranch
     * @param $preventEntryNewStreetsUser
     * @param $conglomerates
     * @param int $specialCashCheck
     * @return static
     */
    public static function create(
        string $ref,
        string $description,
        string $areaRef,
        int $delivery1,
        int $delivery2,
        int $delivery3,
        int $delivery4,
        int $delivery5,
        int $delivery6,
        int $delivery7,
        string $settlementType,
        string $settlementTypeDescription,
        int $cityId,
        int $isBranch,
        $preventEntryNewStreetsUser,
        $conglomerates,
        int $specialCashCheck
    ): self
    {
        $city = new self();
        $city->ref = $ref;
        $city->description = $description;
        $city->areaRef = $areaRef;
        $city->delivery1 = $delivery1;
        $city->delivery2 = $delivery2;
        $city->delivery3 = $delivery3;
        $city->delivery4 = $delivery4;
        $city->delivery5 = $delivery5;
        $city->delivery6 = $delivery6;
        $city->delivery7 = $delivery7;
        $city->settlementType = $settlementType;
        $city->settlementTypeDescription = $settlementTypeDescription;
        $city->cityId = $cityId;
        $city->isBranch = $isBranch;
        $city->preventEntryNewStreetsUser = $preventEntryNewStreetsUser;
        $city->conglomerates = $conglomerates;
        $city->specialCashCheck = $specialCashCheck;
        return $city;
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
    public function getAreaRef(): string
    {
        return $this->areaRef;
    }

    /**
     * @return int
     */
    public function getDelivery1(): int
    {
        return $this->delivery1;
    }

    /**
     * @return int
     */
    public function getDelivery2(): int
    {
        return $this->delivery2;
    }

    /**
     * @return int
     */
    public function getDelivery3(): int
    {
        return $this->delivery3;
    }

    /**
     * @return int
     */
    public function getDelivery4(): int
    {
        return $this->delivery4;
    }

    /**
     * @return int
     */
    public function getDelivery5(): int
    {
        return $this->delivery5;
    }

    /**
     * @return int
     */
    public function getDelivery6(): int
    {
        return $this->delivery6;
    }

    /**
     * @return int
     */
    public function getDelivery7(): int
    {
        return $this->delivery7;
    }

    /**
     * @return string
     */
    public function getSettlementType(): string
    {
        return $this->settlementType;
    }

    /**
     * @return string
     */
    public function getSettlementTypeDescription(): string
    {
        return $this->settlementTypeDescription;
    }

    /**
     * @return int
     */
    public function getCityId(): int
    {
        return $this->cityId;
    }

    /**
     * @return int
     */
    public function getIsBranch(): int
    {
        return $this->isBranch;
    }

    /**
     * @return mixed
     */
    public function getPreventEntryNewStreetsUser()
    {
        return $this->preventEntryNewStreetsUser;
    }

    /**
     * @return mixed
     */
    public function getConglomerates()
    {
        return $this->conglomerates;
    }

    /**
     * @return int
     */
    public function getSpecialCashCheck(): int
    {
        return $this->specialCashCheck;
    }

}

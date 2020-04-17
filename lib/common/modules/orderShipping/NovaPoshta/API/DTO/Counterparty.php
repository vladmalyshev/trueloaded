<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class Counterparty
{
    /** @var string */
    private $description;
    /** @var string */
    private $ref;
    /** @var string */
    private $city;
    /** @var null|string */
    private $counterparty;
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var string */
    private $middleName;
    /** @var string */
    private $counterpartyFullName;
    /** @var string */
    private $ownershipFormRef;
    /** @var string */
    private $ownershipFormDescription;
    /** @var string */
    private $EDRPOU;
    /** @var string */
    private $counterpartyType;
    /** @var string */
    private $cityDescription;

    private function __construct()
    {
    }
    public static function create(
        string $ref,
        string $description,
        string $city,
        string $firstName,
        string $lastName,
        string $middleName,
        string $counterpartyFullName,
        string $ownershipFormRef,
        string $ownershipFormDescription,
        string $EDRPOU,
        string $counterpartyType,
        string $cityDescription,
        ?string $counterparty = null
    ): self
    {
        $counterParty = new self();
        $counterParty->ref = $ref;
        $counterParty->description = $description;
        $counterParty->city = $city;
        $counterParty->firstName = $firstName;
        $counterParty->lastName = $lastName;
        $counterParty->middleName = $middleName;
        $counterParty->counterpartyFullName = $counterpartyFullName;
        $counterParty->ownershipFormRef = $ownershipFormRef;
        $counterParty->ownershipFormDescription = $ownershipFormDescription;
        $counterParty->EDRPOU = $EDRPOU;
        $counterParty->counterpartyType = $counterpartyType;
        $counterParty->cityDescription = $cityDescription;
        $counterParty->counterparty = $counterparty;
        return $counterParty;
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
    public function getRef(): string
    {
        return $this->ref;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getCounterparty(): ?string
    {
        return $this->counterparty;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * @return string
     */
    public function getCounterpartyFullName(): string
    {
        return $this->counterpartyFullName;
    }

    /**
     * @return string
     */
    public function getOwnershipFormRef(): string
    {
        return $this->ownershipFormRef;
    }

    /**
     * @return string
     */
    public function getOwnershipFormDescription(): string
    {
        return $this->ownershipFormDescription;
    }

    /**
     * @return string
     */
    public function getEDRPOU(): string
    {
        return $this->EDRPOU;
    }

    /**
     * @return string
     */
    public function getCounterpartyType(): string
    {
        return $this->counterpartyType;
    }

    /**
     * @return string
     */
    public function getCityDescription(): string
    {
        return $this->cityDescription;
    }

}

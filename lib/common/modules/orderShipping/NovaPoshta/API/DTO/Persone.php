<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class Persone
{
    /** @var string */
    private $description;
    /** @var string */
    private $ref;
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var string */
    private $middleName;
    /** @var string */
    private $counterparty;
    /** @var string */
    private $ownershipForm;
    /** @var string */
    private $ownershipFormDescription;
    /** @var string */
    private $EDRPOU;
    /** @var string */
    private $counterpartyType;
    /** @var ContactPerson */
    private $contactPerson;

    private function __construct()
    {
    }
    public static function create(
        string $ref,
        string $description,
        string $firstName,
        string $lastName,
        string $middleName,
        string $counterparty,
        string $ownershipForm,
        string $ownershipFormDescription,
        string $EDRPOU,
        string $counterpartyType,
        ContactPerson $contactPerson
    ): self
    {
        $counterParty = new self();
        $counterParty->ref = $ref;
        $counterParty->description = $description;
        $counterParty->firstName = $firstName;
        $counterParty->lastName = $lastName;
        $counterParty->middleName = $middleName;
        $counterParty->counterparty = $counterparty;
        $counterParty->ownershipForm = $ownershipForm;
        $counterParty->ownershipFormDescription = $ownershipFormDescription;
        $counterParty->EDRPOU = $EDRPOU;
        $counterParty->counterpartyType = $counterpartyType;
        $counterParty->contactPerson = $contactPerson;
        return $counterParty;
    }

    /**
     * @return ContactPerson
     */
    public function getContactPerson(): ContactPerson
    {
        return $this->contactPerson;
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
    public function getCounterparty(): string
    {
        return $this->counterparty;
    }

    /**
     * @return string
     */
    public function getOwnershipForm(): string
    {
        return $this->ownershipForm;
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

}

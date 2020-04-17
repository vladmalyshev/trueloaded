<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class PersonInfo
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
    private $email;
    /** @var string */
    private $phones;

    private function __construct()
    {
    }
    public static function create(
        string $ref,
        string $description,
        string $firstName,
        string $lastName,
        string $middleName,
        string $email,
        string $phones
    ): self
    {
        $counterParty = new self();
        $counterParty->ref = $ref;
        $counterParty->description = $description;
        $counterParty->firstName = $firstName;
        $counterParty->lastName = $lastName;
        $counterParty->middleName = $middleName;
        $counterParty->email = $email;
        $counterParty->phones = $phones;
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
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhones(): string
    {
        return $this->phones;
    }
}

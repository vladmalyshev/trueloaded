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


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class ContactPerson
{
    /** @var string */
    private $ref;
    /** @var string */
    private $description;
    /** @var string */
    private $lastName;
    /** @var string */
    private $firstName;
    /** @var string */
    private $middleName;

    private function __construct()
    {
    }

    public static function create(
        string $ref,
        string $description,
        string $firstName,
        string $lastName,
        string $middleName
    )
    {
        $person = new self();
        $person->ref = $ref;
        $person->description = $description;
        $person->firstName = $firstName;
        $person->lastName = $lastName;
        $person->middleName = $middleName;
        return $person;
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
    public function getLastName(): string
    {
        return $this->lastName;
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
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

}

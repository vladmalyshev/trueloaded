<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class StreetAddress
{
    /** @var string */
    private $ref;
    /** @var string */
    private $description;

    private function __construct()
    {
    }

    public static function create(string $ref, string $description): self
    {
        $street = new self();
        $street->ref = $ref;
        $street->description = $description;
        return $street;
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
}

<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class Street
{
    /** @var string */
    private $ref;
    /** @var string */
    private $description;
    /** @var string */
    private $streetsTypeRef;
    /** @var string */
    private $streetsType;

    private function __construct()
    {
    }

    public static function create(string $ref, string $description, string $streetsTypeRef, string $streetsType): self
    {
        $street = new self();
        $street->ref = $ref;
        $street->description = $description;
        $street->streetsTypeRef = $streetsTypeRef;
        $street->streetsType = $streetsType;
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

    /**
     * @return string
     */
    public function getStreetsTypeRef(): string
    {
        return $this->streetsTypeRef;
    }

    /**
     * @return string
     */
    public function getStreetsType(): string
    {
        return $this->streetsType;
    }

}

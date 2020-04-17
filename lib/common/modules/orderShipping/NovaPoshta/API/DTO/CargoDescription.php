<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class CargoDescription
{
    private $ref;
    private $description;
    private function __construct()
    {
    }

    public static function create(string $ref, string $description): self
    {
        $type = new self();
        $type->ref = $ref;
        $type->description = $description;
        return $type;
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

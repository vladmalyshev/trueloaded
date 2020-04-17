<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\DTO;


class Area
{
    /** @var string */
    private $ref;
    /** @var string */
    private $description;
    /** @var string */
    private $areasCenter;

    private function __construct()
    {
    }

    public static function createDumb(): self
    {
        $area = new self();
        $area->ref = '';
        $area->description = '';
        return $area;
    }

    public static function createSimply(string $ref, string $description = ''): self
    {
        $area = new self();
        $area->ref = $ref;
        $area->description = $description;
        return $area;
    }

    public static function create(string $ref, string $description, string $areasCenter): self
    {
        $area = new self();
        $area->ref = $ref;
        $area->description = $description;
        $area->areasCenter = $areasCenter;
        return $area;
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
    public function getAreasCenter(): string
    {
        return $this->areasCenter;
    }
}

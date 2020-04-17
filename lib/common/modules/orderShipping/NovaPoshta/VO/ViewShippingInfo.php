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


namespace common\modules\orderShipping\NovaPoshta\VO;


final class ViewShippingInfo
{
    /** @var string */
    private $area;
    /** @var string */
    private $city;
    /** @var string */
    private $warehouse;
    /** @var string */
    private $firstname;
    /** @var string */
    private $lastname;
    /** @var string */
    private $telephone;

    private function __construct()
    {
    }
    public static function create(
        string $area = '',
        string $city = '',
        string $warehouse = '',
        string $firstname = '',
        string $lastname = '',
        string $telephone = ''
    ): self
    {
        $shippingInfo = new self();
        $shippingInfo->area = trim($area);
        $shippingInfo->city = trim($city);
        $shippingInfo->warehouse = trim($warehouse);
        $shippingInfo->firstname = trim($firstname);
        $shippingInfo->lastname = trim($lastname);
        $shippingInfo->telephone = trim($telephone);
        return $shippingInfo;
    }

    /**
     * @return string
     */
    public function getArea(): string
    {
        return $this->area;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getWarehouse(): string
    {
        return $this->warehouse;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function isNotEmpty()
    {
        return
            $this->area !== '' ||
            $this->city !== '' ||
            $this->warehouse !== '' ||
            $this->firstname !== '' ||
            $this->lastname !== '' ||
            $this->telephone !== '';
    }
}

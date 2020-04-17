<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\services;

use common\models\Coupons;
use common\models\repositories\CouponRepository;


class CouponsService
{

    /** @var CouponsRepository */
    private $couponsRepository;

    public function __construct(CouponRepository $couponsRepository)
    {
        $this->couponsRepository = $couponsRepository;
    }

    public function setActive(Coupons $coupon)
    {
        if(!is_object($coupon)){
            throw new \RuntimeException('Coupon error data.');
        }
        if($coupon->coupon_active === Coupons::STATUS_ACTIVE){
            return true;
        }
        return $this->couponsRepository->edit($coupon,['coupon_active' => Coupons::STATUS_ACTIVE]);
    }

    public function setDisable(Coupons $coupon)
    {
        if(!is_object($coupon)){
            throw new \RuntimeException('Coupon error data.');
        }
        if($coupon->coupon_active === Coupons::STATUS_DISABLE){
            return true;
        }
        return $this->couponsRepository->edit($coupon,['coupon_active' => Coupons::STATUS_DISABLE]);
    }

    public function getById(int $id)
    {
        return $this->couponsRepository->getById($id);
    }

}
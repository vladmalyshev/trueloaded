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


use common\models\OrdersTotal;
use common\models\repositories\OrdersTotalRepository;

class OrdersTotalService
{
    /**
     * @var OrdersTotalRepository
     */
    private $ordersTotalRepository;

    public function __construct(OrdersTotalRepository $ordersTotalRepository)
    {
        $this->ordersTotalRepository = $ordersTotalRepository;
    }

    public function getByOrderId($orderId,$asArray = false)
    {
        return $this->ordersTotalRepository->getByOrderId($orderId,$asArray);
    }
    public function update( OrdersTotal $orderTotals, $params = [], $validate = false, $safeOnly = false )
    {
        return $this->ordersTotalRepository->edit($orderTotals,$params,$validate,$safeOnly);
    }
}
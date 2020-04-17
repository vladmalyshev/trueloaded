<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\repositories;


use common\models\OrdersTotal;

class OrdersTotalRepository {

    /**
     * @param $orderId
     * @param bool $asArray
     * @return array|OrdersTotal[]|\yii\db\ActiveRecord[]
     */
	public function getByOrderId($orderId,$asArray = false){
		$orderTotals = OrdersTotal::find()->where(['orders_id' => $orderId])->indexBy('class')->asArray($asArray)->all();
		if(empty($orderTotals)) {
            throw new NotFoundException("Order Totals not found");
        }
		return $orderTotals;
	}

    public function edit( OrdersTotal $orderTotals, $params = [], $validate = false, $safeOnly = false ) {
        foreach ( $params as $attribute => $param ) {
            if ( ! $orderTotals->hasAttribute( $attribute ) ) {
                unset( $params[ $attribute ] );
            }
        }
        $orderTotals->setAttributes( $params, $safeOnly );
        if ( ! $orderTotals->update( $validate, array_keys( $params ) ) ) {
            return $orderTotals->getErrors();
        }
        return true;
    }
}
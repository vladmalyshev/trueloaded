<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;


use common\models\TrackingCarriers;
use yii\helpers\ArrayHelper;

class OrderTrackingNumber
{

    public static function getCarriersVariants()
    {
        return ArrayHelper::map(
            TrackingCarriers::find()
                ->orderBy(['tracking_carriers_name'=>SORT_ASC])
                ->asArray()
                ->all(),
            'tracking_carriers_id', 'tracking_carriers_name'
        );
    }

    public static function getCarrierId($name)
    {
        if ($carrier = TrackingCarriers::findOne(['tracking_carriers_name'=>$name])){
            return $carrier->tracking_carriers_id;
        }
        return 0;
    }

    public static function getCarrierName($id)
    {
        if ($carrier = TrackingCarriers::findOne(['tracking_carriers_id'=>$id])){
            return $carrier->tracking_carriers_name;
        }
        return '';
    }

}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;


use common\api\models\Soap\SoapModel;

class OrderLegend extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Order\LegendDetails {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $details;

    /**
     * @var \common\api\models\Soap\Order\LegendMarketing {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $marketing;


    public static function makeForOrder($orderId)
    {
        $that = new static();
        if ($params_ua = \common\helpers\System::get_ga_detection($orderId)) {
            $that->details = new LegendDetails([
                'ip_address' => $params_ua->ip_address,
                'agent_name' => $params_ua->agent_name,
                'os_name' => $params_ua->os_name,
                'screen_resolution' => $params_ua->resolution,
                'javascript_support' => $params_ua->java,
            ]);

            $that->marketing = new LegendMarketing([
                'customer_origin' => $params_ua->origin,
                'campaign' => $params_ua->utmccn,
                'search' => $params_ua->utmcmd,
                'search_key' => $params_ua->utmctr,
            ]);
        }
        if ( is_null($that->details) && is_null($that->marketing) ) {
            return null;
        }

        return $that;
    }

}
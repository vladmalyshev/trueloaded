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

class OrderTrackingNumberAppend extends SoapModel
{
    /**
     * @var integer
     * @soap
     */
    public $order_id;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $carrier;

    /**
     * @var string
     * @soap
     */
    public $tracking_number;

    /**
     * Send email with tracking code to customer. default true
     * @var bool {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $customer_notify;

    /**
     * @var \common\api\models\Soap\Order\OrderedProductTrackingList Array of OrderedProductTracking {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products;

}
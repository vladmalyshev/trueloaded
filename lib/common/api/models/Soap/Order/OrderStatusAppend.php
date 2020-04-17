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

class OrderStatusAppend extends SoapModel
{
    /**
     * @var integer
     * @soap
     */
    public $order_id;


    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $orders_status_id;

    /**
     * @var string {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $orders_status_name;

    /**
     * @var string
     * @soap
     */
    public $comment;

    /**
     * @var datetime {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_added;

    /**
     * @var bool  {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $customer_notify;

}
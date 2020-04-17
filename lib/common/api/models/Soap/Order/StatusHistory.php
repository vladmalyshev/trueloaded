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

class StatusHistory extends SoapModel
{
    /**
     * @var integer
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
    public $comments;

    /**
     * @var datetime {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_added;

    /**
     * @var bool
     * @soap
     */
    public $customer_notified;

    public function __construct(array $config = [])
    {
        if ( isset($config['customer_notified']) ) {
            $config['customer_notified'] = !!$config['customer_notified'];
        }
        parent::__construct($config);

        if ( !empty($this->date_added) ) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }

    }
}
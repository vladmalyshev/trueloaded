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

class ArrayOfStatusHistory extends SoapModel
{
    /**
     * @var \common\api\models\Soap\Order\StatusHistory  StatusHistory {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $status_history = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $status_history_config) {
            $this->status_history[] = new StatusHistory($status_history_config);
        }
        parent::__construct($config);
    }

}
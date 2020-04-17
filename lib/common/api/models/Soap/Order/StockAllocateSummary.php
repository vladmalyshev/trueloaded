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

class StockAllocateSummary extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $quantity_canceled;

    /**
     * @var integer
     * @soap
     */
    public $quantity_received;

    /**
     * @var integer
     * @soap
     */
    public $quantity_dispatched;

    /**
     * @var integer
     * @soap
     */
    public $quantity_delivered;

    public function __construct(array $config = [])
    {
        $stock_config = [
            'quantity_canceled' => (int)$config['qty_cnld'],
            'quantity_received' => (int)$config['qty_rcvd'],
            'quantity_dispatched' => (int)$config['qty_dspd'],
            'quantity_delivered' => (int)$config['qty_dlvd'],
        ];
        parent::__construct($stock_config);
    }
}
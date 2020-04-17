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


use backend\models\EP\Tools;
use common\api\models\Soap\SoapModel;


class StockAllocate extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $warehouse_id;

    /**
     * @var string
     * @soap
     */
    public $warehouse_name;

    /**
     * @var integer
     * @soap
     */
    public $suppliers_id;

    /**
     * @var string
     * @soap
     */
    public $suppliers_name;

    /**
     * @var integer
     * @soap
     */
    public $location_id;

    /**
     * @var string
     * @soap
     */
    public $location_name;

    /**
     * @var integer
     * @soap
     */
    public $allocate_received;

    /**
     * @var integer
     * @soap
     */
    public $allocate_dispatched;

    /**
     * @var integer
     * @soap
     */
    public $allocate_delivered;

    /**
     * @var integer
     * @soap
     */
    public $is_temporary;

    public function __construct(array $config = [])
    {
        $config['warehouse_name'] = strval(Tools::getInstance()->getWarehouseName($config['warehouse_id']));
        $config['suppliers_name'] = strval(Tools::getInstance()->getSupplierName($config['suppliers_id']));
        $config['location_name'] = strval(Tools::getInstance()->getWarehouseLocationName($config['warehouse_id'], $config['location_id']));

        parent::__construct($config);
    }


}
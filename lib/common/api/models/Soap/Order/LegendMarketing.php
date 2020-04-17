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

class LegendMarketing extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $customer_origin;

    /**
     * @var string
     * @soap
     */
    public $campaign;

    /**
     * @var string
     * @soap
     */
    public $search;

    /**
     * @var string
     * @soap
     */
    public $search_key;

}
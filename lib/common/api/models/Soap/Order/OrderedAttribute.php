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

class OrderedAttribute extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $option_id;

    /**
     * @var integer
     * @soap
     */
    public $value_id;

    /**
     * @var string
     * @soap
     */
    public $option_name;

    /**
     * @var string
     * @soap
     */
    public $option_value_name;

}
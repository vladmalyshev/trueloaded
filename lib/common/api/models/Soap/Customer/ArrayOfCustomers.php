<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Customer;


use common\api\models\Soap\SoapModel;

class ArrayOfCustomers extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Customer\Customer Customer {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $customer = [];

}
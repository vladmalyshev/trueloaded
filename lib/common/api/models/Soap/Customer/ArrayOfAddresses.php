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

class ArrayOfAddresses extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Customer\Address Address {minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $address = [];

    /**
     * @var bool {nillable=0, minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $append;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

}
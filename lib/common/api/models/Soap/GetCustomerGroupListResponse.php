<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Store\ArrayOfCustomerGroups;
use common\api\models\Soap\Store\CustomerGroup;

class GetCustomerGroupListResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\ArrayOfCustomerGroups {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     * @soap
     */
    public $customer_groups;

    public function __construct(array $config = []) {
        $this->customer_groups = new ArrayOfCustomerGroups();

        parent::__construct($config);
    }

    public function build()
    {
        $groups = \common\models\Groups::find()
            ->all();
        foreach ($groups as $group){
            $this->customer_groups->customer_group[] = CustomerGroup::makeFromAR($group);
        }
        parent::build();
    }

}
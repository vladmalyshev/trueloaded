<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;


use common\api\models\Soap\SoapModel;
use yii\helpers\ArrayHelper;

class ArrayOfAssignedCustomerGroup extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\AssignedCustomerGroup AssignedCustomerGroup {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $assigned_customer_group = [];

    public function __construct(array $config = [])
    {
        if ( count($config)>0 && ArrayHelper::isIndexed($config) ) {
            $this->assigned_customer_group = [];
            foreach ($config as $_config) {
                $this->assigned_customer_group[] = new AssignedCustomerGroup($_config);
            }
            $config = [];
        }
        parent::__construct($config);
    }

}
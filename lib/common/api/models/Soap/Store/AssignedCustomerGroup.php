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


use backend\models\EP\Tools;
use common\api\models\Soap\SoapModel;

class AssignedCustomerGroup extends SoapModel
{
    /**
     * @var integer {minOccurs=0,maxOccurs=1}
     * @soap
     */
    public $groups_id;

    /**
     * @var string
     * @soap
     */
    public $groups_name;

    public function __construct(array $config = [])
    {
        if ( isset($config['groups_id']) && empty($config['groups_name']) ) {
            $config['groups_name'] = Tools::getInstance()->getCustomerGroupName($config['groups_id']);
        }
        parent::__construct($config);
    }

}
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

class AssignedPlatform extends SoapModel
{

    /**
     * @var integer {minOccurs=0,maxOccurs=1}
     * @soap
     */
    public $platform_id;

    /**
     * @var string
     * @soap
     */
    public $platform_name;

    public function __construct(array $config = [])
    {
        if ( isset($config['platform_id']) && empty($config['platform_name']) ) {
            $config['platform_name'] = Tools::getInstance()->getPlatformName($config['platform_id']);
        }
        parent::__construct($config);
    }

}
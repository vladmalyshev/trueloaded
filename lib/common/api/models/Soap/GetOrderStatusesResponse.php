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


use common\api\models\Soap\Store\ArrayOfOrderStatus;
use common\api\SoapServer\SoapHelper;

class GetOrderStatusesResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var integer
     * @soap
     */
    public $statusMapVersion = 1;
    /**
     * @var \common\api\models\Soap\Store\ArrayOfOrderStatus Array of OrderStatus {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $statuses;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $mapping_array = [];
        $create_request = [];

        $outStatusesMap = SoapHelper::getServerKeyValue('clientOrder/StatusesMapping');
        if ( !empty($outStatusesMap) ) {
            $outStatusesMap = json_decode($outStatusesMap,true);
        }
        if ( is_array($outStatusesMap) ) {
            $mapping_array = $outStatusesMap;
        }else{
            $clientStatuses = SoapHelper::getServerKeyValue('clientOrder/Statuses');
            if ( !empty($clientStatuses) ) {
                $extract_value = json_decode(base64_decode($clientStatuses), true);
                if (is_array($extract_value)) {
                    foreach( $extract_value as $statusInfo ) {
                        if ( !$statusInfo['external_status_id'] ) continue;
                        $mapping_array[$statusInfo['id']] = $statusInfo['external_status_id'];
                    }
                }
            }
        }

        $outStatusesNeedCreate = SoapHelper::getServerKeyValue('clientOrder/StatusesNeedCreate');
        if ( !empty($outStatusesNeedCreate) ) {
            $outStatusesNeedCreate = json_decode($outStatusesNeedCreate, true);
            if (is_array($outStatusesNeedCreate)) {
                $create_request = $outStatusesNeedCreate;
            }
        }

        $this->statuses = new ArrayOfOrderStatus([
            'mapping_array' => $mapping_array,
            'create_request' => $create_request,
        ]);

    }

    public function build()
    {
        $this->statuses->build();
        parent::build();
    }

}
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


use common\api\models\Soap\Store\ArrayOfQuotationStatus;
use common\api\SoapServer\SoapHelper;

class GetQuotationStatusesResponse extends SoapModel
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
     * @var \common\api\models\Soap\Store\ArrayOfQuotationStatus Array of OrderStatus {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $statuses;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $mapping_array = [];
        $create_request = [];

        $this->statuses = new ArrayOfQuotationStatus([
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
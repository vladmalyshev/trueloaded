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


use common\api\models\AR\Manufacturer;

class GetManufacturerResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\Manufacturer Manufacturer {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $manufacturer;

    protected $manufacturerId = 0;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setManufacturerId($manufacturerId)
    {
        $this->manufacturerId = $manufacturerId;
    }

    public function build()
    {
        if ( $this->status=='ERROR' ) return;
        $objManufacturer = Manufacturer::findOne(['manufacturers_id'=>$this->manufacturerId]);
        $data = $objManufacturer->exportArray([]);

        $this->manufacturer = new \common\api\models\Soap\Products\Manufacturer($data);
    }

}
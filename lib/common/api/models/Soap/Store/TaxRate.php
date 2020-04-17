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

class TaxRate extends SoapModel
{

    /**
     * @var int {nillable=1}
     * @soap
     */
    public $tax_priority;

    /**
     * @var float
     * @soap
     */
    public $tax_rate;

    /**
     * @var string
     * @soap
     */
    public $tax_description;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfGeoZones ArrayOfGeoZones {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $zones;
}
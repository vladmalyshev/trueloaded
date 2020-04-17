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

class GeoZoneLocation extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    var $country_iso2;
    //zone_country_id |   zone_id

    /**
     * @var string {nillable = 1, minOccurs=0, maxOccurs =1}
     * @soap
     */
    var $state;

}
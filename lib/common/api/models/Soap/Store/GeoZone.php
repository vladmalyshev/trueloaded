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

class GeoZone extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    var $geo_zone_name;

    /**
     * @var string
     * @soap
     */
    var $geo_zone_description;

    /**
     * @var \common\api\models\Soap\Store\ArrayOfGeoZoneLocations ArrayOfGeoZoneLocations {nillable=1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    var $geo_zone_locations;

    /**
     * @var datetime {nillable=1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $last_modified;

    /**
     * @var datetime {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $date_added;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if ($this->last_modified){
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }
        if ($this->date_added){
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }
    }


}
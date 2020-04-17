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
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class ArrayOfGeoZones extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\GeoZone GeoZone {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $geo_zone = [];

    public static function populateFrom(ActiveQuery $AR)
    {
        $instance = new self();

        $tools = new Tools();
        foreach($AR->all() as $activeRecord){
            $data = $activeRecord->getAttributes();
            if ( $activeRecord->hasMethod('getLocations') ) {
                $data['geo_zone_locations']['geo_zone_location'] = [];
                foreach ($activeRecord->getLocations()->all() as $location ){
                    $locationData = $location->getAttributes();
                    $countryInfo = $tools->getCountryInfo($location->zone_country_id);
                    $locationData['country_iso2'] = $countryInfo['countries_iso_code_2'];
                    if ($location->zone_id){
                        $locationData['state'] = \common\helpers\Zones::get_zone_code(
                            $location->zone_country_id,
                            $location->zone_id,
                            \common\helpers\Zones::get_zone_name(
                                $location->zone_country_id,
                                $location->zone_id,
                                null
                            )
                        );
                    }
                    $data['geo_zone_locations']['geo_zone_location'][] = new GeoZoneLocation($locationData);
                }
            }
            $instance->geo_zone[] = new GeoZone($data);
        }

        return $instance;
    }
}
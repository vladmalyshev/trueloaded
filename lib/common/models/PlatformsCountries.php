<?php

namespace common\models;

use yii\db\ActiveRecord;

class PlatformsCountries extends ActiveRecord
{
    public static function tableName()
    {
        return '{{platforms_countries}}';
    }
    
    public static function create($array, $platformId)
    {
        $platformsCountries = [];
        foreach ($array as $item => $country) {
            $platformsCountry = new static();
            $platformsCountry->platform_id = $platformId;
            $platformsCountry->countries_id = $country;
            $platformsCountries[] = $platformsCountry;
        }
        return $platformsCountries;
    }
}

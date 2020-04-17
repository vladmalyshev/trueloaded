<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\repositories;


use common\models\PlatformsApi;

class PlatformsApiRepository
{

    /**
     * @param $apiKey
     * @return bool|PlatformsApi
     */
    public static function findPlatformApiByKey($apiKey)
    {
        $apiKey = trim($apiKey);
        if ( !empty($apiKey) ) {
            return PlatformsApi::findOne(['api_key' => $apiKey]);
        }
        return false;
    }

    public static function findPlatformByApiKey($apiKey)
    {
        if ($platformApi = static::findPlatformApiByKey($apiKey)){
            return $platformApi->platform;
        }
        return false;
    }

}
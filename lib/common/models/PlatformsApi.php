<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

use yii\db\ActiveRecord;

class PlatformsApi extends ActiveRecord
{
    /**
     * @property int $platform_id
     */
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'platforms_api';
    }

    /**
     * one-to-one
     * @return object
     */
    public function getPlatform()
    {
        return $this->hasOne(Platforms::className(), ['platform_id' => 'platform_id']);
    }

    public function isWhitelistIp($clientIp)
    {
        $allowed_ips = [];
        if ($this->ip_allowed){
            $allowed_ips = preg_split('/[\s;,]/',(string)$this->ip_allowed,-1,PREG_SPLIT_NO_EMPTY);
            $allowed_ips = array_map('trim',$allowed_ips);
        }

        if ( count($allowed_ips)>0 ){
            foreach ($allowed_ips as $allowed_ip){
                if ( strpos($allowed_ip,'/')!==false ){
                    if (\yii\helpers\IpHelper::inRange($clientIp,$allowed_ip)){
                        return true;
                    }
                }else{
                    if ($clientIp == $allowed_ip) {
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }

}
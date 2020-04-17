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

use common\models\queries\PlatformsQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "platforms".
 *
 * @property int $platform_id
 * @property string $platform_owner
 * @property string $platform_name
 * @property string $platform_url
 * @property string $platform_url_secure
 * @property int $ssl_enabled
 * @property int $is_default
 * @property string $platform_email_address
 * @property string $platform_email_from
 * @property string $platform_email_extra
 * @property string $platform_telephone
 * @property string $platform_landline
 * @property string $date_added
 * @property string $last_modified
 * @property int $is_virtual
 * @property int $is_marketplace
 * @property int $is_default_address
 * @property int $is_default_contact
 * @property int $sort_order
 * @property int $status
 * @property string $defined_languages
 * @property string $defined_currencies
 * @property string $default_language
 * @property string $default_currency
 * @property string $platform_code
 * @property int $need_login
 * @property int $use_social_login
 */
class Platforms extends ActiveRecord {

    /**
     * set table name
     * @return string
     */
    public static function tableName() {
        return 'platforms';
    }

    /**
     * one-to-many
     * @return array
     */
    public function getPlatformsAddressBook() {
        return $this->hasMany(PlatformsAddressBook::className(), ['platform_id' => 'platform_id']);
    }

    /**
     * one-to-one
     * @return object
     */
    public function getDefaultPlatformsAddressBook() {
        return $this->getPlatformsAddressBook()
                        ->where(['is_default' => 1])
                        ->limit(1)
                        ->one();
    }

    /**
     * one-to-many
     * @return array
     */
    public function getPlatformsOpenHours() {
        return $this->hasMany(PlatformsOpenHours::className(), ['platform_id' => 'platform_id']);
    }

    /**
     * one-to-many
     * @return array
     */
    public function getPlatformsCutOffTimes() {
        return $this->hasMany(PlatformsCutOffTimes::className(), ['platform_id' => 'platform_id']);
    }

    /**
     * @return PlatformsQuery|\yii\db\ActiveQuery
     */
    public static function find() {
        return new PlatformsQuery(get_called_class());
    }
    
    /*
     * @return ActiveQuery platforms type object 
     */
    public static function getPlatformsByType($type = ''){
        $platformsQuery = static::find();
        switch($type){
            case "virtual":
                $platformsQuery->where(['is_virtual' => 1]);
                break;
            case "marketplace":
                $platformsQuery->where(['is_marketplace' => 1]);
                break;
            case "non-virtual":
                $platformsQuery->where(['is_virtual' => 0]);
                break;
            case "physical":
            default:
                $platformsQuery->where(['is_marketplace' => 0, 'is_virtual' => 0]);
                break;
        }
        return $platformsQuery;
    }
    
}

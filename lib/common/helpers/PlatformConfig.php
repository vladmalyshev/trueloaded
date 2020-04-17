<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class PlatformConfig {

    public static function getValue($key, $platformId = -1) {
        if (defined($key)) {
            return constant($key);
        } else {
            if ($platformId < 1) {
                if ((int) \common\classes\platform::activeId() > 0) {
                    $platformId = (int) \common\classes\platform::activeId();
                } else {
                    $platformId = (int) \common\classes\platform::defaultId();
                }
            }
            $__platform = \Yii::$app->get('platform');
            $platformConfig = $__platform->config($platformId);
            return $platformConfig->const_value($key);
        }
    }

    public static function getFieldValue($field, $platformId = -1) {
        if ($platformId < 1) {
            if ((int) \common\classes\platform::activeId() > 0) {
                $platformId = (int) \common\classes\platform::activeId();
            } else {
                $platformId = (int) \common\classes\platform::defaultId();
            }
        }
        $__platform = \Yii::$app->get('platform');
        $platformConfig = $__platform->config($platformId);
        return $platformConfig->getPlatformDataField($field);
    }

}

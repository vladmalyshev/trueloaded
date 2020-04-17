<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\widgets;

use Yii;
use common\components\GoogleTools;
use frontend\design\Info;

class GoogleVerification {

    public static function verify() {

        $module = GoogleTools::instance()->getModulesProvider()->getActiveByCode('verification', \common\classes\platform::currentId());
        if ($module) {
            Yii::$app->controller->getView()->registerMetaTag([
                'name' => 'google-site-verification',
                'content' => $module->config[$module->code]['fields'][0]['value'],
                    ], 'google-site-verification');
        }
    }

}

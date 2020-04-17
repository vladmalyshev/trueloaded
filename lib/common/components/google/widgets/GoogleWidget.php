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

use common\components\GoogleTools;
use frontend\design\Info;
use common\classes\platform;

class GoogleWidget extends \yii\base\Widget {

    public function init() {
        parent::init();
    }

    public function run() {

        if (Info::isAdmin())
            return;
        $provider = GoogleTools::instance()->getModulesProvider();
        $to_work = [];
        $priority = [];
        foreach ($provider->getInstalledModules(platform::currentId()) as $module) {
            $_pages = $module->getAvailablePages();
            if (in_array('checkout', $_pages) && strtolower(str_replace("-", "", \Yii::$app->controller->id)) == 'checkout') {
                if (\Yii::$app->controller->action->id == 'success') {
                    $priority[$module->code] = $module->getPriority();
                    $to_work[$module->code] = $module;
                }
            } else if (in_array(strtolower(str_replace("-", "", \Yii::$app->controller->id)), $_pages) || in_array('all', $_pages)) {
                $priority[$module->code] = $module->getPriority();
                $to_work[$module->code] = $module;
            }
        }

        if (is_array($priority)) {
            asort($priority);
            foreach ($priority as $module => $value) {
                echo $to_work[$module]->renderWidget();
            }
        }
    }

}

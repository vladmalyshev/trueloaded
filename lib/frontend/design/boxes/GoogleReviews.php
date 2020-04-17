<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use common\components\GoogleTools;

class GoogleReviews extends Widget {

    public $file;
    public $params;
    public $settings;
    private $module;

    public function init() {
        parent::init();
    }

    public function run() {
        $provider = GoogleTools::instance()->getModulesProvider();
        $reviews = $provider->getActiveByCode('reviews', \common\classes\platform::currentId());
        if ($reviews && $reviews->params['status']) {
            $postition = $this->settings[0]['position'] ?? "BOTTOM_RIGHT";
            return $reviews->getBadgeCode(false, $postition);
        }
    }

}

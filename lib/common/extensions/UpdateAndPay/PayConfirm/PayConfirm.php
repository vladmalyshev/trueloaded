<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\UpdateAndPay\PayConfirm;

use Yii;
use common\extensions\Testimonials\Testimonials;
use common\classes\Images;
use frontend\design\Info;

class PayConfirm extends \yii\base\Widget {

    public $name;
    public $params;
    public $settings;
    public $id;

    public function init() {
        parent::init();
        Testimonials::initConfiguration();
        \common\helpers\Translation::init('samples');
    }

    public static function showSettings($settings) {
        return self::begin()->render('settings.tpl', ['settings' => $settings]);
    }

    public function run() {

        return self::begin()->render('view', $this->params);
    }

}

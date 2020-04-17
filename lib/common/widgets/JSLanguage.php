<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\widgets;

class JSLanguage extends \yii\bootstrap\Widget {

    public $list;

    public function init() {
        parent::init();
    }

    public function run() {

        echo ' var $tranlations = {}; ' . "\n";
        if (is_array($this->list) && count($this->list) > 0) {
            foreach ($this->list as $key => $value) {
                echo ' $tranlations.' . $key . ' = "' . $value . '";' . "\n";
            }
        }
        echo '$tranlations.baseurl = "' . (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . (defined('DIR_WS_ADMIN') ? DIR_WS_ADMIN : DIR_WS_HTTP_CATALOG) . '";' . "\n";
    }

}

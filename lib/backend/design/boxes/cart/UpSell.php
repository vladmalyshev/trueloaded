<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\cart;

use Yii;
use yii\base\Widget;

class UpSell extends Widget {

    public $id;
    public $params;
    public $settings;
    public $visibility;

    public function init() {
        parent::init();
    }

    public function run() {
        $platformList = \common\classes\platform::getList();
        return $this->render('../../views/up-sell.tpl', [
                    'id' => $this->id, 'params' => $this->params, 'settings' => $this->settings,
                    'visibility' => $this->visibility,
        ]);
    }

}

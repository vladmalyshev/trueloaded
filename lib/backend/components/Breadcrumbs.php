<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\components;

use yii\base\Widget;

class Breadcrumbs extends Widget {

    public $navigation = array();
		public $topButtons = array();
    
    public function run() {
        if (isset(\Yii::$app->controller->navigation)) {
            $this->navigation = \Yii::$app->controller->navigation;
        }
        if (isset(\Yii::$app->controller->topButtons)) {
						$this->topButtons = \Yii::$app->controller->topButtons;
        }
        return $this->render('Breadcrumbs', [
          'context' => $this,
        ]);
    }

}


<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use Yii;
use yii\web\ViewAction;

/**
 * Site custom action 
 */
class CustomPageAction extends ViewAction
{

    public $action;
    public $params = [];
    
    public function run()
    {   
        $modify = $this->action;
        $modify = strtolower($modify);
        $modify = str_replace(' ', '_', $modify);
        $modify = preg_replace('/[^a-z0-9_-]/', '', $modify);
        $this->action = $modify;
        return $this->render('custom', ['params' => $this->params]);
    }
}

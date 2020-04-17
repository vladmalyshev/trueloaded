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

/**
 * Site controller
 */
class ContactController extends Sceleton
{

    public function actionIndex()
    {
      
      return $this->render('index.tpl', ['form' => '']);
    }



}

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


class MaintenanceController extends Sceleton
{
    public function actionIndex()
    {
        $this->layout = 'error.tpl';

        \Yii::$app->getResponse()->setStatusCode(503);

        return $this->render('index');
    }
}
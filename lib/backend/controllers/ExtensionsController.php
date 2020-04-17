<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;

class ExtensionsController extends Sceleton
{
    function __construct($id, $mod=null) {
        $module = Yii::$app->request->get('module');
        if ($ext = \common\helpers\Acl::checkExtension($module, 'acl')) {
            $this->acl = $ext::acl();
        }
        return parent::__construct($id, $mod);
    }
    
    public function actionIndex()
    {
        $module = Yii::$app->request->get('module');
        $action = Yii::$app->request->get('action', 'adminActionIndex');
        if ($ext = \common\helpers\Acl::checkExtension($module, $action)) {
            return $ext::$action();
        }
    }

}
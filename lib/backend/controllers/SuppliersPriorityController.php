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

use yii;

class SuppliersPriorityController extends Sceleton
{

    public $acl = ['BOX_HEADING_CATALOG', 'BOX_SUPPLIER_PRIORITY'];

    public function actionIndex()
    {
        $this->selectedMenu = array('catalog', 'suppliers-priority');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('suppliers-priority/index'), 'title' => BOX_SUPPLIER_PRIORITY);
        $this->view->headingTitle = BOX_SUPPLIER_PRIORITY;

        if ($ext = \common\helpers\Acl::checkExtension('SupplierPriority', 'renderSupplierPriorities')) {
            return $ext::renderSupplierPriorities();
        }

        return $this->render('index');
    }

}
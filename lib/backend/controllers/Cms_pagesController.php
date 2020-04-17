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
use backend\components\Information;

class Cms_pagesController extends Sceleton {

    public function actionIndex() {
        $this->selectedMenu = array('cms', 'cms_pages');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('cms_pages/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        return $this->render('index');
    }

}

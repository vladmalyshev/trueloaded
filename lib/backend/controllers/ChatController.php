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

/**
 * default controller to handle user requests.
 */
class ChatController extends Sceleton {

    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_CATALOG_CHAT'];

    public function actionIndex() {
        $this->selectedMenu = array('marketing', 'chat');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('chat/'), 'title' => BOX_CATALOG_CHAT);
        $this->view->headingTitle = BOX_CATALOG_CHAT;
        
        if ($ext = \common\helpers\Acl::checkExtension('TawkToChat', 'actionIndex')) {
            return $ext::actionIndex();
        }
        return $this->render('index');
    }

}

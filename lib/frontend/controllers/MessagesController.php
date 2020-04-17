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
 * Messages extension controller
 */
class MessagesController extends Sceleton {

public function actionIndex() {
        if (Yii::$app->user->isGuest) {
            global $navigation;
            $navigation->set_snapshot();
            tep_redirect(tep_href_link('account/login', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionIndex')) {
            return $ext::actionIndex();
        }
    }

    public function actionList() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionList')) {
            return $ext::actionList();
        }
    }

    public function actionView() {
        if (Yii::$app->user->isGuest) {
            global $navigation;
            $navigation->set_snapshot();
            tep_redirect(tep_href_link('account/login', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionView')) {
            return $ext::actionView();
        }
    }

    public function actionNew() {
        if (Yii::$app->user->isGuest) {
            global $navigation;
            $navigation->set_snapshot();
            tep_redirect(tep_href_link('account/login', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionNew')) {
            return $ext::actionNew();
        }
    }

    public function actionAttachment() {
        if (Yii::$app->user->isGuest) {
            global $navigation;
            $navigation->set_snapshot();
            tep_redirect(tep_href_link('account/login', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionAttachment')) {
            return $ext::actionAttachment();
        }
    }

    public function actionSave() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionSave')) {
            return $ext::actionSave();
        }
    }

    public function actionBulkUnread() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionBulkUnread')) {
            return $ext::actionBulkUnread();
        }
    }

    public function actionBulkStarred() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionBulkStarred')) {
            return $ext::actionBulkStarred();
        }
    }

    public function actionBulkDelete() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionBulkDelete')) {
            return $ext::actionBulkDelete();
        }
    }

    public function actionUpdateUnread() {
        if (Yii::$app->user->isGuest) {
            tep_redirect(tep_href_link('messages', '', 'SSL'));
        }
        if ($ext = \common\helpers\Acl::checkExtension('Messages', 'actionUpdateUnread')) {
            return $ext::actionUpdateUnread();
        }
    }

    public function actionCommunication()
    {
        if ($ext = \common\helpers\Acl::checkExtension('Communication', 'actionIndex')) {
            return $ext::actionIndex();
        }
    }
}

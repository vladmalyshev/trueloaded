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
use yii\web\Controller;

/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Sceleton extends Controller {

    public $enableCsrfValidation = false;
    
    /**
     * @var array the breadcrumbs of the current page.
     */
    public $navigation = array();

    /**
     * @var array 
     */
    public $topButtons = array();

    /**
     * @var stdClass the variables for smarty.
     */
    public $view = null;
    
    /**
     * Access Control List
     * @var array current access level
     */
    public $acl = null;

    /**
     * Selected items in menu
     * @var array 
     */
    public $selectedMenu = array();
    
    function __construct($id,$module=null) {
        if (isset($this->acl) && $this->acl[0] == 'BOX_HEADING_DEPARTMENTS') {
            //skip superadmin menu
        } elseif (!is_null($this->acl)) {
            $lastElement = end($this->acl);
            $this->acl = \common\helpers\AdminBox::buildNavigation($lastElement);
            \common\helpers\Acl::checkAccess($this->acl);
        }
        \backend\design\Data::mainData();
        $this->layout = 'main.tpl';
        \Yii::$app->view->title = \Yii::$app->name;
        $this->view = new \stdClass();
        return parent::__construct($id,$module);
    }

    public function bindActionParams($action, $params)
    {
        if ($action->id == 'index') {
            \common\helpers\Translation::init('admin/' . $action->controller->id);
        } else {
            \common\helpers\Translation::init('admin/' . $action->controller->id . '/' . $action->id);
        }
        \common\helpers\Translation::init('admin/main');
        \common\helpers\Translation::init('main');
        return parent::bindActionParams($action, $params);
    }
    
    public function beforeAction($action) {
        $events = new \backend\components\AdminEvents();
        $events->registerNotificationEvent();
        return parent::beforeAction($action);
    }
    
    public function actions() {
        $actions = parent::actions();
        if ($es =\common\helpers\Acl::checkExtensionAllowed('EventSystem', 'allowed')){
            $actions = array_merge($es::getActions($this->id));
        }
        $actions = array_merge($actions, \common\helpers\Acl::getExtensionActions($this->id));
        return $actions;
    }
}
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
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use frontend\design\Info;
use yii\web\Session;
use common\classes\opc;
use common\components\Customer;

/**
 * Site controller
 */
class IndexController extends Sceleton
{
    /**
     * @inheritdoc
     */
    /*public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }*/

    /**
     * @inheritdoc
     */
    /*public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }*/

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if ($ext = \common\helpers\Acl::checkExtension('SeoRedirects', 'checkRedirect')) {
            $ext::checkRedirect('index.php');
        }
        if ($ext = \common\helpers\Acl::checkExtension('SeoRedirectsNamed', 'checkRedirect')) {
            $ext::checkRedirect('index.php');
        }

        \common\helpers\Translation::init('account/login');
        \common\helpers\Translation::init('account/password-forgotten');
        global $navigation;
        
        if (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED) {
            return $this->render('superadmin.tpl');
        }
        
        if ($ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkNeedLogin')) {
            if ($ext::checkNeedLogin()){
                $params = [
                    'action' => tep_href_link('index/index', 'action=process', 'SSL'),
                    'show_socials' => false,
                ];

                $authContainer = new \frontend\forms\registration\AuthContainer();
                $params['enterModels'] = $authContainer->getForms('index/auth');
                $params['showAddress'] = $authContainer->isShowAddress();
                                
                if (Yii::$app->request->isPost){
                    $scenario = Yii::$app->request->post('scenario');
            
                    $authContainer->loadScenario($scenario);
                    if (!$authContainer->hasErrors()){
                        if (sizeof($navigation->snapshot) > 0) {
                            $origin_href = tep_href_link($navigation->snapshot['page'], \common\helpers\Output::array_to_string($navigation->snapshot['get'], array(tep_session_name())), $navigation->snapshot['mode']);
                            $navigation->clear_snapshot();
                            tep_redirect($origin_href);
                        } else {
                            tep_redirect(tep_href_link('/'));
                        }
                    } else {
                        $messageStack = \Yii::$container->get('message_stack');
                        if ($authContainer->hasErrors($scenario)){
                            foreach ($authContainer->getErrors($scenario) as $error){
                                $messageStack->add((is_array($error)? implode("<br>", $error): $error), $scenario);
                            }
                        }
                        $messages = '';
                        if ($messageStack->size($scenario) > 0){
                            $messages = $messageStack->output($scenario);
                        }
                        $params['messages_'.$scenario] = $messages;
                        $params['active'] = $scenario;                        
                    }
                }

                if (Yii::$app->request->isAjax && !Info::isAdmin()) {
                    $this->layout = 'ajax.tpl';
                }
                if ($_GET['page_name']) {
                    $page_name = $_GET['page_name'];
                } else {
                    $page_name = Info::chooseTemplate('home', 'main', 'not_logged');
                }
                if ($page_name == 'main') {
                    $hasPage = \common\models\ThemesSettings::find()->where([
                        'theme_name' => THEME_NAME,
                        'setting_group' => 'added_page',
                        'setting_name' => 'home',
                        'setting_value' => 'Need Login',
                    ])->count();
                    if ($hasPage) {
                        $page_name = 'Need Login';
                    }
                }

                if ($page_name != 'main') {
                    $tpl = 'index.tpl';
                } else {
                    $tpl = 'index_auth.tpl';
                }
                $page_name = \common\classes\design::pageName($page_name);

                if (!is_array($params)) {
                    $params = [];
                }

                $this->view->page_name = $page_name;
                return $this->render($tpl, ['page_name' => $page_name, 'params' => $params]);
            }
        }


        if (Yii::$app->request->isAjax && !Info::isAdmin()) {
            $this->layout = 'ajax.tpl';
        }

        $params = [];
        if ($_GET['page_name']) {
            if (Info::isAdmin() && $ext = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkNeedLogin')) {
                $authContainer = new \frontend\forms\registration\AuthContainer();
                $params['enterModels'] = $authContainer->getForms('index/auth');
                $params['showAddress'] = $authContainer->isShowAddress();
            }
            $page_name = $_GET['page_name'];
        } else {
            $page_name = Info::chooseTemplate('home', 'main');
        }
        $page_name = \common\classes\design::pageName($page_name);
        $this->view->page_name = $page_name;

        return $this->render('index.tpl', ['page_name' => $page_name, 'params' => $params]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    /* public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    } */

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            $statusCode = $exception->statusCode;
            $name = $exception->getName();
            $message = $exception->getMessage();
            if ($statusCode == 404) {
                header('HTTP/1.0 404 Not Found');
                \app\components\MetaCannonical::setStatus(404);
                $check = tep_db_fetch_array(tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where block_name = '404' and theme_name = '" . THEME_NAME . "'"));
                return $this->render('404', [
                    'hasTemplate' => $check['id'] || Info::isAdmin() ? true : false
                ]);
            }
        }
    }

    public function actionRobotsTxt(){
      $this->layout = false;
      if (is_file(DIR_FS_CATALOG.'/.robots.txt')) {

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->set('Content-Type','text/plain');

        $robots = file_get_contents(DIR_FS_CATALOG.'/.robots.txt');
        if ( strpos($robots,'#SITE_PREFIX#')!==false ) {
            $urlParams = [];
            $urlManager = Yii::$app->getUrlManager();
            if (method_exists($urlManager, 'getSettings')) {
                $urlSettings = $urlManager->getSettings();
                if ($urlSettings['search_engine_friendly_urls'] && $urlSettings['search_engine_unhide']) {
                    if ($urlSettings['seo_url_parts_language']) {
                        $objLanguage = new \common\classes\language();
                        if ( is_array($objLanguage->paltform_languages) && count($objLanguage->paltform_languages)>0 ) {
                            $paltform_languages = array_unique(array_merge([ $objLanguage->dp_language ],$objLanguage->paltform_languages));

                            $_urlParams = $urlParams;
                            $urlParams = [];
                            foreach ($paltform_languages as $feedLanguageCode ) {
                                $copyParams = [];
                                if ( count($_urlParams)==0 ) $_urlParams = [[]];
                                foreach ( $_urlParams as $existingParams ) {
                                    $existingParams['language'] = $feedLanguageCode;
                                    $copyParams[] = $existingParams;
                                }
                                $urlParams = array_merge($urlParams, $copyParams);
                            }
                        }
                    }
                    if ($urlSettings['seo_url_parts_currency']) {
                        $currencies = \Yii::$container->get('currencies');
                        $_urlParams = $urlParams;
                        $urlParams = [];
                        $platform_currencies = array_unique(array_merge([ $currencies->dp_currency ],$currencies->platform_currencies));
                        foreach($platform_currencies as $currencyCode){
                            $copyParams = [];
                            if ( count($_urlParams)==0 ) $_urlParams = [[]];
                            foreach ( $_urlParams as $existingParams ) {
                                $existingParams['currency'] = $currencyCode;
                                $copyParams[] = $existingParams;
                            }
                            $urlParams = array_merge($urlParams, $copyParams);
                        }
                    }
                }
            }

            if ( count($urlParams)==0 ) $urlParams[] = [];
            $sitePrefix = [];
            foreach ( $urlParams as $urlParam ) {
                $urlParam[0] = '/';
                $_url = Yii::$app->urlManager->createAbsoluteUrl($urlParam);
                $urlPath = parse_url($_url,PHP_URL_PATH);
                if ( empty($urlPath) ) $urlPath = '/';
                if ( substr($urlPath,-1)!='/' ) $urlPath.='/';
                $sitePrefix[$urlPath] = $urlPath;
            }

            $robots_array = [];
            foreach (explode("\n",$robots) as $robotsLine){
                if ( strpos($robotsLine,'#SITE_PREFIX#')===false ) {
                    $robots_array[] = $robotsLine;
                    continue;
                }
                foreach ( $sitePrefix as $sitePrefixItem ) {
                    $robots_array[] = preg_replace('/#SITE_PREFIX#\/?/', $sitePrefixItem, $robotsLine);
                }
            }
            $robots = implode("\n",$robots_array);

            $robots = preg_replace('/#SITE_PREFIX#\/?/', '/', $robots);
        }

        if ( (defined('PLATFORM_NEED_LOGIN') && PLATFORM_NEED_LOGIN) || (defined('SUPERADMIN_ENABLED') && SUPERADMIN_ENABLED) ) {
            $robots = str_replace('#SITEMAP#',"", $robots);
        }else {
            $robots = str_replace('#SITEMAP#', "Sitemap: " . Yii::$app->urlManager->createAbsoluteUrl('sitemap.xml', true), $robots);
        }
        return $robots;
      }else{
        throw new \yii\web\NotFoundHttpException();
      }
    }
    
    public function actionLoadLanguagesJs(){
	  //header('X-Content-Type-Options: nosniff');
      $list = \common\helpers\Translation::loadJS('js');
      
      return \common\widgets\JSLanguage::widget(['list' => $list]);
    }


    public function actionDesign()
    {
        $page = Yii::$app->request->get('page');
        $get = Yii::$app->request->get();
        $this->view->page_layout = 'default';
        \common\helpers\Translation::init('admin/design');
        \common\helpers\Translation::init('admin/main');
        switch ($page) {
            case 'empty':
                \common\helpers\Translation::init('shopping-cart');
                break;
            case 'gift-certificate':
                \common\helpers\Translation::init('shopping-cart');
                break;
            case 'shopping-cart':
                \common\helpers\Translation::init('shopping-cart');
                \common\helpers\Translation::init('admin/categories');
                \common\helpers\Translation::init('admin/texts');
                break;
        }
        $widgetsList = \backend\design\Style::getCssWidgetsList($get['theme_name']);

        foreach ($widgetsList as $widget) {
            $widget = str_replace('.w-', '', $widget);
            $widget = str_replace('.b-', '', $widget);
            \frontend\design\Info::addBoxToCss($widget);
        }
        return $this->render('design/' . $page . '.tpl');
    }

    public function actionWrapper()
    {
        $this->layout = 'wrapper.tpl';
        return $this->render('index');
    }

    public function actionSplit()
    {
    }

    public function actionSetFrontendTranslationTime()
    {
        $cookiesGet = Yii::$app->request->cookies;
        if ($cookiesGet->has('frontend_translation')) {
            $cookies = Yii::$app->response->cookies;
            $cookies->add(new \yii\web\Cookie([
                'name' => 'frontend_translation',
                'value' => $cookiesGet->get('frontend_translation'),
                'expire' => time() + 300,
            ]));
        }
    }
    
}

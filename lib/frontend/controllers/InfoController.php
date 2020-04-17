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
class InfoController extends Sceleton
{

    public function actionIndex()
    {
        global $breadcrumb;
        $languages_id = \Yii::$app->settings->get('languages_id');

        if(!$_GET['info_id'])
            die("No page found.");
        $info_id = (int)$_GET['info_id'];

        $sql = tep_db_query("select i1.noindex_option, i1.nofollow_option, i1.rel_canonical, if(length(i1.info_title), i1.info_title, i.info_title) as info_title, if(length(i1.description), i1.description, i.description) as description, i.information_id, i.template_name from " . TABLE_INFORMATION . " i LEFT JOIN " . TABLE_INFORMATION . " i1 on i.information_id = i1.information_id  and i1.languages_id = '" . (int)$languages_id . "' ".(\common\classes\platform::activeId()?" AND i1.platform_id='".\common\classes\platform::currentId()."' ":'')." where i.information_id = '" . (int)$info_id . "' and i.languages_id = '" . (int)$languages_id . "' and i.visible = 1 ".(\common\classes\platform::activeId()?" AND i.platform_id='".\common\classes\platform::currentId()."' ":''));
        $row=tep_db_fetch_array($sql);

        if ($row['page_title'] == ''){
            $title = $row['info_title'];
        }else{
            $title = $row['page_title'];
        }
        if ( $title ) {
            $breadcrumb->add($title, tep_href_link(FILENAME_INFORMATION, 'info_id=' . $row['information_id']));
        }
        $params = tep_db_prepare_input(Yii::$app->request->get());
        if ($params['page_name']){
            $page_name = $params['page_name'];
        } elseif ($row['template_name']) {
            $page_name = $row['template_name'];
        } else {
            $page_name = 'info';
        }

        if ($page_name == '0_blank') {
            \frontend\design\Info::addBoxToCss('hidden-boxes');
        }

        \common\helpers\Seo::showNoindexMetaTag($row['noindex_option'], $row['nofollow_option']);
        if (!empty($row['rel_canonical'])) {
            \app\components\MetaCannonical::instance()->setCannonical($row['rel_canonical']);
        }

        $page_name = \common\classes\design::pageName($page_name);

        \frontend\design\Info::addBlockToPageName($page_name);

        return $this->render('index.tpl', [
            'description' => $row['description'],
            'title' => $title,
            'page' => 'info',
            'page_name' => $page_name
        ]);
    }

    public function actions()
    {   global $HTT_GET_VARS;
        $action = $_GET['action'];
        $params = array_diff($_GET, [$action]);
        unset($_GET['action']);
        unset($HTT_GET_VARS['action']);
        return [
            'custom' => [
                'class' => '\frontend\controllers\CustomPageAction',
                'action' => $action,
                'params' => $params,
            ],
        ];
    }
    
    public function getHerfLang($platforms_languages){
        $pages = tep_db_query("select seo_page_name, languages_id from " . TABLE_INFORMATION . " where platform_id = '" . (int)PLATFORM_ID . "' and visible = 1 and information_id = '" . (int)$_GET['info_id'] . "' and languages_id in (" . implode(",", array_values($platforms_languages)) . ")");
        $list = $except = [];
        if (tep_db_num_rows($pages)){
            while($page = tep_db_fetch_array($pages)){
                if (!empty($page['seo_page_name'])){
                    $except[] = $_GET['info_id'];
                }
                $list[$page['languages_id']] = [$page['seo_page_name'], $except];
            }
        }
        return $list;
    }

    public function actionComponents()
    {
        $page_name = Yii::$app->request->get('page_name');

        return $this->render('components.tpl', [
            'page_name' => $page_name
        ]);
    }
}

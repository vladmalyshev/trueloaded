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

use common\models\DesignBoxes;
use frontend\design\Info;
use Yii;
use yii\helpers\FileHelper;
use backend\design\Uploads;
use backend\design\Steps;
use backend\design\Style;
use common\classes\design;
use backend\design\Theme;
use backend\design\Backups;
use backend\design\FrontendStructure;
use common\models\ThemesStyles;
use common\models\ThemesSettings;
/**
 *
 */
class DesignController extends Sceleton {

    public $acl = ['BOX_HEADING_DESIGN_CONTROLS', 'BOX_HEADING_THEMES'];

  /**
   *
   */
  public function actionIndex()
  {
    return '';
  }


  public function actionTheme()
  {
    $params = Yii::$app->request->get();

    $theme = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($params['theme_name']) . "'"));

    $this->selectedMenu = array('design_controls', 'design/themes');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/themes'), 'title' => $theme['theme_name']);
    $this->view->headingTitle = $theme['theme_name'];

    return $this->render('theme.tpl', [
      'theme' => $theme,
    ]);
  }


  public function actionThemeRestore()
  {
    $params = Yii::$app->request->get();
    //echo file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/design.sql');
    //die;

    $boxes_sql = tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");
    while ($item = tep_db_fetch_array($boxes_sql)){
      tep_db_query("delete from " . TABLE_DESIGN_BOXES . " where id = '" . (int)$item['id'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . (int)$item['id'] . "'");
    }

    $boxes_sql1 = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");
    while ($item = tep_db_fetch_array($boxes_sql1)){
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$item['id'] . "'");
    }

    tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where 	theme_name = '" . tep_db_input($params['theme_name']) . "'");
    tep_db_query("delete from " . TABLE_THEMES_STYLES . " where 	theme_name = '" . tep_db_input($params['theme_name']) . "'");


    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/design_boxes.sql'));
    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/design_boxes_settings.sql'));
    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/design_boxes_settings_tmp.sql'));
    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/design_boxes_tmp.sql'));
    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/themes_settings.sql'));
    tep_db_query(file_get_contents(DIR_FS_CATALOG . 'lib/frontend/themes/' . $params['theme_name'] . '/sql/themes_styles.sql'));

    //tep_redirect(tep_href_link('design/theme', 'theme_name=' . $params['theme_name']));

    $this->actionElementsSave();

    return 'ok';
  }

    public function actionThemes()
    {
        $params = Yii::$app->request->post();

        $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl('design/theme-add') . '" class="create_item menu-ico">' . TEXT_ADD_THEME . '</a>';

        $this->selectedMenu = array('design_controls', 'design/themes');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/themes'), 'title' => BOX_HEADING_THEMES);
        $this->view->headingTitle = BOX_HEADING_THEMES;

        $themes_array = tep_db_query("select * from " . TABLE_THEMES . " order by sort_order");
        $themes = array();
        while ($item = tep_db_fetch_array($themes_array)){

            $theme = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($item['theme_name']) . "' and setting_name = 'use_mobile_theme'"));
            if ($theme['setting_value']) {
                $item['link'] = Yii::$app->urlManager->createUrl(['design/choose-view', 'theme_name' => $item['theme_name']]);
            } else {
                $item['link'] = Yii::$app->urlManager->createUrl(['design/elements', 'theme_name' => $item['theme_name']]);
            }

            if ($item['parent_theme']) {
                $parent = \common\models\Themes::findOne(['theme_name' => $item['parent_theme']]);
                $item['parent_theme_title'] = $parent['title'];
            }

            $themes[] = $item;
        }

        return $this->render('themes.tpl', [
            'themes' => $themes,
        ]);
    }

    public function actionThemeAdd()
    {
        \common\helpers\Translation::init('admin/design');

        $themes = array();
        $query = tep_db_query("select id, theme_name, title from " . TABLE_THEMES . " where install = '1' order by sort_order");
        while ($theme = tep_db_fetch_array($query)){
            $themes[] = $theme;
        }

        $this->layout = 'popup.tpl';
        return $this->render('theme-add.tpl', ['themes' => $themes, 'action' => Yii::$app->urlManager->createUrl('design/theme-add-action')]);
    }

    public function actionThemeAddAction()
    {
        \common\helpers\Translation::init('admin/design');
        $params = Yii::$app->request->get();
        $this->layout = false;

        if (!$params['title']) {
            return json_encode(['code' => 1, 'text' => THEME_TITLE_REQUIRED]);
        }

        if (!$params['theme_name']) {
            $name = $params['title'];
            $name = strtolower($name);
            $name = str_replace(' ', '_', $name);
            $name = preg_replace('/[^a-z0-9_-]/', '', $name);
            $params['theme_name'] = $name;
        }
        if (!preg_match("/^[a-z0-9_\-]+$/", $params['theme_name'])) {
            return json_encode(['code' => 1, 'text' => 'Enter only lowercase letters and numbers for theme name']);
        }

        $theme = tep_db_query("select id from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");
        if (tep_db_num_rows($theme) > 0){
            return json_encode(['code' => 1, 'text' => 'Theme with this name already exist']);
        }

        $query = tep_db_query("select id, sort_order from " . TABLE_THEMES . " where install = '1'");
        while ($theme = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'sort_order' => $theme['sort_order'] + 1,
            );
            tep_db_perform(TABLE_THEMES, $sql_data_array, 'update', " id = '" . $theme['id'] . "'");
        }

        $sql_data_array = array(
            'theme_name' => $params['theme_name'],
            'title' => $params['title'],
            'install' => 1,
            'is_default' => 0,
            'sort_order' => 0,
            'parent_theme' => ($params['parent_theme'] && $params['theme_source'] == 'theme' && $params['parent_theme_files'] == 'link' ? $params['parent_theme'] : 0)
        );
        tep_db_perform(TABLE_THEMES, $sql_data_array);


        if ($params['parent_theme'] && $params['theme_source'] == 'theme'){

            Theme::copyTheme($params['theme_name'], $params['parent_theme'], $params['parent_theme_files']);
            Theme::copyTheme($params['theme_name'] . '-mobile', $params['parent_theme'] . '-mobile', $params['parent_theme_files']);

        }

        if ($params['theme_source'] == 'url' || $params['theme_source'] == 'computer') {

            $zip = new \ZipArchive();


            $path = \Yii::getAlias('@webroot');
            $path .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
            $path .= 'themes' . DIRECTORY_SEPARATOR . $params['theme_name'] . DIRECTORY_SEPARATOR;
            $pathDesktop = $path . 'desktop' . DIRECTORY_SEPARATOR;
            $pathMobile = $path . 'mobile' . DIRECTORY_SEPARATOR;
            $arrMobile = '';

            if (!file_exists($path)){
                mkdir($path);
            }
            if ($params['theme_source'] == 'url') {
                copy($params['theme_source_url'], $path . 'theme.zip');
            } else {
                $uploaded = \Yii::getAlias('@webroot');
                $uploaded .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $params['theme_source_computer'];
                copy($uploaded, $path . 'theme.zip');
                unlink($uploaded);
            }
            if (substr($path . 'theme.zip', -4) == '.zip' && $zip->open($path . 'theme.zip', \ZipArchive::CREATE) === TRUE){
                $zip->extractTo ($path);
                if (!is_dir($pathDesktop)) {
                    $pathDesktop = $path;
                }
                $arrDesktop = json_decode(file_get_contents($pathDesktop . 'theme-tree.json'), true);
                if (is_file($pathMobile . 'theme-tree.json')) {
                    $arrMobile = json_decode(file_get_contents($pathMobile . 'theme-tree.json'), true);
                }
                Theme::copyFiles($params['theme_name']);
            } else {
                $arrDesktop = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
            }
            if (is_array($arrDesktop) && $params['theme_name']){

                Theme::importTheme($arrDesktop, $params['theme_name']);
                if ($arrMobile) {
                    Theme::importTheme($arrMobile, $params['theme_name'] . '-mobile');
                }

                Style::createCache($params['theme_name']);

                return json_encode(['code' => 2, 'text' => 'Theme added']);
            }

            unlink($path . 'theme.zip');
            unlink($path . 'theme-tree.json');

        }

        if ($params['landing']) {
            $sql_data_array = array(
                'theme_name' => $params['theme_name'],
                'setting_group' => 'hide',
                'setting_name' => 'landing',
                'setting_value' => '1',
            );
            tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
        }

        Style::createCache($params['theme_name']);
        Style::createCache($params['theme_name'] . '-mobile');

        return json_encode(['code' => 2, 'text' => 'Theme added']);
    }

  public function actionClearTheme()
  {
    $count = 0;
    $query = tep_db_query("select box_id from " . TABLE_DESIGN_BOXES_SETTINGS);
    while ($item = tep_db_fetch_array($query)){
      $box = tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where id = '" . (int)$item['id'] . "'");
      if (tep_db_num_rows($box) == 0) {
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where id = '" . (int)$item['id'] . "'");
        $count++;
      }
    }
    $query = tep_db_query("select box_id from " . TABLE_DESIGN_BOXES_SETTINGS_TMP);
    while ($item = tep_db_fetch_array($query)){
      $box = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
      if (tep_db_num_rows($box) == 0) {
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where id = '" . (int)$item['id'] . "'");
        $count++;
      }
    }
    echo 'removed ' .  $count . 'rows';
  }

  public function actionThemeRemove(){

    $params = Yii::$app->request->get();

    Theme::themeRemove($params['theme_name']);
    Theme::themeRemove($params['theme_name'] . '-mobile');

    return Yii::$app->getResponse()->redirect(array('design/themes'));
  }



  //   admin/design/theme-setting?theme_name=theme-1
  public function actionThemeSetting()
  {
    $params = Yii::$app->request->get();

    $design_boxes = tep_db_query("select * from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");

    $val = '<div style="float: left; width: 50%"><h2>design_boxes</h2><pre>';
    while ($item = tep_db_fetch_array($design_boxes)){
        $val .= var_export([
            'block_name' => $item['block_name'],
            'widget_name' => $item['widget_name'],
            'widget_params' => $item['widget_params'],
            'sort_order' => $item['sort_order'],
        ],true).",\n";
      /*$val .= '
      array(
            \'block_name\' => \'' . $item['block_name'] . '\',
            \'widget_name\' => \'' . $item['widget_name'] . '\',
            \'widget_params\' => \'' . $item['widget_params'] . '\',
            \'sort_order\' => \'' . $item['sort_order'] . '\'
          ),';*/
    }
    $val .= '</pre></div>';

    $design_boxes = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");

    $val .= '<div style="float: left; width: 50%"><h2>design_boxes</h2><pre>';
    while ($item = tep_db_fetch_array($design_boxes)){
        $val .= var_export([
            'setting_group' => $item['setting_group'],
            'setting_name' => $item['setting_name'],
            'setting_value' => $item['setting_value'],
        ],true).",\n";
      /*$val .= '
      array(
            \'setting_group\' => \'' . $item['setting_group'] . '\',
            \'setting_name\' => \'' . $item['setting_name'] . '\',
            \'setting_value\' => \'' . $item['setting_value'] . '\'
          ),';*/
    }
    $val .= '</pre></div>';

    return $val;
  }


  public function actionThemeEdit()
  {
    $languages_id = \Yii::$app->settings->get('languages_id');
    \common\helpers\Translation::init('admin/design');

    $params = Yii::$app->request->get();

    $language_query = tep_db_fetch_array(tep_db_query("select code from " . TABLE_LANGUAGES . " where languages_id = '" . $languages_id . "' order by sort_order"));
    $language_code = $language_query['code'];

    $this->topButtons[] = '<span data-href="' . Yii::$app->urlManager->createUrl(['design/theme-save', 'theme_name' => $params['theme_name']]) . '" class="btn btn-confirm btn-save-boxes btn-elements">'.IMAGE_SAVE.'</span> <span class="redo-buttons"></span>';

    $this->selectedMenu = array('design_controls', 'design/themes');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/elements'), 'title' => BOX_HEADING_MAIN_STYLES . ' "' . Theme::getThemeTitle($params['theme_name']) . '"');
    $this->view->headingTitle = BOX_HEADING_MAIN_STYLES . ' "' . Theme::getThemeTitle($params['theme_name']) . '"';

    $editable_links = array();
    $editable_links['home'] = tep_href_link('..');

    $not_array = array();

    $bundle_sets_query = tep_db_query("select p.products_id from " . TABLE_PRODUCTS . " p, " . TABLE_SETS_PRODUCTS . " sp where sp.sets_id = p.products_id and p.products_status = '1' order by sp.sort_order");
    if ($bundle_sets = tep_db_fetch_array($bundle_sets_query)){
      $editable_links['bundle'] = tep_href_link('../catalog/product?products_id=' . $bundle_sets['products_id']);
      $not_array[] = $bundle_sets['products_id'];
    }
    while ($bundle_sets = tep_db_fetch_array($bundle_sets_query)){
      $editable_links['bundle'] = tep_href_link('../catalog/product?products_id=' . $bundle_sets['products_id']);
      if (!in_array($bundle_sets['products_id'], $not_array)) {
        $not_array[] = $bundle_sets['products_id'];
      }
    }

    $attributes_query = tep_db_query("select p.products_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id = p.products_id");
    if ($attributes = tep_db_fetch_array($attributes_query)){
      $editable_links['attributes'] = tep_href_link('../catalog/product?products_id=' . $attributes['products_id']);
      if (!in_array($attributes['products_id'], $not_array)) {
        $not_array[] = $attributes['products_id'];
      }
    }
    while ($attributes = tep_db_fetch_array($attributes_query)){
      $editable_links['attributes'] = tep_href_link('../catalog/product?products_id=' . $attributes['products_id']);
      if (!in_array($attributes['products_id'], $not_array)) {
        $not_array[] = $attributes['products_id'];
      }
    }

    if (count($not_array) > 0) {
      $products_query = tep_db_query("select products_id from " . TABLE_PRODUCTS . " where products_status = 1 and products_id not in ('" . implode("','", $not_array) . "')");
      if ($products = tep_db_fetch_array($products_query)) {
        $editable_links['product'] = tep_href_link('../catalog/product?products_id=' . $products['products_id']);
      }
    }

    $categories_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where parent_id != 0 and categories_status = 1");
    if ($categories = tep_db_fetch_array($categories_query)){
      $editable_links['categories'] = tep_href_link('../catalog/index', 'cPath=' . $categories['parent_id']);
    }

    $categories_query = tep_db_query("select c.categories_id from " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p2c.categories_id = c.categories_id and c.categories_status = 1");
    if ($categories = tep_db_fetch_array($categories_query)){
      $editable_links['products'] = tep_href_link('../catalog/index', 'cPath=' . $categories['categories_id']);
    }

    $information_query = tep_db_query("select 	information_id from " . TABLE_INFORMATION . " where visible = 1 AND platform_id='".\common\classes\platform::firstId()."' ");
    if ($information = tep_db_fetch_array($information_query)){
      $editable_links['information'] = tep_href_link('../info/index', 'info_id=' . $information['information_id']);
    }


    $editable_links['cart'] = tep_href_link('../shopping-cart/index');

    $editable_links['success'] = tep_href_link('../checkout/success');

    $editable_links['contact'] = tep_href_link('../contact/index');

    $editable_links['gift'] = tep_href_link('../catalog/gift-card');

    $editable_links['delivery-location-default'] = tep_href_link('../delivery-location');

    $get_location_r = tep_db_query("SELECT id FROM ".TABLE_SEO_DELIVERY_LOCATION." WHERE parent_id=0 AND platform_id='".\common\classes\platform::firstId()."' LIMIT 1");
    if ( tep_db_num_rows($get_location_r)>0 ) {
        $_location = tep_db_fetch_array($get_location_r);
        $editable_links['delivery-location'] = tep_href_link('../delivery-location/','id='.$_location['id']);
    }

    $css = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'css' and setting_name = 'css'"));
    $javascript = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'javascript' and setting_name = 'javascript'"));

    return $this->render('theme-edit.tpl', [
      'menu' => 'theme-edit',
      'link_save' => Yii::$app->urlManager->createUrl(['design/theme-save', 'theme_name' => $params['theme_name']]),
      'link_cancel' => Yii::$app->urlManager->createUrl(['design/theme-cancel']),
      'theme_name' => ($params['theme_name'] ? $params['theme_name'] : 'theme-1'),
      'clear_url' => ($params['theme_name'] ? true : false),
      'editable_links' => $editable_links,
      'css' => $css['setting_value'],
      'javascript' => $javascript['setting_value'],
      'language_code' => $language_code
    ]);
  }

    public function actionCss()
    {
        \common\helpers\Translation::init('admin/design');

        $params = Yii::$app->request->get();

        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/css'), 'title' => 'CSS "' . Theme::getThemeTitle($params['theme_name']) . '"');
        $this->selectedMenu = array('design_controls', 'design/themes');

        $this->topButtons[] = '<span class="btn btn-confirm btn-save-css btn-elements ">' . IMAGE_SAVE . '</span><span class="redo-buttons"></span>';

        Style::changeCssAttributes($params['theme_name']);

        $style = Style::getCss($params['theme_name']);

        $css = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'css' and setting_name = 'css'"));
        if ($css['setting_value']) {
            $style .= $css['setting_value'];
        }

        $setting = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where setting_name = 'development_mode' and setting_group = 'hide' and theme_name = '" . tep_db_input($params['theme_name']) . "'"));
        $cookies = Yii::$app->request->cookies;
        $css_status = 0;
        if ($setting['setting_value']) {
            $css_status = 1;

            if (!$cookies->getValue('css_status')) {
                $cookies->add(new \yii\web\Cookie([
                    'name' => 'css_status',
                    'value' => 1,
                ]));
            }
        }


        return $this->render('css.tpl', [
            'menu' => 'css',
            'theme_name' => ($params['theme_name'] ? $params['theme_name'] : 'theme-1'),
            'css' => $style,
            'css_status' => $css_status,
            'widgets_list' => Style::getCssWidgetsList($params['theme_name'])
        ]);
    }

    public function actionGetCss()
    {
        $get = Yii::$app->request->get();

        if ($get['widget'] == 'all'){
            $widget = [];
        } elseif ($get['widget'] == 'main') {
            $widget = [''];
        } else {
            $widget = [$get['widget']];
        }

        $css = Style::getCss($get['theme_name'], $widget);

        if ($get['widget'] != 'all' && $get['widget'] != 'main' && $get['widget'] != 'block_box') {
            $css = str_replace(($get['widget'] ? $get['widget'] . ' ' : ''), '', $css);
        }

        return $css;
    }

  public function actionJs()
  {
    \common\helpers\Translation::init('admin/design');

    $params = Yii::$app->request->get();

      $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/js'), 'title' => 'JS "' . Theme::getThemeTitle($params['theme_name']) . '"');
      $this->selectedMenu = array('design_controls', 'design/themes');

    $this->topButtons[] = '<span class="btn btn-confirm btn-save-javascript btn-elements ">' . IMAGE_SAVE . '</span>';

    $javascript = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'javascript' and setting_name = 'javascript'"));

    return $this->render('js.tpl', [
      'menu' => 'js',
      'theme_name' => ($params['theme_name'] ? $params['theme_name'] : 'theme-1'),
      'javascript' => $javascript['setting_value'],
    ]);
  }

  public function actionCssSave()
  {
      $params = Yii::$app->request->post();

      $devPath = DIR_FS_CATALOG . 'themes/' . $params['theme_name'] . '/css/';

      if ($params['widget'] == 'all') {
          \yii\helpers\FileHelper::createDirectory($devPath);
          file_put_contents($devPath . 'develop.css', $params['css']);
      }
      /*$develop = fopen($devPath . 'develop.css', "w");
      fwrite($develop, $params['css']);
      fclose($develop);*/

      return Style::cssSave($params);
  }


  public function actionJavascriptSave()
  {
    $params = Yii::$app->request->post();

    $total = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'javascript' and setting_group = 'javascript'"));

    $query = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'javascript' and setting_group = 'javascript'");
    $javascript_old = tep_db_fetch_array($query);
    $javascript_old = $javascript_old['setting_value'];

    if (tep_db_num_rows($query) == 0) {
      $sql_data_array = array(
        'theme_name' => $params['theme_name'],
        'setting_group' => 'javascript',
        'setting_name' => 'javascript',
        'setting_value' => $params['javascript']
      );
      tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
    } else {
      $sql_data_array = array(
        'setting_value' => $params['javascript']
      );
      tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array, 'update', " theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'javascript' and setting_name = 'javascript'");
    }

    $data = [
      'theme_name' => $params['theme_name'],
      'javascript_old' => $javascript_old,
      'javascript' => $params['javascript'],
    ];
    Steps::javascriptSave($data);

    return '';

  }

  public function actionThemeSave()
  {
    $get = Yii::$app->request->get();

    //Steps::themeSave($get['theme_name']);

    //tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($get['theme_name']) . "'");
    //tep_db_query("INSERT INTO " . TABLE_THEMES_STYLES . " SELECT * FROM " . TABLE_THEMES_STYLES_TMP . " WHERE theme_name = '" . tep_db_input($get['theme_name']) . "'");

    /*tep_db_query("TRUNCATE TABLE " . TABLE_THEMES_STYLES);
    tep_db_query("INSERT " . TABLE_THEMES_STYLES . " SELECT * FROM " . TABLE_THEMES_STYLES_TMP . ";");*/

    return 'Saved';
  }

  public function actionThemeCancel()
  {
    $get = Yii::$app->request->get();

    //Steps::themeCancel($get['theme_name']);

    //tep_db_query("delete from " . TABLE_THEMES_STYLES_TMP . " where theme_name = '" . tep_db_input($get['theme_name']) . "'");
    //tep_db_query("INSERT INTO " . TABLE_THEMES_STYLES_TMP . " SELECT * FROM " . TABLE_THEMES_STYLES . " WHERE theme_name = '" . tep_db_input($get['theme_name']) . "'");

    /*tep_db_query("TRUNCATE TABLE " . TABLE_THEMES_STYLES_TMP);
    tep_db_query("INSERT " . TABLE_THEMES_STYLES_TMP . " SELECT * FROM " . TABLE_THEMES_STYLES . ";");*/

    return 'Canseled';
  }


  public function actionElements()
  {
    \common\helpers\Translation::init('admin/design');

    $languages_id = \Yii::$app->settings->get('languages_id');
    $this->selectedMenu = array('design', 'elements');
    $params = Yii::$app->request->get();

    $language_query = tep_db_fetch_array(tep_db_query("select code from " . TABLE_LANGUAGES . " where languages_id = '" . $languages_id . "' order by sort_order"));
    $language_code = $language_query['code'];

    $this->topButtons[] = '<span data-href="' . Yii::$app->urlManager->createUrl(['design/elements-save']) . '" class="btn btn-confirm btn-save-boxes btn-elements">' . IMAGE_SAVE . '</span> <span class="btn btn-preview-2 btn-elements">' . IMAGE_PREVIEW_POPUP . '</span> <span class="btn btn-preview btn-elements" title="Alt + P">' . IMAGE_PREVIEW . '</span><span class="btn btn-edit btn-elements" style="display: none" title="Alt + P">' . IMAGE_EDIT . '</span><span class="redo-buttons"></span>';

    $query = tep_db_fetch_array(tep_db_query("select id, title from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($params['theme_name']) . "'"));

    $_theme_id = (int)$query['id'];
    $platform_select = array();
    $_attached_platform_list_r = tep_db_query("SELECT platform_id FROM ".TABLE_PLATFORMS_TO_THEMES." WHERE theme_id='".$_theme_id."' ");
    if ( tep_db_num_rows($_attached_platform_list_r)>0 ) {
      while( $_attached_platform = tep_db_fetch_array($_attached_platform_list_r) ) {
        foreach (\common\classes\platform::getList() as $_platform_info) {
          if ($_platform_info['id']==$_attached_platform['platform_id']){
            $platform_select[] = $_platform_info;
          }
        }
      }
    }
    if ( count($platform_select)==0 ) {
      $platform_select = \common\classes\platform::getList();
      $platform_select = array_slice($platform_select,0,1);
    }

    //$this->selectedMenu = array('design_controls', 'design/elements');
    $this->selectedMenu = array('design_controls', 'design/themes');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/elements'), 'title' => BOX_HEADING_ELEMENTS . ' "' . Theme::getThemeTitle($params['theme_name']) . '"');
    $this->view->headingTitle = BOX_HEADING_ELEMENTS . ' "' . Theme::getThemeTitle($params['theme_name']) . '"';

      $extensionPageTypes = \common\helpers\Acl::getExtensionPageTypes();
      $pageTypes = [];
      foreach ($extensionPageTypes as $type){
          $pageTypes[$type['group']] = $pageTypes[$type['group']] . ' ' . $type['name'];
      }

    $per_platform_links = array();
    foreach( $platform_select as $_platform ) {
      Yii::$app->get('platform')->config($_platform['id'])->catalogBaseUrlWithId(true);

        $extensions = [];
      $editable_links = array(
        'home' => '',
        'product' => '',
        'attributes' => '',
        'bundle' => '',
        'categories' => '',
        'products' => '',
        'information' => '',
        'cart' => '',
        'success' => '',
        'contact' => '',
        'email' => '',
        'invoice' => '',
        'packingslip' => '',
        'creditnote' => '',
        'quote' => '',
        'sample' => '',
      );
      $editable_links['home'] = tep_catalog_href_link('', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=main');

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'home'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)){
            $page_name = design::pageName($item['setting_value']);
            $editable_links['home' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>' . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }

      $not_array = array();

      if (count($not_array) > 0) {
        $products_query = tep_db_query("select p.products_id from " . TABLE_PRODUCTS . " p inner join ".TABLE_PLATFORMS_PRODUCTS." plp on plp.products_id=p.products_id and plp.platform_id='".$_platform['id']."' where p.products_status = 1 and p.products_id not in ('" . implode("','", $not_array) . "')");
      } else {
        $products_query = tep_db_query("select p.products_id from " . TABLE_PRODUCTS . " p inner join ".TABLE_PLATFORMS_PRODUCTS." plp on plp.products_id=p.products_id and plp.platform_id='".$_platform['id']."' where p.products_status = 1");
      }
      if ($products = tep_db_fetch_array($products_query)) {
        $editable_links['product'] = tep_catalog_href_link('catalog/product', 'products_id=' . $products['products_id'].'&theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'product'");

          $i = 0;
          while ($item = tep_db_fetch_array($settings)){
              $page_name = design::pageName($item['setting_value']);
              $editable_links['product' . $i] = array(
                  'page_name' => $page_name,
                  'name' => $item['setting_value'],
                  'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>' . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                  'link' => tep_catalog_href_link('catalog/product', 'products_id=' . $products['products_id'].'&theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
              );
              $i++;
          }



          $editable_links['productListing'] = tep_catalog_href_link('catalog/product-listing', 'products_id=' . $products['products_id'].'&theme_name=' . $params['theme_name'] . '&language=' . $language_code);
          $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'productListing'");

          $i = 0;
          while ($item = tep_db_fetch_array($settings)){
              $page_name = design::pageName($item['setting_value']);
              $editable_links['productListing' . $i] = array(
                  'page_name' => $page_name,
                  'name' => $item['setting_value'],
                  'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>' . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                  'link' => tep_catalog_href_link('catalog/product-listing', 'products_id=' . $products['products_id'].'&theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
              );
              $i++;
          }
      }

      $categories_query = tep_db_query("select c.parent_id from " . TABLE_CATEGORIES . " c inner join ".TABLE_PLATFORMS_CATEGORIES." plc on plc.categories_id=c.categories_id and plc.platform_id='".$_platform['id']."' where parent_id != 0 and categories_status = 1");
      if ($categories = tep_db_fetch_array($categories_query)) {
        $editable_links['categories'] = tep_catalog_href_link('catalog/index', 'cPath=' . $categories['parent_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'categories'");
        $i=0;
        while ($item = tep_db_fetch_array($settings)){
          $page_name = design::pageName($item['setting_value']);
          $editable_links['categories' . $i] = array(
            'page_name' => $page_name,
              'name' => $item['setting_value'],
            'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span> <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
            'link' => tep_catalog_href_link('catalog/index', 'cPath=' . $categories['parent_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
          );
          $i++;
        }
      }

        $editable_links['products'] = tep_catalog_href_link('catalog/all-products', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . $params['theme_name'] . "' and setting_group = 'added_page' and setting_name = 'products'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)) {
            $page_name = design::pageName($item['setting_value']);
            $editable_links['products' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>' . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('catalog/all-products', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }
        $editable_links['products' . $i] = array(
            'page_name' => 'manufacturers',
            'name' => 'manufacturers',
            'page_title' => 'Manufacturers',
            'link' => tep_catalog_href_link('catalog/manufacturers', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
        );

      $information_query = tep_db_query("select information_id from " . TABLE_INFORMATION . " where visible = 1 AND platform_id='" . \common\classes\platform::firstId() . "' ");
      if ($information = tep_db_fetch_array($information_query)) {
        $editable_links['information'] = tep_catalog_href_link('info/index', 'info_id=' . $information['information_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and (setting_name = 'info' or setting_name = 'custom')");
          $i = 0;
          while ($item = tep_db_fetch_array($settings)){
              $page_name = design::pageName($item['setting_value']);
              $editable_links['information' . $i] = array(
                  'page_name' => $page_name,
                  'name' => $item['setting_value'],
                  'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                  'link' => tep_catalog_href_link('info/index', 'info_id=' . $information['information_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
              );
              $i++;
          }

      }

      $editable_links['404'] = tep_catalog_href_link('index/404', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['sample'] = tep_catalog_href_link('sample-cart/index', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['quote'] = tep_catalog_href_link('quote-cart/index', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['wishlist'] = tep_catalog_href_link('account/wishlist', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['login_checkout'] = tep_catalog_href_link('checkout/login',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['checkout'] = tep_catalog_href_link('checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=checkout');

        $editable_links['confirmation'] = tep_catalog_href_link('checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=confirmation');

        $editable_links['checkout_no_shipping'] = tep_catalog_href_link('checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=checkout_no_shipping');

        $editable_links['confirmation_no_shipping'] = tep_catalog_href_link('checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=confirmation');


        $editable_links['login_checkout2'] = tep_catalog_href_link('checkout/login',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=login_2');

        $editable_links['checkout2'] = tep_catalog_href_link('checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=index_2');

        $editable_links['checkout_no_shipping2'] = tep_catalog_href_link('checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=index_2');

        $editable_links['confirmation2'] = tep_catalog_href_link('checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=confirmation_2');

        $editable_links['confirmation_no_shipping2'] = tep_catalog_href_link('checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=confirmation_2');


        $editable_links['login_checkout_q'] = tep_catalog_href_link('quote-checkout/login',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['checkout_q'] = tep_catalog_href_link('quote-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=index');

        $editable_links['confirmation_q'] = tep_catalog_href_link('quote-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=confirmation');

        $editable_links['checkout_no_shipping_q'] = tep_catalog_href_link('quote-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=index');

        $editable_links['confirmation_no_shipping_q'] = tep_catalog_href_link('quote-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=confirmation');


        $editable_links['success'] = tep_catalog_href_link('checkout/success',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);


        $editable_links['login_checkout2_q'] = tep_catalog_href_link('quote-checkout/login',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=login_2');

        $editable_links['checkout2_q'] = tep_catalog_href_link('quote-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=index_2');

        $editable_links['checkout_no_shipping2_q'] = tep_catalog_href_link('quote-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=index_2');

        $editable_links['confirmation2_q'] = tep_catalog_href_link('quote-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=confirmation_2');

        $editable_links['confirmation_no_shipping2_q'] = tep_catalog_href_link('quote-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&no_shipping=1&page_name=confirmation_2');


        $editable_links['success_q'] = tep_catalog_href_link('quote-checkout/success',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);


        $editable_links['login_checkout_s'] = tep_catalog_href_link('sample-checkout/login',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['checkout_s'] = tep_catalog_href_link('sample-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=index');

        $editable_links['checkout_free_s'] = tep_catalog_href_link('sample-checkout/index',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&sample_free=1&page_name=index');

        $editable_links['confirmation_s'] = tep_catalog_href_link('sample-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=confirmation');

        $editable_links['confirmation_free_s'] = tep_catalog_href_link('sample-checkout/confirmation',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&sample_free=1&page_name=confirmation');


        $editable_links['success_s'] = tep_catalog_href_link('sample-checkout/success',
            'theme_name=' . $params['theme_name'] . '&language=' . $language_code);


      $editable_links['cart'] = tep_catalog_href_link('shopping-cart/index', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['contact'] = tep_catalog_href_link('contact/index', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['email'] = tep_catalog_href_link('email-template', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'email'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)){
            $page_name = design::pageName($item['setting_value']);
            $editable_links['email' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('email-template', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }

      $editable_links['gift_card'] = tep_catalog_href_link('catalog/gift', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'gift_card'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)){
            $page_name = design::pageName($item['setting_value']);
            $editable_links['gift' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('catalog/gift', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = 'components'");
        $editable_links_components = array();
        $i = 0;
        while ($item = tep_db_fetch_array($settings)){
            $page_name = design::pageName($item['setting_value']);
            $editable_links['components' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('info/components', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }

      $editable_links['login_account'] = tep_catalog_href_link('account/login', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['logoff'] = tep_catalog_href_link('account/logoff', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $editable_links['logoff_forever'] = tep_catalog_href_link('account/logoff', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&forever=1');

      $editable_links['password_forgotten'] = tep_catalog_href_link('account/password-forgotten', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '');

      $editable_links['gift'] = tep_href_link('../catalog/gift-card', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['delivery-location-default'] = tep_catalog_href_link('delivery-location', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['delivery-location'] = tep_catalog_href_link('delivery-location','not_root=1&theme_name=' . $params['theme_name'] . '&language=' . $language_code);

      $order_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where platform_id='".$_platform['id']."' limit 1");
      if (tep_db_num_rows($order_query) == 0){
        $order_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " limit 1");
      }
      if ($order = tep_db_fetch_array($order_query)) {
        $editable_links['invoice'] = tep_catalog_href_link('orders/invoice', 'orders_id=' . $order['orders_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $editable_links['packingslip'] = tep_catalog_href_link('orders/packingslip', 'orders_id=' . $order['orders_id'] . '&theme_name=' . $params['theme_name'] . '&language=' . $language_code);
      }

          $editable_links['creditnote'] = tep_catalog_href_link('orders/credit-note', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and (setting_name = 'invoice' or setting_name = 'packingslip' or setting_name = 'label')");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)){
            $page_name = design::pageName($item['setting_value']);
            $editable_links['orders' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>' . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('orders/invoice', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name . '&orders_id=' . $order['orders_id'])
            );
            $i++;
        }

        if (\frontend\design\Info::hasBlog()){
            $editable_links['blog'] = tep_catalog_href_link('blog', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        }

        $editable_links['pdf_cover'] = tep_catalog_href_link('pdf/cover', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $editable_links['pdf'] = tep_catalog_href_link('pdf', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);

        $editable_links['account'] = tep_catalog_href_link('account', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . $params['theme_name'] . "' and setting_group = 'added_page' and setting_name = 'account'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)) {
            $page_name = design::pageName($item['setting_value']);
            $editable_links['account' . $i] = array(
                'page_name' => $page_name,
                'name' => $item['setting_value'],
                'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
              //'page_title' => $item['setting_value'] . ' <span class="edit" data-name="' . $item['setting_value'] . '"></span>',
                'link' => tep_catalog_href_link('account', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
            );
            $i++;
        }

        foreach ($extensionPageTypes as $type) {
            $settings = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'added_page' and setting_name = '" . $type['name'] . "'");
            $i = 0;
            while ($editable_links[$type['group'] . $i]) {
                $i++;
            }
            while ($item = tep_db_fetch_array($settings)) {
                $page_name = design::pageName($item['setting_value']);
                $actionArr = explode('?', $type['action']);
                $editable_links[$type['group'] . $i] = array(
                    'page_name' => $page_name,
                    'name' => $item['setting_value'],
                    'page_title' => $item['setting_value'] . ' <span class="remove" data-name="' . $item['setting_value'] . '"></span>',
                    'link' => tep_catalog_href_link($actionArr[0], ($actionArr[1] ? $actionArr[1] . '&' : '') . 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . '&page_name=' . $page_name)
                );
                $i++;
            }
        }


        $link = tep_catalog_href_link('promotions', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'promotions', 'title' => 'Promotions'];
        $per_platform_extensions_links['promotions'] = $link;

        $link = tep_catalog_href_link('sitemap', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'sitemap', 'title' => 'Sitemap'];
        $per_platform_extensions_links['sitemap'] = $link;

        $link = tep_catalog_href_link('reviews', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'reviews', 'title' => 'Reviews'];
        $per_platform_extensions_links['reviews'] = $link;

        $link = tep_catalog_href_link('account/trade-form', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['account'][] = ['link' => $link, 'name' => 'account', 'title' => 'Trade form'];
        $per_platform_extensions_links['account'] = $link;

        $link = tep_catalog_href_link('account/trade-form-pdf', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['account'][] = ['link' => $link, 'name' => 'account', 'title' => 'Trade form PDF'];
        $per_platform_extensions_links['account'] = $link;

        $link = tep_catalog_href_link('subscribers', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'subscribe', 'title' => 'Subscribe'];
        $per_platform_extensions_links['account'] = $link;

        $link = tep_catalog_href_link('subscribers/subscribed', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'subscribed', 'title' => 'Subscribed'];
        $per_platform_extensions_links['account'] = $link;

        $link = tep_catalog_href_link('subscribers/unsubscribed', 'theme_name=' . $params['theme_name'] . '&language=' . $language_code);
        $extensions['informations'][] = ['link' => $link, 'name' => 'unsubscribed', 'title' => 'Unubscribed'];
        $per_platform_extensions_links['account'] = $link;


        $addedGroups = [];
        $mainGroups = ['home', 'catalog', 'informations', 'account', 'cart', 'checkout2', 'checkout', 'checkoutQuote2', 'checkoutQuote', 'checkoutSample', 'orders', 'emails', 'pdf', 'components'];
        foreach (\common\helpers\Acl::getExtensionPages() as $page){
            $actionArr = explode('?', $page['action']);
            $link = tep_catalog_href_link($actionArr[0], ($actionArr[1] ? $actionArr[1] . '&' : '') . 'theme_name=' . $params['theme_name'] . '&language=' . $language_code . ($page['params'] ? '&' : '') . $page['params']);
            $extensions[$page['group']][] = [
                'link' => $link,
                'name' => $page['name'],
                'title' => $page['title'],
                'type' => $page['type'],
            ];
            $per_platform_extensions_links[$_platform['id']][$page['name']] = $link;
            if (!in_array($page['group'], $mainGroups) && !in_array($page['group'], $addedGroups)) {
                $addedGroups[] = $page['group'];
            }
        }

        $per_platform_extensions[ $_platform['id'] ] = $extensions;

        $per_platform_links[ $_platform['id'] ] = $editable_links;

      Yii::$app->get('platform')->config($_platform['id'])->catalogBaseUrlWithId();
    }

    Yii::$app->get('platform')->config(\common\classes\platform::firstId());

    reset($per_platform_links);
    $first_platform_links = current($per_platform_links);
    reset($per_platform_extensions);
    $first_platform_extensions = current($per_platform_extensions);
    if ( isset($_COOKIE['page-url']) && !in_array($_COOKIE['page-url'], $first_platform_links) ) {
      setcookie('page-url', null, -1, DIR_WS_ADMIN);
    }

    if (\frontend\design\Info::themeSetting('landing', 'hide', $params['theme_name'])) {
        $landing = 1;
    } else {
        $landing = 0;
    }
      $per_platform_links = array_merge($per_platform_links, $per_platform_extensions_links);

      return $this->render('elements.tpl', [
          'menu' => 'elements',
          'link_save' => Yii::$app->urlManager->createUrl(['design/elements-save']),
          'link_cancel' => Yii::$app->urlManager->createUrl(['design/elements-cancel']),
          'theme_name' => ($params['theme_name'] ? $params['theme_name'] : 'theme-1'),
          'clear_url' => ($params['theme_name'] ? true : false),
          'editable_links' => $first_platform_links,
          'per_platform_links' => $per_platform_links,
          'extensions' => $first_platform_extensions,
          'platform_select' => $platform_select,
          'editable_links_components' => $editable_links_components,
          'landing' => $landing,
          'addedGroups' => $addedGroups,
          'types' => $pageTypes,
      ]);
  }


    public function actionElementsSave()
    {
        \common\helpers\Translation::init('admin/design');
        $get = tep_db_prepare_input(Yii::$app->request->get());

        Theme::elementsSave($get['theme_name']);

        Steps::elementsSave($get['theme_name']);

        return '<div class="popup-heading">' . TEXT_NOTIFIC . '</div><div class="popup-content pop-mess-cont">'.MESSAGE_SAVED.'</div>';
    }


  public function actionElementsCancel()
  {
    $get = tep_db_prepare_input(Yii::$app->request->get());

    Steps::elementsCancel($get['theme_name']);

    $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($get['theme_name']) . "'");
    while ($item = tep_db_fetch_array($query)){
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$item['id'] . "'");
    }

    $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($get['theme_name']) . "'");
    while ($item = tep_db_fetch_array($query)){

      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $item);

      tep_db_query("INSERT INTO " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " SELECT * FROM " . TABLE_DESIGN_BOXES_SETTINGS . " WHERE box_id = '" . (int)$item['id'] . "'");
    }


    /*tep_db_query("TRUNCATE TABLE " . TABLE_DESIGN_BOXES_TMP);
    tep_db_query("INSERT " . TABLE_DESIGN_BOXES_TMP . " SELECT * FROM " . TABLE_DESIGN_BOXES . ";");
    tep_db_query("TRUNCATE TABLE " . TABLE_DESIGN_BOXES_SETTINGS_TMP);
    tep_db_query("INSERT " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " SELECT * FROM " . TABLE_DESIGN_BOXES_SETTINGS . ";");*/

    return '<div class="popup-heading">' . TEXT_NOTIFIC . '</div><div class="popup-content pop-mess-cont">Canceled</div>';
  }


  public function actionInvoice()
  {
    $this->selectedMenu = array('design_controls', 'design/invoice');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/invoice'), 'title' => BOX_HEADING_INVOICE);
    $this->view->headingTitle = BOX_HEADING_INVOICE;

    return $this->render('invoice.tpl');
  }


  public function actionBlocksMove()
  {
    $params = Yii::$app->request->post();

    $this->actionBackupAuto($params['theme_name']);

    $i = 1;
    $positions = array();
    if (is_array($params['id'])) foreach ($params['id'] as $item){
      $id = substr($item, 4);
      $sql_data_array = array(
        'block_name' => tep_db_prepare_input($params['name']),
        'sort_order' => $i,
      );
      $i++;
      $positions[] = array_merge(['id' => $id], $sql_data_array);
      $positions_old[] = tep_db_fetch_array(tep_db_query("select id, block_name, sort_order from " . TABLE_DESIGN_BOXES_TMP . " where id='" . (int)$id . "'"));
      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array, 'update', "id = '" . (int)$id . "'");
    }

    $data = [
      'positions' => $positions,
      'positions_old' => $positions_old,
      'theme_name' => $params['theme_name'],
    ];
    Steps::blocksMove($data);

    return json_encode('');
  }

  public static function deleteBlock($id) {
    $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "' or block_name = 'block-" . tep_db_input($id) . "-2' or block_name = 'block-" . tep_db_input($id) . "-3' or block_name = 'block-" . tep_db_input($id) . "-4' or block_name = 'block-" . tep_db_input($id) . "-5'");
    while ($item = tep_db_fetch_array($query)){
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . $item['id'] . "'");
      self::deleteBlock($item['id']);
    }
  }

  public function actionBoxDelete()
  {
    $params = tep_db_prepare_input(Yii::$app->request->post());

    $id = substr($params['id'], 4);

    Steps::boxDelete([
      'theme_name' => $params['theme_name'],
      'id' => $id
    ]);

    $this->actionBackupAuto($params['theme_name']);

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$id . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "'");

    self::deleteBlock($id);

    return json_encode('');
  }


  public function actionWidgetsList()
  {
    \common\helpers\Translation::init('admin/design');
    $params = Yii::$app->request->get();

    $widgets = array();
    if ($params['type'] == 'product'){
      $widgets[] = array('name' => 'title', 'title' => PRODUCTS_WIDGETS, 'description' => '', 'type' => 'product');
      $widgets[] = array('name' => 'product\Name', 'title' => TEXT_PRODUCTS_NAME, 'description' => '', 'type' => 'product', 'class' => 'name');
      $widgets[] = array('name' => 'product\Images', 'title' => TEXT_PRODUCTS_IMAGES, 'description' => '', 'type' => 'product', 'class' => 'images');
      $widgets[] = array('name' => 'product\ImagesAdditional', 'title' => TEXT_ADDITIONAL_IMAGES, 'description' => '', 'type' => 'product', 'class' => 'images');
      $widgets[] = array('name' => 'product\Attributes', 'title' => TEXT_PRODUCTS_ATTRIBUTES, 'description' => '', 'type' => 'product', 'class' => 'attributes');
      $widgets[] = array('name' => 'product\Inventory', 'title' => BOX_CATALOG_INVENTORY, 'description' => '', 'type' => 'product', 'class' => 'attributes');
      $widgets[] = array('name' => 'product\Bundle', 'title' => TEXT_PRODUCTS_BUNDLE, 'description' => '', 'type' => 'product', 'class' => 'bundle');
      $widgets[] = array('name' => 'product\InBundles', 'title' => TEXT_PRODUCTS_IN_BUNDLE, 'description' => '', 'type' => 'product', 'class' => 'in-bundles');
      $widgets[] = array('name' => 'product\Price', 'title' => TEXT_PRODUCTS_PRICE, 'description' => '', 'type' => 'product', 'class' => 'price');
      $widgets[] = array('name' => 'product\QuantityDiscounts', 'title' => QUANTITY_DISCOUNTS, 'description' => '', 'type' => 'product', 'class' => 'price');
      $widgets[] = array('name' => 'product\Quantity', 'title' => TEXT_QUANTITY_INPUT, 'description' => '', 'type' => 'product', 'class' => 'quantity');
	  $widgets[] = array('name' => 'product\Stock', 'title' => TEXT_STOCK_INDICATION, 'description' => '', 'type' => 'product', 'class' => 'stock');
      $widgets[] = array('name' => 'product\Buttons', 'title' => TEXT_BUY_BUTTON, 'description' => '', 'type' => 'product', 'class' => 'buttons');
      $widgets[] = array('name' => 'product\WishlistButton', 'title' => TEXT_WISHLIST_BUTTON, 'description' => '', 'type' => 'product', 'class' => 'buttons');
      $widgets[] = array('name' => 'product\PersonalCatalogButton', 'title' => TEXT_PERSONAL_CATALOG, 'description' => '', 'type' => 'product', 'class' => 'buttons');
      $widgets[] = array('name' => 'product\Description', 'title' => TEXT_PRODUCTS_DESCRIPTION, 'description' => '', 'type' => 'product', 'class' => 'description');
      $widgets[] = array('name' => 'product\DescriptionShort', 'title' => TEXT_PRODUCTS_DESCRIPTION_SHORT, 'description' => '', 'type' => 'product', 'class' => 'description');
      $widgets[] = array('name' => 'product\Reviews', 'title' => TEXT_PRODUCTS_REVIEWS, 'description' => '', 'type' => 'product', 'class' => 'reviews');
      $widgets[] = array('name' => 'product\Properties', 'title' => TEXT_PRODUCTS_PROPERTIES, 'description' => '', 'type' => 'product', 'class' => 'properties');
      $widgets[] = array('name' => 'product\Model', 'title' => TABLE_HEADING_PRODUCTS_MODEL, 'description' => '', 'type' => 'product', 'class' => 'properties');
      $widgets[] = array('name' => 'product\PropertiesIcons', 'title' => TEXT_PROPERTIES_ICONS, 'description' => '', 'type' => 'product', 'class' => 'properties');
      $widgets[] = array('name' => 'product\AlsoPurchased', 'title' => TEXT_ALSO_PURCHASED, 'description' => '', 'type' => 'product', 'class' => 'also-purchased');
      $widgets[] = array('name' => 'product\CrossSell', 'title' => TEXT_CROSS_SELL_PRODUCTS, 'description' => '', 'type' => 'product', 'class' => 'cross-sell');
      $widgets[] = array('name' => 'product\Brand', 'title' => TEXT_LABEL_BRAND, 'description' => '', 'type' => 'product', 'class' => 'brands');
      $widgets[] = array('name' => 'product\Video', 'title' => TEXT_VIDEO, 'description' => '', 'type' => 'product', 'class' => 'video');
      $widgets[] = array('name' => 'product\Documents', 'title' => TAB_DOCUMENTS, 'description' => '', 'type' => 'product', 'class' => 'description');
      $widgets[] = array('name' => 'product\Configurator', 'title' => TEXT_CONFIGURATOR, 'description' => '', 'type' => 'product', 'class' => 'configurator');
        $widgets[] = array('name' => 'product\ImageMapProduct', 'title' => 'Image Map Product', 'description' => '', 'type' => 'product', 'class' => '');
      $widgets[] = array('name' => 'product\CustomersActivity', 'title' => TEXT_ACTIVE_CUSTOMERS, 'description' => '', 'type' => 'product', 'class' => '');
      $widgets[] = array('name' => 'product\AvailableInWarehouses', 'title' => TEXT_AVAILABLE_AT_WAREHOUSES, 'description' => '', 'type' => 'product', 'class' => '');
      $widgets[] = array('name' => 'product\PromotionIcons', 'title' => 'Promotion Icons', 'description' => '', 'type' => 'product', 'class' => '');
      $widgets[] = array('name' => 'product\Dimensions', 'title' => TEXT_DIMENSION, 'description' => '', 'type' => 'product', 'class' => '');
    }
    if ($params['type'] == 'inform'){
      $widgets[] = array('name' => 'title', 'title' => INFOPAGES_WIDGETS, 'description' => '', 'type' => 'inform');
      $widgets[] = array('name' => 'info\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'inform', 'class' => 'title');
      $widgets[] = array('name' => 'info\Content', 'title' => TEXT_CONTENT, 'description' => '', 'type' => 'inform', 'class' => 'content');
      $widgets[] = array('name' => 'contact\ContactForm', 'title' => CONTACT_FORM, 'description' => '', 'type' => 'general', 'class' => 'contact-form');
      $widgets[] = array('name' => 'info\Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'inform', 'class' => 'images');
      $widgets[] = array('name' => 'info\DescriptionShort', 'title' => TEXT_PRODUCTS_DESCRIPTION_SHORT, 'description' => '', 'type' => 'inform', 'class' => 'description');
        $widgets[] = array('name' => 'info\ImageMapInfo', 'title' => 'Image Map Info', 'description' => '', 'type' => 'inform', 'class' => '');

    }
      if ($params['type'] == 'subscribe'){
          $widgets[] = array('name' => 'subscribers\SubscribeForm', 'title' => 'Subscribe Form', 'description' => '', 'type' => 'subscribe', 'class' => '');
          $widgets[] = array('name' => 'subscribers\UnsubscribeForm', 'title' => 'Unsubscribe Form', 'description' => '', 'type' => 'subscribe', 'class' => '');
      }
    if ($params['type'] == 'catalog'){
	    $widgets[] = array('name' => 'product\WeddingRegistryButton', 'title' => TEXT_WEDDING_REGISTRY, 'description' => '', 'type' => 'catalog', 'class' => 'buttons');

	    $widgets[] = array('name' => 'title', 'title' => CATALOGS_WIDGETS, 'description' => '', 'type' => 'catalog');
      $widgets[] = array('name' => 'catalog\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'catalog', 'class' => 'title');
      $widgets[] = array('name' => 'catalog\Description', 'title' => TEXT_CATEGORY_DESCRIPTION, 'description' => '', 'type' => 'catalog', 'class' => 'description');
      $widgets[] = array('name' => 'catalog\Image', 'title' => TEXT_CATEGORY_IMAGE, 'description' => '', 'type' => 'catalog', 'class' => 'image');
      //$widgets[] = array('name' => 'PagingBar', 'title' => TEXT_PAGING_BAR, 'description' => '', 'type' => 'catalog', 'class' => 'paging-bar');
      $widgets[] = array('name' => 'catalog\Paging', 'title' => TEXT_PAGING, 'description' => '', 'type' => 'catalog', 'class' => 'paging-bar');
      $widgets[] = array('name' => 'catalog\CountsItems', 'title' => COUNTS_ITEMS_ON_PAGE, 'description' => '', 'type' => 'catalog', 'class' => 'paging-bar');
      $widgets[] = array('name' => 'Listing', 'title' => TEXT_PRODUCT_LISTING, 'description' => '', 'type' => 'catalog', 'class' => 'listing');
      //$widgets[] = array('name' => 'ListingFunctionality', 'title' => TEXT_LISTING_FUNCTIONALITY_BAR, 'description' => '', 'type' => 'catalog', 'class' => 'listing-functionality');
      $widgets[] = array('name' => 'catalog\ListingLook', 'title' => TEXT_LISTING_LOOK, 'description' => '', 'type' => 'catalog', 'class' => 'listing-functionality');
      $widgets[] = array('name' => 'catalog\CompareButton', 'title' => TEXT_COMPARE_BUTTON, 'description' => '', 'type' => 'catalog', 'class' => 'listing-functionality');
      $widgets[] = array('name' => 'catalog\Sorting', 'title' => TEXT_SORTING, 'description' => '', 'type' => 'catalog', 'class' => 'listing-functionality');
      $widgets[] = array('name' => 'catalog\ItemsOnPage', 'title' => TEXT_ITEMS_ON_PAGE, 'description' => '', 'type' => 'catalog', 'class' => 'listing-functionality');
      $widgets[] = array('name' => 'Categories', 'title' => TEXT_CATEGORIES, 'description' => '', 'type' => 'catalog', 'class' => 'categories');
      $widgets[] = array('name' => 'Filters', 'title' => TEXT_FILTERS, 'description' => '', 'type' => 'catalog', 'class' => 'filters');
      $widgets[] = array('name' => 'catalog\ImageMapCategory', 'title' => 'Image Map Category', 'description' => '', 'type' => 'catalog', 'class' => '');
      $widgets[] = array('name' => 'catalog\DeliveryLocations', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_TOP_LOCATION_LIST, 'description' => '', 'type' => 'catalog', 'class' => '');
      $widgets[] = array('name' => 'catalog\B2bAddButton', 'title' => B2B_ADD_BUTTON, 'description' => '', 'type' => 'catalog', 'class' => '');
    }
    if ($params['type'] == 'cart'){
      $widgets[] = array('name' => 'title', 'title' => SHOPPING_CART_WIDGETS, 'description' => '', 'type' => 'cart');
      $widgets[] = array('name' => 'cart\ContinueBtn', 'title' => CONTINUE_BUTTON, 'description' => '', 'type' => 'cart', 'class' => 'continue-button');
      $widgets[] = array('name' => 'cart\CheckoutBtn', 'title' => CHECKOUT_BUTTON, 'description' => '', 'type' => 'cart', 'class' => 'checkout-button');
      $widgets[] = array('name' => 'cart\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'cart', 'class' => 'products');
      $widgets[] = array('name' => 'cart\SubTotal', 'title' => SUB_TOTAL_AND_GIFT_WRAP_PRICE, 'description' => '', 'type' => 'cart', 'class' => 'price');
      $widgets[] = array('name' => 'cart\GiftCertificate', 'title' => GIFT_CERTIFICATE, 'description' => '', 'type' => 'cart', 'class' => 'gift-certificate');
      $widgets[] = array('name' => 'cart\DiscountCoupon', 'title' => DISCOUNT_COUPON, 'description' => '', 'type' => 'cart', 'class' => 'discount-coupon');
      $widgets[] = array('name' => 'cart\OrderReference', 'title' => TEXT_ORDER_REFERENCE, 'description' => '', 'type' => 'cart', 'class' => 'order-reference');
      $widgets[] = array('name' => 'cart\GiveAway', 'title' => BOX_CATALOG_GIVE_AWAY, 'description' => '', 'type' => 'cart', 'class' => 'give-away');
      $widgets[] = array('name' => 'cart\UpSell', 'title' => FIELDSET_ASSIGNED_UPSELL_PRODUCTS, 'description' => '', 'type' => 'cart', 'class' => 'up-sell');
      $widgets[] = array('name' => 'cart\ShippingEstimator', 'title' => SHOW_SHIPPING_ESTIMATOR_TITLE, 'description' => '', 'type' => 'cart', 'class' => 'shipping-estimator');
      $widgets[] = array('name' => 'cart\OrderTotal', 'title' => ORDER_PRICE_TOTAL, 'description' => '', 'type' => 'cart', 'class' => 'order-total');
      $widgets[] = array('name' => 'cart\CartTabs', 'title' => 'Cart Tabs', 'description' => '', 'type' => 'cart', 'class' => '');
      $widgets[] = array('name' => 'cart\CreditAmount', 'title' => 'Credit Amount', 'description' => '', 'type' => 'cart', 'class' => '');
      $widgets[] = array('name' => 'cart\BonusPoints', 'title' => 'Bonus Points', 'description' => '', 'type' => 'cart', 'class' => '');
      $widgets[] = array('name' => 'cart\FreeDelivery', 'title' => 'Free Delivery', 'description' => '', 'type' => 'cart', 'class' => '');
    }

      if ($params['type'] == 'quote'){
          $widgets[] = array('name' => 'quote\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => 'Quote', 'type' => 'quote', 'class' => 'products');
          $widgets[] = array('name' => 'cart\CartTabs', 'title' => 'Cart Tabs', 'description' => '', 'type' => 'quote', 'class' => '');
          $widgets[] = array('name' => 'quote\CheckoutBtn', 'title' => CHECKOUT_BUTTON, 'description' => 'Quote', 'type' => 'quote', 'class' => 'checkout-button');
      }

      if ($params['type'] == 'sample'){
          $widgets[] = array('name' => 'sample\Products', 'title' => TEXT_SAMPLE_PRODUCTS, 'description' => 'Sample', 'type' => 'sample', 'class' => 'products');
          $widgets[] = array('name' => 'cart\CartTabs', 'title' => 'Cart Tabs', 'description' => '', 'type' => 'sample', 'class' => '');
          $widgets[] = array('name' => 'sample\CheckoutBtn', 'title' => TEXT_SAMPLE_CHECKOUT_BUTTON, 'description' => 'Sample', 'type' => 'sample', 'class' => 'checkout-button');
      }

      if ($params['type'] == 'wishlist'){
          $widgets[] = array('name' => 'title', 'title' => TEXT_WISHLIST, 'description' => '', 'type' => 'wishlist');
          $widgets[] = array('name' => 'cart\CartTabs', 'title' => 'Cart Tabs', 'description' => '', 'type' => 'wishlist', 'class' => '');
          $widgets[] = array('name' => 'account\Wishlist', 'title' => TEXT_WISHLIST, 'description' => '', 'type' => 'wishlist', 'class' => '');
      }

    if ($params['type'] == 'checkout'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_CHECKOUT, 'description' => '', 'type' => 'checkout');
      $widgets[] = array('name' => 'checkout\ContinueBtn', 'title' => CONTINUE_BUTTON, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\Shipping', 'title' => TEXT_CHOOSE_SHIPPING_METHOD, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\ShippingAddress', 'title' => ENTRY_SHIPPING_ADDRESS, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\BillingAddress', 'title' => TEXT_BILLING_ADDRESS, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\PaymentMethod', 'title' => TEXT_SELECT_PAYMENT_METHOD, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\CreditAmount', 'title' => CREDIT_AMOUNT, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\BonusPoints', 'title' => TEXT_BONUS_POINTS, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\ContactInformation', 'title' => CATEGORY_CONTACT, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\Comments', 'title' => TABLE_HEADING_COMMENTS, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'checkout\Totals', 'title' => TEXT_TOTALS, 'description' => '', 'type' => 'checkout', 'class' => '');
      $widgets[] = array('name' => 'cart\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'checkout', 'class' => '');
        $widgets[] = array('name' => 'quote\Products', 'title' => 'Quote Products', 'description' => '', 'type' => 'checkout', 'class' => 'products');
        $widgets[] = array('name' => 'sample\Products', 'title' => 'Sample Products', 'description' => '', 'type' => 'checkout', 'class' => 'products');
        $widgets[] = array('name' => 'checkout\CreateAccount', 'title' => 'Create Account', 'description' => '', 'type' => 'checkout', 'class' => '');
        $widgets[] = array('name' => 'checkout\ShippingChoice', 'title' => 'Shipping Choice', 'description' => '', 'type' => 'checkout', 'class' => '');
        $widgets[] = array('name' => 'checkout\Terms', 'title' => TEXT_TERMS_CONDITIONS, 'description' => '', 'type' => 'checkout', 'class' => '');
    }

    if ($params['type'] == 'confirmation'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_CONFIRMATION, 'description' => '', 'type' => 'confirmation');
      $widgets[] = array('name' => 'checkout\ConfirmBtn', 'title' => TEXT_CONFIRMATION_BUTTON, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\ShippingConfirm', 'title' => TEXT_CHOOSE_SHIPPING_METHOD, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\ShippingAddressConfirm', 'title' => ENTRY_SHIPPING_ADDRESS, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\BillingAddressConfirm', 'title' => TEXT_BILLING_ADDRESS, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\PaymentMethodConfirm', 'title' => TEXT_SELECT_PAYMENT_METHOD, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\CommentsConfirm', 'title' => TABLE_HEADING_COMMENTS, 'description' => '', 'type' => 'confirmation', 'class' => '');
        $widgets[] = array('name' => 'checkout\Totals', 'title' => TEXT_TOTALS, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'cart\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'confirmation', 'class' => '');
      $widgets[] = array('name' => 'checkout\EditBtn', 'title' => TEXT_EDIT_LINK, 'description' => '', 'type' => 'confirmation', 'class' => '');
        $widgets[] = array('name' => 'quote\Products', 'title' => 'Quote Products', 'description' => '', 'type' => 'confirmation', 'class' => 'products');
        $widgets[] = array('name' => 'sample\Products', 'title' => 'Sample Products', 'description' => '', 'type' => 'confirmation', 'class' => 'products');
        $widgets[] = array('name' => 'checkout\ContactConfirm', 'title' => 'Contact info', 'description' => '', 'type' => 'confirmation', 'class' => '');
    }

    if ($params['type'] == 'success'){
      $widgets[] = array('name' => 'title', 'title' => CHECKOUT_SUCCESS_WIDGETS, 'description' => '', 'type' => 'success');
      $widgets[] = array('name' => 'success\ContinueBtn', 'title' => CONTINUE_BUTTON, 'description' => '', 'type' => 'success', 'class' => 'continue-button');
      $widgets[] = array('name' => 'success\PrintBtn', 'title' => PRINT_BUTTON, 'description' => '', 'type' => 'success', 'class' => 'print-button');
      $widgets[] = array('name' => 'success\Download', 'title' => IMAGE_DOWNLOAD, 'description' => '', 'type' => 'success', 'class' => 'download-button');
    }

    if ($params['type'] == 'contact'){
      $widgets[] = array('name' => 'title', 'title' => CONTACT_PAGE_WIDGETS, 'description' => '', 'type' => 'contact');
      $widgets[] = array('name' => 'contact\ContactForm', 'title' => CONTACT_FORM, 'description' => '', 'type' => 'contact', 'class' => 'contact-form');
      $widgets[] = array('name' => 'contact\Map', 'title' => TEXT_MAP, 'description' => '', 'type' => 'contact', 'class' => 'map');
      $widgets[] = array('name' => 'contact\Contacts', 'title' => TEXT_CONTACTS, 'description' => '', 'type' => 'contact', 'class' => 'contacts');
      $widgets[] = array('name' => 'contact\StreetView', 'title' => GOOGLE_STREET_VIEW, 'description' => '', 'type' => 'contact', 'class' => 'street-view');
        $widgets[] = array('name' => 'info\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'contact', 'class' => 'title');
    }

    if ($params['type'] == 'email'){
      $widgets[] = array('name' => 'title', 'title' => TABLE_HEADING_EMAIL_TEMPLATES, 'description' => '', 'type' => 'email');
      $widgets[] = array('name' => 'email\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'email', 'class' => 'title');
      $widgets[] = array('name' => 'email\Date', 'title' => TEXT_CURRENT_DATE, 'description' => '', 'type' => 'email', 'class' => 'date');
      $widgets[] = array('name' => 'email\Content', 'title' => TEXT_CONTENT, 'description' => '', 'type' => 'email', 'class' => 'content');
      $widgets[] = array('name' => 'email\BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'email', 'class' => 'block-box');
      $widgets[] = array('name' => 'Banner', 'title' => TEXT_BANNER, 'description' => '', 'type' => 'email', 'class' => 'banner');
      $widgets[] = array('name' => 'email\Logo', 'title' => TEXT_LOGO, 'description' => '', 'type' => 'email', 'class' => 'logo');
      $widgets[] = array('name' => 'email\Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'email', 'class' => 'image');
      $widgets[] = array('name' => 'Text', 'title' => TEXT_TEXT, 'description' => '', 'type' => 'email', 'class' => 'text');
      $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'email', 'class' => 'import');
      $widgets[] = array('name' => 'Copyright', 'title' => COPYRIGHT, 'description' => '', 'type' => 'email', 'class' => 'copyright');
    }

      if ($params['type'] == 'invoice'){
          $widgets[] = array('name' => 'title', 'title' => INVOICE_TEMPLATE, 'description' => '', 'type' => 'invoice');
      }
      if ($params['type'] == 'creditnote'|| $params['type'] == 'orders'){
          $widgets[] = array('name' => 'title', 'title' => TABLE_HEADING_ORDER, 'description' => '', 'type' => 'invoice');
      }
    if ($params['type'] == 'invoice' || $params['type'] == 'creditnote' || $params['type'] == 'orders'){
      $widgets[] = array('name' => 'BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'invoice', 'class' => 'block-box');
      $widgets[] = array('name' => 'Logo', 'title' => TEXT_LOGO, 'description' => '', 'type' => 'invoice', 'class' => 'logo');
      $widgets[] = array('name' => 'email\Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'invoice', 'class' => 'image');
      $widgets[] = array('name' => 'Text', 'title' => TEXT_TEXT, 'description' => '', 'type' => 'invoice', 'class' => 'text');
      $widgets[] = array('name' => 'invoice\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'invoice', 'class' => 'products');
      $widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'invoice', 'class' => 'store-address');
      $widgets[] = array('name' => 'invoice\StorePhone', 'title' => TEXT_STORE_PHONE, 'description' => '', 'type' => 'invoice', 'class' => 'store-phone');
      $widgets[] = array('name' => 'invoice\StoreEmail', 'title' => TEXT_STORE_EMAIL, 'description' => '', 'type' => 'invoice', 'class' => 'store-email');
      $widgets[] = array('name' => 'invoice\StoreSite', 'title' => TEXT_STORE_SITE, 'description' => '', 'type' => 'invoice', 'class' => 'store-site');
      $widgets[] = array('name' => 'invoice\ShippingAddress', 'title' => ENTRY_SHIPPING_ADDRESS, 'description' => '', 'type' => 'invoice', 'class' => 'shipping-address');
      $widgets[] = array('name' => 'invoice\BillingAddress', 'title' => TEXT_BILLING_ADDRESS, 'description' => '', 'type' => 'invoice', 'class' => 'shipping-address');
      $widgets[] = array('name' => 'invoice\ShippingMethod', 'title' => TEXT_CHOOSE_SHIPPING_METHOD, 'description' => '', 'type' => 'invoice', 'class' => 'shipping-method');
      $widgets[] = array('name' => 'invoice\AddressQrcode', 'title' => ADDRESS_QRCODE, 'description' => '', 'type' => 'invoice', 'class' => 'address-qrcode');
      $widgets[] = array('name' => 'invoice\OrderBarcode', 'title' => ORDER_BARCODE, 'description' => '', 'type' => 'invoice', 'class' => 'order-barcode');
      $widgets[] = array('name' => 'invoice\CustomerName', 'title' => TEXT_CUSTOMER_NAME, 'description' => '', 'type' => 'invoice', 'class' => 'customer-name');
      $widgets[] = array('name' => 'invoice\CustomerEmail', 'title' => TEXT_CUSTOMER_EMAIL, 'description' => '', 'type' => 'invoice', 'class' => 'customer-email');
      $widgets[] = array('name' => 'invoice\CustomerPhone', 'title' => TEXT_CUSTOMER_PHONE, 'description' => '', 'type' => 'invoice', 'class' => 'customer-phone');
      $widgets[] = array('name' => 'invoice\Totals', 'title' => TRXT_TOTALS, 'description' => '', 'type' => 'invoice', 'class' => 'totals');
      $widgets[] = array('name' => 'invoice\OrderId', 'title' => TEXT_ORDER_ID, 'description' => '', 'type' => 'invoice', 'class' => 'order-id');
      $widgets[] = array('name' => 'invoice\InvoiceId', 'title' => TEXT_INVOICE_PREFIX."_".TEXT_CREDIT_NOTE_PREFIX, 'description' => '', 'type' => 'invoice', 'class' => 'invoice-id');
      $widgets[] = array('name' => 'invoice\PaymentDate', 'title' => TEXT_PAYMENT_DATE, 'description' => '', 'type' => 'invoice', 'class' => 'payment-date');
      $widgets[] = array('name' => 'invoice\PaymentMethod', 'title' => TEXT_SELECT_PAYMENT_METHOD, 'description' => '', 'type' => 'invoice', 'class' => 'payment-method');
      $widgets[] = array('name' => 'invoice\PaidMark', 'title' => TEXT_PAID_MARK, 'description' => '', 'type' => 'invoice', 'class' => 'paid-mark');
      $widgets[] = array('name' => 'invoice\UnpaidMark', 'title' => TEXT_UNPAID_MARK, 'description' => '', 'type' => 'invoice', 'class' => 'unpaid-mark');
      $widgets[] = array('name' => 'invoice\Container', 'title' => TEXT_CONTAINER, 'description' => '', 'type' => 'invoice', 'class' => 'container');
      $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'invoice', 'class' => 'import');
      $widgets[] = array('name' => 'Copyright', 'title' => COPYRIGHT, 'description' => '', 'type' => 'invoice', 'class' => 'copyright');
      $widgets[] = array('name' => 'invoice\IpAddress', 'title' => TEXT_IP_ADDRESS, 'description' => '', 'type' => 'invoice', 'class' => '');
      $widgets[] = array('name' => 'invoice\TotalProductsQty', 'title' => TEXT_TOTAL_PRODUCTS_QTY, 'description' => '', 'type' => 'invoice', 'class' => '');
      $widgets[] = array('name' => 'invoice\TotalProductsDelivered', 'title' => 'Total Products Delivered', 'description' => '', 'type' => 'invoice', 'class' => '');
      $widgets[] = array('name' => 'invoice\TotalProductsCanceled', 'title' => 'Total Products Canceled', 'description' => '', 'type' => 'invoice', 'class' => '');
      $widgets[] = array('name' => 'invoice\OrderType', 'title' => TEXT_ORDER_TYPE, 'description' => '', 'type' => 'invoice', 'class' => '');
        $widgets[] = array('name' => 'invoice\Transactions', 'title' => TEXT_TRANSACTIONS, 'description' => '', 'type' => 'invoice', 'class' => '');
        $widgets[] = array('name' => 'invoice\PurchaseOrderNo', 'title' => 'Purchase Order Number', 'description' => '', 'type' => 'invoice', 'class' => '');
        $widgets[] = array('name' => 'invoice\Comments', 'title' => 'Comments', 'description' => '', 'type' => 'invoice', 'class' => '');
        $widgets[] = array('name' => 'invoice\Currency', 'title' => 'Currency', 'description' => '', 'type' => 'invoice', 'class' => '');
    }

    if ($params['type'] == 'packingslip'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_PACKINGSLIP, 'description' => '', 'type' => 'packingslip');
      $widgets[] = array('name' => 'BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'packingslip', 'class' => 'block-box');
      $widgets[] = array('name' => 'email\Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'packingslip', 'class' => 'image');
      $widgets[] = array('name' => 'Text', 'title' => TEXT_TEXT, 'description' => '', 'type' => 'packingslip', 'class' => 'text');
      $widgets[] = array('name' => 'packingslip\Products', 'title' => TABLE_HEADING_PRODUCTS, 'description' => '', 'type' => 'packingslip', 'class' => 'products');
      $widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'packingslip', 'class' => 'store-address');
      $widgets[] = array('name' => 'invoice\ShippingMethod', 'title' => TEXT_CHOOSE_SHIPPING_METHOD, 'description' => '', 'type' => 'packingslip', 'class' => 'shipping-method');
      $widgets[] = array('name' => 'invoice\StorePhone', 'title' => TEXT_STORE_PHONE, 'description' => '', 'type' => 'packingslip', 'class' => 'store-phone');
      $widgets[] = array('name' => 'invoice\StoreEmail', 'title' => TEXT_STORE_EMAIL, 'description' => '', 'type' => 'packingslip', 'class' => 'store-email');
      $widgets[] = array('name' => 'invoice\StoreSite', 'title' => TEXT_STORE_SITE, 'description' => '', 'type' => 'packingslip', 'class' => 'store-site');
      $widgets[] = array('name' => 'invoice\ShippingAddress', 'title' => ENTRY_SHIPPING_ADDRESS, 'description' => '', 'type' => 'packingslip', 'class' => 'shipping-address');
      $widgets[] = array('name' => 'invoice\BillingAddress', 'title' => TEXT_BILLING_ADDRESS, 'description' => '', 'type' => 'packingslip', 'class' => 'shipping-address');
      $widgets[] = array('name' => 'invoice\AddressQrcode', 'title' => ADDRESS_QRCODE, 'description' => '', 'type' => 'packingslip', 'class' => 'address-qrcode');
      $widgets[] = array('name' => 'invoice\OrderBarcode', 'title' => ORDER_BARCODE, 'description' => '', 'type' => 'packingslip', 'class' => 'order-barcode');
      $widgets[] = array('name' => 'invoice\CustomerName', 'title' => TEXT_CUSTOMER_NAME, 'description' => '', 'type' => 'packingslip', 'class' => 'customer-name');
      $widgets[] = array('name' => 'invoice\CustomerEmail', 'title' => TEXT_CUSTOMER_EMAIL, 'description' => '', 'type' => 'packingslip', 'class' => 'customer-email');
      $widgets[] = array('name' => 'invoice\CustomerPhone', 'title' => TEXT_CUSTOMER_PHONE, 'description' => '', 'type' => 'packingslip', 'class' => 'customer-phone');
      $widgets[] = array('name' => 'invoice\OrderId', 'title' => TEXT_ORDER_ID, 'description' => '', 'type' => 'packingslip', 'class' => 'order-id');
      $widgets[] = array('name' => 'invoice\PaymentMethod', 'title' => TEXT_SELECT_PAYMENT_METHOD, 'description' => '', 'type' => 'packingslip', 'class' => 'payment-method');
      $widgets[] = array('name' => 'invoice\Container', 'title' => TEXT_CONTAINER, 'description' => '', 'type' => 'packingslip', 'class' => 'container');
      $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'packingslip', 'class' => 'import');
      $widgets[] = array('name' => 'Copyright', 'title' => COPYRIGHT, 'description' => '', 'type' => 'packingslip', 'class' => 'copyright');
        $widgets[] = array('name' => 'invoice\IpAddress', 'title' => TEXT_IP_ADDRESS, 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\TotalProductsQty', 'title' => TEXT_TOTAL_PRODUCTS_QTY, 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\TotalProductsDelivered', 'title' => 'Total Products Delivered', 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\TotalProductsCanceled', 'title' => 'Total Products Canceled', 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\OrderType', 'title' => TEXT_ORDER_TYPE, 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\Transactions', 'title' => TEXT_TRANSACTIONS, 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\PurchaseOrderNo', 'title' => 'Purchase Order Number', 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\Comments', 'title' => 'Comments', 'description' => '', 'type' => 'packingslip', 'class' => '');
        $widgets[] = array('name' => 'invoice\Currency', 'title' => 'Currency', 'description' => '', 'type' => 'packingslip', 'class' => '');
    }

    if ($params['type'] == 'gift'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_GIFT_CARD, 'description' => '', 'type' => 'gift');
      $widgets[] = array('name' => 'gift\Form', 'title' => TEXT_FORM, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\AmountView', 'title' => AMOUNT_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\MessageView', 'title' => MESSAGE_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\CodeView', 'title' => CODE_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'gift', 'class' => 'store-address');
      $widgets[] = array('name' => 'invoice\StorePhone', 'title' => TEXT_STORE_PHONE, 'description' => '', 'type' => 'gift', 'class' => 'store-phone');
      $widgets[] = array('name' => 'invoice\StoreEmail', 'title' => TEXT_STORE_EMAIL, 'description' => '', 'type' => 'gift', 'class' => 'store-email');
      $widgets[] = array('name' => 'invoice\StoreSite', 'title' => TEXT_STORE_SITE, 'description' => '', 'type' => 'gift', 'class' => 'store-site');
        $widgets[] = array('name' => 'info\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'gift', 'class' => 'title');
        $widgets[] = array('name' => 'gift\Card', 'title' => TEXT_GIFT_CARD, 'description' => '', 'type' => 'gift', 'class' => 'title');
    }

    if ($params['type'] == 'gift_card'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_GIFT_CARD, 'description' => '', 'type' => 'gift');
      $widgets[] = array('name' => 'gift\Form', 'title' => TEXT_FORM, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\AmountView', 'title' => AMOUNT_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\MessageView', 'title' => MESSAGE_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'gift\CodeView', 'title' => CODE_VIEW, 'description' => '', 'type' => 'gift', 'class' => 'contact-form');
      $widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'gift', 'class' => 'store-address');
      $widgets[] = array('name' => 'invoice\StorePhone', 'title' => TEXT_STORE_PHONE, 'description' => '', 'type' => 'gift', 'class' => 'store-phone');
      $widgets[] = array('name' => 'invoice\StoreEmail', 'title' => TEXT_STORE_EMAIL, 'description' => '', 'type' => 'gift', 'class' => 'store-email');
      $widgets[] = array('name' => 'invoice\StoreSite', 'title' => TEXT_STORE_SITE, 'description' => '', 'type' => 'gift', 'class' => 'store-site');
        $widgets[] = array('name' => 'info\Title', 'title' => TEXT_TITLE_, 'description' => '', 'type' => 'gift', 'class' => 'title');
    }

    if ($params['type'] != 'email' && $params['type'] != 'invoice' && $params['type'] != 'packingslip' && $params['type'] != 'pdf' && $params['type'] != 'orders') {
      $widgets[] = array('name' => 'title', 'title' => GENERAL_WIDGETS, 'description' => '', 'type' => 'general');
      $widgets[] = array('name' => 'BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'general', 'class' => 'block-box');
      $widgets[] = array('name' => 'Tabs', 'title' => TEXT_TABS, 'description' => '', 'type' => 'general', 'class' => 'tabs');
      $widgets[] = array('name' => 'Brands', 'title' => TEXT_BRANDS, 'description' => '', 'type' => 'general', 'class' => 'brands');
      $widgets[] = array('name' => 'Bestsellers', 'title' => TEXT_BESTSELLERS, 'description' => '', 'type' => 'general', 'class' => 'bestsellers');
      $widgets[] = array('name' => 'Banner', 'title' => TEXT_BANNER, 'description' => '', 'type' => 'general', 'class' => 'banner');
      $widgets[] = array('name' => 'SpecialsProducts', 'title' => TEXT_SPECIALS_PRODUCTS, 'description' => '', 'type' => 'general', 'class' => 'specials-products');
      $widgets[] = array('name' => 'FeaturedProducts', 'title' => BOX_CATALOG_FEATURED, 'description' => '', 'type' => 'general', 'class' => 'featured-products');
      $widgets[] = array('name' => 'NewProducts', 'title' => TEXT_NEW_PRODUCTS, 'description' => '', 'type' => 'general', 'class' => 'new-products');
      $widgets[] = array('name' => 'NewProductsWithParams', 'title' => TEXT_NEW_PRODUCTS_PARAMS, 'description' => '', 'type' => 'general', 'class' => 'new-products-params');
      $widgets[] = array('name' => 'ViewedProducts', 'title' => VIEWED_PRODUCTS, 'description' => '', 'type' => 'general', 'class' => 'viewed-products');
      $widgets[] = array('name' => 'Logo', 'title' => TEXT_LOGO, 'description' => '', 'type' => 'general', 'class' => 'logo');
      $widgets[] = array('name' => 'Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'general', 'class' => 'image');
      $widgets[] = array('name' => 'Video', 'title' => TEXT_VIDEO, 'description' => '', 'type' => 'general', 'class' => 'video');
      $widgets[] = array('name' => 'Text', 'title' => TEXT_TEXT, 'description' => '', 'type' => 'general', 'class' => 'text');
      $widgets[] = array('name' => 'Heading', 'title' => TEXT_SEO_HEADING, 'description' => '', 'type' => 'general', 'class' => '');
      $widgets[] = array('name' => 'InfoPage', 'title' => INFORMATION_PAGES, 'description' => '', 'type' => 'general', 'class' => 'text');
      $widgets[] = array('name' => 'Reviews', 'title' => TEXT_REVIEWS, 'description' => '', 'type' => 'general', 'class' => 'reviews');
      $widgets[] = array('name' => 'Menu', 'title' => TEXT_MENU, 'description' => '', 'type' => 'general', 'class' => 'menu');
      $widgets[] = array('name' => 'Languages', 'title' => TEXT_LANGUAGES_, 'description' => '', 'type' => 'general', 'class' => 'languages');
      $widgets[] = array('name' => 'Currencies', 'title' => TEXT_CURRENCIES, 'description' => '', 'type' => 'general', 'class' => 'currencies');
      $widgets[] = array('name' => 'Search', 'title' => TEXT_SEARCH, 'description' => '', 'type' => 'general', 'class' => 'search');
      $widgets[] = array('name' => 'Cart', 'title' => TEXT_CART, 'description' => '', 'type' => 'general', 'class' => 'cart');
      $widgets[] = array('name' => 'Breadcrumb', 'title' => TEXT_BREADCRUMB, 'description' => '', 'type' => 'general', 'class' => 'breadcrumb');
      $widgets[] = array('name' => 'Compare', 'title' => TEXT_COMPARE, 'description' => '', 'type' => 'general', 'class' => 'compare');
      //$widgets[] = array('name' => 'Address', 'title' => 'Store Address', 'description' => '', 'type' => 'general', 'class' => 'contacts');
      //$widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'general', 'class' => 'store-address');
      $widgets[] = array('name' => 'Copyright', 'title' => COPYRIGHT, 'description' => '', 'type' => 'general', 'class' => 'copyright');
      $widgets[] = array('name' => 'Account', 'title' => TEXT_ACCOUNT, 'description' => '', 'type' => 'general', 'class' => 'account');
      $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'general', 'class' => 'import');

        if (\frontend\design\Info::hasBlog()){
            $widgets[] = array('name' => 'BlogSidebar', 'title' => TEXT_BLOG_SIDEBAR, 'description' => '', 'type' => 'general', 'class' => 'menu');
            $widgets[] = array('name' => 'BlogContent', 'title' => TEXT_BLOG_CONTENT, 'description' => '', 'type' => 'general', 'class' => 'content');
        }

      $widgets[] = array('name' => 'Subscribers', 'title' => BOX_CUSTOMERS_SUBSCRIBERS, 'description' => '', 'type' => 'general', 'class' => 'account');
      //$widgets[] = array('name' => 'Subscribe', 'title' => 'Subscribe', 'description' => '', 'type' => 'general', 'class' => 'account');
      $widgets[] = array('name' => 'Quote', 'title' => TEXT_QUOTE_CART, 'description' => '', 'type' => 'general', 'class' => 'quote');
      $widgets[] = array('name' => 'StoreName', 'title' => TEXT_STORE_NAME, 'description' => '', 'type' => 'general', 'class' => '');
      $widgets[] = array('name' => 'WidgetsAria', 'title' => 'Widgets Aria', 'description' => '', 'type' => 'general', 'class' => '');
      $widgets[] = array('name' => 'GoogleReviews', 'title' => TEXT_GOOGLE_REVIEWS, 'description' => '', 'type' => 'general', 'class' => 'contact-form');
      $widgets[] = array('name' => 'CustomerData', 'title' => TEXT_CUSTOMER_DATA, 'description' => '', 'type' => 'general', 'class' => '');
      $widgets[] = array('name' => 'ProductElement', 'title' => TEXT_PRODUCT_ELEMENT, 'description' => '', 'type' => 'general', 'class' => '');

      if (\common\helpers\Acl::checkExtension('Samples', 'allowed')) {
        $widgets[] = array('name' => 'Sample', 'title' => TEXT_SAMPLE_CART, 'description' => '', 'type' => 'general', 'class' => 'sample');
      }
      if (\common\helpers\Acl::checkExtension('Trustpilot', 'allowed')) {
        $client = new \common\extensions\Trustpilot\Trustpilot();
        if ($client->anyAPIKeyExists()){
          $widgets[] = array('name' => 'TrustPilotReviews', 'title' => TEXT_TRUSTPILOT_REVIEWS, 'description' => '', 'type' => 'general', 'class' => 'content');
        }
      }
        $widgets[] = array('name' => 'ImageMap', 'title' => 'Image Map', 'description' => '', 'type' => 'general', 'class' => '');
        $widgets[] = array('name' => 'SocialLinks', 'title' => 'Social Links', 'description' => '', 'type' => 'general', 'class' => '');
        $widgets[] = array('name' => 'FiltersSimple', 'title' => FILTERS_SIMPLE, 'description' => '', 'type' => 'general', 'class' => '');
        //$widgets[] = array('name' => 'CartPopUp', 'title' => 'CartPopUp', 'description' => '', 'type' => 'general', 'class' => '');

        $widgets[] = array('name' => 'DeliveryLocation\ListByPage', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_LIST, 'description' => '', 'type' => 'general', 'class' => 'delivery-location-list');
    }


      if ($params['type'] == 'pdf') {
          $widgets[] = array('name' => 'BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'pdf', 'class' => 'block-box');
          $widgets[] = array('name' => 'Logo', 'title' => TEXT_LOGO, 'description' => '', 'type' => 'pdf', 'class' => 'logo');
          $widgets[] = array('name' => 'Image', 'title' => TEXT_IMAGE_, 'description' => '', 'type' => 'pdf', 'class' => 'image');
          $widgets[] = array('name' => 'Text', 'title' => TEXT_TEXT, 'description' => '', 'type' => 'pdf', 'class' => 'text');
          $widgets[] = array('name' => 'invoice\StoreAddress', 'title' => TEXT_STORE_ADDRESS, 'description' => '', 'type' => 'pdf', 'class' => 'store-address');
          //$widgets[] = array('name' => 'Copyright', 'title' => COPYRIGHT, 'description' => '', 'type' => 'pdf', 'class' => 'copyright');
          $widgets[] = array('name' => 'invoice\StorePhone', 'title' => TEXT_STORE_PHONE, 'description' => '', 'type' => 'pdf', 'class' => 'store-phone');
          $widgets[] = array('name' => 'invoice\StoreEmail', 'title' => TEXT_STORE_EMAIL, 'description' => '', 'type' => 'pdf', 'class' => 'store-email');
          $widgets[] = array('name' => 'invoice\StoreSite', 'title' => TEXT_STORE_SITE, 'description' => '', 'type' => 'pdf', 'class' => 'store-site');
          $widgets[] = array('name' => 'invoice\Container', 'title' => TEXT_CONTAINER, 'description' => '', 'type' => 'pdf', 'class' => 'container');
          $widgets[] = array('name' => 'pdf\ProductElement', 'title' => TEXT_PRODUCT_ELEMENT, 'description' => '', 'type' => 'pdf', 'class' => '');
          $widgets[] = array('name' => 'pdf\CategoryName', 'title' => CATEGORY_NAME, 'description' => '', 'type' => 'pdf', 'class' => '');
          $widgets[] = array('name' => 'pdf\CategoryImage', 'title' => TEXT_CATEGORY_IMAGE, 'description' => '', 'type' => 'pdf', 'class' => '');
          $widgets[] = array('name' => 'pdf\CategoryDescription', 'title' => TEXT_CATEGORY_DESCRIPTION, 'description' => '', 'type' => 'pdf', 'class' => '');
          $widgets[] = array('name' => 'pdf\PageNumber', 'title' => TEXT_PAGE_NUMBER, 'description' => '', 'type' => 'pdf', 'class' => '');
          $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'pdf', 'class' => 'import');
      }


    if ($params['type'] == 'delivery-location-default'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_DELIVERY_LOCATION_INDEX, 'description' => '', 'type' => 'delivery-location-default');
      $widgets[] = array('name' => 'DeliveryLocation\TopLocationList', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_TOP_LOCATION_LIST, 'description' => '', 'type' => 'delivery-location-default', 'class' => 'delivery-location-featured-list');
    }

    if ($params['type'] == 'delivery-location'){
      $widgets[] = array('name' => 'title', 'title' => TEXT_DELIVERY_LOCATION, 'description' => '', 'type' => 'delivery-location');
      $widgets[] = array('name' => 'DeliveryLocation\LocationTitle', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_TITLE, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-title');
      $widgets[] = array('name' => 'DeliveryLocation\LocationHeadlineImage', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_HEADLINE_IMAGE, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-headline-image');
      $widgets[] = array('name' => 'DeliveryLocation\LocationDescription', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_DESC, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-description');
      $widgets[] = array('name' => 'DeliveryLocation\LocationLongDescription', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_DESC2, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-long-description');
      $widgets[] = array('name' => 'DeliveryLocation\FeaturedList', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_FEATURED_LIST, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-featured-list');
      $widgets[] = array('name' => 'DeliveryLocation\LocationList', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_LIST, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-list');
      $widgets[] = array('name' => 'DeliveryLocation\LocationProducts', 'title' => TEXT_WIDGET_DELIVERY_LOCATION_PRODUCTS, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-products');
      $widgets[] = array('name' => 'DeliveryLocation\CrossPages', 'title' => TEXT_CROSS_PAGES, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-products');
      $widgets[] = array('name' => 'DeliveryLocation\Date', 'title' => TABLE_HEADING_DATE_ADDED, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-products');
      $widgets[] = array('name' => 'DeliveryLocation\ProductCategories', 'title' => DATE_PRODUCT_CATEGORIES, 'description' => '', 'type' => 'delivery-location', 'class' => 'delivery-location-products');
    }

      if ($params['type'] == 'account'){
          $widgets[] = array('name' => 'title', 'title' => TEXT_ACCOUNT, 'description' => '', 'type' => 'account');
          $widgets[] = array('name' => 'account\AccountLink', 'title' => ACCOUNT_LINK, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\LastOrder', 'title' => DATE_LAST_ORDERED, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderCount', 'title' => ORDER_COUNT, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\TotalOrdered', 'title' => TOTAL_ORDERED, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\CreditAmount', 'title' => CREDIT_AMOUNT, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\CreditAmountHistory', 'title' => CREDIT_AMOUNT_HISTORY, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\PointsEarnt', 'title' => TEXT_POINTS_EARNT, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\PointsEarntHistory', 'title' => POINTS_EARNT_HISTORY, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\ApplyCertificate', 'title' => APPLY_CERTIFICATE_FORM, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\CustomerData', 'title' => TEXT_CUSTOMER_DATA, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\AccountEdit', 'title' => EDIT_MAIN_DETAILS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\SwitchNewsletter', 'title' => TEXT_SWITCH_NEWSLETTER, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\ChangePassword', 'title' => TEXT_CHANGE_PASSWORD, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\PrimaryAddress', 'title' => TEXT_PRIMARY_ADDRESS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\AddressBook', 'title' => TEXT_ADDRESS_BOOK, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\EditAddress', 'title' => TEXT_EDIT_ADDRESS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\Tokens', 'title' => TEXT_TOKENS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrdersHistory', 'title' => TEXT_ORDERS_HISTORY, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderData', 'title' => TEXT_ORDER_DATA, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderProducts', 'title' => ORDER_PRODUCTS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderSubTotals', 'title' => ORDER_SUBTOTALS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderHistory', 'title' => ORDER_HISTORY, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderInvoiceButton', 'title' => ORDER_INVOICE_BUTTON, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderNotPaid', 'title' => ORDER_NOT_PAID, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderPayButton', 'title' => ORDER_PAY_BUTTON, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderReorderButton', 'title' => REORDER_BUTTON, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderDownload', 'title' => IMAGE_DOWNLOAD, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderCancelAndReorder', 'title' => CANCEL_AND_REORDER_BUTTON, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\Wishlist', 'title' => TEXT_WISHLIST, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\PersonalCatalog', 'title' => TEXT_PERSONAL_CATALOG, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\Reviews', 'title' => BOX_CATALOG_REVIEWS, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderHeading', 'title' => TEXT_ORDER_HEADING, 'description' => '', 'type' => 'account', 'class' => '');
          $widgets[] = array('name' => 'account\OrderTracking', 'title' => TEXT_ORDER_TRACKING, 'description' => '', 'type' => 'account', 'class' => '');
      }

      if ($params['type'] == 'trade_form' || $params['type'] == 'trade_form_pdf'){
          $widgets[] = array('name' => 'account\CustomerAdditionalField', 'title' => 'CustomerAdditionalField', 'description' => '', 'type' => 'trade_form', 'class' => '');
          $widgets[] = array('name' => 'account\BackButton', 'title' => 'BackButton', 'description' => '', 'type' => 'trade_form', 'class' => '');
          $widgets[] = array('name' => 'account\SaveButton', 'title' => 'SaveButton', 'description' => '', 'type' => 'trade_form', 'class' => '');
          $widgets[] = array('name' => 'account\PdfButton', 'title' => 'PdfButton', 'description' => '', 'type' => 'trade_form', 'class' => '');
          $widgets[] = array('name' => 'account\AddressesList', 'title' => 'AddressesList', 'description' => '', 'type' => 'trade_form', 'class' => '');
      }
      if ($params['type'] == 'trade_form_pdf'){
          $widgets[] = array('name' => 'account\CombinedField', 'title' => 'CombinedField', 'description' => '', 'type' => 'trade_form', 'class' => '');
      }

      if ($params['type'] == 'login') {
          $widgets[] = array('name' => 'title', 'title' => 'Login', 'description' => '', 'type' => 'login');
          $widgets[] = array('name' => 'login\Returning', 'title' => 'Returning customer', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'login\Register', 'title' => 'Register', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'login\Socials', 'title' => 'Socials login', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'login\Guest', 'title' => 'Guest login', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'quote\FastOrder', 'title' => 'Fast Order', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'checkout\GuestBtn', 'title' => 'Guest Button', 'description' => '', 'type' => 'login', 'class' => '');
          $widgets[] = array('name' => 'checkout\CreateBtn', 'title' => 'Create Button', 'description' => '', 'type' => 'login', 'class' => '');
      }

      if ($params['type'] == 'password_forgotten') {
          $widgets[] = array('name' => 'login\PasswordForgotten', 'title' => 'Password Forgotten', 'description' => '', 'type' => 'login', 'class' => '');
      }

    if ($params['type'] == 'index'){
      $widgets[] = array('name' => 'title', 'title' => HOME_PAGE_WIDGETS, 'description' => '', 'type' => 'index');
      $widgets[] = array('name' => 'TopCategories', 'title' => TEXT_CATEGORIES, 'description' => '', 'type' => 'index', 'class' => 'categories');
      $widgets[] = array('name' => 'login\Returning', 'title' => 'Returning customer', 'description' => '', 'type' => 'index', 'class' => 'categories');
      $widgets[] = array('name' => 'login\Register', 'title' => 'Register', 'description' => '', 'type' => 'index', 'class' => 'categories');
      $widgets[] = array('name' => 'login\Enquire', 'title' => 'Enquire', 'description' => '', 'type' => 'index', 'class' => 'categories');
    }

      if ($params['type'] == 'promotions'){
          $widgets[] = array('name' => 'promotions\PromoList', 'title' => BOX_PROMOTIONS, 'description' => '', 'type' => 'promotions', 'class' => '');
      }

      if ($params['type'] == 'sitemap'){
          $widgets[] = array('name' => 'sitemap\Categories', 'title' => 'Categories', 'description' => '', 'type' => 'sitemap', 'class' => '');
          $widgets[] = array('name' => 'sitemap\InfoPages', 'title' => 'Info Pages', 'description' => '', 'type' => 'sitemap', 'class' => '');
          $widgets[] = array('name' => 'sitemap\DeliveryLocation', 'title' => 'Delivery Locations', 'description' => '', 'type' => 'sitemap', 'class' => '');
      }

      if ($params['type'] == 'reviews'){
          $widgets[] = array('name' => 'reviews\Heading', 'title' => 'Heading', 'description' => '', 'type' => 'reviews', 'class' => '');
          $widgets[] = array('name' => 'reviews\Content', 'title' => 'Content', 'description' => '', 'type' => 'reviews', 'class' => '');
      }

      if ($params['type'] == 'productListing'){
          $widgets[] = array('name' => 'title', 'title' => TEXT_LISTING_ITEM, 'description' => '', 'type' => 'productListing');
          $widgets[] = array('name' => 'productListing\name', 'title' => TEXT_PRODUCT_NAME, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\image', 'title' => TEXT_IMAGE, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\stock', 'title' => BOX_SETTINGS_BOX_STOCK_INDICATION, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\description', 'title' => TEXT_PRODUCTS_DESCRIPTION_SHORT, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\model', 'title' => TEXT_MODEL, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\properties', 'title' => TEXT_PRODUCTS_PROPERTIES, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\rating', 'title' => TEXT_RATING, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\ratingCounts', 'title' => TEXT_RATING_COUNTS, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\price', 'title' => TABLE_HEADING_PRODUCTS_PRICE, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\bonusPoints', 'title' => TEXT_BONUS_POINTS, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\buyButton', 'title' => TEXT_BUY_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\quoteButton', 'title' => REQUEST_FOR_QUOTE_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\sampleButton', 'title' => REQUEST_FOR_SAMPLE_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\qtyInput', 'title' => TEXT_QUANTITY_INPUT, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\viewButton', 'title' => TEXT_VIEW_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\wishlistButton', 'title' => TEXT_WISHLIST_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\compare', 'title' => TEXT_COMPARE, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\attributes', 'title' => TEXT_ATTRIBUTES, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'productListing\paypalButton', 'title' => TEXT_PAYPAL_BUTTON, 'description' => '', 'type' => 'productListing', 'class' => '');
          $widgets[] = array('name' => 'Import', 'title' => IMPORT_BLOCK, 'description' => '', 'type' => 'productListing', 'class' => 'import');
          $widgets[] = array('name' => 'BlockBox', 'title' => TEXT_BLOCK, 'description' => '', 'type' => 'productListing', 'class' => 'block-box');
          //$widgets[] = array('name' => 'productListing\amazonButton', 'title' => 'amazon button', 'description' => '', 'type' => 'productListing', 'class' => '');
      }

    //$widgets[] = array('name' => 'Wristband', 'title' => 'wristband', 'description' => '', 'type' => 'general');

    //Committed because not stylized
    //$widgets[] = array('name' => 'CatalogPages\CategoryPagesList', 'title' => TEXT_WIDGET_CATEGORY_PAGE, 'description' => TEXT_WIDGET_CATEGORY_PAGE, 'type' => 'index', 'class' => 'delivery-location-products');
    //$widgets[] = array('name' => 'CatalogPages\CategoryPagesLastList', 'title' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST, 'description' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST, 'type' => 'index', 'class' => 'delivery-location-products');
    //$widgets[] = array('name' => 'CatalogPages\CategoryPagesLastListByCatalog', 'title' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BY_CATALOG, 'description' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BY_CATALOG, 'type' => 'index', 'class' => 'delivery-location-products');
    //$widgets[] = array('name' => 'CatalogPages\CategoryPagesLastListBlock', 'title' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BLOCK, 'description' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BLOCK, 'type' => 'index', 'class' => 'delivery-location-products');
    //$widgets[] = array('name' => 'CatalogPages\CategoryPagesLastListByCatalogBlock', 'title' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BY_CATALOG_BLOCK, 'description' => TEXT_WIDGET_CATEGORY_PAGE_LAST_LIST_BY_CATALOG_BLOCK, 'type' => 'index', 'class' => 'delivery-location-products');

      $type = $params['type'];
    $path = DIR_FS_CATALOG . 'lib'
      . DIRECTORY_SEPARATOR . 'backend'
      . DIRECTORY_SEPARATOR . 'design'
      . DIRECTORY_SEPARATOR . 'boxes'
      . DIRECTORY_SEPARATOR . 'include';
    if (file_exists($path)) {
      $dir = scandir($path);
      foreach ($dir as $file) {
        if (file_exists($path . DIRECTORY_SEPARATOR . $file) && is_file($path . DIRECTORY_SEPARATOR . $file)) {
          require $path . DIRECTORY_SEPARATOR . $file;
        }
      }
    }

    $widgets = array_merge($widgets, \common\helpers\Acl::getExtensionWidgets($params['type']));

      if ($params['type'] == 'productListing'){
          $productListing = [];
          foreach ($widgets as $key => $widget) {
              if ($widget['type'] == 'productListing'){
                  $productListing[] = $widget;
              }
          }
          $widgets = $productListing;
      }

    return json_encode($widgets);
  }


  public function actionBoxAdd()
  {
    $params = tep_db_prepare_input(Yii::$app->request->post());

    $this->actionBackupAuto($params['theme_name']);

    $sql_data_array = array(
      'theme_name' => $params['theme_name'],
      'block_name' => $params['block'],
      'widget_name' => $params['box'],
      'sort_order' => $params['order'],
    );
    tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);
    $id = tep_db_insert_id();

    Steps::boxAdd(array_merge($sql_data_array, ['block_id' => $id]));

    return json_encode($params);
  }


  public function actionBoxAddSort()
  {
    $params = tep_db_prepare_input(Yii::$app->request->post());

    $this->actionBackupAuto($params['theme_name']);

    $sql_data_array = array(
      'theme_name' => $params['theme_name'],
      'block_name' => $params['block'],
      'widget_name' => $params['box'],
      'sort_order' => $params['order'],
    );
    tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);

    $query_id = tep_db_insert_id();

    $i = 1;
    $sort_arr = array();
    $sort_arr_old = array();
    foreach ($params['id'] as $item){
      if ($item == 'new'){
        $id = $query_id;
      } else {
        $id = substr($item, 4);
      }
      $sql_data_array2 = array(
        'sort_order' => $i,
      );
      $sort_arr[$id] = $i;
      $i++;

      $query = tep_db_fetch_array(tep_db_query("select sort_order from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$id . "'"));
      $sort_arr_old[$id] = $query['sort_order'];

      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array2, 'update', "id = '" . (int)$id . "'");
    }
    Steps::boxAdd(array_merge($sql_data_array, ['block_id' => $query_id, 'sort_arr' => $sort_arr, 'sort_arr_old' => $sort_arr_old]));

    return json_encode($params['order']);
  }


  public function actionEditableList()
  {

    $dir = scandir(Yii::getAlias('@app') . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'boxes');
    $widgets = array();
    foreach($dir as $item){
      if (substr($item, -4) == '.php'){
        $widgets[] = substr($item, 0, -4);
      }
    }

    return json_encode($widgets);
  }

    public function actionCopyPage()
    {
        $theme_name = Yii::$app->request->post('theme_name');
        $page_to = Yii::$app->request->post('page_to');
        $page_from = Yii::$app->request->post('page_from');

        if (!$theme_name || !$page_to || $page_from) {
            return '';
        }

        $aldBoxes = \common\models\DesignBoxes::find()->where([
            'theme_name' => $theme_name,
            'block_name' => $page_to,
        ])->asArray()->all();

        foreach ($aldBoxes as $box) {
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$box['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$box['id'] . "'");
            self::deleteBlock($box['id']);
        }

        $boxes = DesignBoxes::find()->where([
            'block_name' => $page_from,
            'theme_name' => $theme_name,
        ])->asArray()->all();

        foreach ($boxes as $box) {
            $tree = \backend\design\Theme::blocksTree($box['id']);
            Theme::blocksTreeImport($tree, $theme_name, $page_to);
        }

        return '';
    }

    public function actionCopyPagePopUp()
    {
        \common\helpers\Translation::init('admin/design');
        //TODO: add  Steps::actionCopyPagePopUp()
        $page_name = Yii::$app->request->get('page_name');
        $theme_name = Yii::$app->request->get('theme_name');

        $typeSetting = \common\models\ThemesSettings::find()->where([
            'theme_name' => $theme_name,
            'setting_group' => 'added_page',
            'setting_value' => $page_name,
        ])->asArray()->one();
        $type = $typeSetting['setting_name'];


        $andWere = [];
        $andWere = array_merge($andWere, FrontendStructure::getUnitedTypesGroup($type));
        $andWere = array_merge($andWere, FrontendStructure::getUnitedTypesGroup($page_name));

        $pages = \common\models\ThemesSettings::find()->where([
            'theme_name' => $theme_name,
            'setting_group' => 'added_page',
        ])
            ->andWhere(['in', 'setting_name', $andWere])
            ->asArray()
            ->all();

        if ($type) {
            $pages[] = [
                'setting_value' => $type,
            ];
        }

        $definedPages = FrontendStructure::getPages();
        $selectPage = [];
        foreach ($pages as $page) {
            if ($page['setting_value'] != $page_name) {
                $name = design::pageName($page['setting_value']);
                if ($definedPages[$name]) {
                    $title = $definedPages[$name]['title'];
                } else {
                    $title = $page['setting_value'];
                }
                $selectPage[] = [
                    'name' => $name,
                    'title' => $title,
                ];
            }
        }

        $this->layout = 'popup.tpl';
        return $this->render('copy-page-pop-up.tpl', [
            'page_name' => design::pageName($page_name),
            'page_title' => $page_name,
            'pages' => $selectPage,
            'theme_name' => $theme_name,
        ]);
    }

    public function actionAddPage()
    {
        \common\helpers\Translation::init('admin/design');
        $params = Yii::$app->request->get();

        $page_types = explode(' ', trim($params['page_type']));

        $extensionPageTypes = [];
        foreach (\common\helpers\Acl::getExtensionPageTypes() as $type){
            $extensionPageTypes[$type['name']] = $type['title'];
        }

        $types = [];
        foreach ($page_types as $type) {
            switch ($type){
                case 'home': $title = TEXT_HOME; break;
                case 'product': $title = TEXT_PRODUCT; break;
                case 'products': $title = TEXT_LISTING_PRODUCTS; break;
                case 'categories': $title = TEXT_LISTING_CATEGORIES; break;
                case 'info': $title = TEXT_INFORMATION; break;
                case 'account': $title = TEXT_ACCOUNT; break;
                case 'custom': $title = ENTRY_CUSTOM_LINK; break;
                case 'email': $title = IMAGE_EMAIL; break;
                case 'gift_card': $title = TEXT_GIFT_CARD; break;
                case 'invoice': $title = TEXT_INVOICE; break;
                case 'packingslip': $title = TEXT_PACKINGSLIP; break;
                case 'components': $title = 'Component'; break;
                default: $title = $type;
            }
            if ($extensionPageTypes[$type]) {
                $title = $extensionPageTypes[$type];
            }
            $types[$type] = $title;
        }

        $this->layout = 'popup.tpl';
        return $this->render('add-page.tpl', [
            'theme_name' => $params['theme_name'],
            'page_type' => count($page_types) == 1 ? $page_types[0] : '',
            'action' => Yii::$app->urlManager->createUrl('design/add-page-action'),
            'types' => $types
        ]);
    }

    public function actionAddPageAction()
    {
        \common\helpers\Translation::init('admin/design');
        $params = Yii::$app->request->get();

        $theme_name = tep_db_prepare_input($params['theme_name']);
        $page_name = tep_db_prepare_input($params['page_name']);
        $page_type = tep_db_prepare_input($params['page_type']);

        if (!$theme_name) {
            return json_encode(['code' => 1, 'text' => THEME_UNKNOWN]);
        }
        if (!$page_name) {
            return json_encode(['code' => 1, 'text' => ENTER_PAGE_NAME]);
        }

        $count = ThemesSettings::find()->where([
            'theme_name' => $theme_name,
            'setting_group' => 'added_page',
            'setting_name' => $page_type,
            'setting_value' => $page_name,
        ])->count();

        if ($count > 0) {
            return json_encode(['code' => 1, 'text' => THIS_PAGE_ALREADY_EXIST]);
        }

        $sqlDataArray = array(
            'theme_name' => $theme_name,
            'setting_group' => 'added_page',
            'setting_name' => $page_type,
            'setting_value' => $page_name
        );

        $themesSettings = new ThemesSettings();
        $themesSettings->attributes = $sqlDataArray;
        $themesSettings->save();

        \backend\design\Theme::savePageSettings($params);

        $boxes = DesignBoxes::find()->where([
            'block_name' => $page_type,
            'theme_name' => $theme_name,
        ])->asArray()->all();

        foreach ($boxes as $box) {
            $tree = \backend\design\Theme::blocksTree($box['id']);
            Theme::blocksTreeImport($tree, $theme_name, \common\classes\design::pageName($page_name));
        }

        Steps::addPage( array_merge(['id' => tep_db_insert_id()], $sqlDataArray));

        return json_encode(['code' => 2, 'text' => PAGE_ADDED]);
    }

    public function actionRemovePageTemplate()
    {
        \common\helpers\Translation::init('admin/design');
        $params = Yii::$app->request->get();

        $theme_name = tep_db_prepare_input($params['theme_name']);
        $page_title = tep_db_prepare_input($params['page_name']);
        $page_name = \common\classes\design::pageName($page_title);

        if ($theme_name && $page_name) {

            $count = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'added_page' and setting_value = '" . tep_db_input($page_title) . "'"));
            if ($count['total'] == 1) {

                Steps::removePageTemplate([
                    'theme_name' => $theme_name,
                    'page_title' => $page_title
                ]);

                $this->actionBackupAuto($params['theme_name']);

                tep_db_fetch_array(tep_db_query("
                        delete 
                        from " . TABLE_THEMES_SETTINGS . " 
                        where 
                            theme_name = '" . tep_db_input($theme_name) . "' and 
                            ((setting_group = 'added_page' and setting_value = '" . tep_db_input($page_title) . "') or
                             (setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_title) . "'))
                "));

                $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = '" . tep_db_input($page_name) . "'");
                while ($item = tep_db_fetch_array($query)){
                    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
                    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . $item['id'] . "'");
                    self::deleteBlock($item['id']);
                }

                return json_encode(['code' => 2, 'text' => '']);
            }
        }
    }

    public function actionAddPageSettings()
    {
        \common\helpers\Translation::init('admin/design');
        $get = Yii::$app->request->get();

        $page_type = tep_db_fetch_array(tep_db_query("select setting_name from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($get['theme_name']) . "' and setting_group = 'added_page' and setting_value = '" . tep_db_input($get['page_name']) . "'"));

        if (in_array($get['page_name'], ['invoice', 'packingslip', 'creditnote'])) {
            $page_type['setting_name'] = 'order';
        }

        $query = tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($get['theme_name']) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($get['page_name']) . "'");

        $added_page_settings = array();
        while ($item = tep_db_fetch_array($query)){
            if (strpos($item['setting_value'], ':')){
                $setArr = explode(':', $item['setting_value']);
                $added_page_settings[$setArr[0]] = $setArr[1];
            } else {
                $added_page_settings[$item['setting_value']] = true;
            }
        }

        $this->layout = 'popup.tpl';
        return $this->render('add-page-settings.tpl', [
            'theme_name' => $get['theme_name'],
            'page_name' => $get['page_name'],
            'page_type' => $page_type['setting_name'],
            'added_page_settings' => $added_page_settings,
            'action' => Yii::$app->urlManager->createUrl('design/add-page-settings-action')
        ]);
    }

  public function actionAddPageSettingsAction()
  {
    \common\helpers\Translation::init('admin/design');
    $post = Yii::$app->request->post();

    $theme_name = tep_db_prepare_input($post['theme_name']);
    $page_name = tep_db_prepare_input($post['page_name']);

    $settings_old = array();
    $settings = array();
    $query_settings = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_name) . "'");
    while ($item = tep_db_fetch_array($query_settings)){
      $settings_old[] = $item;
    }

    \backend\design\Theme::savePageSettings($post);

    $query_settings = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_name) . "'");
    while ($item = tep_db_fetch_array($query_settings)){
      $settings[] = $item;
    }

    Steps::addPageSettings([
      'theme_name' => $theme_name,
      'page_name' => $page_name,
      'settings_old' => $settings_old,
      'settings' => $settings
    ]);

    return json_encode(['code' => 1, 'text' => '']);
  }

  public function actionBoxEdit()
  {
    \common\helpers\Translation::init('admin/design');
    $params = tep_db_prepare_input(Yii::$app->request->get());
    $id = substr($params['id'], 4);

    $settings = array();
    $items_query = tep_db_query("select id, widget_name, widget_params, theme_name from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$id . "'");
    $widget_params = '';
    if ($item = tep_db_fetch_array($items_query)) {
      $widget_params = $item['widget_params'];

      $media_query = array();
      $media_query_arr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($item['theme_name']) . "' and setting_name = 'media_query'");
      while ($item1 = tep_db_fetch_array($media_query_arr)){
        $media_query[] = $item1;
      }
      $settings['media_query'] = $media_query;
      $settings['theme_name'] = $item['theme_name'];
    }



    $visibility = array();
    $settings_query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "'");
    while ($set = tep_db_fetch_array($settings_query)) {
      if (!$set['visibility']){
        $settings[$set['language_id']][$set['setting_name']] = $set['setting_value'];
      } else {
          if (count(Style::vArr($set['visibility'])) == 1) {
              $visibility[$set['language_id']][$set['visibility']][$set['setting_name']] = $set['setting_value'];
          }
      }
    }

    $font_added = array();
    $font_added_arr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($item['theme_name']) . "' and setting_name = 'font_added'");
    while ($item1 = tep_db_fetch_array($font_added_arr)){
      preg_match('/font-family:[ \'"]+([^\'^"^;^}]+)/', $item1['setting_value'], $val);
      $font_added[] = $val[1];
    }
    $settings['font_added'] = $font_added;
    $settings['theme_name'] = $item['theme_name'];


    if (is_file(Yii::getAlias('@app') . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'boxes' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $params['name']) . '.php')){
      $widget_name = 'backend\design\boxes\\' .str_replace('\\\\', '\\', $params['name']);
      return $widget_name::widget(['id' => $id, 'params' => $widget_params, 'settings' => $settings, 'visibility' => $visibility]);
	} elseif($ext = \common\helpers\Acl::checkExtension($params['name'], 'showTabSettings', true)){
      $widget_name = 'backend\design\boxes\Def';
      $settings = array_merge($settings, ['tabs'=> array('class'=> $ext, 'method' => 'showTabSettings')]);
      return $widget_name::widget(['id' => $id, 'params' => $widget_params, 'settings' => $settings, 'visibility' => $visibility, 'block_type' => $params['block_type']]);
    } elseif($ext = \common\helpers\Acl::checkExtension($params['name'], 'showSettings', true)){
        $widget_name = 'backend\design\boxes\Def';
        $settings = array_merge($settings, ['class'=> $ext, 'method' => 'showSettings']);
        return $widget_name::widget(['id' => $id, 'params' => $widget_params, 'settings' => $settings, 'visibility' => $visibility, 'block_type' => $params['block_type']]);
    }else {
      $widget_name = 'backend\design\boxes\Def';
      return $widget_name::widget(['id' => $id, 'params' => $widget_params, 'settings' => $settings, 'visibility' => $visibility, 'block_type' => $params['block_type']]);
    }
  }

  public function saveBoxSettings($id, $language, $key, $val, $visibility = '')
  {

    if ($val !== '' && $val !== 'off') {

      $theme_name = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$id . "'"));

      if ($key == 'background_image' || $key == 'logo' || $key == 'poster'){

        $setting_value = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "' and setting_name = '" . tep_db_input($key) . "' and	language_id='" . $language . "' and visibility = '" . tep_db_input($visibility) . "'"));

        if ($val && $setting_value['setting_value'] != $val) {
          $val_tmp = Uploads::move($val, 'themes/' . $theme_name['theme_name'] . '/img');
          if ($val_tmp){
            $val = $val_tmp;
          }
        }
      }

      if (($key == 'video_upload' || $key == 'poster_upload') && $val) {
        $val_tmp = Uploads::move($val, 'themes/' . $theme_name['theme_name'] . '/img');
        if ($val_tmp){
          $val = $val_tmp;
          switch ($key){
            case 'video_upload': $key = 'video'; break;
            case 'poster_upload': $key = 'poster'; break;
          };
        }
      }

      if ($key == 'logo') {
          \common\classes\Images::createWebp($val, false, '');
      }

      $total = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "' and setting_name = '" . tep_db_input($key) . "' and	language_id='" . $language . "' and visibility = '" . tep_db_input($visibility) . "'"));

      if ($total['total'] == 0) {
        $sql_data_array = array(
          'box_id' => $id,
          'setting_name' => $key,
          'setting_value' => $val,
          'language_id' => $language,
          'visibility' => $visibility
        );
        tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array);
      } else {
        $sql_data_array = array(
          'setting_value' => $val
        );
        tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array, 'update', "box_id = '" . (int)$id . "' and 	setting_name = '" . tep_db_input($key) . "' and	language_id='" . $language . "' and visibility = '" . tep_db_input($visibility) . "'");
      }

    } else {
      $total = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "' and setting_name = '" . tep_db_input($key) . "' and	language_id='" . $language . "' and visibility = '" . tep_db_input($visibility) . "'"));

      if ($total['total'] > 0) {
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$id . "' and 	setting_name = '" . tep_db_input($key) . "' and	language_id='" . $language . "' and visibility = '" . tep_db_input($visibility) . "'");
      }
    }
  }

  public function actionBoxSave()
  {
    $values = Yii::$app->request->post('values');

    $params = Style::paramsFromOneInput($values);
    //$params = tep_db_prepare_input($params);

    if (isset($params['product_types']) && is_array($params['product_types'])) {
      $tmp = 0;
      //2do jquery.edit-[box|theme].js pass checkbox value/remove from params if unchecked VL
      foreach ($params['product_types'] as $v => $foo) {
        if (!empty($foo)) {
          $tmp |= $v;
        }
      }
      $params['setting'][0]['product_types'] = $tmp;
    }

    $p = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$params['id'] . "'"));
    $this->actionBackupAuto($p['theme_name']);

    $box_settings_old = array();
    $query = tep_db_query("select setting_name, setting_value, language_id, visibility from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$params['id'] . "'");
    while ($item = tep_db_fetch_array($query)){
      $box_settings_old[] = $item;
    }

    if ($params['setting'] || $params['visibility']) {
      for ($i=0; $i<17; $i++){
        if ($params['setting'][0]['sort_hide_' . $i]) {
          $params['setting'][0]['sort_hide_' . $i] = 0;
        } elseif (isset($params['setting'][0]['sort_hide_' . $i])) {
          $params['setting'][0]['sort_hide_' . $i] = 1;
        }
      }

      if ($params['setting'][0]['font_size_dimension'] && !$params['setting'][0]['font-size']) {
          $params['setting'][0]['font_size_dimension'] = '';
      }

        $convertSettings = [
            // visibility widgets on various pages
            'visibility_home', 'visibility_first_view', 'visibility_more_view', 'visibility_logged', 'visibility_not_logged', 'visibility_product', 'visibility_catalog', 'visibility_info', 'visibility_cart', 'visibility_checkout', 'visibility_success', 'visibility_account', 'visibility_login', 'visibility_other',

            //items on listing product
            'show_name', 'show_image', 'show_stock', 'show_description', 'show_model', 'show_properties', 'show_rating', 'show_rating_counts', 'show_price', 'show_buy_button', 'show_qty_input', 'show_view_button', 'show_wishlist_button', 'show_compare', 'show_bonus_points', 'show_attributes', 'show_paypal_button', 'show_amazon_button',

            'show_name_rows', 'show_image_rows', 'show_stock_rows', 'show_description_rows', 'show_model_rows', 'show_properties_rows', 'show_rating_rows', 'show_rating_counts_rows', 'show_price_rows', 'show_buy_button_rows', 'show_qty_input_rows', 'show_view_button_rows', 'show_wishlist_button_rows', 'show_compare_rows', 'show_bonus_points_rows', 'show_attributes_rows', 'show_paypal_button_rows', 'show_amazon_button_rows',

            'show_name_b2b', 'show_image_b2b', 'show_stock_b2b', 'show_description_b2b', 'show_model_b2b', 'show_properties_b2b', 'show_rating_b2b', 'show_rating_counts_b2b', 'show_price_b2b', 'show_buy_button_b2b', 'show_qty_input_b2b', 'show_view_button_b2b', 'show_wishlist_button_b2b', 'show_compare_b2b', 'show_bonus_points_b2b', 'show_attributes_b2b', 'show_paypal_button_b2b', 'show_amazon_button_b2b',
        ];

        foreach ($convertSettings as $setting) {
            if (isset($params['setting'][0][$setting]) && !$params['setting'][0][$setting]) {
                $params['setting'][0][$setting] = 1;
            } elseif ($params['setting'][0][$setting] == 1) {
                $params['setting'][0][$setting] = '';
            }
        }

        if (is_array($params['setting'])) {
            foreach ($params['setting'] as $language => $set) {

                if (strlen($set['video_upload']) > 3) unset($set['video']);
                if (strlen($set['poster_upload']) > 3) unset($set['poster']);

                foreach ($set as $key => $val) {
                    if (is_array($val)){
                        $val = implode(',', $val);
                    }
                    $this->saveBoxSettings($params['id'], $language, $key, $val);
                }
            }
        }

      if (is_array($params['visibility'])) {
          foreach ($params['visibility'] as $language => $set) {
              foreach ($set as $visibility => $set2) {
                  foreach ($set2 as $key => $val) {
                      if (is_array($val)){
                          $val = implode(',', $val);
                      }
                      $this->saveBoxSettings($params['id'], $language, $key, $val, $visibility);
                  }
              }
          }
      }
    }

    if ($params['uploads'] == '1'){
      if ($params['params'] != ''){

        $file_name = Uploads::move($params['params'], 'themes/' . $p['theme_name'] . '/img');

        $sql_data_array = array(
          'widget_params' => $file_name
        );
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array, 'update', "id = '" . (int)$params['id'] . "'");
      }
    } else {
      $sql_data_array = array(
        'widget_params' => tep_db_prepare_input($params['params'])
      );
      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array, 'update', "id = '" . (int)$params['id'] . "'");
    }

    $box_settings = array();
    $query = tep_db_query("select setting_name, setting_value, language_id, visibility from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$params['id'] . "'");
    while ($item = tep_db_fetch_array($query)){
      $box_settings[] = $item;
    }

      Style::createCache($params['theme_name']);

      Steps::boxSave([
          'box_id' => $params['id'],
          'theme_name' => $p['theme_name'],
          'box_settings' => $box_settings,
          'box_settings_old' => $box_settings_old
      ]);


      return json_encode( '');
  }


  public function actionStyleEdit()
  {
    \common\helpers\Translation::init('admin/design');
    $params = tep_db_prepare_input(Yii::$app->request->get());
    $this->actionBackupAuto($params['theme_name']);

    $settings = array();
    $styles_query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = '" . tep_db_input($params['data_class']) . "'");
    $visibility = array();
    while ($styles_arr = tep_db_fetch_array($styles_query)){
      if (!$styles_arr['visibility']){
        $settings[0][$styles_arr['attribute']] = $styles_arr['value'];
      } else {
        $visibility[0][$styles_arr['visibility']][$styles_arr['attribute']] = $styles_arr['value'];
      }
    }
    $this->layout = 'popup.tpl';



    $media_query = array();
    $media_query_arr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_name = 'media_query'");
    while ($item1 = tep_db_fetch_array($media_query_arr)){
      $media_query[] = $item1;
    }
    $settings['media_query'] = $media_query;


    $font_added = array();
    $font_added_arr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_name = 'font_added'");
    while ($item1 = tep_db_fetch_array($font_added_arr)){
      preg_match('/font-family:[ \'"]+([^\'^"^;^}]+)/', $item1['setting_value'], $val);
      $font_added[] = $val[1];
    }
    $settings['font_added'] = $font_added;
    $settings['data_class'] = $params['data_class'];
    $settings['theme_name'] = $params['theme_name'];
    $widget_name = 'backend\design\boxes\StyleEdit';
    return $widget_name::widget(['id' => 0, 'params' => '', 'settings' => $settings, 'visibility' => $visibility, 'block_type' => '']);

    /*return $this->render('style-edit.tpl', [
      'data_class' => $params['data_class'],
      'theme_name' => $params['theme_name'],
      'settings' => $styles
    ]);*/
  }

    public function styleSave($styles, $params, $visibility = '')
    {
        if (is_array($styles)) foreach ($styles as $key => $val) {

            $accessibility = '';
            if (preg_match('/^(\.w-[0-9a-zA-Z\-\_]+)/', $key, $matches)) {
                $accessibility = $matches[1];
            }

            $total = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = '" . tep_db_input($params['data_class']) . "' and attribute = '" . tep_db_input($key) . "' and visibility='" . tep_db_input($visibility) . "' and media = ''"));

            if ($val !== '') {

                if ($key == 'background_image') {
                    $setting_value = tep_db_fetch_array(tep_db_query("select ts.value from " . TABLE_THEMES_STYLES . " ts where ts.theme_name = '" . tep_db_input($params['theme_name']) . "' and ts.selector = '" . tep_db_input($params['data_class']) . "' and ts.attribute = '" . tep_db_input($key) . "' and visibility='" . tep_db_input($visibility) . "' and media = ''"));

                    if ($setting_value['value'] != $val) {
                        $val_tmp = Uploads::move($val, 'themes/' . $params['theme_name'] . '/img');
                        if ($val_tmp) $val = $val_tmp;
                    }
                }

                if ($total['total'] == 0) {
                    $sql_data_array = array(
                        'theme_name' => $params['theme_name'],
                        'selector' => $params['data_class'],
                        'attribute' => $key,
                        'value' => $val,
                        'visibility' => $visibility,
                        'media' => '',
                        'accessibility' => $accessibility,
                    );
                    tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array);
                } else {
                    $sql_data_array = array(
                        'value' => $val,
                    );
                    tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array, 'update', "theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = '" . tep_db_input($params['data_class']) . "' and attribute = '" . tep_db_input($key) . "' and visibility='" . tep_db_input($visibility) . "' and media = ''");
                }

            } else {
                if ($total['total'] > 0) {
                    tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = '" . tep_db_input($params['data_class']) . "' and attribute = '" . tep_db_input($key) . "' and visibility='" . tep_db_input($visibility) . "' and media = ''");
                }
            }
        }
    }

    public function actionStyleSave()
    {
        $values = Yii::$app->request->post('values');
        $post = Yii::$app->request->post();

        $params = Style::paramsFromOneInput($values);

        $params = tep_db_prepare_input($params);

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where selector='" . tep_db_input($params['data_class']) . "' and theme_name='" . tep_db_input($params['theme_name']) . "'");
        $styles_old = [];
        while($item = tep_db_fetch_array($query)){
            $styles_old[] = $item;
        }

        if (is_array($params['visibility'][0])) {
            foreach ($params['visibility'][0] as $key => $item) {
                $this->styleSave($item, $post, $key);
            }
        }
        $this->styleSave($params['setting'][0], $post);

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where selector='" . tep_db_input($params['data_class']) . "' and theme_name='" . tep_db_input($params['theme_name']) . "'");
        $styles = [];
        while($item = tep_db_fetch_array($query)){
            $styles[] = $item;
        }

        $attributesChanged = array();
        $attributesDelete = array();
        $attributesNew = array();

        foreach ($styles_old as $item) {

            $find = false;
            foreach ($styles as $i => $attr) {
                if (
                    $attr['selector'] == $item['selector'] &&
                    $attr['attribute'] == $item['attribute'] &&
                    $attr['visibility'] == $item['visibility'] &&
                    $attr['media'] == $item['media'] &&
                    $attr['accessibility'] == $item['accessibility']
                ) {
                    if ($attr['value'] != $item['value']) {
                        $attributesChanged[] = [
                            'selector' => $attr['selector'],
                            'attribute' => $attr['attribute'],
                            'value_old' => $item['value'],
                            'value' => $attr['value'],
                            'visibility' => $attr['visibility'],
                            'media' => $attr['media'],
                            'accessibility' => $attr['accessibility']
                        ];
                    }
                    unset($styles[$i]);
                    $find = true;
                }
            }
            if (!$find) {
                $attributesDelete[] = [
                    'selector' => $item['selector'],
                    'attribute' => $item['attribute'],
                    'value' => $item['value'],
                    'visibility' => $item['visibility'],
                    'media' => $item['media'],
                    'accessibility' => $item['accessibility']
                ];
            }
        }

        foreach ($styles as $attr) {
            $attributesNew[] = [
                'theme_name' => $post['theme_name'],
                'selector' => $attr['selector'],
                'attribute' => $attr['attribute'],
                'value' => $attr['value'],
                'visibility' => $attr['visibility'],
                'media' => $attr['media'],
                'accessibility' => $attr['accessibility']
            ];
        }

        Style::createCache($post['theme_name']);

        $data = [
            'theme_name' => $post['theme_name'],
            'attributes_changed' => $attributesChanged,
            'attributes_delete' => $attributesDelete,
            'attributes_new' => $attributesNew,
        ];
        Steps::cssSave($data);

        return '';
    }

  public function actionBackups()
  {
    \common\helpers\Translation::init('admin/design');
    $params = tep_db_prepare_input(Yii::$app->request->get());

    $this->selectedMenu = array('design_controls', 'design/themes');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/themes'), 'title' => TEXT_BACKUPS . ' "' . Theme::getThemeTitle($params['theme_name']) . '"');

    $this->topButtons[] = '<a href="' . Yii::$app->urlManager->createUrl(['design/backup-add', 'theme_name' => $params['theme_name']]) . '" class="create_item">' . NEW_NEW_BACKUP . '</a>';

    $this->view->headingTitle = TEXT_BACKUPS;

    return $this->render('backups.tpl', [
      'menu' => 'backups',
      'theme_name' => $params['theme_name'],
      'messages' => [],
    ]);
  }
  public function actionBackupsList ()
  {

    $draw = Yii::$app->request->get('draw', 1);
    $start = Yii::$app->request->get('start', 0);
    $length = Yii::$app->request->get('length', 10);
    $theme_name = tep_db_prepare_input(Yii::$app->request->get('theme_name', 10));

    if ($length == -1)
      $length = 10000;

    $current_page_number = ($start / $length) + 1;
    $responseList = [];

    if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
      switch ($_GET['order'][0]['column']) {
        case 0:
          $orderBy = "date_added " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
          break;
        case 1:
          $orderBy = "comments " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
          break;
        default:
          $orderBy = "date_added";
          break;
      }
    } else {
      $orderBy = "date_added";
    }

    $orders_status_query_raw = "select * from " . TABLE_DESIGN_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "' order by " . $orderBy . " limit " . (int)$_GET['start'] . ", " . (int)$_GET['length'];
    $count = tep_db_num_rows(tep_db_query("select * from " . TABLE_DESIGN_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "' order by " . $orderBy));
    //$orders_status_split = new \splitPageResults($current_page_number, $length, $orders_status_query_raw, $query_numrows);
    $orders_status_query = tep_db_query($orders_status_query_raw);

    $query_numrows = 0;
    while ($orders_status = tep_db_fetch_array($orders_status_query)) {

      $short_desc = $orders_status['comments'];
      $short_desc = preg_replace("/<.*?>/", " ", $short_desc);
      if (strlen($short_desc) > 128) {
        $short_desc = substr($short_desc, 0, 122) . '...';
      }

      $responseList[] = array(
        \common\helpers\Date::date_long($orders_status['date_added'], "%d %b %Y / %H:%M:%S"),
        $short_desc . '<input type="hidden" class="backup_id" name="backup_id" value="' . $orders_status['backup_id'] . '">',
      );
      $query_numrows++;
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $count,
      'recordsFiltered' => $count,
      'data' => $responseList
    ];
    echo json_encode($response);
  }

  public function actionBackupsActions() {

    $this->layout = false;

    $backup_id = intval(Yii::$app->request->post('backup_id'));
    if (!empty($backup_id)) {
      $query = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_DESIGN_BACKUPS . " where backup_id = '" . $backup_id . "'"));

      echo '<br><div style="font-size: 12px">';
      echo str_replace("\n", '<br>', $query['comments']);
      echo '</div>';
      echo '<div class="btn-toolbar btn-toolbar-order">';
      echo '<button class="btn btn-no-margin" onclick="backupRestore(\'' . $backup_id . '\')">' . IMAGE_RESTORE . '</button>';
      echo '<button class="btn btn-delete" onclick="translateDelete(\'' . $backup_id . '\')">' . IMAGE_DELETE . '</button>';
      echo '</div>';
    }
  }
  public function actionBackupAdd() {
    \common\helpers\Translation::init('admin/design');

    $params = Yii::$app->request->get();

    $this->layout = false;
    return $this->render('add.tpl', [
      'theme_name' => $params['theme_name'],
    ]);
  }

  public function actionBackupAuto($theme_name){

    $query = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and 	setting_group = 'hide' and setting_name = 'backup_date'"));

    if (!$query['setting_value'] || \common\helpers\Date::date_long($query['setting_value'], '%Y%m%d') != date("Ymd")){
      $sql_data_array = array(
        'theme_name' => $theme_name,
        'setting_group' => 'hide',
        'setting_name' => 'backup_date',
        'setting_value' => 'now()',
      );
      if ($query['setting_value']){
        tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array, 'update', "theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'hide' and setting_name = 'backup_date'");
      } else {
        tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
      }

      $this->actionBackupSubmit($theme_name, 'Auto saved');
    }

  }

    public function actionBackupSubmit($theme_name = '', $comments = '')
    {
        $params = tep_db_prepare_input(Yii::$app->request->post());

        if (!$params['theme_name']) $params['theme_name'] = $theme_name;

        $sql_data_array = array(
            'date_added' => 'now()',
            'theme_name' => $params['theme_name'],
            'comments' => ($params['comments'] ? $params['comments'] : $comments),
        );
        tep_db_perform(TABLE_DESIGN_BACKUPS, $sql_data_array);

        $backup_id = tep_db_insert_id();

        Steps::backupSubmit([
            'theme_name' => $params['theme_name'],
            'backup_id' => $backup_id,
            'comments' => ($params['comments'] ? $params['comments'] : $comments)
        ]);

        Backups::create($params['theme_name'], $backup_id);
        Backups::create($params['theme_name'] . '-mobile', $backup_id);

        return json_encode('');
    }


    public function actionExport()
    {
        $theme_name = tep_db_prepare_input(Yii::$app->request->get('theme_name'));

        return \backend\design\Theme::export($theme_name);
    }

  public function actionExportBlock() {

    $params = Yii::$app->request->get();
    $id = intval(substr($params['id'], 4));
    if ($id) {

      $query = tep_db_fetch_array(tep_db_query("select widget_name, theme_name from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . $id . "'"));
      $file_name = $query['theme_name'] . '_' . $query['widget_name'] . '_' . $id;

      header('Content-Type: application/json');
      header("Content-Transfer-Encoding: utf-8");
      header('Content-disposition: attachment; filename="' . $file_name . '.json"');
      return json_encode(\backend\design\Theme::blocksTree($id));
    }
    return '';
  }

    public function actionImport()
    {
        $params = Yii::$app->request->get();
        if ($_FILES['file']['error'] == UPLOAD_ERR_OK  && is_uploaded_file($_FILES['file']['tmp_name'])) {
            if ( \backend\design\Theme::import($params['theme_name'],$_FILES['file']['tmp_name']) ) {
                return '1';
            }
        }
        return 'error';
    }

  public function actionImportBlock() {
    $params = Yii::$app->request->get();
    if ($_FILES['file']['error'] == UPLOAD_ERR_OK  && is_uploaded_file($_FILES['file']['tmp_name'])) {
      $arr = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
      if (is_array($arr)){

        $box_id = substr($params['box_id'], 4);
        $query = tep_db_fetch_array(tep_db_query("select sort_order from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$box_id . "'"));

        $box_id_new = Theme::blocksTreeImport($arr, $params['theme_name'], $params['block_name'], $query['sort_order']);

        tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$box_id . "'");
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$box_id . "'");

        $data = [
          'box_id_old' => $box_id,
          'box_id' => $box_id_new,
          'theme_name' => $params['theme_name'],
        ];
        Steps::importBlock($data);

        return '1';
      }
    }
    return '';
  }

    public function actionBackupRestore()
    {

        $params = Yii::$app->request->post();

        $query = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_DESIGN_BACKUPS . " where backup_id = '" . (int)$params['backup_id'] . "' limit 1"));

        Backups::backupRestore($params['backup_id'], $query['theme_name']);
        Backups::backupRestore($params['backup_id'], $query['theme_name'] . '-mobile');

        Steps::backupRestore([
            'theme_name' => $query['theme_name'],
            'backup_id' => $params['backup_id']
        ]);
    }

  public function actionBackupDelete() {
    $params = Yii::$app->request->post();

    tep_db_query("delete from " . TABLE_DESIGN_BACKUPS . " where backup_id = '" . (int)$params['backup_id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_BACKUPS . " where backup_id = '" . (int)$params['backup_id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_BACKUPS . " where backup_id = '" . (int)$params['backup_id'] . "'");
    tep_db_query("delete from " . TABLE_THEMES_SETTINGS_BACKUPS . " where 	backup_id = '" . (int)$params['backup_id'] . "'");
    tep_db_query("delete from " . TABLE_THEMES_STYLES_BACKUPS . " where 	backup_id = '" . (int)$params['backup_id'] . "'");
  }

  public function actionGallery() {
    $get = tep_db_prepare_input(Yii::$app->request->get());
    $path = $get['path'] ? $get['path'] : 'images';
    $htm = '';
    if ($get['theme_name']){
      $files2 = scandir(DIR_FS_CATALOG . 'themes/' . $get['theme_name'] . '/img');
      foreach ($files2 as $item){
        $s = strtolower(substr($item, -3));
        if (!$get['type'] && ($s == 'gif' || $s == 'png' || $s == 'jpg' || $s == 'peg' || $s == 'svg')){
          $htm .= '<div class="item item-themes"><div class="image"><img src="' . DIR_WS_CATALOG . 'themes/' . $get['theme_name'] . '/img/' . $item . '" title="' . $item . '" alt="' . $item . '"></div><div class="name" data-path="themes/' . $get['theme_name'] . '/img/">' . $item . '</div></div>';
        } elseif ($get['type'] == 'video' && ($s == 'mp4' || $s == 'mov')){
          $htm .= '<div class="item item-themes"><div class="image" style="height: 0; overflow: hidden"><img src="' . DIR_WS_CATALOG . 'themes/' . $get['theme_name'] . '/img/' . $item . '"></div><div class="name" style="white-space: normal" data-path="themes/' . $get['theme_name'] . '/img/">' . $item . '</div></div>';
        }
      }
    }
    $files = scandir(DIR_FS_CATALOG . $path);
    foreach ($files as $item){
      $s = strtolower(substr($item, -3));
      if (!$get['type'] && ($s == 'gif' || $s == 'png' || $s == 'jpg' || $s == 'peg' || $s == 'svg')){
        $htm .= '<div class="item item-general"><div class="image"><img src="' . DIR_WS_CATALOG . $path . '/' . $item . '" title="' . $item . '" alt="' . $item . '"></div><div class="name" data-path="' . $path . '/">' . $item . '</div></div>';
      } elseif ($get['type'] == 'video' && ($s == 'mp4' || $s == 'mov')){
        $htm .= '<div class="item item-general"><div class="image" style="height: 0; overflow: hidden"><img src="' . DIR_WS_CATALOG . $path . '/' . $item . '"></div><div class="name" style="white-space: normal" data-path="' . $path . '/">' . $item . '</div></div>';
      }
    }
    return $htm;
  }

  public function actionSettings() {
    \common\helpers\Translation::init('admin/design');
    \common\helpers\Translation::init('admin/js');
    $params = tep_db_prepare_input(Yii::$app->request->get());
    $post = tep_db_prepare_input(Yii::$app->request->post(),false);

    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/settings'), 'title' => THEME_SETTINGS . ' "' . Theme::getThemeTitle($params['theme_name']) . '"');
    $this->selectedMenu = array('design_controls', 'design/themes');

    $this->topButtons[] = '<span class="redo-buttons"></span>';

    if (count($post) > 0){

      foreach ($post['setting'] as $key => $val) {
        $total = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''"));
        $total2 = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''"));
        if ($val) {
          if ($key == 'background_image') {
            $setting_value = tep_db_fetch_array(tep_db_query("select ts.value from " . TABLE_THEMES_STYLES . " ts where ts.theme_name = '" . tep_db_input($params['theme_name']) . "' and ts.selector = 'body' and ts.attribute = '" . tep_db_input($key) . "' and visibility=''"));

            if ($setting_value['value'] != $val) {
              $val = Uploads::move($val, 'themes/' . $params['theme_name'] . '/img');
            }
          }
          $sql_data_array = array(
            'theme_name' => $params['theme_name'],
            'selector' => 'body',
            'attribute' => $key,
            'value' => $val,
          );
          if ($total['total'] == 0) {
            tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array);
          } else {
            tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array, 'update', "theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''");
          }
          if ($total2['total'] == 0) {
            tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array);
          } else {
            tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array, 'update', "theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''");
          }
        } else {
          if ($total['total'] > 0) {
            tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''");
            tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and attribute = '" . tep_db_input($key) . "' and visibility=''");
          }
        }
      }
      //$this->actionThemeSave();


      $them_settings_old = [];
      $query_s = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and (setting_group = 'main' or setting_group = 'extend' or setting_group = 'hide')");
      while ($item = tep_db_fetch_array($query_s)){
        $them_settings_old[] = $item;
      }
      /*echo '<pre>';
      var_dump($them_settings_old);
      echo '</pre>';
      echo json_encode($them_settings_old);die;*/

      foreach ($post['settings'] as $setting_name => $setting_value){

        $sql_data_array = array(
          'theme_name' => $params['theme_name'],
          'setting_group' => 'main',
          'setting_name' => $setting_name,
          'setting_value' => $setting_value,
        );

        $query = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'main' and setting_name = '" . tep_db_input($setting_name) . "'"));
        if ($query['total'] > 0){
          tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array, 'update', " theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'main' and setting_name = '" . tep_db_input($setting_name) . "'");
        } else {
          tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
        }

      }


      if (is_array($post['extend'])) {
        foreach ($post['extend'] as $setting_name => $val) {
          foreach ($val as $id => $setting_value) {

            $sql_data_array = array(
                'setting_value' => $setting_value,
            );
            $query = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'extend' and setting_name = '" . tep_db_input($setting_name) . "' and id = '" . (int)$id . "'"));
            if ($query['total'] > 0) {
              tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array, 'update', " theme_name = '" . tep_db_input($params['theme_name']) . "' and setting_group = 'extend' and setting_name = '" . tep_db_input($setting_name) . "' and id = '" . (int)$id . "'");
            }
          }
        }
      }

      Theme::saveFavicon();
      Theme::saveThemeImage('logo');
      Theme::saveThemeImage('na_category');
      Theme::saveThemeImage('na_product');

      $them_settings = [];
      $query_s = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and (setting_group = 'main' or setting_group = 'extend' or setting_group = 'hide')");
      while ($item = tep_db_fetch_array($query_s)){
        $them_settings[] = $item;
      }

      $data = [
        'theme_name' => $params['theme_name'],
        'them_settings_old' => $them_settings_old,
        'them_settings' => $them_settings,
      ];
      Steps::settings($data);
    }

    $query = tep_db_query("select setting_name, setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($params['theme_name']) . "'");

    $settings = array();
    while ($item = tep_db_fetch_array($query)){
      $settings[$item['setting_name']] = $item['setting_value'];
    }

    $styles = array();
    $styles_query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($params['theme_name']) . "' and selector = 'body' and visibility=''");
    while ($styles_arr = tep_db_fetch_array($styles_query)){
      $styles[$styles_arr['attribute']] = $styles_arr['value'];
    }

    $path = \Yii::getAlias('@webroot');
    $path .= DIRECTORY_SEPARATOR;
    $path .= '..';
    $path .= DIRECTORY_SEPARATOR;
    $path .= 'themes';
    $path .= DIRECTORY_SEPARATOR;
    $path .= $_GET['theme_name'];
    $path .= DIRECTORY_SEPARATOR;
    $path .= 'icons';
    $path .= DIRECTORY_SEPARATOR;
    if (is_file($path . 'favicon-16x16.png')){
      $favicon = '../themes/' . $_GET['theme_name'] . '/icons/favicon-16x16.png';
    } else {
      $favicon = '../themes/basic/icons/favicon-16x16.png';
    }

    return $this->render('settings.tpl', [
      'favicon' => $favicon,
      'menu' => 'settings',
      'settings' => $settings,
      'setting' => $styles,
      'theme_name' => $params['theme_name'],
      'action' => Yii::$app->urlManager->createUrl(['design/settings', 'theme_name' => $params['theme_name']]),
        'is_mobile' => strpos($_GET['theme_name'], '-mobile') ? true : false
    ]);
  }

  public function actionExtend() {
    $get = tep_db_prepare_input(Yii::$app->request->get());

    if ($get['remove']){
      Steps::extendRemove(['theme_name' => $get['theme_name'], 'id' => (int)$get['remove']]);

      tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$get['remove'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where visibility = '" . (int)$get['remove'] . "'");
      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where visibility = '" . (int)$get['remove'] . "'");
      tep_db_query("delete from " . TABLE_THEMES_STYLES . " where visibility = '" . (int)$get['remove'] . "'");
      //tep_db_query("delete from " . TABLE_THEMES_STYLES_TMP . " where visibility = '" . (int)$get['remove'] . "'");
    }

    if ($get['add']){
      $sql_data_array = array(
        'theme_name' =>$get['theme_name'],
        'setting_group' => 'extend',
        'setting_name' => $get['setting_name'],
        'setting_value' => '',
      );
      tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
      $added_id = tep_db_insert_id();

      $sql_data_array['id'] = $added_id;
      Steps::extendAdd(['theme_name' => $get['theme_name'], 'data' => $sql_data_array]);
    }

    $query = tep_db_query("select id, setting_name, setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($get['theme_name']) . "' and setting_group = 'extend' and setting_name = '" . tep_db_input($get['setting_name']) . "'");
    $arr = array();
    while ($item = tep_db_fetch_array($query)){
      $arr[] = $item;
    }
    return json_encode($arr);
  }


  public function actionDemoStyles() {
    $post = tep_db_prepare_input(Yii::$app->request->post());
    $class = str_replace('\\', '', $post['data_class']);
    $style = $class . '{' . \frontend\design\Block::styles($post['setting']).'}';

    $key_arr = explode(',', $class);
    for ($i = 1; $i < 5; $i++) {
      $add = '';
      switch ($i) {
        case 1: $add = ':hover'; break;
        case 2: $add = '.active'; break;
        case 3: $add = ':before'; break;
        case 4: $add = ':after'; break;
      }
      $selector_arr = array();
      foreach ($key_arr as $item) {
        $selector_arr[] = trim($item) . $add;
      }
      $selector = implode(', ', $selector_arr);
      $params[0] = $post['visibility'][0][$i];
      $style .= $selector . '{' . \frontend\design\Block::styles($params) . '}';
    }

    echo $style;
  }

    public function actionLog()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());
        \common\helpers\Translation::init('admin/design');
        $this->topButtons[] = '<span class="redo-buttons"></span>';

        $this->selectedMenu = array('design_controls', 'design/themes');
        $this->view->headingTitle = LOG_TEXT . ' "' . Theme::getThemeTitle($get['theme_name']) . '"';
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/settings'), 'title' => 'Log "' . Theme::getThemeTitle($get['theme_name']) . '"');

        $admins = array();
        $query = tep_db_query("select admin_id, admin_firstname, admin_lastname, admin_email_address from " . TABLE_ADMIN . "");
        while ($item = tep_db_fetch_array($query)){
            $admins[$item['admin_id']] = $item;
        }

        $date = [];
        if ($get['from']) {
            $date['from'] = $get['from'];
        }
        if ($get['to']) {
            $date['to'] = $get['to'];
        }

        if (Yii::$app->request->isAjax) {
            $this->layout = 'popup.tpl';
        }

        $updates = Style::getNewUpdates($get['parent_theme']);

        return $this->render('log.tpl', [
            'tree' => Steps::log($get['theme_name'], $date),
            'admins' => $admins,
            'theme_name' => $get['theme_name'],
            'menu' => 'log',
            'from' => $get['from'],
            'to' => $get['to'],
            'apple_update' => count($updates) > 0 ? false : true,
            'update_buttons' => \backend\components\Information::showHidePage()
        ]);
    }

    public function actionLogDetails()
    {
        \common\helpers\Translation::init('admin/design');
        $get = tep_db_prepare_input(Yii::$app->request->get());

        if (Yii::$app->request->isAjax) {
            $this->layout = 'popup.tpl';
        }

        return $this->render('log-details.tpl', [
            'details' => Steps::logDetails($get['id']),
        ]);
    }

  public function actionUndo() {
    $get = tep_db_prepare_input(Yii::$app->request->get());
    Steps::undo($get['theme_name']);
  }

  public function actionRedo() {
    $get = tep_db_prepare_input(Yii::$app->request->get());
    Steps::redo($get['theme_name'], $get['steps_id']);
  }

  public function actionRedoButtons() {
    \common\helpers\Translation::init('admin/design');
    $get = tep_db_prepare_input(Yii::$app->request->get());

    $redo_query = tep_db_query("select sr.steps_id, sr.event, sr.date_added, sr.admin_id from " . TABLE_THEMES_STEPS . " sr left join " . TABLE_THEMES_STEPS . " sa on sr.parent_id = sa.steps_id where sa.active='1' and sr.theme_name='" . tep_db_input($get['theme_name']) . "'");
    $redo = '';
    while ($item = tep_db_fetch_array($redo_query)){
      $redo .= '<span class="btn btn-redo btn-elements" data-id="' . $item['steps_id'] . '" data-event="' . $item['event'] . '" title="' . Steps::logNames($item['event']) . ' (' . \common\helpers\Date::date_long($item['date_added'], "%d %b %Y / %H:%M:%S") . ')">' . LOG_REDO . '</span>';
    }

    $undo = tep_db_fetch_array(tep_db_query("select steps_id, event, date_added, admin_id from " . TABLE_THEMES_STEPS . " where active='1' and parent_id!='0' and theme_name='" . tep_db_input($get['theme_name']) . "'"));

    if ($undo['steps_id']) {
      $redo .= '<span class="btn btn-undo btn-elements" data-event="' . $undo['event'] . '" title="' . Steps::logNames($undo['event']) . ' (' . \common\helpers\Date::date_long($undo['date_added'], "%d %b %Y / %H:%M:%S") . ')">' . LOG_UNDO . '</span>';
    }

    echo $redo;
  }

  public  function actionStepRestore()
  {
    \common\helpers\Translation::init('admin/design');
    $get = tep_db_prepare_input(Yii::$app->request->get());
    $text = Steps::restore($get['id']);
    if ($text){
      $text = '
<div class="popup-box-wrap pop-mess">
    <div class="around-pop-up"></div>
    <div class="popup-box">
        <div class="pop-up-close pop-up-close-alert"></div>
        <div class="pop-up-content">
            <div class="popup-content pop-mess-cont pop-mess-cont-error">
                ' . $text . '
            </div>
        </div>
            <div class="noti-btn">
                    <div></div>
                    <div><span class="btn btn-primary">' . TEXT_BTN_OK . '</span></div>
                </div>
    </div>
<script>
    $(\'body\').scrollTop(0);
    $(\'.pop-mess .pop-up-close-alert, .noti-btn .btn\').click(function () {
        $(this).parents(\'.pop-mess\').remove();
    });
</script>
</div>
';
    }
    return $text;
  }

  public  function actionFindSelector()
  {
    $get = tep_db_prepare_input(Yii::$app->request->get());

    $selectors_query = tep_db_query("
      select DISTINCT selector
      from " . TABLE_THEMES_STYLES . "
      where theme_name = '" . tep_db_input($get['theme_name']) . "' and
        selector LIKE '%" . tep_db_input($get['selector']) . "%'
");

    $html = '';
    while ($item = tep_db_fetch_array($selectors_query)) {
      $html .= '<div class="item">' . $item['selector'] . '</div>';
    }

    if ($html == '') {
      $html = '<div class="no-selector">Not found selectors.</div>';
    }

    return $html;

  }

  public  function actionStyles()
  {
    \common\helpers\Translation::init('admin/design');
    $get = tep_db_prepare_input(Yii::$app->request->get());

    $this->topButtons[] = '<span data-href="' . Yii::$app->urlManager->createUrl(['design/theme-save', 'theme_name' => $get['theme_name']]) . '" class="btn btn-confirm btn-save-boxes btn-elements">'.IMAGE_SAVE.'</span> <span class="redo-buttons"></span>';

    $this->selectedMenu = array('design_controls', 'design/themes');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/elements'), 'title' => BOX_HEADING_MAIN_STYLES . ' "' . Theme::getThemeTitle($get['theme_name']) . '"');
    $this->view->headingTitle = BOX_HEADING_MAIN_STYLES . ' "' . Theme::getThemeTitle($get['theme_name']) . '"';

    $path = \Yii::getAlias('@webroot');
    $path .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    $path .= 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
    $path .= 'themes' . DIRECTORY_SEPARATOR . 'basic' . DIRECTORY_SEPARATOR;
    $path .= 'index' . DIRECTORY_SEPARATOR . 'design';
    $files = scandir($path);
    $sf = array();
    foreach ($files as $item) {
      if ($item != '.' && $item != '..') {
        $content = file_get_contents($path  . DIRECTORY_SEPARATOR . $item);
        preg_match_all("/Info\:\:dataClass\([\'\"]([^}]+)[\'\"]/", $content, $arr);
        $sf = array_merge($sf, $arr[1]);
      }
    }


    $selectors_query = tep_db_query("
      select DISTINCT selector
      from " . TABLE_THEMES_STYLES . "
      where theme_name = '" . tep_db_input($get['theme_name']) . "'
");

    $selectors = array();
    while ($item = tep_db_fetch_array($selectors_query)) {
      $selectors[] = $item['selector'];
    }

    sort($selectors);

    $list = array();
    foreach ($selectors as $item) {
      $items = explode(',', $item);
      foreach ($items as $i) {
        $i = preg_replace("/[ ]+/", ' ', trim($i));
        $list[$i] = $item;
      }
    }
    asort($list);

    $list2 = array();
    foreach ($list as $item => $key) {
      $items = explode(' ', $item);
      $new = true;
      if (in_array($key, $sf)){
        $new = false;
      }
      $list2[$items[0]][] = ['short' => $item, 'long' => $key, 'new' => $new];
    }


    $fontColors = array();
    $query = tep_db_query("select value from " . TABLE_THEMES_STYLES . " where theme_name = '" .tep_db_input($get['theme_name']) . "' and attribute = 'color'");
    while ($item = tep_db_fetch_array($query)) {
      if ($fontColors[$item['value']]){
        $fontColors[$item['value']]++;
      } else {
        $fontColors[$item['value']] = 1;
      }
    }
    $query = tep_db_query("select bs.setting_value from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" .tep_db_input($get['theme_name']) . "' and bs.setting_name = 'color'");
    while ($item = tep_db_fetch_array($query)) {
      if ($fontColors[$item['setting_value']]){
        $fontColors[$item['setting_value']]++;
      } else {
        $fontColors[$item['setting_value']] = 1;
      }
    }

    $backgroundColors = array();
    $query = tep_db_query("select value from " . TABLE_THEMES_STYLES . " where theme_name = '" .tep_db_input($get['theme_name']) . "' and attribute = 'background-color'");
    while ($item = tep_db_fetch_array($query)) {
      if ($backgroundColors[$item['value']]){
        $backgroundColors[$item['value']]++;
      } else {
        $backgroundColors[$item['value']] = 1;
      }
    }
    $query = tep_db_query("select bs.setting_value from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" .tep_db_input($get['theme_name']) . "' and bs.setting_name = 'background-color'");
    while ($item = tep_db_fetch_array($query)) {
      if ($backgroundColors[$item['setting_value']]){
        $backgroundColors[$item['setting_value']]++;
      } else {
        $backgroundColors[$item['setting_value']] = 1;
      }
    }

    $borderColors = array();
    $query = tep_db_query("select value from " . TABLE_THEMES_STYLES . " where theme_name = '" .tep_db_input($get['theme_name']) . "' and attribute in ('border-top-color', 'border-left-color', 'border-right-color', 'border-bottom-color', 'border-color')");
    while ($item = tep_db_fetch_array($query)) {
      if ($borderColors[$item['value']]){
        $borderColors[$item['value']]++;
      } else {
        $borderColors[$item['value']] = 1;
      }
    }
    $query = tep_db_query("select bs.setting_value from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" .tep_db_input($get['theme_name']) . "' and bs.setting_name in ('border-top-color', 'border-left-color', 'border-right-color', 'border-bottom-color', 'border-color')");
    while ($item = tep_db_fetch_array($query)) {
      if ($borderColors[$item['setting_value']]){
        $borderColors[$item['setting_value']]++;
      } else {
        $borderColors[$item['setting_value']] = 1;
      }
    }

    $fontFamily = array();
    $query = tep_db_query("select value from " . TABLE_THEMES_STYLES . " where theme_name = '" .tep_db_input($get['theme_name']) . "' and attribute = 'font-family'");
    while ($item = tep_db_fetch_array($query)) {
      if ($item['value'] != 'FontAwesome' && $item['value'] != 'trueloaded') {
        if ($fontFamily[$item['value']]) {
          $fontFamily[$item['value']]++;
        } else {
          $fontFamily[$item['value']] = 1;
        }
      }
    }
    $query = tep_db_query("select bs.setting_value from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" .tep_db_input($get['theme_name']) . "' and bs.setting_name = 'font-family'");
    while ($item = tep_db_fetch_array($query)) {
      if ($item['setting_value'] != 'FontAwesome' && $item['setting_value'] != 'trueloaded') {
        if ($fontFamily[$item['setting_value']]) {
          $fontFamily[$item['setting_value']]++;
        } else {
          $fontFamily[$item['setting_value']] = 1;
        }
      }
    }

    $fontAdded = array();
    $fontAddedArr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($get['theme_name']) . "' and setting_name = 'font_added'");
    while ($item1 = tep_db_fetch_array($fontAddedArr)){
      preg_match('/font-family:[ \'"]+([^\'^"^;^}]+)/', $item1['setting_value'], $val);
      $fontAdded[] = $val[1];
    }

    return $this->render('styles.tpl', [
        'theme_name' => $get['theme_name'],
        'selectors' => $selectors,
        'list' => $list2,
        'fontColors' => $fontColors,
        'backgroundColors' => $backgroundColors,
        'borderColors' => $borderColors,
        'fontFamily' => $fontFamily,
        'fontAdded' => $fontAdded,
    ]);
  }

    public  function actionStylesChange()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());

        Steps::stylesChange([
            'from' => $get['from'],
            'to' => $get['to'],
            'style' => $get['style'],
            'theme_name' => $get['theme_name']
        ]);


        if ($get['style'] == 'border-color') {
            $attribute = " and attribute in ('border-top-color', 'border-left-color', 'border-right-color', 'border-bottom-color', 'border-color')";
        } else {
            $attribute = " and attribute = '" . tep_db_input($get['style']) . "'";
        }
        tep_db_perform(
            TABLE_THEMES_STYLES,
            array('value' => $get['to']),
            'update',
            " theme_name = '" . tep_db_input($get['theme_name']) . "'" . $attribute . " and value = '" . tep_db_input($get['from']) . "'"
        );

        if ($get['style'] == 'border-color') {
            $setting_name = " and bs.setting_name in ('border-top-color', 'border-left-color', 'border-right-color', 'border-bottom-color', 'border-color')";
        } else {
            $setting_name = " and bs.setting_name = '" . tep_db_input($get['style']) . "'";
        }
        $query = tep_db_query("select bs.id from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" . tep_db_input($get['theme_name']) . "' " . $setting_name . " and bs.setting_value = '" . tep_db_input($get['from']) . "'");
        while ($item = tep_db_fetch_array($query)) {
            tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, array('setting_value' => $get['to']), 'update', " id = '" . $item['id'] . "'");
        }

        Style::createCache($get['theme_name']);

        return '<div style="padding: 30px;">Changed</div><script type="text/javascript">setTimeout(function(){location.reload()}, 500);</script>';
    }

  public  function actionRemoveClass()
  {
    $get = tep_db_prepare_input(Yii::$app->request->get());

    Steps::removeClass([
        'class' => $get['class'],
        'theme_name' => $get['theme_name']
    ]);

    tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" .  tep_db_input($get['theme_name']) . "' and selector = '" . tep_db_input($get['class']) . "'");

    return 'Ok';
  }


    public function actionRemoveHiddenBoxes() {
        $theme_query = tep_db_query("select theme_name from " . TABLE_THEMES . " where 1");
        while ($theme = tep_db_fetch_array($theme_query)) {
            $query = tep_db_query("select bs.box_id from " . TABLE_DESIGN_BOXES_SETTINGS . " bs left join " . TABLE_DESIGN_BOXES . " b on b.id = bs.box_id where bs.setting_name = 'display_none' and bs.visibility = '' and b.theme_name = '" . tep_db_input($theme['theme_name']) . "'");
            $removed = '';
            while ($item = tep_db_fetch_array($query)) {
                $id = $item['box_id'];
                $removed .= $id . '<br>';
                /*Steps::boxDelete([
                    'theme_name' => $theme['theme_name'],
                    'id' => $id
                ]);*/
                //$this->actionBackupAuto($theme['theme_name']);
                //tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int) $id . "'");
                //tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int) $id . "'");
                self::deleteBlock($id);
            }
            tep_db_query("DELETE FROM " . TABLE_THEMES_STYLES . " WHERE visibility > 10 AND visibility NOT IN (SELECT id FROM " . TABLE_THEMES_SETTINGS . " WHERE `setting_name` LIKE 'media_query' )");
        }
        return 'Removed:<br>' . $removed;
    }


    public function actionCreateUpdate() {
        $post = tep_db_prepare_input(Yii::$app->request->post());

        $idArr = [];
        foreach ($post['id_array'] as $item) {
            $idArr[] = (int)$item;
        }

        $query = tep_db_query("
            select * 
            from " . TABLE_THEMES_STEPS . " 
            where
                theme_name = '" . tep_db_input($post['theme_name']) . "' and
                event = 'cssSave' and
                steps_id in('" . implode("','", $idArr) . "')
            order by date_added asc");

        $themeSteps = [];

        while ($item = tep_db_fetch_array($query)) {
            $themeSteps[] = json_decode($item['data'], true);
        }

        $attributes = Style::mergeSteps($themeSteps);

        $attributes['attributes_new'] = Style::changeVisibilityFromIdToWidth($attributes['attributes_new']);
        $attributes['attributes_changed'] = Style::changeVisibilityFromIdToWidth($attributes['attributes_changed']);
        $attributes['attributes_delete'] = Style::changeVisibilityFromIdToWidth($attributes['attributes_delete']);


        $filePath = DIR_FS_CATALOG . 'themes'
            . DIRECTORY_SEPARATOR . $post['theme_name']
            . DIRECTORY_SEPARATOR . 'updates'
            . DIRECTORY_SEPARATOR;
        \yii\helpers\FileHelper::createDirectory($filePath);
        $date = date("U");
        $fileLength = file_put_contents($filePath . $date . '.json', json_encode($attributes));

        if ($fileLength) {
            Style::saveUpdateDate($post['theme_name'], $date);

            return '<div style="padding: 20px; text-align: center">Update created</div>';
        }

        return '<div style="padding: 20px; text-align: center">Error: Update not created</div>';

    }

    public function actionApplyUpdate()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());

        $updates = Style::getNewUpdates($get['theme_name']);

        $update = Style::mergeSteps($updates);

        $update = Style::changeVisibilityFromWidthToId($update, $get['theme_name']);

        $update = Style::addExistValueFromCurrentTheme($update, $get['theme_name']);// and add local_id

        $update = Style::changeSelectorsByVisibility($update);

        $attributesByMedia = Style::addToArraySortedByMediaAndSelector($update, $get['theme_name']);

        if (Yii::$app->request->isAjax) {
            $this->layout = 'popup.tpl';
        }

        return $this->render('apply-update.tpl', [
            'attributes' => $attributesByMedia,
            'theme_name' => $get['theme_name'],
        ]);

    }

    public function actionApplyUpdateSubmit()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());
        $post = tep_db_prepare_input(Yii::$app->request->post());

        $updates = Style::getNewUpdates($get['theme_name']);

        $update = Style::mergeSteps($updates);

        $update = Style::changeVisibilityFromWidthToId($update, $get['theme_name']);

        $update = Style::addExistValueFromCurrentTheme($update, $get['theme_name']);// and add local_id

        Style::saveUpdate($post, $update, $get['theme_name']);

        $sql_data_array = array(
            'theme_name' => $get['theme_name'],
            'setting_group' => 'hide',
            'setting_name' => 'theme_update',
            'setting_value' => date("U"),
        );
        tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);

        return Yii::$app->getResponse()->redirect(['design/log', 'theme_name' => $get['theme_name']]);

    }

    public function actionCssStatus()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());

        $devPath = DIR_FS_CATALOG . 'themes/' . $get['theme_name'] . '/development/';
        if (!is_file($devPath)) {
            \yii\helpers\FileHelper::createDirectory($devPath);
        }

        $development_mode = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where setting_name = 'development_mode' and setting_group = 'hide' and theme_name = '" . tep_db_input($get['theme_name']) . "'"));
        tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where setting_name = 'development_mode' and setting_group = 'hide' and theme_name = '" . tep_db_input($get['theme_name']) . "'");

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES_CACHE . " where theme_name = '" . tep_db_input($get['theme_name']) . "'");
        while ($item = tep_db_fetch_array($query)) {
            if (!$item['accessibility']) {
                $item['accessibility'] = 'main';
            }

            if ($get['status']) {

                file_put_contents($devPath . 'style' . $item['accessibility'] . '.css', $item['css']);

            } elseif (filemtime($devPath . 'style' . $item['accessibility'] . '.css') > $development_mode['setting_value']) {
                $css = file_get_contents($devPath . 'style' . $item['accessibility'] . '.css');

                if ($item['accessibility'] != 'main') {
                    $css = str_replace($item['accessibility'], '', $css);
                }

                $params = [
                    'css' => $css,
                    'theme_name' => $get['theme_name'],
                    'widget' => $item['accessibility'],
                ];
                Style::cssSave($params);
            }
        }

        if ($get['status']) {
            tep_db_perform(TABLE_THEMES_SETTINGS, [
                'theme_name' => $get['theme_name'],
                'setting_name' => 'development_mode',
                'setting_group' => 'hide',
                'setting_value' => date("U"),
            ]);
        }

        $cookies = Yii::$app->response->cookies;
        $cookies->add(new \yii\web\Cookie([
            'name' => 'css_status',
            'value' => $get['status'],
        ]));

        return 'ok';

    }

    public function actionStyleTab()
    {
        \common\helpers\Translation::init('admin/design');
        $get = tep_db_prepare_input(Yii::$app->request->get());

        if ($get['box_id']) {
            $query = tep_db_query("
                select setting_name, setting_value
                from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " 
                where 
                    box_id = '" . (int)$get['box_id'] . "' and 
                    visibility = '" . tep_db_input($get['visibility'] ? $get['visibility'] : '') . "' and
                    language_id = '0'
            ");
        } elseif ($get['data_class']) {
            $query = tep_db_query("
                select attribute as setting_name, value as setting_value
                from " . TABLE_THEMES_STYLES . " 
                where theme_name = '" .  tep_db_input($get['theme_name']) . "' and
                selector = '" . tep_db_input($get['data_class']) . "' and 
                visibility = '" . tep_db_input($get['visibility'] ? $get['visibility'] : '') . "'
            ");
        }

        $value = [];
        while ($item = tep_db_fetch_array($query)) {
            $value[$item['setting_name']] = $item['setting_value'];
        }

        $this->layout = 'popup.tpl';

        $font_added = array();
        $font_added_arr = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($get['theme_name']) . "' and setting_name = 'font_added'");
        while ($item1 = tep_db_fetch_array($font_added_arr)){
            preg_match('/font-family:[ \'"]+([^\'^"^;^}]+)/', $item1['setting_value'], $val);
            $font_added[] = $val[1];
        }

        return $this->render('/../design/boxes/views/include/style_tab.tpl', [
            'id' => $get['id'],
            'name' => $get['name'],
            'value' => $value,
            'responsive' => ($get['visibility'] > 10 ? '1' : ''),
            'responsive_settings' => json_decode($get['responsive_settings'], true),
            'block_view' => $get['block_view'],
            'font_added' => $font_added,
        ]);

    }

    public function actionChooseView()
    {
        $get = tep_db_prepare_input(Yii::$app->request->get());

        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/choose-view'), 'title' => 'Choose View "' . Theme::getThemeTitle($get['theme_name']) . '"');
        $this->selectedMenu = array('design_controls', 'design/themes');

        return $this->render('choose-view.tpl', [
            'theme_name' => $get['theme_name'],
            'theme_name_mobile' => $get['theme_name'] . '-mobile'
        ]);
    }

    public function actionCreateMobileTheme()
    {
        \common\helpers\Translation::init('admin/design');

        $theme_name = Yii::$app->request->get('theme_name');

        if (substr($theme_name, -7) !== '-mobile') {
            return WRONG_THEME_NAME;
        }

        $desktop_theme_name = substr($theme_name, 0, -7);

        $theme = tep_db_fetch_array(tep_db_query("select id from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($desktop_theme_name) . "'"));
        if (!$theme['id']) {
            return WRONG_THEME_NAME;
        }

        Theme::themeRemove($theme_name, false);
        Theme::copyTheme($theme_name, $desktop_theme_name, 'link');
        Style::createCache($theme_name);

        return TEXT_CREATED;
    }

    public function actionCreateStylesFile()
    {
        \common\helpers\Translation::init('admin/design');

        $theme_name = Yii::$app->request->get('theme_name', 'theme-1');

        $allStyles = ThemesStyles::find()
            ->select(['selector', 'attribute', 'value', 'visibility', 'media', 'accessibility'])
            ->where(['theme_name' => $theme_name])
            ->andWhere(['not', ['accessibility' => '']])
            ->orderBy('accessibility')
            ->asArray()
            ->all();

        $stylesByAccessibility = [];
        foreach ($allStyles as $styles) {
            $stylesByAccessibility[$styles['accessibility']][] = [
                'selector' => $styles['selector'],
                'attribute' => $styles['attribute'],
                'value' => $styles['value'],
                'visibility' => $styles['visibility'],
                'media' => $styles['media'],
            ];
        }

        $stylesJson = json_encode($stylesByAccessibility);
        file_put_contents(DIR_FS_CATALOG . 'themes/basic/styles.json', $stylesJson);


        $widgetAreas = DesignBoxes::find()
            ->where(['theme_name' => $theme_name])
            ->andWhere(['not', ['block_name' => '']])
            ->andWhere(['not', ['block_name' => 'block-%']])
            ->asArray()
            ->all();

        $boxes = [];
        foreach ($widgetAreas as $area) {
            $boxes[$area['block_name']][] = Theme::blocksTree($area['id']);
        }

        $boxesJson = json_encode($boxes);
        file_put_contents(DIR_FS_CATALOG . 'themes/basic/boxes.json', $boxesJson);


        $pagesArr = ThemesSettings::find()
            ->where([
                'theme_name' => $theme_name,
                'setting_group' => 'added_page',
            ])
            ->asArray()
            ->all();

        $pages = [];
        foreach ($pagesArr as $page) {

            $settings = [];
            $settingsArr = ThemesSettings::find()
                ->where([
                    'theme_name' => $theme_name,
                    'setting_group' => 'added_page_settings',
                    'setting_name' => $page['setting_value'],
                ])
                ->asArray()
                ->all();
            foreach ($settingsArr as $setting) {
                $settings[] = $setting['setting_value'];
            }

            $pages[] = [
                'type' => $page['setting_name'],
                'name' => $page['setting_value'],
                'settings' => $settings
            ];
        }

        $pagesJson = json_encode($pages);
        file_put_contents(DIR_FS_CATALOG . 'themes/basic/pages.json', $pagesJson);


        return 'Created';
    }

    public function actionApplyStylesFile()
    {
        \common\helpers\Translation::init('admin/design');

        $theme_name = Yii::$app->request->get('theme_name', 'theme-1');

        $stylesByAccessibility = json_decode(file_get_contents(DIR_FS_CATALOG . 'themes/basic/styles.json'), true);

        $s = 0;
        foreach ($stylesByAccessibility as $accessibility => $styles) {
            $count = ThemesStyles::find()
                ->where([
                    'accessibility' => $accessibility,
                    'theme_name' => $theme_name,
                ])
                ->count();
            if ($count == 0) {
                $s++;
                $insertingStyles = [];
                foreach ($styles as $style) {
                    $insertingStyles[] = [
                        'theme_name' => $theme_name,
                        'selector' => $style['selector'],
                        'attribute' => $style['attribute'],
                        'value' => $style['value'],
                        'visibility' => $style['visibility'],
                        'media' => $style['media'],
                        'accessibility' => $accessibility,
                    ];
                }

                $columnNameArray = [
                    'theme_name',
                    'selector',
                    'attribute',
                    'value',
                    'visibility',
                    'media',
                    'accessibility',
                ];

                Yii::$app->db->createCommand()
                    ->batchInsert(
                        'themes_styles', $columnNameArray, $insertingStyles
                    )
                    ->execute();

            }
        }


        $boxesAreas = json_decode(file_get_contents(DIR_FS_CATALOG . 'themes/basic/boxes.json'), true);

        $b = 0;
        foreach ($boxesAreas as $area => $boxes) {

            $count = DesignBoxes::find()
                ->where([
                    'block_name' => $area,
                    'theme_name' => $theme_name,
                ])
                ->count();
            if ($count == 0) {
                $b++;
                foreach ($boxes as $box) {
                    Theme::blocksTreeImport($box, $theme_name, '', '', true);
                }
            }
        }


        $pagesArr = json_decode(file_get_contents(DIR_FS_CATALOG . 'themes/basic/pages.json'), true);

        foreach ($pagesArr as $page) {
            $count = ThemesSettings::find()
                ->where([
                    'theme_name' => $theme_name,
                    'setting_group' => 'added_page',
                    'setting_name' => $page['type'],
                    'setting_value' => $page['name'],
                ])
                ->count();
            if ($count == 0) {
                $settings = new ThemesSettings();
                $settings->attributes  = [
                    'theme_name' => $theme_name,
                    'setting_group' => 'added_page',
                    'setting_name' => $page['type'],
                    'setting_value' => $page['name'],
                ];
                $settings->save();

                if (count($page['settings']) > 0){
                    foreach ($page['settings'] as $set){

                        $settings = new ThemesSettings();
                        $settings->attributes  = [
                            'theme_name' => $theme_name,
                            'setting_group' => 'added_page_settings',
                            'setting_name' => $page['name'],
                            'setting_value' => $set,
                        ];
                        $settings->save();

                    }
                }
            }
        }

        $this->redirect(\yii\helpers\Url::toRoute(['design/settings', 'theme_name' => $theme_name]));
        //return 'styles: ' . $s . '; blocks: ' . $b;
    }

    public function actionGetComponentHtml()
    {
        $getRequest = \Yii::$app->request->get();
        if (!$getRequest['name']) {
            return '';
        }

        $platformsToThemes = \common\models\PlatformsToThemes::findOne((int)$getRequest['platform_id']);
        $themes = \common\models\Themes::findOne($platformsToThemes['theme_id']);
        $theme_name = $themes->theme_name;

        $getRequest['theme_name'] = $theme_name;
        if ($getRequest['option'] && $getRequest['option_val']) {
            $getRequest[$getRequest['option']] = $getRequest['option_val'];
        }

        define('THEME_NAME', $theme_name);

        $block = \frontend\design\Block::widget([
            'name' => \common\classes\design::pageName($getRequest['name']),
            'params' => [
                'params' => $getRequest,

            ]
        ]);

        $css = file_get_contents(Info::themeFile('/css/base_3.css', 'fs'));

        $widgets = \frontend\design\Info::getWidgetsNames();
        $areaArr[] = '';
        foreach ($widgets as $widget) {
            $areaArr[] = tep_db_input($widget);
        }
        $area = "'" . implode("','", $areaArr) . "'";
        $query = tep_db_query("select css from " . TABLE_THEMES_STYLES_CACHE . " where theme_name = '" . tep_db_input($theme_name) . "' and accessibility in(" . $area . ")");

        while ($item = tep_db_fetch_array($query)) {
            $css .= $item['css'];
        }
        $css .= \frontend\design\Block::getStyles();
        $css = \frontend\design\Info::minifyCss($css);

        $css = '<style type="text/css">' . $css . '</style>';

        return $block . $css;

    }

    public function actionWebp ()
    {
        \common\helpers\Translation::init('admin/design');
        $this->selectedMenu = array('design_controls', 'design/themes');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/themes'), 'title' => 'Create webp images');
        $this->view->headingTitle = TITLE_CREATE_WEBP_IMAGES;

        $buttonSettings = \common\helpers\Acl::getExtensionCreateImagesSettings();

        return $this->render('webp.tpl', [
            'imagewebp' => function_exists('imagewebp'),
            'buttonSettings' => $buttonSettings
        ]);
    }

    public function actionCreateWebp ()
    {
        $type = Yii::$app->request->get('type', false);
        $iteration = (int)\Yii::$app->request->get('iteration', 0);

        return \common\classes\Images::createAllWebpImages($type, $iteration);
    }

    public function actionCreatePdfFont() {
        $fontPath = Yii::$app->request->post('font_path');
        if (substr($fontPath, 0, 4) == 'http'){
            return \TCPDF_FONTS::addTTFfont($fontPath);
        } else {
            if (is_file(DIR_FS_CATALOG . $fontPath)) {
                return \TCPDF_FONTS::addTTFfont(DIR_FS_CATALOG . $fontPath);
            } else {
                return false;
            }
        }
    }
}

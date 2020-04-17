<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design;

use Yii;
use yii\base\Widget;
use frontend\design\Info;

class Block extends Widget
{

    public $name;
    public $params;
    public static $widgetsList = [];

    public function init()
    {
        parent::init();
    }

    public static function getStyles(){
        global $block_styles;

        return \backend\design\Style::getStylesWrapper($block_styles);
    }

    public static function styles($settings, $teg = false)
    {
        $style = '';

        $style .= \backend\design\Style::getAttributes($settings[0]);

        if ($settings[0]['display_none']){
            $style .= 'display:none;';
        }

        if ($settings[0]['box_align']){
            if ($settings[0]['box_align'] == 1){
                $style .= 'float: left;clear: none;';
            }
            if ($settings[0]['box_align'] == 2){
                $style .= 'display: inline-block;';
            }
            if ($settings[0]['box_align'] == 3){
                $style .= 'float: right;clear: none;';
            }
        }


        if ($style && $teg) {
            $style = ' style="' . $style . '"';
        }


        return $style;
    }

  public function colInRow($val, $id)
  {

    $htm = '#box-' . $id . ' .products-listing.cols-' . $val . ' div.item:nth-child(n){clear:none;width:' . round(100/$val, 4) . '%}';
    $htm .= '#box-' . $id . ' .products-listing.cols-' . $val . ' div.item:nth-child(' . $val . 'n+1){clear: both}';
    $htm .= '#box-' . $id . ' .products-listing.cols-1 div.item:nth-child(n){clear:none;width:100%}';
    $htm .= '#box-' . $id . ' .products-listing.cols-1 div.item{clear: both}';

    return $htm;
  }

  public function run()
  {
    global $block_styles, $allWidgetsOnPage;

    $colInRowCarousel = [];
    static $media_query = false;
    static $mediaNames = false;
    static $maxWidth = false;
    static $mediaTheme = THEME_NAME;

    self::addBlockName($this->name);

    $chooseTheme = '';
    if (substr($this->name, 0, 6) != 'block-') {
        $chooseTheme = " and theme_name = '" . THEME_NAME . "' ";
    }

    $items_query = tep_db_query("select id, widget_name, widget_params from " . (\frontend\design\Info::isAdmin() ? TABLE_DESIGN_BOXES_TMP : TABLE_DESIGN_BOXES) . " where block_name = '" . $this->name . "' " . $chooseTheme . " order by sort_order");

    $count = tep_db_num_rows($items_query);

      if ($count == 0 && !Info::isAdmin() && substr($this->name, 0, 6) != 'block-') {
          foreach (Info::$themeMap as $theme) {
              if ($theme == THEME_NAME || $theme == 'basic') {
                  continue;
              }
              $items_query = tep_db_query("select id, widget_name, widget_params from " . (\frontend\design\Info::isAdmin() ? TABLE_DESIGN_BOXES_TMP : TABLE_DESIGN_BOXES) . " where block_name = '" . $this->name . "' and theme_name = '" . $theme . "' order by sort_order");
              $count = tep_db_num_rows($items_query);
              if ($count > 0) {
                  $mediaTheme = $theme;
                  break;
              }
          }
      }

      if ( !is_array($media_query) || substr($this->name, 0, 6) != 'block-') {
          $media_query = [];
          $maxWidth = [];
          $media_query_arr = tep_db_query("SELECT * FROM " . TABLE_THEMES_SETTINGS . " WHERE theme_name = '" . $mediaTheme . "' AND setting_name = 'media_query'");
          while ($item = tep_db_fetch_array($media_query_arr)) {
              $media_query[] = $item;
              $mediaNames[$item['id']] = $item['setting_value'];
              $sizes = explode('w', $item['setting_value']);
              if (!$sizes[0] && $sizes[1]) {
                  $maxWidth[$item['id']] = $sizes[1];
              }
          }
      }

    $block = '';
    $blockOpen = '';
    if ($count > 0 || \frontend\design\Info::isAdmin() || !$this->params['tabs']){
        $blockOpen .= '<div class="block' . ($this->params['type'] ? ' ' . $this->params['type'] : '') . '"' . (\frontend\design\Info::isAdmin() ? ' data-name="' . $this->name . '"' . ($this->params['type'] ? ' data-type="' . $this->params['type'] . '"' : '') . ($this->params['cols'] ? ' data-cols="' . $this->params['cols'] . '"' : '') : '') . ($this->params['tabs'] ? ' id="tab-' . $this->name . '"' : '') . '>';
    }
    if ($count > 0) {
      while ($item = tep_db_fetch_array($items_query)) {
        $widget_array = array();

        $settings = array();
        $visibility = array();
        $settings_query = tep_db_query("select * from " . (\frontend\design\Info::isAdmin() ? TABLE_DESIGN_BOXES_SETTINGS_TMP : TABLE_DESIGN_BOXES_SETTINGS) . " where box_id = '" . (int)$item['id'] . "'");
          while ($set = tep_db_fetch_array($settings_query)) {
              if ($set['visibility'] > 0){
                  $visibility[$set['visibility']][$set['language_id']][$set['setting_name']] = $set['setting_value'];
                  if ($mediaNames[$set['visibility']]) {
                      $settings['visibility'][$set['setting_name']][$mediaNames[$set['visibility']]] = $set['setting_value'];
                  }
                  if ($set['setting_name'] == 'col_in_row' && $maxWidth[$set['visibility']]) {
                      $colInRowCarousel[$maxWidth[$set['visibility']]] = $set['setting_value'];
                  }
              } else {
                  $settings[$set['language_id']][$set['setting_name']] = $set['setting_value'];
              }
          }

        if (!$settings[0]['ajax']) {
            $allWidgetsOnPage[$item['widget_name']] = $item['widget_name'];
        }

        if ($item['widget_name'] == 'Html')$item['widget_name'] = 'Html_box';
        $widget_name = 'frontend\design\boxes\\' . $item['widget_name'];

        //$widget_array['params'] = $item['widget_params'];

        $widget_array['params'] = $this->params['params'];
        $widget_array['id'] = $item['id'];

        $settings[0]['params'] = $item['widget_params'];
        if (isset($this->params['params']['page_block'])) {
            $settings[0]['page_block'] = $this->params['params']['page_block'];
        }
        $settings['colInRowCarousel'] = $colInRowCarousel;

        $widget_array['settings'] = $settings;

          $visibilityOverlap = false;
          $pageName = Yii::$app->request->get('page_name');
          if ($pageName) {
              $query = tep_db_query("
                  select * 
                  from " . TABLE_THEMES_SETTINGS . " 
                  where theme_name = '" . THEME_NAME . "' and setting_group = 'added_page_settings'");

              $themeSettings = [];
              while ($themeSettingsItem = tep_db_fetch_array($query)) {
                  if (\common\classes\design::pageName($themeSettingsItem['setting_name']) == $pageName) {
                      $themeSettings[$themeSettingsItem['setting_value']] = 1;
                  }
              }

              if (
                ($settings[0]['visibility_first_view'] && !$themeSettings['first_visit']
                    || !$settings[0]['visibility_first_view'] && $themeSettings['first_visit']) &&
                ($settings[0]['visibility_more_view'] && !$themeSettings['more_visits']
                    || !$settings[0]['visibility_more_view'] && $themeSettings['more_visits']) &&
                ($settings[0]['visibility_logged'] && !$themeSettings['logged_customer']
                    || !$settings[0]['visibility_logged'] && $themeSettings['logged_customer']) &&
                ($settings[0]['visibility_not_logged'] && !$themeSettings['not_logged']
                    || !$settings[0]['visibility_not_logged'] && $themeSettings['not_logged'])
              ) {
                  $visibilityOverlap = true;
              }
          }

          $cookies = Yii::$app->request->cookies;

        if ((
            !$visibilityOverlap &&
            !(
                !$settings[0]['visibility_first_view'] && Yii::$app->user->isGuest && !$cookies->has('was_visit') ||
                !$settings[0]['visibility_more_view'] && Yii::$app->user->isGuest && $cookies->has('was_visit') ||
                !$settings[0]['visibility_logged'] && !Yii::$app->user->isGuest ||
                !$settings[0]['visibility_not_logged'] && Yii::$app->user->isGuest
            ) ||

            Yii::$app->controller->id == 'index' && Yii::$app->controller->action->id == 'index' && $settings[0]['visibility_home'] ||
            Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'product' && $settings[0]['visibility_product'] ||
            Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'index' && $settings[0]['visibility_catalog'] ||
            Yii::$app->controller->id == 'info' && Yii::$app->controller->action->id == 'index' && $settings[0]['visibility_info'] ||
            Yii::$app->controller->id == 'shopping-cart' && Yii::$app->controller->action->id == 'index' && $settings[0]['visibility_cart'] ||
            Yii::$app->controller->id == 'checkout' && Yii::$app->controller->action->id != 'success' && $settings[0]['visibility_checkout'] ||
            Yii::$app->controller->id == 'checkout' && Yii::$app->controller->action->id == 'success' && $settings[0]['visibility_success'] ||
            Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id != 'login' && $settings[0]['visibility_account'] ||
            Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id == 'login' && $settings[0]['visibility_login']
        ) && !\frontend\design\Info::isAdmin()){
        } elseif((
          !(Yii::$app->controller->id == 'index' && Yii::$app->controller->action->id == 'index' ||
          Yii::$app->controller->id == 'index' && Yii::$app->controller->action->id == 'design' ||
          Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'product' ||
          Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'index' ||
          Yii::$app->controller->id == 'info' && Yii::$app->controller->action->id == 'index' ||
          Yii::$app->controller->id == 'cart' && Yii::$app->controller->action->id == 'index' ||
          Yii::$app->controller->id == 'checkout' && Yii::$app->controller->action->id != 'success' ||
          Yii::$app->controller->id == 'checkout' && Yii::$app->controller->action->id == 'success' ||
          Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id != 'login' ||
          Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id == 'login') &&
          $settings[0]['visibility_other']
        ) && !\frontend\design\Info::isAdmin()) {
        } else {

          if ($_GET['to_pdf']) {
            $settings[0]['p_width'] = Info::blockWidth($item['id']);
          }

          if ($settings[0]['ajax'] && !\frontend\design\Info::isAdmin()){
            $widget = '
<div class="preloader"></div>
<script type="text/javascript">
  tl(function(){
    $.get("' . tep_href_link('get-widget/one') . '", {
          id: "' . $item['id'] . '",
          action: "' . Yii::$app->controller->id . '/' . Yii::$app->controller->action->id . '",
          ' . (count($_GET) > 0 ? str_replace('{', '', str_replace('}', '', json_encode($_GET))) : '') . '
    }, function(d){
      $("#box-' . $item['id'] . '").html(d)
    })
  });
</script>
';
          } else {
            if (is_file(Yii::getAlias('@app') . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'boxes' . DIRECTORY_SEPARATOR .  str_replace('\\', DIRECTORY_SEPARATOR, $item['widget_name']) . '.php')){
                $widget = $widget_name::widget($widget_array);
            } elseif (($ext_widget = \common\helpers\Acl::runExtensionWidget($item['widget_name'], $widget_array)) !== false){
                $widget = $ext_widget;
            } else {
                $widget = '';
            }
          }
          if ($settings[0]['style_class']) {
              $styleClass = $settings[0]['style_class'];
              $styleClass = preg_replace('/[\s]+/', ' ', $styleClass);
              $classes = explode(' ', $styleClass);
              foreach ($classes as $class) {
                  \frontend\design\Info::addCustomClassToCss($class);
              }
          }

          $assetName = '\frontend\assets\boxes\\' . $item['widget_name'] . 'Asset';
          if (class_exists($assetName)){
              $assetName::register($this->view);
          }

          self::addToWidgetsList($item['widget_name']);

          $page_block = $this->params['params']['page_block'] ?? Info::pageBlock();

          if ($widget != '' || \frontend\design\Info::isAdmin()) {
              $block .=
                  '<div class="box' .
                  ($item['widget_name'] == 'BlockBox' || $item['widget_name'] == 'Tabs' || $item['widget_name'] == 'invoice\Container' || $item['widget_name'] == 'email\BlockBox' ? '-block type-' . $settings[0]['block_type'] : '') .
                  ($item['widget_name'] == 'Tabs' ? ' tabs' : '') .
                  ($settings[0]['style_class'] ? ' ' . $settings[0]['style_class'] : '') .
                  self::nameToClass($item['widget_name']) .
                  '" ' .
                  ($page_block == 'orders' || $page_block == 'email' || $page_block == 'packingslip' || $page_block == 'invoice' || $page_block == 'pdf' || $page_block == 'pdf_cover' || $page_block == 'gift_card' || $page_block == 'trade_form_pdf' || $this->params['params']['inline_styles'] ? self::styles($settings, true) : '') . ' data-name="' . $item['widget_name'] . '" id="box-' . $item['id'] . '">';
          }

          $style = self::styles($settings);
          $hover = self::styles($visibility[1]);
          $active = self::styles($visibility[2]);
          $before = self::styles($visibility[3]);
          $after = self::styles($visibility[4]);
          if ($style) {
            $block_styles[0] .= '#box-' . $item['id'] . '{' . $style . '}';
          }
          if ($hover) {
            $block_styles[0] .= '#box-' . $item['id'] . ':hover{' . $hover . '}';
          }
          if ($active) {
            $block_styles[0] .= '#box-' . $item['id'] . '.active{' . $active . '}';
          }
          if ($before) {
            $block_styles[0] .= '#box-' . $item['id'] . ':before{' . $before . '}';
          }
          if ($after) {
            $block_styles[0] .= '#box-' . $item['id'] . ':after{' . $after . '}';
          }
          if ($settings[0]['col_in_row']){
            $block_styles[0] .= $this->colInRow($settings[0]['col_in_row'], $item['id']);
          }
          foreach ($media_query as $item2){
            $style = self::styles($visibility[$item2['id']]);
            if ($style){
              $block_styles[$item2['id']] .= '#box-' . $item['id'] . '{' . $style . '}';
            }
            if ($visibility[$item2['id']][0]['only_icon']){
              $block_styles[$item2['id']] .= '#box-' . $item['id'] . ' .no-text {display:none;}';
            }
            if ($visibility[$item2['id']][0]['schema']){
              $block_styles[$item2['id']] .= \backend\design\Style::schema($visibility[$item2['id']][0]['schema'], '#box-' . $item['id']);
            }
            if ($visibility[$item2['id']][0]['col_in_row']){
              $block_styles[$item2['id']] .= $this->colInRow($visibility[$item2['id']][0]['col_in_row'], $item['id']);
            }
          }

          if ($widget == ''){
            if (\frontend\design\Info::isAdmin()) $block .= '<div class="no-widget-name">Here added ' . $item['widget_name'] . ' widget</div>';
          } else {
            $block .= $widget;
          }
          if ($widget != '' || \frontend\design\Info::isAdmin()) $block .= '</div>';

        }


      }
    }
    if ($count > 0 || \frontend\design\Info::isAdmin() || !$this->params['tabs']){
        if ($block){
            $block = $blockOpen . $block . '</div>';
        } elseif (!$block && Info::isAdmin()) {
            $block = $blockOpen . '&nbsp;</div>';
        }
    }

    return $block;
  }



    public static function nameToClass($name)
    {
        $class = preg_replace('/([A-Z])/', "-\$1", $name);
        $class = str_replace('\\', '-', $class);
        $class = ' w-' . $class;
        $class = str_replace('--', '-', $class);
        $class = strtolower($class);

        return $class;
    }


    public static $blockNamesStr;

    public static function addBlockName($name)
    {
        if (substr($name, 0, 6) != 'block-' && $name != 'header' && $name != 'footer') {
            self::$blockNamesStr .= '_' . $name;
        }
    }

    public static function addToWidgetsList($name)
    {
        if (!in_array($name, self::$widgetsList)){
            self::$widgetsList[] = $name;
        }
    }


}

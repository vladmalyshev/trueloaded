<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use frontend\design\Info;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\helpers\MenuHelper;
use yii\helpers\Html;
use frontend\design\EditData;

class Menu extends Widget
{

  public $params;
  public $settings;
  public $id;

  public function init()
  {
    parent::init();
  }

    public function getAccountPages()
    {
        $settings = tep_db_query("select DISTINCT setting_value from " . TABLE_THEMES_SETTINGS . " where setting_group = 'added_page' and setting_name = 'account'");
        $i = 0;
        while ($item = tep_db_fetch_array($settings)) {
            $i++;
            $account_pages[] = ['type_id' => hexdec(substr(md5($item['setting_value']),0,7)), 'name' => $item['setting_value']];
        }
        return $account_pages;
    }

  public function run()
  {
    global $cPath_array;
    global $request_type;
    $languages_id = \Yii::$app->settings->get('languages_id');

    $sab_categories = array();

    $brands = \common\helpers\MenuHelper::getBrandsList();

      $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

    $accountPages = $this->getAccountPages();

    static $categories_all = array();
    if (!$categories_all){
        $categories_join = '';
        if ( \common\classes\platform::activeId() ) {
          $categories_join .= " inner join " . TABLE_PLATFORMS_CATEGORIES . " plc on c.categories_id = plc.categories_id  and plc.platform_id = '" . \common\classes\platform::currentId() . "' ";
        }
        $sql = tep_db_query("
                select c.categories_id, c.parent_id, cd.categories_name
                from " . TABLE_CATEGORIES . " c {$categories_join}
                  left join " . TABLE_CATEGORIES_DESCRIPTION . " cd on cd.categories_id = c.categories_id and cd.language_id = " . (int)$languages_id . "
                where c.categories_status = 1 and cd.affiliate_id = 0
                order by c.sort_order, cd.categories_name
              ");
        while ($row = tep_db_fetch_array($sql)) {
          $categories_all[$row['categories_id']] = $row;
        }
    }


    $is_menu = false;
    $menu = array();
    $sql = tep_db_query("
            select i.id, i.parent_id, i.link, i.link_id, i.link_type, i.target_blank, i.class, i.sub_categories, i.custom_categories, i.sort_order, t.title, i.menu_id
            , m.last_modified, cd.categories_name
            from " . TABLE_MENUS . " m
              inner join " . TABLE_MENU_ITEMS . " i on i.menu_id = m.id and i.platform_id='".\common\classes\platform::currentId()."'
              left join " . TABLE_MENU_TITLES . " t on t.item_id = i.id and t.language_id = " . (int)$languages_id . "
              left join " . TABLE_CATEGORIES_DESCRIPTION . " cd on cd.language_id =" . (int)$languages_id . " and cd.categories_id=i.link_id and i.link_type='categories'
            where
              m.menu_name = '" . $this->settings[0]['params'] . "'
            order by i.sort_order
          ");
    while ($row = tep_db_fetch_array($sql)) {

      if ($row['link_type'] == 'info') {

        if (!$row['title']) {
          $sql1=tep_db_query("SELECT information_id, info_title, page_title from " . TABLE_INFORMATION ." WHERE visible='1' and languages_id =".(int)$languages_id." and information_id='" . $row['link_id'] . "' AND platform_id='".\common\classes\platform::currentId()."' ");
          while($row1=tep_db_fetch_array($sql1)){
            if ($row1['info_title']) $row['title'] = $row1['info_title'];
            elseif ($row1['page_title']) $row['title'] = $row1['page_title'];
          }
        }

        $row['link'] = tep_href_link('info', 'info_id=' . $row['link_id']);

        if (Yii::$app->controller->id == 'info' && $_GET['info_id'] == $row['link_id']){
          $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
        }

      } elseif ($row['link_type'] == 'categories') {
          $new_categories = [];
        if (!$row['title']) {
          if ($row['link_id'] == '999999999') {
            //$row['title'] = 'All categories';
            /// new categories (added after menu update) are shown in any case.
            $sql3 = tep_db_query(
              "select c.categories_id, c.parent_id, cd.categories_name ".
              "from " . TABLE_CATEGORIES . " c  ".
              " inner join ".TABLE_PLATFORMS_CATEGORIES." pc on pc.categories_id=c.categories_id and pc.platform_id='".\common\classes\platform::currentId()."' ".
              " left join " . TABLE_CATEGORIES_DESCRIPTION . " cd on c.categories_id = cd.categories_id  ".
              "where c.date_added > '" . $row['last_modified'] . "' and cd.language_id = '" . $languages_id . "' and cd.affiliate_id=0 and c.categories_status=1"
            );
            if (tep_db_num_rows($sql3) > 0){
              while ($item = tep_db_fetch_array($sql3)){
                $new_categories[] = $item;
              }
            }
          } else {
            if (!empty($row['categories_name'])) {
              $row['title'] = $row['categories_name'];
            }
          }
          if (count($new_categories) > 0){
            if ($row['link_id'] == 999999999)$current = 0;
            else $current = $row['link_id'];
            foreach ($new_categories as $item){
              if ($item['parent_id'] == $current){
                if ( Info::themeSetting('show_empty_categories') ) {
                  if ( Info::themeSetting('show_category_product_count')) {
                      $r_count = \common\helpers\Categories::count_products_in_category($item['categories_id']);
                  } else {
                    $r_count = 1;
                  }
                }else{
                  if ( Info::themeSetting('show_category_product_count')) {
                    $r_count = \common\helpers\Categories::count_products_in_category($item['categories_id']);
                  } else {
                    $r_count = \common\helpers\Categories::notEmpty($item['categories_id']);
                    
                  }
                }

                $menu[] = array(
                  'count' => $r_count,
                  'parent_id' => $row['id'],
                  'link_type' => 'categories',
                  'name' => $item['categories_name'],
                  'link_id' => $item['categories_id'],
                  'new_category' => $item['categories_id'],
                  'title' => $item['categories_name'],
                  'link' => tep_href_link('catalog', 'cPath=' . $item['categories_id']),
                );
              }
            }
          }
        }

        if ($row['sub_categories']){
          $sab_categories[] = $row['id'];
        }
        if ( Info::themeSetting('show_empty_categories') ) {
            $row['count'] = 1;
        }else{
            if ( Info::themeSetting('show_category_product_count')) {
              $row['count'] = \common\helpers\Categories::count_products_in_category($row['link_id'])>0?1:-1;
            } else {
              $row['count'] = \common\helpers\Categories::notEmpty($row['link_id'])>0?1:-1;
            }
        }
        if ( Info::themeSetting('show_category_product_count') && $row['count']!=-1 ) {
            $row['count'] = \common\helpers\Categories::count_products_in_category($row['link_id']);
        }

        $row['link'] = tep_href_link('catalog', 'cPath=' . $row['link_id']);

        if (Yii::$app->controller->id == 'catalog'){
          if (is_array($cPath_array)){
            $cp = $cPath_array;
          } else {
            $cp = explode('_', $_GET['cPath']);
          }
          if (in_array($row['link_id'], $cp)) {
            $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
          }
        }

      } elseif ($row['link_type'] == 'brands') {

          $row['link'] = Yii::$app->urlManager->createUrl(['catalog', 'manufacturers_id' => $row['link_id']]);
          if (!$row['title']) {
              if ($row['link_id'] == '999999998') {
                  //$row['title'] = 'All bands';
                  /// new bands (added after menu update) are shown in any case.
                  $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name, manufacturers_image from " . TABLE_MANUFACTURERS ." where date_added > '" . $row['last_modified'] . "' order by manufacturers_name asc");
                  if (tep_db_num_rows($manufacturers_query) > 0){
                      while ($item = tep_db_fetch_array($manufacturers_query)){
                          $new_bands[] = $item;
                      }
                  }
              } else {
                  $row['title'] = $brands[$row['link_id']]['manufacturers_name'];
              }
              if (is_array($new_bands) && count($new_bands) > 0){
                  if ($row['link_id'] == 999999998)$current = 0;
                  else $current = $row['link_id'];
                  foreach ($new_bands as $item){
                      if ($item['parent_id'] == $current){
                          //$r_count = count(\common\helpers\Manufacturers::products_ids_manufacturer($item['manufacturers_id'], false, \common\classes\platform::currentId())) ? -1 : 0;
                          $r_count = \common\helpers\Manufacturers::hasAnyProduct($item['manufacturers_id'], false, \common\classes\platform::currentId()) ? -1 : 0;

                          $menu[] = array(
                              'count' => $r_count,
                              'parent_id' => $row['id'],
                              'link_type' => 'bands',
                              'name' => $item['manufacturers_name'],
                              'link_id' => $item['manufacturers_id'],
                              'title' => $item['manufacturers_name'],
                              'link' => Yii::$app->urlManager->createUrl(['catalog', 'manufacturers_id' => $item['manufacturers_id']]),
                          );
                      }
                  }
              }
              $m = \common\helpers\Manufacturers::get_manufacturer_info('manufacturers_name', $row['link_id']);
              $row['title'] = $m;
          }
          //$row['count'] = count(\common\helpers\Manufacturers::products_ids_manufacturer($row['link_id'], false, \common\classes\platform::currentId())) === 0 ? -1 : 0;
          $row['count'] = \common\helpers\Manufacturers::hasAnyProduct($row['link_id'], false, \common\classes\platform::currentId())? -1 : 0;



          $row['link'] = Yii::$app->urlManager->createUrl(['catalog', 'manufacturers_id' => $row['link_id']]);

          if (Yii::$app->controller->id == 'catalog'){
              if (
                  //link_id => manufacturer_id products_ids_manufacturer returns products_id
                  //in_array($row['link_id'], \common\helpers\Manufacturers::products_ids_manufacturer($item['manufacturers_id'], false, \common\classes\platform::currentId())) ||
                  $row['link_id'] == $_GET['manufacturers_id']
              ) {
                  $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
          }

      } elseif ($row['link_type'] == 'all-products') {
          
          $output = [];
          parse_str($row['custom_categories'], $output);
          foreach ($output as $ok => $ov) {
              if (empty($ov)) {
                  unset($output[$ok]);
              }
          }
          
          $row['link'] = tep_href_link('catalog/all-products', http_build_query($output));
          
          if (!$row['title']) {
            if (!$row['link']) {
              $row['title'] = $row['link'];
            }
          }
          
      } elseif ($row['link_type'] == 'custom') {

        if (!$row['title']) {
          if (!$row['link']) {
            $row['title'] = $row['link'];
          }
        }

        if (strpos($row['link'], 'http') !== 0 && strpos($row['link'], '//') !== 0 && $row['link']){
          $arr = explode('?', $row['link']);
          //$row['link'] = tep_href_link($arr[0], $arr[1],preg_match('/^(account|checkout)/', $arr[0])?'SSL':'NONSSL');
          $row['link'] = tep_href_link($arr[0], $arr[1],$request_type);
        }

        if (str_replace('//', '', str_replace('http://', '', str_replace('https://', '', $row['link']))) == $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']){
          $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
        }

      } elseif ($row['link_type'] == 'account') {

          $pageName = '';
          foreach ($accountPages as $accountPage) {
              if ($row['link_id'] == $accountPage['type_id']){
                  $pageName = $accountPage['name'];
                  $row['title'] = $row['title'] ? $row['title'] : $accountPage['name'];
                  $row['page'] = \common\classes\design::pageName($accountPage['name']);
              }
          }
          $is_multi = \Yii::$app->get('storage')->get('is_multi');
          if ($is_multi && $CustomersMultiEmails = \common\helpers\Acl::checkExtension('CustomersMultiEmails', 'checkLink')) {
              if (!$CustomersMultiEmails::checkLink($pageName)) {
                  continue;
              }
          }
          $row['link'] = Yii::$app->urlManager->createUrl(['account', 'page_name' => $row['page']]);

      } elseif ($row['link_type'] == 'default'){

            if ($row['link_id'] == '8888886'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_HOME') : TEXT_HOME;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'index' && Yii::$app->controller->action->id == 'index'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888885'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_HEADER_CONTACT_US') : TEXT_HEADER_CONTACT_US;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'contact' && Yii::$app->controller->action->id == 'index'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888887'){
              if (!Yii::$app->user->isGuest){
                $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_HEADER_LOGOUT') : TEXT_HEADER_LOGOUT;
              } else {
                $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_SIGN_IN') : TEXT_SIGN_IN;
              }
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
            } elseif ($row['link_id'] == '8888888'){
              if (!Yii::$app->user->isGuest){
                $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_MY_ACCOUNT') : TEXT_MY_ACCOUNT;
                if (Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id == 'index'){
                  $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
                }
                $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              } else {
                $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_CREATE_ACCOUNT') : TEXT_CREATE_ACCOUNT;
                if (Yii::$app->controller->id == 'account' && Yii::$app->controller->action->id == 'login'){
                  $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
                }
                 $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              }
            } elseif ($row['link_id'] == '8888884'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'NAVBAR_TITLE_CHECKOUT') : NAVBAR_TITLE_CHECKOUT;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'checkout' && Yii::$app->controller->action->id == 'index'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888883'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_HEADING_SHOPPING_CART') : TEXT_HEADING_SHOPPING_CART;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'shopping-cart' && Yii::$app->controller->action->id == 'index'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888882'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'NEW_PRODUCTS') : NEW_PRODUCTS;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'products-new'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888881'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'FEATURED_PRODUCTS') : FEATURED_PRODUCTS;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'featured-products'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888880'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'SPECIALS_PRODUCTS') : SPECIALS_PRODUCTS;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'sales'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888879'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_GIFT_CARD') : TEXT_GIFT_CARD;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'catalog' && Yii::$app->controller->action->id == 'gift-card'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888878'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_ALL_PRODUCTS') : TEXT_ALL_PRODUCTS;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'catalog' && (Yii::$app->controller->action->id == 'all-products')    ){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888877'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_SITE_MAP') : TEXT_SITE_MAP;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'sitemap'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888876'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_PROMOTIONS') : TEXT_PROMOTIONS;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'promotions'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888875'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_WEDDING_REGISTRY') : TEXT_WEDDING_REGISTRY;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'wedding-registry'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888874'){
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'MANAGE_YOUR_WEDDING_REGISTRY') : MANAGE_YOUR_WEDDING_REGISTRY;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'wedding-registry/manage'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            } elseif ($row['link_id'] == '8888873'){
                if (!\common\helpers\Customer::check_customer_groups($customer_groups_id, 'groups_is_reseller')) {
                    continue;
                }
              $row['title'] = $row['title'] ? EditData::addEditDataTegTranslation($row['title'], 'TEXT_QUICK_ORDER') : TEXT_QUICK_ORDER;
              $row['link'] = MenuHelper::getUrlByLinkId($row['link_id'], $row['link_type']);
              if (Yii::$app->controller->id == 'quick-order'){
                $row['class'] .= ($row['class'] ? ' ' : '') . 'active';
              }
            }
        }

        $row['title'] = \frontend\design\EditData::addEditDataTeg($row['title'], 'menu', $row['link_type'], $row['id']);

      /*if ( \common\classes\platform::activeId() ) {
        $sql1 = tep_db_fetch_array(tep_db_query("SELECT count(*) as total from " . TABLE_CATEGORIES . " c inner join " . TABLE_PLATFORMS_CATEGORIES . " plc on c.categories_id = plc.categories_id  and plc.platform_id = '" . \common\classes\platform::currentId() . "' where c.categories_id='" . $row['link_id'] . "' and categories_status = '1'"));
      }else{
        $sql1 = tep_db_fetch_array(tep_db_query("SELECT count(*) as total from " . TABLE_CATEGORIES . " where categories_id='" . $row['link_id'] . "' and categories_status = '1'"));
      }*/
      //if ($row['link_type'] != 'categories' || $sql1['total'] > 0 || $row['link_id'] == '999999999') {
      if ($row['link_type'] != 'categories' || isset($categories_all[$row['link_id']]) || $row['link_id'] == '999999999') {
        $sql2 = tep_db_fetch_array(tep_db_query("SELECT count(*) as total from " . TABLE_INFORMATION . " where information_id='" . $row['link_id'] . "' and visible = '1' AND platform_id='".\common\classes\platform::currentId()."' "));
        if ($row['link_type'] != 'info' || $sql2['total'] > 0) {
          $menu[] = $row;
        }
      }

      $is_menu = true;
    }

    $categories = array_values($categories_all);


/*    foreach ($menu as $item){
      if (array_search($item['id'], $sab_categories)){

      }
    }*/

    $hide_size = array();
    $media_query_arr = tep_db_query("select t.setting_value from " . TABLE_THEMES_SETTINGS . " t, " . (Info::isAdmin() ? TABLE_DESIGN_BOXES_SETTINGS_TMP : TABLE_DESIGN_BOXES_SETTINGS) . " b where b.setting_name = 'hide_menu' and  b.visibility = t.id and  b.box_id = '" . (int)$this->id . "'");
    while ($item = tep_db_fetch_array($media_query_arr)){      
      $hide_size[] = explode('w', $item['setting_value']);
    }


      if ($this->settings[0]['class'] == 'mobile') {
          Info::addBlockToWidgetsList('menu-mobile');
          $languages = [];
          $currenciesArray = [];
          global $lng;
          if (is_array($lng->catalog_languages)) {
              foreach ($lng->catalog_languages as $key => $value) {
                  if (!in_array($value['code'], $lng->paltform_languages)) continue;
                  if (Yii::$app->controller->id . '/' . Yii::$app->controller->action->id == 'index/index') {
                      $link = tep_href_link('/', \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'language=' . $key, $request_type);
                  } else {
                      $link = tep_href_link(Yii::$app->controller->id . (Yii::$app->controller->action->id != 'index' ? '/' . Yii::$app->controller->action->id : ''), \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'language=' . $key, $request_type);
                  }

                  $languages[] = array(
                      'image' => Html::img(DIR_WS_ICONS . $value['image'], ['width' => 24, 'height' => 16, 'class' => 'language-icon', 'alt' => $value['name'], 'title' => $value['name']]),
                      'name' => $value['name'],
                      'link' => $link,
                      'id' => $value['id'],
                      'key' => $key
                  );
              }
          }

          $currencies = \Yii::$container->get('currencies');
          if (is_array($currencies->currencies)) {
              foreach ($currencies->currencies as $key => $value) {
                  $value['key'] = $key;
                  if (!in_array($key, $currencies->platform_currencies)) continue;
                  if (Yii::$app->controller->id . '/' . Yii::$app->controller->action->id == 'index/index') {
                      $value['link'] = tep_href_link('/', \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'currency=' . $key, $request_type);
                  } else {
                      $value['link'] = tep_href_link(Yii::$app->controller->id . (Yii::$app->controller->action->id != 'index' ? '/' . Yii::$app->controller->action->id : ''), \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'currency=' . $key, $request_type);
                  }
                  $currenciesArray[] = $value;

              }
          }

          return IncludeTpl::widget(['file' => 'boxes/mobile-menu.tpl', 'params' => [
              'menu' => $menu,
              'categories' => $categories,
              'settings' => $this->settings,
              'menu_htm' => $this->menuTree($menu),
              'hide_size' => $hide_size,
              'id' => $this->id,
              'languages' => $languages,
              'languages_id' => $languages_id,
              'currenciesArray' => $currenciesArray,
          ]]);
      } else {
          Info::addBlockToWidgetsList($this->settings[0]['class']);
      }
    
    return IncludeTpl::widget(['file' => 'boxes/menu.tpl', 'params' => [
      'menu' => $menu,
      'categories' => $categories,
      'is_menu' => ($this->settings[0]['params'] ? true : false),
      'settings' => $this->settings,
      'menu_htm' => $this->menuTree($menu),
      'hide_size' => $hide_size,
      'id' => $this->id ? $this->id : rand (999999 , 9999999),
    ]]);
  }
  
  public function menuTree ($menu, $parent = 0, $ul = true) {
    $htm = '';
    
    foreach ($menu as $item){
      if ($item['parent_id'] == $parent){
        if ($item['link_id'] == 999999999 || $item['link_id'] == 999999998){
          $htm .= $this->menuTree($menu, $item['id'], false);
        } else {
          if ($item['count'] != -1){
            $htm .= '<li' . ($item['class'] ? ' class="' . $item['class'] . '"' : '') . '>';
            if ($item['title']){
              if ($item['link']){
                $htm .= '<a href="' . $item['link'] . '"' . ($item['target_blank'] == 1 ? ' target="_blank"' : '') . '>' . $item['title'] . '</a>';
              } else {
                $htm .= '<span class="no-link">' . $item['title'] . '</span>';
              }
            }
            
            if ($item['link_type'] != 'categories' || $item['sub_categories'] == 1){
              $htm .= $this->menuTree($menu, $item['id']);
            }
            $htm .= '</li>';
          }
        }
      }
    }
    if ($ul && $htm) $htm = '<ul>' . $htm . '</ul>';
    
    return $htm;
  }


}

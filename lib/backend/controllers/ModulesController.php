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

use common\classes\platform;
use common\models\Platforms;
use Yii;
use common\helpers\Translation;
use common\classes\modules;

use backend\models\Configuration;

class ModulesController extends Sceleton
{

    public $acl = ['BOX_HEADING_MODULES'];

      public $module_type;
      public $module_entity;
      public $module_directory;
      public $module_need_requiring;
      public $module_key;
      public $module_key_sort;
      public $module_class;
      public $module_namespace;
      protected $module_const_prefix;
      public $enabled;
      public $validated_extensions = array('php');
      protected $selected_platform_id;
      private $_page_title = '';

      public function __construct($id, $module)
      {
        parent::__construct($id, $module);

        $this->selected_platform_id = \common\classes\platform::firstId();
        $try_set_platform = Yii::$app->request->get('platform_id', 0);
        if ( Yii::$app->request->isPost ) {
          $try_set_platform = Yii::$app->request->post('platform_id', $try_set_platform);
        }
        if ( $try_set_platform>0 ) {
          foreach (\common\classes\platform::getList(false) as $_platform) {
            if ((int)$try_set_platform==(int)$_platform['id']){
              $this->selected_platform_id = (int)$try_set_platform;
            }
          }
        }
        Yii::$app->get('platform')->config($this->selected_platform_id)->constant_up();
        \common\helpers\Translation::init('admin/modules');
      }


      public function rules($set){

            if (tep_not_null($set)) {
              $this->module_need_requiring = true;
              switch ($set) {
                case 'label':
                    \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_LABEL']);
                    $path = \Yii::getAlias('@common');
                    $path .= DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'label' . DIRECTORY_SEPARATOR;
                    $this->module_type = 'label';
                    $this->module_entity = 'label';
                    $this->module_directory = $path;
                    $this->module_key = 'MODULE_LABEL_INSTALLED';
                    $this->module_key_sort = 'DD_MODULE_LABEL_SORT';
                    $this->module_const_prefix = 'MODULE_LABEL_';
                    $this->module_class = 'ModuleLabel';
                    $this->_page_title = HEADING_TITLE_MODULES_LABEL;
                    $this->module_need_requiring = false;
                    $this->module_namespace = "common\\modules\\label\\";
                    break;
                case 'shipping':
                    \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_SHIPPING']);
                    $this->module_type = 'shipping';
                    $this->module_entity = 'shipping';
                    $path = \Yii::getAlias('@common');
                    $path .= DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'orderShipping' . DIRECTORY_SEPARATOR;
                    $this->module_directory = $path;
                    //$this->module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';
                    $this->module_key = 'MODULE_SHIPPING_INSTALLED';
                    $this->module_key_sort = 'DD_MODULE_SHIPPING_SORT';
                    $this->module_const_prefix = 'MODULE_SHIPPING_';
                    $this->module_class = 'ModuleShipping';
                    $this->_page_title = HEADING_TITLE_MODULES_SHIPPING;
                    //define('HEADING_TITLE', HEADING_TITLE_MODULES_SHIPPING);
                    $this->module_need_requiring = false;
                    $this->module_namespace = "common\\modules\\orderShipping\\";
                    break;
              case 'dropshipping':
                    \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_DROPSHIPPING']);
                  $this->module_type = 'dropshipping';
                  $this->module_entity = 'dropshipping';
                  $this->module_directory = DIR_FS_CATALOG_MODULES . 'dropshipping/';
                  $this->module_key = 'MODULE_DROPSHIPPING_INSTALLED';
                  $this->module_key_sort = 'DD_MODULE_DROPSHIPPING_SORT';
                  $this->module_const_prefix = 'MODULE_DROPSHIPPING_';
                  $this->module_class = 'ModuleDropShipping';
                  $this->_page_title = HEADING_TITLE_MODULES_DROPSHIPPING;
                  //define('HEADING_TITLE', HEADING_TITLE_MODULES_SHIPPING);
                  break;
                case 'ordertotal':
                    \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_ORDER_TOTAL']);
                    $this->module_type = 'order_total';
                    $this->module_entity = 'ordertotal';
                    $path = \Yii::getAlias('@common');
                    $path .= DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'orderTotal' . DIRECTORY_SEPARATOR;
                    $this->module_directory = $path;
                    //$this->module_directory = DIR_FS_CATALOG_MODULES . 'order_total/';
                    $this->module_key = 'MODULE_ORDER_TOTAL_INSTALLED';
                    $this->module_key_sort = 'DD_MODULE_ORDER_TOTAL_SORT';
                    $this->module_const_prefix = 'MODULE_ORDER_TOTAL_';
                    $this->module_class = 'ModuleTotal';
                    $this->_page_title = HEADING_TITLE_MODULES_ORDER_TOTAL;
                    //define('HEADING_TITLE', HEADING_TITLE_MODULES_ORDER_TOTAL);
                    $this->module_need_requiring = false;
                    $this->module_namespace = "common\\modules\\orderTotal\\";
                  break;
                case 'extensions':
                  \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_EXTENSIONS']);
                  $path = \Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR;
                  $this->module_type = 'extensions';
                  $this->module_entity = 'extensions';
                  $this->module_directory = $path;
                  $this->module_key = 'MODULE_EXTENSIONS_INSTALLED';
                  $this->module_key_sort = 'DD_MODULE_EXTENSIONS_SORT';
                  $this->module_const_prefix = '';
                  $this->module_class = 'ModuleExtensions';
                  $this->_page_title = HEADING_TITLE_MODULES_EXTENSIONS;
                  $this->module_need_requiring = false;
                  $this->selected_platform_id = 0;
                  break;
                case 'payment':
                default:
                    \common\helpers\Acl::checkAccess(['BOX_HEADING_MODULES', 'BOX_MODULES_PAYMENT']);
                  $this->module_type = 'payment';
                  $this->module_entity = 'payment';
                  $path = \Yii::getAlias('@common');
                  $path .= DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'orderPayment' . DIRECTORY_SEPARATOR;
                  $this->module_directory = $path;
                  //$this->module_directory = DIR_FS_CATALOG_MODULES . 'payment/';
                  $this->module_key = 'MODULE_PAYMENT_INSTALLED';
                  $this->module_key_sort = 'DD_MODULE_PAYMENT_SORT';
                  $this->module_const_prefix = 'MODULE_PAYMENT_';
                  $this->module_class = 'ModulePayment';
                  $this->_page_title = HEADING_TITLE_MODULES_PAYMENT;
                  //define('HEADING_TITLE', HEADING_TITLE_MODULES_PAYMENT);
                  $this->module_need_requiring = false;
                  $this->module_namespace = "common\\modules\\orderPayment\\";
                  break;
              }
            }

        }


        public function actionIndex()
        {
            $set = Yii::$app->request->get('set', 'payment');

            switch ($set) {
                case 'payment':
                    $this->acl[] = 'BOX_MODULES_PAYMENT';
                    $type = Yii::$app->request->get('type', 'online');
                    if ($type == 'online') {
                        $this->acl[] = 'BOX_MODULES_PAYMENT_ONLINE';
                    } else {
                        $this->acl[] = 'BOX_MODULES_PAYMENT_OFFLINE';
                    }
                    break;
                case 'shipping':
                    $this->acl[] = 'BOX_MODULES_SHIPPING';
                    break;
                case 'label':
                    $this->acl[] = 'BOX_MODULES_LABEL';
                    break;
                case 'ordertotal':
                    $this->acl[] = 'BOX_MODULES_ORDER_TOTAL';
                    break;
                 case 'extensions':
                    $this->acl[] = 'BOX_MODULES_EXTENSIONS';
                    break;
                case 'dropshipping':
                    $this->acl[] = 'BOX_MODULES_DROPSHIPPING';
                    break;
            }

            $this->rules($set);

            $oldaction = Yii::$app->request->get('action', '');
            $subaction = Yii::$app->request->get('subaction', '');
            $module = Yii::$app->request->get('module', '');
//
//echo "aaaa  $set == 'payment' && $oldaction == 'install' && $subaction == 'conntest' && $module"; die;
if ($set == 'payment' && $oldaction == 'install' && $subaction == 'conntest' && $module != '') { // paypal's tests
            $ret = '';
            $file = $module . '.php';
            $platform_config = Yii::$app->get('platform')->config($this->selected_platform_id);
            echo DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file;
            die;
            if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)){
              require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
            }
            require_once($this->module_directory . $file);

            $class = $module;
            if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\{$this->module_class}") ) {

              $module = new $class;
              if (method_exists($module, 'getTestConnectionResult')) {
                $ret = $module->getTestConnectionResult();
              }
            }
            return '~~~~~' . $ret . '!!! ' . $this->selected_platform_id . '+++' .print_r($platform_config,1);
}
            $this->selectedMenu        = array( 'modules', 'modules?set='.$set );
            $this->navigation[]        = array( 'link' => Yii::$app->urlManager->createUrl( 'modules/index' ), 'title' => $this->_page_title );
            $this->view->headingTitle  = $this->_page_title;
            $this->view->modulesTable = array(
								/*array(
                'title' => '<input type="checkbox" class="uniform">',
                'not_important' => 2
								),*/
                array(
                    'title'         => TABLE_HEADING_MODULES,
                    'not_important' => 0
                ),
                array(
                    'title'         => TABLE_TEXT_STATUS,
                    'not_important' => 3
                ),
            );

            $this->view->filters = new \stdClass();

            $this->view->filters->row = (int) $_GET['row'];
            $this->view->filters->all_countries = (int) $_GET['all_countries'];
            $this->view->filters->inactive = (int) $_GET['inactive'];
            $this->view->filters->not_installed = (int) $_GET['not_installed'];

            $_platforms = \common\classes\platform::getList(false);
            foreach( $_platforms as $_idx=>$_platform ) {
              $_platforms[$_idx]['link'] = Yii::$app->urlManager->createUrl(['modules/index','platform_id'=>$_platform['id'],'set'=>$set]);
            }

            $type = Yii::$app->request->get('type', '');

            return $this->render('index', [
              'set' => $set,
                'type' => $type,
              'platforms' => $_platforms,
              'isMultiPlatforms' => $this->module_type != 'extensions' && \common\classes\platform::isMulti(false),
              'selected_platform_id' => $this->selected_platform_id,
            ]);
        }

       private function directoryList(){
          $directory_array = array();
          if ($dir = @dir($this->module_directory)) {
            while ($file = $dir->read()) {
              if (!is_dir($this->module_directory . $file)) {
                if (in_array($this->module_type, ['label', 'order_total', 'payment', 'shipping'])){
                  $directory_array[] = $this->module_namespace . $file;
                } else if (in_array(substr($file, strrpos($file, '.')+1), $this->validated_extensions)) {
                  $directory_array[] = $file;
                }
              } else if ($ext = \common\helpers\Acl::checkExtension($file, 'allowed')) {
                  $directory_array[] = $ext;
              }
            }
            sort($directory_array);
            $dir->close();
          }
          return $directory_array;
       }

      /**
       * @param modules\Module $module
       * @param $set
       * @return \objectInfo
       */
       private function createInfo($module, $set){
         $languages_id = \Yii::$app->settings->get('languages_id');


         $module_info = array(
           'code' => $module->code,
           'title' => $module->title,
           'description' => $module->description,
           'status' => $module->check($this->selected_platform_id),
         );

         $module_keys = $module->keys();

         $keys_extra = array();
         for ($j=0, $k=sizeof($module_keys); $j<$k; $j++) {
           $key_value_query = tep_db_query(
             "select configuration_title, configuration_value, configuration_description, use_function, set_function ".
             "from " . TABLE_PLATFORMS_CONFIGURATION . " ".
             "where platform_id = '".intval($this->selected_platform_id)."' AND configuration_key = '" . tep_db_input($module_keys[$j]) . "'"
           );
           $key_value = tep_db_fetch_array($key_value_query);

           $keys_extra[$module_keys[$j]]['title'] = (tep_not_null($value = Translation::getTranslationValue($module_keys[$j].'_TITLE', $set , $languages_id)) ? $value : $key_value['configuration_title']);
           $keys_extra[$module_keys[$j]]['value'] =  $key_value['configuration_value'];
           $keys_extra[$module_keys[$j]]['description'] = (tep_not_null($value = Translation::getTranslationValue($module_keys[$j].'_DESCRIPTION', $set , $languages_id)) ? $value : $key_value['configuration_description']);
           $keys_extra[$module_keys[$j]]['use_function'] = $key_value['use_function'];
           $keys_extra[$module_keys[$j]]['set_function'] = $key_value['set_function'];
         }

         $module_info['keys'] = $keys_extra;

         return new \objectInfo($module_info);
       }

       private function fetchArrays($module, $file, &$installed_modules, &$modules_files){
         /**
          * @var modules\Module $module
          */
              if ($module->check($this->selected_platform_id) > 0) {
                $modules_files[$module->code] = $file;
                $sort_key = $module->describe_sort_key();
                $module_sort_order = $module->sort_order;
                if ( is_object($sort_key) && is_a($sort_key,'common\classes\modules\ModuleSortOrder')) {
                  $get_sort_order_value_r = tep_db_query(
                    "select configuration_value ".
                    "from " . TABLE_PLATFORMS_CONFIGURATION . " ".
                    "where platform_id = '".intval($this->selected_platform_id)."' AND configuration_key = '" . tep_db_input($sort_key->key) . "'"
                  );
                  if ( tep_db_num_rows($get_sort_order_value_r)>0 ) {
                    $_sort_order_value = tep_db_fetch_array($get_sort_order_value_r);
                    $module_sort_order = $_sort_order_value['configuration_value'];
                  }
                }
                if ($module_sort_order > 0 && !isset($installed_modules[$module_sort_order])) {
                  $installed_modules[$module_sort_order] = $file;
                } else {
                  $installed_modules[] = \common\helpers\Acl::checkExtension($file, 'allowed')? (new \ReflectionClass($file))->getShortName() : $file;
                }
              }
       }

    public function actionList()
    {
          $draw = Yii::$app->request->get('draw', 1);
          $search = Yii::$app->request->get('search', '');
          $start = Yii::$app->request->get('start', 0);
          $length = Yii::$app->request->get('length', 15);
          $set = Yii::$app->request->get('set', 'payment');

          $formFilter = Yii::$app->request->get('filter');
          parse_str($formFilter, $output);

          if ( isset($output['platform_id']) ){
            $this->selected_platform_id = (int)$output['platform_id'];
          }

          if ( isset($output['all_countries']) && $output['all_countries'] == 1 ){
              $showAllCountries = true;
          } else {
              $showAllCountries = false;
          }

          if ( isset($output['not_installed']) && $output['not_installed'] == 1 ){
              $showNotInstalled = true;
          } else {
              $showNotInstalled = false;
          }

          if ( isset($output['inactive']) && $output['inactive'] == 1 ){
              $showInactive = true;
          } else {
              $showInactive = false;
          }
          if ( isset($output['type']) && !empty($output['type']) ){
              $type = $output['type'];
          }

          $this->rules($set);

          //$file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
          $directory_array = $this->directoryList();

          $active_modules = array();
          $installed_modules = array();
          $modules_files = array();
          $responseList = array();

          $_search_active = false;
          $_sort_by_title = array();

          \common\helpers\Translation::init($this->module_entity);

	    $platform_country = platform::country($this->selected_platform_id);
	    for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
            $file = $directory_array[$i];

            $class = (strpos($file, ".") !== false ? substr($file, 0, strrpos($file, '.')):$file);

            if ($this->module_need_requiring) {
                if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)){
                  require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
                }
                require_once($this->module_directory . $file);

            }/* else {
                $class = "common\\modules\\label\\" . $class;
            }*/

            if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\{$this->module_class}") ) {
              $module = new $class;
              $skip = false;
              if ($this->module_type == 'payment') {
                  switch ($type) {
                      case 'online':
                          $skip = !$module->isOnline();
                          break;
                      case 'offline':
                          $skip = $module->isOnline();
                          break;
                      default:
                          break;
                  }
              }
              /**
               * @var modules\Module $module
               */

              if ( is_array($search) && !empty($search['value']) ) {
                $_search_active = true;
                if ( stripos($module->title,$search['value'])===false ) continue;
              }

              $this->fetchArrays($module, $file, $installed_modules, $modules_files);
              $mInfo = $this->createInfo($module, $set);

              if ($skip) {
                continue;
              }

              $_sort_by_title[$file] = strtolower($module->title);

              $installed = false;
              $active = false;
              $buttons = '';

              if ($mInfo->status == '1') {
                $buttons .= '<button class="btn btn-small" onClick="return changeModule(\'' . $mInfo->code . '\', \'remove\')" title="' . TEXT_REMOVE . '">' . TEXT_REMOVE . '</button>';
                $installed = true;
                $active = $module->is_module_enabled($this->selected_platform_id);
              }else{
                $buttons .= '<input type="button" class="btn btn-small" title="'.IMAGE_INSTALL.'" value="' . \common\helpers\Output::output_string(IMAGE_INSTALL) . '" onClick="return changeModule(\'' . $module->code . '\', \'install\')">';
              }

              $active_modules[$file] = $active;
              $edit_link = Yii::$app->urlManager->createUrl(['modules/edit','platform_id'=>$this->selected_platform_id,'set'=>$set, 'module' => $mInfo->code]);

              if((in_array('', $module->getCountries()) || in_array($platform_country->countries_iso_code_3, $module->getCountries())) || $showAllCountries){
	              $responseList[$file] = array(
		              //'<input type="checkbox" class="uniform">',
		              '<div class="handle_cat_list click_double" '.($installed?' data-click-double="' .$edit_link. '"':'').'><span class="handle"><i class="icon-hand-paper-o"></i></span><div class="module_title' . ($active ? '' :' dis_module') . '">' . (defined($module->title.'_TITLE') ? constant($module->title.'_TITLE'):$module->title) . tep_draw_hidden_field('module', $class, 'class="cell_identify" data-installed="'.($installed?'true':'false').'"') . '</div></div>',
		              ($installed?'<input name="enabled" type="checkbox" data-module="'.$module->code.'" class="check_on_off" ' . ($active ? 'checked' :'') . '>':'').
		              (empty($buttons)?'':$buttons),
	              );
              }
            }
          }


          $get_actual_value = tep_db_fetch_array(tep_db_query(
            "SELECT configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE configuration_key='".tep_db_input($this->module_key_sort)."' AND platform_id='".intval($this->selected_platform_id)."'"
          ));
          $get_actual_installed_value = tep_db_fetch_array(tep_db_query(
            "SELECT configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE configuration_key='".tep_db_input($this->module_key)."' AND platform_id='".intval($this->selected_platform_id)."'"
          ));
          if ( false && is_array($get_actual_value) && !empty($get_actual_value['configuration_value']) ) {
            $new_responseList = array();
            foreach(explode(';',$get_actual_value['configuration_value']) as $__push_key){
              if (!isset($responseList[$__push_key])) continue;
              $new_responseList[] = $responseList[$__push_key];
              unset($responseList[$__push_key]);
            }
            $responseList = array_merge($new_responseList, array_values($responseList));
          }else {
            // {{
            if ( !in_array($this->module_type,['extensions']) ) {
                $_responseList = [];
                foreach ($responseList as $__path_key => $__value) {
                    if (strpos($__path_key, '\\') !== false) $__path_key = substr($__path_key, strrpos($__path_key, '\\') + 1);
                    $_responseList[$__path_key] = $__value;
                }
                $responseList = $_responseList;

                $_active_modules = $active_modules;
                foreach ($active_modules as $__path_key => $__value) {
                    if (strpos($__path_key, '\\') !== false) $__path_key = substr($__path_key, strrpos($__path_key, '\\') + 1);
                    $_active_modules[$__path_key] = $__value;
                }
                $active_modules = $_active_modules;
            }
            // }}

            //sort installed by sort key, then uninstalled by title
            $_installed_top = array();
            $_check_installed = $installed_modules;
            foreach (explode(';',$get_actual_installed_value['configuration_value']) as $__installed_module_file) {
              if (!isset($responseList[$__installed_module_file])) continue;
              unset($_check_installed[$__installed_module_file]);
              if ($showInactive || $active_modules[$__installed_module_file]) {
                $_installed_top[] = $responseList[$__installed_module_file];
              }
              unset($responseList[$__installed_module_file]);
            }
            if ( count($_check_installed)>0 ) foreach ($_check_installed as $__installed_module_file) {
              if (!isset($responseList[$__installed_module_file])) continue;
              if ($showInactive || $active_modules[$__installed_module_file]) {
                $_installed_top[] = $responseList[$__installed_module_file];
              }
              unset($responseList[$__installed_module_file]);
            }

            asort($_sort_by_title, SORT_STRING);
            $_sort_uninstalled = array_keys($_sort_by_title);
            if ( is_array($get_actual_value) && !empty($get_actual_value['configuration_value']) ) {
              $_sort_uninstalled = explode(';',$get_actual_value['configuration_value']);
            }

            //$responseList = array_merge($_installed_top, array_values($responseList));
            $new_responseList = $_installed_top;
            if ($showNotInstalled) {
                if ( count($responseList)>0 ) {
                  $new_responseList[] = array('<span class="modules_divider"></span>','<span class="modules_divider"></span>'); // blackline
                }

                foreach ($_sort_uninstalled as $__push_key) {
                  if (!isset($responseList[$__push_key])) continue;
                    $new_responseList[] = $responseList[$__push_key];
                  unset($responseList[$__push_key]);
                }
                $responseList = array_merge($new_responseList, array_values($responseList));
            } else {
                $responseList = $new_responseList;
            }


          }

          $response = array(
              'draw' => $draw,
              'recordsTotal' => sizeof($directory_array),
              'recordsFiltered' => sizeof($directory_array),
              'params' => array(
                'set' => $set,
                'platform_id' => $this->selected_platform_id,
              ),
              'data' => $responseList,
          );

          if ( !$_search_active && $this->module_type != 'extensions') {
            foreach ($installed_modules as $key => $file) {
                if (preg_match('/common\\\\modules\\\\[^\\\\]+\\\\(.+)$/si', $file, $match)) {
                    $installed_modules[$key] = $match[1];
                }
            }
            ksort($installed_modules);
            $check_query = tep_db_query("select configuration_value from " . TABLE_PLATFORMS_CONFIGURATION. " where configuration_key = '" . tep_db_input($this->module_key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
            if (tep_db_num_rows($check_query)) {
              $check = tep_db_fetch_array($check_query);
              if ($check['configuration_value'] != implode(';', $installed_modules)) {
                tep_db_query("update " . TABLE_PLATFORMS_CONFIGURATION. " set configuration_value = '" . implode(';', array_map('tep_db_input',$installed_modules)) . "', last_modified = now() where configuration_key = '" . tep_db_input($this->module_key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
              }
            } else {
              tep_db_query("insert into " . TABLE_PLATFORMS_CONFIGURATION. " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, platform_id) values ('Installed Modules', '" . tep_db_input($this->module_key) . "', '" . implode(';', array_map('tep_db_input',$installed_modules)) . "', 'This is automatically updated. No need to edit.', '6', '0', now(), '".intval($this->selected_platform_id)."')");
            }
          }

        echo json_encode($response);

    }

    public function actionView()
    {
          $languages_id = \Yii::$app->settings->get('languages_id');
          $set = Yii::$app->request->post('set', 'payment');
          $file = Yii::$app->request->post('module', '');
          $enabled = Yii::$app->request->post('enabled', '');
          if ( empty($file) ) {
            die;
          }
          $file .= '.php';

          $this->rules($set);

          $installed_modules = array();
          $modules_files = array();
          $heading = $contents = array();

            \common\helpers\Translation::init($this->module_entity);

            $class = substr($file, 0, strrpos($file, '.'));
            if ($this->module_need_requiring) {//if ($this->module_type != 'label') {
                if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)){
                    require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
                }
                require_once($this->module_directory . $file);
            }

          if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\{$this->module_class}") ) {
            $module = new $class;
            /**
             * @var modules\Module $module
             */

            $this->fetchArrays($module, $file, $installed_modules, $modules_files);

            if($mInfo = $this->createInfo($module, $set)){
              $sort_order_key_name = $this->module_const_prefix . strtoupper($class).'_SORT_ORDER';
              if ($this->module_type == 'order_total') {
                $sort_order_key_name = $this->module_const_prefix . strtoupper(preg_replace('/^ot_/', '', $class)).'_SORT_ORDER';
              }elseif($this->module_type == 'payment'){
                if ( !defined($sort_order_key_name) && strpos($class,'multisafepay_')===0 ) {
                  $_alter_sort_order_key_name = $this->module_const_prefix . strtoupper(preg_replace('/^multisafepay_/', 'msp_', $class)).'_SORT_ORDER';
                  if ( defined($_alter_sort_order_key_name) ) $sort_order_key_name = $_alter_sort_order_key_name;
                }
              }

              $heading[] = array('text' => '<b>' . (defined($mInfo->title.'_TITLE')? constant($mInfo->title.'_TITLE'):$mInfo->title) . '</b>');
              echo '<div class="or_box_head">' . (defined($mInfo->title.'_TITLE')? constant($mInfo->title.'_TITLE'):$mInfo->title) . '</div>';

              if ($mInfo->status == '1') {
                $keys = '';
                if (is_array($mInfo->keys)) foreach ($mInfo->keys as $__key_name => $value) {
                  if ( $__key_name==$sort_order_key_name ) {
                    continue;
                  }
                  $keys .= '<b>' . $value['title'] . '</b><br>';

                  $_t = Translation::getTranslationValue(strtoupper(str_replace(" ", "_", $value['value'])), 'configuration', $languages_id);
                  $value['value'] = (tep_not_null($_t) ? $_t : $value['value']);
                  unset($_t);

                  if ($value['use_function']) {
                    $use_function = $value['use_function'];
                    if (preg_match('/->/', $use_function)) {
                      $class_method = explode('->', $use_function);
                      if (!is_object(${$class_method[0]})) {
                        ${$class_method[0]} = Yii::createObject($class_method[0]);
                        if (!is_object(${$class_method[0]})) {
                            include_once(DIR_WS_CLASSES . $class_method[0] . '.php');
                            ${$class_method[0]} = new $class_method[0]();
                        }
                      }
                      $keys .= tep_call_function($class_method[1], $value['value'], ${$class_method[0]});
                    } else {
                      $keys .= tep_call_function($use_function, $value['value']);
                    }
                  } else {

                    $keys .= $value['value'];
                  }
                  $keys .= '<br>';
                }

                $edit_link = Yii::$app->urlManager->createUrl(['modules/edit','platform_id'=>$this->selected_platform_id,'set'=>$set, 'module' => $mInfo->code]);
                $translate_link = Yii::$app->urlManager->createUrl(['modules/translation','platform_id'=>$this->selected_platform_id,'set'=>$set, 'module' => $mInfo->code]);
                $keys = substr($keys, 0, strrpos($keys, '<br>'));
                  echo '<div class="btn-toolbar btn-toolbar-order">';
                  if (!isset($module->isExtension)){
                    echo '<a class="btn btn-edit btn-primary btn-no-margin" href="' .$edit_link. '">'.IMAGE_EDIT.'</a>';
                    echo '<button class="btn btn-delete" onClick="return changeModule(\'' . $mInfo->code . '\', \'remove\')">' . TEXT_REMOVE . '</button>';
                    echo '<a class="btn btn-edit btn-default btn-no-margin" href="' .$translate_link. '">' . IMAGE_BUTTON_TRANSLATE . '</a>';
                  }else{
                      echo '<a class="btn btn-edit btn-primary btn-no-margin" href="' .$edit_link. '">'.IMAGE_EDIT.'</a>';
                      echo '<button class="btn btn-delete" onClick="return changeModule(\'' . $mInfo->code . '\', \'remove\')">' . TEXT_REMOVE . '</button>';
                  }

                //$contents[] = array('align' => 'center', 'text' => '<input type="button" class="btn btn-delete" value="Remove" onClick="return changeModule(\'' . $mInfo->code . '\', \'remove\')"> <input type="button" class="btn btn-primary" value="Edit" onClick="return editModule(\'' . $mInfo->code . '\')"> ');
                  echo '</div>';
                  echo '<div class="module_row"><div>' . (defined($mInfo->description.'_TITLE')? constant($mInfo->description.'_TITLE'):$mInfo->description) . '</div><div>' . $keys . '</div></div>';
                /*$contents[] = array('text' => '<br>' . $mInfo->description);
                $contents[] = array('text' => '<br>' . $keys);*/
              } else {
                  echo '<div class="btn-toolbar btn-toolbar-order">';
                  echo '<input type="button" class="btn btn-primary btn-process-order" value="' . IMAGE_INSTALL . '" onClick="return changeModule(\'' . $mInfo->code . '\', \'install\')">';
                  echo '</div>';
                  echo '<div class="module_row"><div>' .(defined($mInfo->description.'_TITLE')? constant($mInfo->description.'_TITLE'):$mInfo->description) . '</div></div>';
                /*$contents[] = array('align' => 'center', 'text' => '<input type="button" class="btn" value="Install" onClick="return changeModule(\'' . $mInfo->code . '\', \'install\')">');
                $contents[] = array('text' => '<br>' . $mInfo->description);*/
              }

            }

          }
    }

    public function actionEdit($set, $module)
    {
          $languages_id = \Yii::$app->settings->get('languages_id');
          $this->selectedMenu        = array( 'modules', 'modules?set='.$set );
          $heading = $contents = array();
          $file = $module . '.php';

          $this->rules($set);

          $installed_modules = array();
          $modules_files = array();
          $heading = $contents = array();

          \common\helpers\Translation::init('admin/modules');
          \common\helpers\Translation::init($this->module_entity);

          $keys = '';
          $class = substr($file, 0, strrpos($file, '.'));
          if ($this->module_type == 'label') {
              $class = "common\\modules\\label\\" . $class;
          } elseif ($this->module_type == 'order_total') {
              $class = "common\\modules\\orderTotal\\" . $class;
          } elseif ($this->module_type == 'payment') {
              $class = "common\\modules\\orderPayment\\" . $class;
          } elseif ($this->module_type == 'shipping') {
              $class = "common\\modules\\orderShipping\\" . $class;
          } else {
            if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)){
              require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
            }elseif($this->module_type=='extensions'){
                $class = "common\\extensions\\" . $class."\\".$class;
            }
            if ( is_file($this->module_directory . $file) ) {
                include_once($this->module_directory . $file);
            }
          }

          if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\{$this->module_class}")) {
            $module = new $class;
            /**
             * @var modules\Module $module
             */

             // $this->fetchArrays($module, $file, $installed_modules, $modules_files);

            if($mInfo = $this->createInfo($module, $set)){
              $sort_order_key_name = $this->module_const_prefix . strtoupper($class).'_SORT_ORDER';
              if ($this->module_type == 'order_total') {
                $sort_order_key_name = $this->module_const_prefix . strtoupper(preg_replace('/^ot_/', '', $class)).'_SORT_ORDER';
              }elseif($this->module_type == 'payment'){
                if ( !defined($sort_order_key_name) && strpos($class,'multisafepay_')===0 ) {
                  $_alter_sort_order_key_name = $this->module_const_prefix . strtoupper(preg_replace('/^multisafepay_/', 'msp_', $class)).'_SORT_ORDER';
                  if ( defined($_alter_sort_order_key_name) ) $sort_order_key_name = $_alter_sort_order_key_name;
                }
              }
              if (is_array($mInfo->keys)) foreach ($mInfo->keys as $key => $value) {
                if ( $sort_order_key_name==$key ) {
                  $keys .= tep_draw_hidden_field('configuration[' . $key . ']', $value['value']);
                  continue;
                }

                $keys .= '<div class="after modules-line"><div class="modules-label"><b>' . $value['title'] . '</b><div class="modules-description">' . $value['description'] . '</div></div>';
                $method = trim(substr($value['set_function'], 0, strpos($value['set_function'], '(')));
                if ($value['set_function'] && function_exists($method) ) {
                    //$_args = preg_replace("/".$method."[\s\(]*/i", "", $value['set_function']). "'" . $value['value'] . "', '" . $key . "'";
                    $_args = [$value['value'], $key];
                    $keys .= call_user_func_array($method, $_args);
                }elseif (!empty($class) && $value['set_function'] && method_exists($class, $method) ){
                    $_args = [$value['value'], $key];
                    $keys .= call_user_func_array([$class, $method], $_args);
                }elseif ($value['set_function'] && method_exists ('backend\models\Configuration', $method)) {
                  // eval('$keys .= ' . $value['set_function'] . "'" . $value['value'] . "', '" . $key . "');");
                  $_args = preg_replace("/".$method."[\s\(]*/i", "", $value['set_function']). "'" . $value['value'] . "', '" . $key . "'";
                  $keys .= call_user_func(array('backend\models\Configuration', $method), $_args);
                } else {
                  $_t = Translation::getTranslationValue(strtoupper(str_replace(" ", "_", $value['value'])), 'configuration', $languages_id);
                  $_t = (tep_not_null($_t) ? $_t : $value['value']);
                  $keys .= tep_draw_input_field('configuration[' . $key . ']', $_t);
                }
                $keys .= '</div><br>';
              }
              $keys = substr($keys, 0, strrpos($keys, '<br>'));

              $heading[] = array('text' => '<b>' . $mInfo->title . '</b>');

            }
            if (method_exists($module, 'extra_params')) {
                $this->view->extra_params = $module->extra_params();
            }
            if (method_exists($module, 'getLabels')) {
                $restriction .= $module->getLabels($this->selected_platform_id);
            }
            if (method_exists($module, 'getZeroPrice')) {
                $restriction .= $module->getZeroPrice($this->selected_platform_id);
            }
            if (method_exists($module, 'getVisibility')) {
                $restriction .= $module->getVisibility($this->selected_platform_id);
            }
            if (method_exists($module, 'getGroupRestriction')) {
                $restriction .= $module->getGroupRestriction($this->selected_platform_id);
            }
            if (method_exists($module, 'getRestriction')) {
                $restriction .= $module->getRestriction($this->selected_platform_id, $languages_id);
            }

        }
        if (Yii::$app->request->isAjax) {
            $this->layout = false;
        }
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('modules/index'), 'title' => $mInfo->title);
        return $this->render('edit.tpl', [
            'mainKey' => $keys,
            'restriction' => $restriction,
            'codeMod'=>$mInfo->code,
            'set' => $set,
            'selected_platform_id' => $this->selected_platform_id,
        ]);
    }

      public function actionSave(){
        $set = \Yii::$app->request->get('set', '');
        $module = \Yii::$app->request->post('module', '');
        $this->rules($set);

        if (is_array($_POST['configuration'])) foreach ($_POST['configuration'] as $key => $value) {            
            if( is_array( $value ) ){
                $value = implode( ", ", $value);
                $value = preg_replace ("/, --none--/", "", $value);
            }
            tep_db_query("update " . TABLE_PLATFORMS_CONFIGURATION. " set configuration_value = '" . tep_db_input(tep_db_prepare_input($value)) . "' where configuration_key = '" . tep_db_input($key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
        }
        
        if (isset($_FILES['configuration'])){
            $path = Yii::$aliases['@common'] .DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'certificates';
            \yii\helpers\FileHelper::createDirectory($path);
            foreach ($_FILES['configuration']['name'] as $key => $value) {
                if (move_uploaded_file ( $_FILES['configuration']['tmp_name'][$key], $path . DIRECTORY_SEPARATOR . $value)){
                    tep_db_query("update " . TABLE_PLATFORMS_CONFIGURATION. " set configuration_value = '" . tep_db_input(tep_db_prepare_input($value)) . "' where configuration_key = '" . tep_db_input($key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
                }
            }
        }

        Translation::init($this->module_entity);
        if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $module . '.php')) {
          require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $module . '.php');
        }
        if (file_exists($this->module_directory . $module . '.php')) {
          include_once($this->module_directory . $module . '.php');
        }
        if ($this->module_namespace && in_array($set ,['label', 'ordertotal', 'payment', 'shipping'])) {
            $module = $this->module_namespace . $module;
        }

        if (class_exists($module)) {
          $object = new $module;
          if (method_exists($object, 'setZeroPrice')) {
            $object->setZeroPrice();
          }
          if (method_exists($object, 'setVisibility')) {
            $object->setVisibility();
          }
          if (method_exists($object, 'setRestriction')) {
            $object->setRestriction();
          }
          if (method_exists($object, 'setLabels')) {
            $object->setLabels();
          }
          if (method_exists($object, 'setGroupRestriction')) {
            $object->setGroupRestriction();
          }
          if (method_exists($object, 'extra_params')) {
            $object->extra_params();
          }
        }
        //return $this->redirect(array('list', array('set' => $set)));
		//$this->redirect(Yii::$app->urlManager->createUrl('modules/list?set='.$set));
      }

    public function actionTranslation(){
      $languages_id = \Yii::$app->settings->get('languages_id');

      \common\helpers\Translation::init('admin/modules');

      $_module = Yii::$app->request->get('module', '');
      $set = Yii::$app->request->get('set', 'payment');
      $row = Yii::$app->request->get('row', '0');

      $this->rules($set);
      Translation::init($this->module_entity);

      $this->selectedMenu        = array( 'modules', 'modules?set='.$set );
      $this->navigation[]        = array( 'link' => Yii::$app->urlManager->createUrl( 'modules/index' ), 'title' => $this->_page_title );
      $this->view->headingTitle  = $this->_page_title;

      $file_extension = ".php";


      $class = basename($_module);

      $this->selectedMenu = array( 'modules', 'modules?set='.$set );
      $heading = $contents = array();
      $file = $_module . $file_extension;

      $class = substr($file, 0, strrpos($file, '.'));
      if ($this->module_type == 'label') {
          $class = "common\\modules\\label\\" . $class;
      } elseif ($this->module_type == 'order_total') {
          $class = "common\\modules\\orderTotal\\" . $class;
      } elseif ($this->module_type == 'payment') {
          $class = "common\\modules\\orderPayment\\" . $class;
      } elseif ($this->module_type == 'shipping') {
          $class = "common\\modules\\orderShipping\\" . $class;
      } else {
        if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)) {
          require_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
        }
        if (file_exists($this->module_directory . $file)) {
          include_once($this->module_directory . $file);
        }
      }

      if (class_exists($class)) {
         $module = new $class;
      }

      $params = [];

      if (is_object($module)){
        $keys = array_merge([/*$module->title, $module->description*/], $module->keys());

        $_consts = get_defined_constants(true);
        $_consts = $_consts['user'];

        $language_consts = [];
        $_code = $module->code;
        if ($this->module_type == 'order_total'){
          $_code = substr($_code, 3);
        }
        if (is_array($_consts) && count($_consts) && (in_array("MODULE_" . strtoupper($this->module_type . '_' . $module->code)."_TEXT_TITLE", $_consts) || in_array("MODULE_" . strtoupper($this->module_type . '_' . $_code)."_TITLE", $_consts))){
          $ex = explode("_TEXT_TITLE", "MODULE_" . strtoupper($this->module_type . '_' . $_code)."_TEXT_TITLE");
          if (count($ex) == 1){
            $ex = explode("_TITLE", "MODULE_" . strtoupper($this->module_type . '_' . $_code)."_TITLE");
          }
          if (count($ex) > 1){
            $module_prefix = $ex[0];
            foreach($_consts as $name => $value){
              if (strpos($name, $module_prefix .'_' ) !== false && !in_array($name, $keys)){
                $language_consts[] = ['key' => $name, 'value' => $value];
              }
            }
          }
        }

        if(is_array($keys)){
          $languages = \common\helpers\Language::get_languages(true);
          $title_label = Translation::getTranslationValue('TEXT_TITLE_LABEL', 'configuration', $languages_id);
          $desc_label = Translation::getTranslationValue('TEXT_DESC_LABEL', 'configuration', $languages_id);

          for( $i = 0, $n = sizeof( $languages ); $i < $n; $i++ ) {

              $languages[$i]['logo'] = $languages[$i]['image'];


              if (count($language_consts)){
                  foreach($language_consts as $data){
                    $key = $data['key'];
                    $params[$i][$key]['configuration_title_label'] = $title_label;
                    $_value = (tep_not_null($_value = Translation::getTranslationValue($key, $set, $languages[$i]['id']))? $_value :$data['value']);
                    $params[$i][$key]['configuration_title'] = tep_draw_input_field('configuration_title[' . $languages[$i]['id'] . ']['.$key.']', $_value, 'class="form-control form-control-small"');
                    $params[$i][$key]['configuration_desc_label'] = '&nbsp;';
                    $params[$i][$key]['configuration_description'] = '&nbsp;';

                }
              }

              foreach($keys as $key){

                $param = tep_db_fetch_array(tep_db_query(
                  "select configuration_title, configuration_description from " . TABLE_PLATFORMS_CONFIGURATION. " where configuration_key = '" . strval($key). "' LIMIT 1"
                ));

                $params[$i]['id'] = $languages[$i]['id'];

                $config_values = new \objectInfo($param);

                $params[$i][$key]['configuration_title_label'] = $title_label;
                $params[$i][$key]['configuration_title'] = tep_draw_input_field('configuration_title[' . $languages[$i]['id'] . ']['.$key.']', $config_values->configuration_title, 'class="form-control form-control-small"');
                $params[$i][$key]['configuration_desc_label'] = $desc_label;
                $params[$i][$key]['configuration_description'] = tep_draw_input_field('configuration_description[' . $languages[$i]['id'] . ']['.$key.']', $config_values->configuration_description, 'class="form-control form-control-small"');

            }

          }
        }

      }

      if (Yii::$app->request->isPost){

          $languages = \common\helpers\Language::get_languages(true);

          $accepted = ['configuration_title', 'configuration_description'];

          foreach($_POST as $config_key => $config_variants){

            if (!in_array($config_key, $accepted)) continue;

            $ex = explode("_", $config_key);

            $param = strtoupper($ex[1]);

            for( $i = 0, $n = sizeof( $languages ); $i < $n; $i++ ) {

              if (is_array($config_variants[$languages[$i]['id']])) foreach ($config_variants[$languages[$i]['id']] as $cKey => $cValue) {

                if (in_array($cKey, $module->keys())){
                  Translation::setTranslationValue($cKey . '_' . $param, $set, $languages[$i]['id'], $cValue);
                } else {
                  Translation::setTranslationValue($cKey, $set, $languages[$i]['id'], $cValue);
                }

              }
            }
          }

          \common\helpers\Translation::resetCache();

        if (Yii::$app->request->isAjax){
          $this->layout = false;
          echo 'ok';
        } else
        return $this->redirect(Yii::$app->urlManager->createUrl('modules/?set='.$set.'&row='.$row));

      } else
        return $this->render('translation', ['params' => $params, 'languages' => $languages, 'codeMod'=> $module->code]);
    }

    public function needTranslation($module, $set){
      if (isset($module->isExtension)) {return false;}
      $keys = array_merge([$module->title, $module->description], $module->keys());
      if (is_array($keys)){
        $res = [];
        foreach($keys as $key){
          if (!tep_not_null(Translation::getTranslationValue($key.'_TITLE', $set))){
            $res[] = 1;
          } else {
            $res[] = 0;
          }
        }

        if (array_sum($res) > 0){ // need tranlation
          return true;
        }
      }
      return false;
    }

    public function actionChange()
    {
        $response = [];

        $set = Yii::$app->request->post('set', 'payment');

        $_module = Yii::$app->request->post('module', '');
        $action = Yii::$app->request->post('action', '');
        $enabled = Yii::$app->request->post('enabled', '');
        $file_extension = ".php";
        $this->rules($set);
        \common\helpers\Translation::init($this->module_entity);
        $class = basename($_module);
        if ($this->module_need_requiring) {//if ($this->module_type != 'label') {
            if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $class . $file_extension)){//ordertotal
                include_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $class . $file_extension);
            }
            if (file_exists($this->module_directory . $class . $file_extension)) {
              include_once($this->module_directory . $class . $file_extension);
            }
        } else {
            $ext = \common\helpers\Acl::checkExtension($class, 'allowed');
            if (!$ext){
                if ($this->module_namespace) {
                    $class = $this->module_namespace . $_module;
                }
            } else {
                $file_extension = "";
                $class = $ext;
            }
        }
        if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\{$this->module_class}") ) {
          $module = new $class;
          if (isset($module->isExtension)) $class = (new \ReflectionClass($class))->getShortName ();
          /**
           * @var modules\Module $module
           */
          if ($action == 'install') {
            if (!$module->check($this->selected_platform_id)) {
              $module->remove($this->selected_platform_id);
              $module->install($this->selected_platform_id);
              if ($this->needTranslation($module, $set)){
                $response['need_translate'] = $module->code;
              }
              $installed_modules_str = defined($this->module_key)?constant($this->module_key):'';
              $get_actual_value = tep_db_fetch_array(tep_db_query(
                "SELECT configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE configuration_key='".tep_db_input($this->module_key)."' AND platform_id='".intval($this->selected_platform_id)."'"
              ));
              if ( is_array($get_actual_value) ) {
                $installed_modules_str = $get_actual_value['configuration_value'];
              }
              $order_count = (count(explode(';',$installed_modules_str))+1)*10+10;
              tep_db_query(
                "update " . TABLE_PLATFORMS_CONFIGURATION. " ".
                "set configuration_value = TRIM(BOTH ';' FROM CONCAT(configuration_value,';".tep_db_input($class . $file_extension)."')), last_modified = now() ".
                "where configuration_key = '" . tep_db_input($this->module_key) . "' ".
                " AND platform_id='".intval($this->selected_platform_id)."'"
              );

              $module->update_sort_order($this->selected_platform_id, $order_count);
            }
            \common\helpers\Translation::resetCache();
          } elseif ($action == 'remove') {
            $module->remove($this->selected_platform_id);
            tep_db_query(
              "update " . TABLE_PLATFORMS_CONFIGURATION . " ".
              "set configuration_value = TRIM(BOTH ';' FROM REPLACE(CONCAT(';',configuration_value,';'),'".tep_db_input($class . $file_extension)."','')), last_modified = now() ".
              "where configuration_key = '" . tep_db_input($this->module_key) . "' ".
              " AND platform_id='".intval($this->selected_platform_id)."'"
            );
            \common\helpers\Translation::resetCache();
          }elseif($action == 'status'){
            $module->enable_module($this->selected_platform_id, $enabled == 'on' );
					}
        }

        $response['redirect'] = Yii::$app->urlManager->createUrl(['modules/list', 'set'=>$set, 'platform_id'=>$this->selected_platform_id]);


        echo json_encode($response);
    }

      public function actionSortOrder()
      {

        $sorted = Yii::$app->request->post('module',array());
        $set = Yii::$app->request->post('set','payment');
        $this->rules($set);

        $this->updateModulesSortOrder($sorted);

        echo json_encode(array('redirect'=>Yii::$app->urlManager->createUrl(['modules/list', 'set'=>$set, 'platform_id'=>$this->selected_platform_id])));

      }

      protected function updateModulesSortOrder($sorted){

        global $language;
        $sorted = array_map(function($val){
          if ( strpos($val,'\\')!==false ) $val = substr($val, strrpos($val,'\\')+1);
          if ( strpos($val,'.php')===false ) {
            $val.='.php';
          }
          return $val;
        },$sorted);
        $sorted = array_values($sorted);

        \common\helpers\Translation::init($this->module_entity);

        $installed_sort = defined($this->module_key)?constant($this->module_key):'';
        $get_actual_value = tep_db_fetch_array(tep_db_query(
          "SELECT configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE configuration_key='".tep_db_input($this->module_key)."' AND platform_id='".intval($this->selected_platform_id)."'"
        ));
        if ( is_array($get_actual_value) ) {
          $installed_sort = $get_actual_value['configuration_value'];
        }

        if ( strpos(implode(';', $sorted),$installed_sort)!==0 || implode(';', $sorted)!=$installed_sort ) {
          $sorted_idx = array_flip($sorted);
          $new_order = array();
          foreach( explode(';',$installed_sort) as $__installed_module ) {
            if (isset($sorted_idx[$__installed_module])){
              $new_order[$sorted_idx[$__installed_module]] = $__installed_module;
            }else {
              $new_order[] = $__installed_module;
            }
          }
          ksort($new_order);
          $installed_sort = implode(';',$new_order);
          $check_query = tep_db_query("select configuration_value from " . TABLE_PLATFORMS_CONFIGURATION . " where configuration_key = '" . tep_db_input($this->module_key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
          if (tep_db_num_rows($check_query)>0) {
            $check = tep_db_fetch_array($check_query);
            if ($check['configuration_value'] != $installed_sort) {
              tep_db_query("update " . TABLE_PLATFORMS_CONFIGURATION . " set configuration_value = '" . $installed_sort . "', last_modified = now() where configuration_key = '" . tep_db_input($this->module_key) . "' AND platform_id='".intval($this->selected_platform_id)."'");
            }
          } else {
            tep_db_query("insert into " . TABLE_PLATFORMS_CONFIGURATION. " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, platform_id) values ('Installed Modules', '" . tep_db_input($this->module_key) . "', '" . tep_db_input($installed_sort) . "', 'This is automatically updated. No need to edit.', '6', '0', now(), '".intval($this->selected_platform_id)."')");
          }
          $order_count = 0;
          foreach ($new_order as $module_file ) {
            $order_count+=10;

            $class = substr($module_file, 0, strrpos($module_file, '.'));
            if ( $this->module_namespace ){
                $class = $this->module_namespace.$class;
            }

            if (class_exists($class)) {
              $module = new $class;
              /**
               * @var modules\Module $module
               */
              $module->update_sort_order($this->selected_platform_id, $order_count);
            }
          }
        }

        $check_query = tep_db_query("select configuration_value from " . TABLE_PLATFORMS_CONFIGURATION. " where configuration_key = '" . tep_db_input($this->module_key_sort) . "' AND platform_id='".intval($this->selected_platform_id)."'");
        if (tep_db_num_rows($check_query)) {
          $check = tep_db_fetch_array($check_query);
          if ($check['configuration_value'] != implode(';', $sorted)) {
            tep_db_query("update " . TABLE_PLATFORMS_CONFIGURATION. " set configuration_value = '" . tep_db_input(implode(';', $sorted)) . "', last_modified = now() where configuration_key = '" . tep_db_input($this->module_key_sort) . "' AND platform_id='".intval($this->selected_platform_id)."'");
          }
        } else {
          tep_db_query("insert into " . TABLE_PLATFORMS_CONFIGURATION. " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, platform_id) values ('Modules Sort', '" . tep_db_input($this->module_key_sort) . "', '" . tep_db_input(implode(';', $sorted)) . "', 'This is automatically updated. No need to edit.', '6', '0', now(), '".intval($this->selected_platform_id)."')");
        }
      }

      public function actionMultisafepay()
      {
          $languages_id = \Yii::$app->settings->get('languages_id');
          $heading = $contents = array();
          $file = 'multisafepay.php';

          $this->rules('payment');

          $installed_modules = array();
          $modules_files = array();
          $heading = $contents = array();

          if (file_exists(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file)){
            include_once(DIR_FS_CATALOG_LANGUAGES . 'modules/' . $this->module_type . '/' . $file);
          }

          \common\helpers\Translation::init('admin/modules');
          include_once($this->module_directory . $file);

          $module_keys = array(
                'MODULE_PAYMENT_MULTISAFEPAY_API_SERVER',
                'MODULE_PAYMENT_MULTISAFEPAY_ACCOUNT_ID',
                'MODULE_PAYMENT_MULTISAFEPAY_SITE_ID',
                'MODULE_PAYMENT_MULTISAFEPAY_SITE_SECURE_CODE',
                'MODULE_PAYMENT_MULTISAFEPAY_AUTO_REDIRECT',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_INITIALIZED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_COMPLETED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_UNCLEARED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_RESERVED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_VOID',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_DECLINED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REVERSED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REFUNDED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_EXPIRED',
                'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_PARTIAL_REFUNDED',
                'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ENABLER',
                'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ICON_DISABLED',
          );

          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_API_SERVER'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Type account', 'MODULE_PAYMENT_MULTISAFEPAY_API_SERVER', 'Live account', '<a href=\'http://www.multisafepay.com/nl/klantenservice-zakelijk/open-een-testaccount.html\' target=\'_blank\' style=\'text-decoration: underline; font-weight: bold; color:#696916; \'>Sign up for a free test account!</a>', '6', '21', 'tep_cfg_select_option(array(\'Live account\', \'Test account\'), ', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ACCOUNT_ID'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Account ID', 'MODULE_PAYMENT_MULTISAFEPAY_ACCOUNT_ID', '', 'Your merchant account ID', '6', '22', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_SITE_ID'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Site ID', 'MODULE_PAYMENT_MULTISAFEPAY_SITE_ID', '', 'ID of this site', '6', '23', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_SITE_SECURE_CODE'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Site Code', 'MODULE_PAYMENT_MULTISAFEPAY_SITE_SECURE_CODE', '', 'Site code for this site', '6', '24', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_AUTO_REDIRECT'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Auto Redirect', 'MODULE_PAYMENT_MULTISAFEPAY_AUTO_REDIRECT', 'True', 'Enable auto redirect after payment', '6', '20', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_INITIALIZED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Initialized Order Status', 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_INITIALIZED', 0, 'In progress', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_COMPLETED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Completed Order Status',   'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_COMPLETED',   0, 'Completed successfully', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_UNCLEARED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Uncleared Order Status',   'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_UNCLEARED',   0, 'Not yet cleared', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_RESERVED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Reserved Order Status',    'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_RESERVED',    0, 'Reserved', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_VOID'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Voided Order Status',      'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_VOID',        0, 'Cancelled', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_DECLINED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Declined Order Status',    'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_DECLINED',    0, 'Declined (e.g. fraud, not enough balance)', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REVERSED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Reversed Order Status',    'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REVERSED',    0, 'Undone', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REFUNDED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Refunded Order Status',    'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_REFUNDED',    0, 'refunded', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_EXPIRED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Expired Order Status',     'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_EXPIRED',     0, 'Expired', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_PARTIAL_REFUNDED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Partial refunded Order Status',     'MODULE_PAYMENT_MULTISAFEPAY_ORDER_STATUS_ID_PARTIAL_REFUNDED',     0, 'Partial Refunded', '6', '0', 'tep_cfg_pull_down_order_statuses(', '\common\helpers\Order::get_order_status_name', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ENABLER'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable gateway titles in checkout', 'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ENABLER', 'True', 'Enable the gateway title in checkout', '6', '20', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
          }
          $check = tep_db_fetch_array(tep_db_query("select count(*) as key_exists from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ICON_DISABLED'"));
          if (!$check['key_exists']) {
            tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable icons in gateway titles. If disabled it will overrule option above.', 'MODULE_PAYMENT_MULTISAFEPAY_TITLES_ICON_DISABLED', 'True', 'Enable the icon in the checkout title for the gateway', '6', '20', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
          }


          $keys_extra = array();
          for ($j=0, $k=sizeof($module_keys); $j<$k; $j++) {
            $key_value_query = tep_db_query("select configuration_title, configuration_value, configuration_description, use_function, set_function from " . TABLE_CONFIGURATION . " where configuration_key = '" . tep_db_input($module_keys[$j]) . "'");
            $key_value = tep_db_fetch_array($key_value_query);

            $keys_extra[$module_keys[$j]]['title'] = (tep_not_null($value = Translation::getTranslationValue($module_keys[$j].'_TITLE', $set , $languages_id)) ? $value : $key_value['configuration_title']);
            $keys_extra[$module_keys[$j]]['value'] =  $key_value['configuration_value'];
            $keys_extra[$module_keys[$j]]['description'] = (tep_not_null($value = Translation::getTranslationValue($module_keys[$j].'_DESCRIPTION', $set , $languages_id)) ? $value : $key_value['configuration_description']);
            $keys_extra[$module_keys[$j]]['use_function'] = $key_value['use_function'];
            $keys_extra[$module_keys[$j]]['set_function'] = $key_value['set_function'];
          }

          $keys = '';
          if (is_array($keys_extra)) foreach ($keys_extra as $key => $value) {
            if ( $sort_order_key_name==$key ) {
              $keys .= tep_draw_hidden_field('configuration[' . $key . ']', $value['value']);
              continue;
            }

            $keys .= '<b>' . $value['title'] . '</b><br>' . $value['description'] . '<br>';

            if ($value['set_function']) {
              eval('$keys .= ' . $value['set_function'] . "'" . $value['value'] . "', '" . $key . "');");
            } else {
              $keys .= tep_draw_input_field('configuration[' . $key . ']', $value['value']);
            }
            $keys .= '<br><br>';
          }
          $keys = substr($keys, 0, strrpos($keys, '<br><br>'));

          $class = substr($file, 0, strrpos($file, '.'));
          if (class_exists($class)) {
            $module = new $class;

            if ($mInfo = $this->createInfo($module, 'payment')) {
              $heading[] = array('text' => '<b>' . $mInfo->title . '</b>');
            }
          }

          if (Yii::$app->request->isAjax) {
            $this->layout = false;
          }

          $this->selectedMenu        = array( 'modules', 'modules/multisafepay');
          $this->navigation[]        = array( 'link' => Yii::$app->urlManager->createUrl( 'modules/multisafepay' ), 'title' => $mInfo->title );

          return $this->render('edit.tpl', ['mainKey' => $keys, 'codeMod'=>$mInfo->code, 'set' => 'payment']);
      }

    public function actionExtraParams() {
        $set = \Yii::$app->request->post('set', '');
        $module = \Yii::$app->request->post('module', '');

        $this->rules($set);
        \common\helpers\Translation::init($this->module_entity);

        if ($this->module_type == 'label') {
            $module = "common\\modules\\label\\" . $module;
        } elseif ($this->module_type == 'order_total') {
            $module = "common\\modules\\orderTotal\\" . $module;
        } elseif ($this->module_type == 'payment') {
            $module = "common\\modules\\orderPayment\\" . $module;
        } elseif ($this->module_type == 'shipping') {
            $module = "common\\modules\\orderShipping\\" . $module;
        } elseif (file_exists(DIR_FS_CATALOG_MODULES . $set . '/' . $module . '.php')) {
            include_once(DIR_FS_CATALOG_MODULES . $set . '/' . $module . '.php');
        }
        if (class_exists($module)) {
            $object = new $module;
            if (method_exists($object, 'extra_params')) {
                echo $object->extra_params();
            }
        }
    }

}

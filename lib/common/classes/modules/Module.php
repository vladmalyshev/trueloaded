<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\modules\orderShipping\np;
use common\modules\orderTotal\ot_shipping;

abstract class Module{
  
/**
 * @prop \common\services\OrderManager $manager
 */
    public $manager;
    public $code;
    public $sort_order = 0;

    protected $countries = [''];
    protected $visibility = ['shop'];

    protected $defaultTranslationArray = [];

    public function __construct()
    {
        $this->_init();
    }

    protected function _init()
    {
        foreach ($this->defaultTranslationArray as $define => $translation) {
            if (!defined($define)) {
                define($define, $translation);
            }
        }
    }

    public function getTitle($method = '') {
        return $this->title;
    }

    public function check( $platform_id ) {
    $keys = $this->keys();
    if ( count($keys)==0 || ((int)$platform_id==0 && !$this->isExtension)) return 0;

    $check_keys_r = tep_db_query(
      "SELECT configuration_key ".
      "FROM " . TABLE_PLATFORMS_CONFIGURATION . " ".
      "WHERE configuration_key IN('".implode("', '",array_map('tep_db_input',$keys))."') AND platform_id='".(int)$platform_id."'"
    );
    $installed_keys = array();
    while( $check_key = tep_db_fetch_array($check_keys_r) ) {
      $installed_keys[$check_key['configuration_key']] = $check_key['configuration_key'];
    }

    $check_status = isset($installed_keys[$keys[0]])?1:0;

    $install_keys = false;
    foreach( $keys as $idx=>$module_key ) {
      if ( !isset($installed_keys[$module_key]) && $check_status ) {
        // missing key
        if ( !is_array($install_keys) ) $install_keys = $this->get_install_keys($platform_id);
        $this->add_config_key($platform_id, $module_key, $install_keys[$module_key]);
      }
    }

    return $check_status;
  }

  public function install( $platform_id ) {
    $keys = $this->get_install_keys($platform_id);
    if ( count($keys)==0 || ((int)$platform_id==0 && !$this->isExtension) ) return false;

    foreach($keys as $key=>$data) {
      $this->add_config_key($platform_id, $key, $data);
    }
  }

  protected function add_config_key($platform_id, $key, $data )
  {
    $sql_data = array(
      'platform_id' => (int)$platform_id,
      'configuration_key' => $key,
      'configuration_title' => isset($data['title'])?$data['title']:'',
      'configuration_value' => isset($data['value'])?$data['value']:'',
      'configuration_description' => isset($data['description'])?$data['description']:'',
      'configuration_group_id' => isset($data['group_id'])?$data['group_id']:'6',
      'sort_order' => isset($data['sort_order'])?$data['sort_order']:'0',
      'date_added' => 'now()',
    );
    if ( isset($data['use_function']) ) {
      $sql_data['use_function'] = $data['use_function'];
    }
    if ( isset($data['set_function']) ) {
      $sql_data['set_function'] = $data['set_function'];
    }
    tep_db_perform(TABLE_PLATFORMS_CONFIGURATION, $sql_data);
  }

  public function remove($platform_id) {
    $keys = $this->keys();
    if ( count($keys)>0 && ((int)$platform_id!=0 || isset($this->isExtension) )) {
      tep_db_query(
        "DELETE FROM ".TABLE_PLATFORMS_CONFIGURATION." ".
        "WHERE platform_id='".(int)$platform_id."' AND configuration_key IN('".implode("', '",$keys)."')"
      );
    }
  }

  function keys(){
    return array_keys($this->configure_keys());
  }

  /**
   * @return ModuleStatus
   */
  abstract public function describe_status_key();

  /**
   * @return ModuleSortOrder
   */
  abstract public function describe_sort_key();
  /**
   * @return array
   */

  abstract public function configure_keys();

  public function enable_module($platform_id, $flag){
    $key_info = $this->describe_status_key();
    if ( !is_object($key_info) || !is_a($key_info,'common\classes\modules\ModuleStatus')) return;

    $this->update_config_key(
      $platform_id,
      $key_info->key,
      $flag?$key_info->value_enabled:$key_info->value_disabled
    );
  }

  /**
   * @param $platform_id
   * @return bool
   */
  public function is_module_enabled($platform_id){
    $key_info = $this->describe_status_key();
    if ( !is_object($key_info) || !is_a($key_info,'common\classes\modules\ModuleStatus')) return false;

    return $this->get_config_key($platform_id,$key_info->key)==$key_info->value_enabled;
  }


  public function update_sort_order($platform_id, $new_sort_order){
    $key_info = $this->describe_sort_key();
    if ( !is_object($key_info) || !is_a($key_info,'common\classes\modules\ModuleSortOrder')) return;
    $this->update_config_key($platform_id, $key_info->key, (int)$new_sort_order );
  }

  protected function update_config_key($platform_id, $key, $value){
    tep_db_query(
      "UPDATE ".TABLE_PLATFORMS_CONFIGURATION." ".
      "SET configuration_value='".tep_db_input($value)."', last_modified=NOW() " .
      "WHERE configuration_key='".tep_db_input($key)."' AND platform_id='".(int)$platform_id."'"
    );
  }

  protected function get_config_key($platform_id, $key){
    $get_key_value_r = tep_db_query(
      "SELECT configuration_value ".
      "FROM ".TABLE_PLATFORMS_CONFIGURATION." ".
      "WHERE configuration_key='".tep_db_input($key)."' AND platform_id='".(int)$platform_id."'"
    );
    if ( tep_db_num_rows($get_key_value_r)>0 ) {
      $key_value = tep_db_fetch_array($get_key_value_r);
      return $key_value['configuration_value'];
    }
    return false;
  }

  public function save_config($platform_id, $new_data_array){
    if (is_array($new_data_array)) {
      $module_keys = $this->keys();
      foreach( $new_data_array as $update_key=>$new_value ){
        if ( !in_array($update_key,$module_keys) ) continue;
        $this->update_config_key($platform_id, $update_key, $new_value);
      }
    }
  }

  protected function get_install_keys($platform_id)
  {
    return $this->configure_keys();
  }

    public function getCountries(){
        $modulesCountries = \common\models\ModulesCountries::findOne(['code' => $this->code]);
        if (is_object($modulesCountries)) {
            $countries = explode(',', $modulesCountries->countries);
            return array_merge($this->countries, $countries);
        }
        return $this->countries;
    }

    public function getRestriction($platform_id, $languages_id, $ignoreVisibility = false) {
        if ( (int)$platform_id==0 ) return '';

        $countriesAccess = $this->getCountries();

        $variants = [];
        $variants[''] = 'Worldwide';
        global $languages_id;
        $countries = tep_db_query("SELECT c.countries_name, c.countries_iso_code_3 FROM " . TABLE_PLATFORMS_ADDRESS_BOOK . " AS pab LEFT JOIN " . TABLE_COUNTRIES . " AS c ON (c.countries_id = pab.entry_country_id) where c.language_id = '" . (int) $languages_id . "' group by c.countries_id");
        while ($countriesValue = tep_db_fetch_array($countries)) {
            $variants[$countriesValue['countries_iso_code_3']] = $countriesValue['countries_name'];
        }
        foreach ($countriesAccess as $code) {
            if (!isset($variants[$code])) {
            $country = \common\models\Countries::findOne(['countries_iso_code_3' => $code, 'language_id' => $languages_id]);
                if (is_object($country)) {
                    $variants[$code] = $country->countries_name;
                } else {
                    $variants[$code] = $code;
                }
            }
        }
        ksort($variants);

        $response = '<table width="50%"><thead><tr><th>' . TEXT_FOR_COUNTRIES . '</th></thead><tbody>';
        foreach ($variants as $code => $name) {
            $response .= '<tr><td>';
            $params = '';
            if (in_array($code, $this->countries)) {
                $params = 'disabled';
            }
            $response .= tep_draw_checkbox_field('countries[' . $code . ']', '1', in_array($code, $countriesAccess), '', $params );
            $response .= $name;
            $response .= '</td></tr>';
        }
        $response .= '</tbody></table>';
        if ($ignoreVisibility) {
            return $response;
        }
        $response .= '<table width="50%"><thead><tr><th>' . TEXT_VISIBILITY . '</th></thead><tbody>';
        $variants = ['shop_order' => 'Checkout','shop_quote' => 'Quotation','shop_sample' => 'Sample', 'admin' => 'Admin area', 'pos' => 'POS'];
        $visibilityAccess = $this->visibility;
        $modulesVisibility = \common\models\ModulesVisibility::findOne(['code' => $this->code]);
        if (is_object($modulesVisibility)) {
            $visibility = explode(',', $modulesVisibility->area);
            $visibilityAccess = array_merge($this->visibility, $visibility);
        }
        foreach ($variants as $code => $name) {
            $response .= '<tr><td>';
            $params = '';
            if (in_array($code, $this->visibility)) {
                $params = 'disabled';
            }
            $response .= tep_draw_checkbox_field('visibility[' . $code . ']', '1', in_array($code, $visibilityAccess), '', $params );
            $response .= $name;
            $response .= '</td></tr>';
        }
        $response .= '</tbody></table>';
        return $response;
    }

    public function setRestriction() {
        $platform_id = (int)\Yii::$app->request->post('platform_id');
        if ( (int)$platform_id==0 ) return false;

        $countries = \Yii::$app->request->post('countries');
        $selectedCountries = [];
        if (is_array($countries)) {
            foreach ($countries as $code => $checked) {
                if ($code === 0) {
                    $code = '';
                }
                if ($checked == 1) {
                    $selectedCountries[] = $code;
                }
            }
        }
        sort($selectedCountries);
        $modulesCountries = \common\models\ModulesCountries::findOne(['code' => $this->code]);
        if (!is_object($modulesCountries)) {
            $modulesCountries = new \common\models\ModulesCountries();
            $modulesCountries->code = $this->code;
        }
        $modulesCountries->countries = implode(',' , $selectedCountries);
        $modulesCountries->save();

        $visibility = \Yii::$app->request->post('visibility');
        $selectedVisibility = [];
        if (is_array($visibility)) {
            foreach ($visibility as $code => $checked) {
                if ($code === 0) {
                    $code = '';
                }
                if ($checked == 1) {
                    $selectedVisibility[] = $code;
                }
            }
        }
        sort($selectedVisibility);
        $modulesVisibility = \common\models\ModulesVisibility::findOne(['code' => $this->code]);
        if (!is_object($modulesVisibility)) {
            $modulesVisibility = new \common\models\ModulesVisibility();
            $modulesVisibility->code = $this->code;
        }
        $modulesVisibility->area = implode(',' , $selectedVisibility);
        $modulesVisibility->save();

        return true;
    }


    public function getVisibily($restrict = [])
    {
        $result = false;
        $modulesVisibility = \common\helpers\Modules::loadVisibility($this->code);
        if ( is_array($modulesVisibility) ){
            $result = (bool)count(array_intersect($restrict, $modulesVisibility));
        }
        return $result;
        /*
        $modulesVisibility = \common\models\ModulesVisibility::findOne(['code' => $this->code]);
        $result = false;
        if (is_object($modulesVisibility)) {
            $modulesVisibility = explode(',', $modulesVisibility->area);
            $result = (bool)count(array_intersect($restrict, $modulesVisibility));
        }
        return $result;
        */
    }

    public function getGroupRestriction($platform_id) {
        if ( (int)$platform_id==0 ) return '';

        $groups = \common\helpers\Group::get_customer_groups_list();

        $modulesGroups = \common\models\ModulesGroupsSettings::findOne(['platform_id' => $platform_id, 'code' => $this->code]);
        $visibilityAccess = [];

        if (!is_null($modulesGroups) && !empty($modulesGroups->group_list)) {
            $visibilityAccess = explode(',', $modulesGroups->group_list);
        }

        $response = '<table width="50%"><thead><tr><th>' . TEXT_FOR_GROUPS . ' ' . tep_draw_checkbox_field('group_restriction', '1', !is_null($modulesGroups), '', 'onchange="return updateGroupRestriction(this);"' ) . '</th></thead><tbody>';

        foreach ($groups as $id => $name) {
            $response .= '<tr><td>';
            $params = '';
            if (is_null($modulesGroups)) {
                $params = 'disabled';
            }
            $response .= tep_draw_checkbox_field('group_visibility[]', $id, in_array($id, $visibilityAccess), '', $params );
            $response .= $name;
            $response .= '</td></tr>';
        }

        $response .= '</tbody></table>';
        $response .= '<script type="text/javascript">function updateGroupRestriction(obj) { if ( $(obj).is(":checked") ) { $("input[name^=\'group_visibility\']").prop("disabled", false); } else { $("input[name^=\'group_visibility\']").prop("disabled", true); } }</script>';
        return $response;
    }

    public function setGroupRestriction() {
        $platform_id = (int)\Yii::$app->request->post('platform_id');
        if ( (int)$platform_id==0 ) return false;

        $modulesGroups = \common\models\ModulesGroupsSettings::findOne(['platform_id' => $platform_id, 'code' => $this->code]);

        $group_restriction = (int)\Yii::$app->request->post('group_restriction');
        if ($group_restriction == 1) {
            $group_visibility = \Yii::$app->request->post('group_visibility', []);
            if (is_null($modulesGroups)) {
                $modulesGroups = new \common\models\ModulesGroupsSettings();
                $modulesGroups->platform_id = $platform_id;
                $modulesGroups->code = $this->code;
            }
            try {
              $modulesGroups->group_list = implode(',', $group_visibility);
              $modulesGroups->save(false);
            } catch (\Exception $e) {
              \Yii::warning($e->getMessage() . ' ' . $e->getTraceAsString());
            }
            
        } else {
            if (!is_null($modulesGroups)) {
                $modulesGroups->delete();
            }
        }

    }

    public function getGroupVisibily($platform_id, $groups_id)
    {
        if ( (int)$platform_id==0 ) return true;
        //allow to disable for all groups if ( (int)$groups_id==0 ) return true;
        $modulesGroups = \common\models\ModulesGroupsSettings::findOne(['platform_id' => $platform_id, 'code' => $this->code]);
        if (!is_null($modulesGroups)) {
          if (!empty(trim($modulesGroups->group_list))) {
            $visibilityAccess = explode(',', $modulesGroups->group_list);
          } else {
            $visibilityAccess = [];
          }
            if (!in_array($groups_id, $visibilityAccess)) {
                return false;
            }
        }
        return true;
    }


    public $billing;
    public $delivery;

    public function setBilling(array $billing){
        $this->billing = $billing;
    }

    public function setDelivery(array $delivery){
        $this->delivery = $delivery;
    }

/**
 * get tax rate and tax description by tax class id (for current order delivery/billing address)
 * @param int $tax_class_id
 * @return array [
            'tax_class_id' => $tax_class_id,
 *
            'tax' => $tax, //Tax::get_tax_rate
 * 
            'tax_description' => $tax_description
        ];
 */
    function getTaxValues($tax_class_id) {

        $delivery_tax_values = \common\helpers\Tax::getTaxValues($this->manager->getPlatformId(), $tax_class_id, $this->delivery['country']['id'], $this->delivery['zone_id']);
        if ($delivery_tax_values['tax'] > 0) {
            return $delivery_tax_values;
        } else {
            return \common\helpers\Tax::getTaxValues($this->manager->getPlatformId(), $tax_class_id, $this->billing['country']['id'], $this->billing['zone_id']);
        }

    }

    public static function round($number, $precision) {
        if (abs($number) < (1 / pow(10, $precision + 1))) {
            $number = 0;
        }
        if (strpos($number, '.') AND (strlen(substr($number, strpos($number, '.') + 1)) > $precision)) {
            $number = substr($number, 0, strpos($number, '.') + 1 + $precision + 1);
            if (substr($number, -1) >= 5) {
                if ($precision > 1) {
                    $number = substr($number, 0, -1) + ('0.' . str_repeat(0, $precision - 1) . '1');
                } elseif ($precision == 1) {
                    $number = substr($number, 0, -1) + 0.1;
                } else {
                    $number = substr($number, 0, -1) + 1;
                }
            } else {
                $number = substr($number, 0, -1);
            }
        }
        return $number;
    }

    /**
     * Dump method for caption instead cost in order totals
     * @see np
     * @see ot_shipping
     * @return string|bool
     */
    public function costUserCaption()
    {
        return false;
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\modules\orderShipping;

use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

  class dp extends ModuleShipping{
    var $code, $title, $description, $icon, $enabled, $num_dp;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_DP_TEXT_TITLE' => 'German Post',
        'MODULE_SHIPPING_DP_TEXT_DESCRIPTION' => 'German Post - World Net',
        'MODULE_SHIPPING_DP_TEXT_WAY' => 'Dispatch to',
        'MODULE_SHIPPING_DP_TEXT_UNITS' => 'kg',
        'MODULE_SHIPPING_DP_INVALID_ZONE' => 'Unfortunately it is not possible to dispatch into this country',
        'MODULE_SHIPPING_DP_UNDEFINED_RATE' => 'Forwarding expenses cannot be calculated for the moment'
    ];

// class constructor
    function __construct() {
        parent::__construct();

        $this->code = 'dp';
        $this->title = MODULE_SHIPPING_DP_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_DP_TEXT_DESCRIPTION;
        if (!defined('MODULE_SHIPPING_DP_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->sort_order = MODULE_SHIPPING_DP_SORT_ORDER;
        $this->icon = DIR_WS_ICONS . 'shipping_dp.gif';
        $this->tax_class = MODULE_SHIPPING_DP_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_DP_STATUS == 'True') ? true : false);

      if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_DP_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_DP_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }

      // CUSTOMIZE THIS SETTING FOR THE NUMBER OF ZONES NEEDED
      $this->num_dp = 6;
    }

// class methods
    function quote($method = '') {

      $dest_country = $this->delivery['country']['iso_code_2'];
      $dest_zone = 0;
      $error = false;

      for ($i=1; $i<=$this->num_dp; $i++) {
        $countries_table = constant('MODULE_SHIPPING_DP_COUNTRIES_' . $i);
        $country_zones = explode(",", $countries_table);
        if (in_array($dest_country, $country_zones)) {
          $dest_zone = $i;
          break;
        }
      }

      if ($dest_zone == 0) {
        $error = true;
      } else {
        $shipping = -1;
        $dp_cost = constant('MODULE_SHIPPING_DP_COST_' . $i);

        $dp_table = preg_split("/[:,]/" , $dp_cost);
        for ($i=0; $i<sizeof($dp_table); $i+=2) {
          if ($this->shipping_weight <= $dp_table[$i]) {
            $shipping = $dp_table[$i+1];
            $shipping_method = MODULE_SHIPPING_DP_TEXT_WAY . ' ' . $dest_country . ': ';
            break;
          }
        }

        if ($shipping == -1) {
          $shipping_cost = 0;
          $shipping_method = MODULE_SHIPPING_DP_UNDEFINED_RATE;
        } else {
          $shipping_cost = ($shipping + MODULE_SHIPPING_DP_HANDLING);
        }
      }

      $this->quotes = array('id' => $this->code,
                            'module' => MODULE_SHIPPING_DP_TEXT_TITLE,
                            'methods' => array(array('id' => $this->code,
                                                     'title' => $shipping_method . ' (' . $this->shipping_num_boxes . ' x ' . $this->shipping_weight . ' ' . MODULE_SHIPPING_DP_TEXT_UNITS .')',
                                                     'cost' => $shipping_cost * $this->shipping_num_boxes)));

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      if ($error == true) $this->quotes['error'] = MODULE_SHIPPING_DP_INVALID_ZONE;

      return $this->quotes;
    }

    protected function get_install_keys($platform_id)
    {
      $keys = $this->configure_keys();

      $prefill_values = array(
        'MODULE_SHIPPING_DP_COUNTRIES_1' => 'AD,AT,BE,CZ,DK,FO,FI,FR,GR,GL,IE,IT,LI,LU,MC,NL,PL,PT,SM,SK,SE,CH,VA,GB,SP',
        'MODULE_SHIPPING_DP_COST_1' => '5:16.50,10:20.50,20:28.50',
        'MODULE_SHIPPING_DP_COUNTRIES_2' => 'AL,AM,AZ,BY,BA,BG,HR,CY,GE,GI,HU,IS,KZ,LT,MK,MT,MD,NO,SI,UA,TR,YU,RU,RO,LV,EE',
        'MODULE_SHIPPING_DP_COST_2' => '5:25.00,10:35.00,20:45.00',
        'MODULE_SHIPPING_DP_COUNTRIES_3' => 'DZ,BH,CA,EG,IR,IQ,IL,JO,KW,LB,LY,OM,SA,SY,US,AE,YE,MA,QA,TN,PM',
        'MODULE_SHIPPING_DP_COST_3' => '5:29.00,10:39.00,20:59.00',
        'MODULE_SHIPPING_DP_COUNTRIES_4' => 'AF,AS,AO,AI,AG,AR,AW,AU,BS,BD,BB,BZ,BJ,BM,BT,BO,BW,BR,IO,BN,BF,BI,KH,CM,CV,KY,CF,TD,CL,CN,CC,CO,KM,CG,CR,CI,CU,DM,DO,EC,SV,ER,ET,FK,FJ,GF,PF,GA,GM,GH,GD,GP,GT,GN,GW,GY,HT,HN,HK,IN,ID,JM,JP,KE,KI,KG,KP,KR,LA,LS',
        'MODULE_SHIPPING_DP_COST_4' => '5:35.00,10:50.00,20:80.00',
        'MODULE_SHIPPING_DP_COUNTRIES_5' => 'MO,MG,MW,MY,MV,ML,MQ,MR,MU,MX,MN,MS,MZ,MM,NA,NR,NP,AN,NC,NZ,NI,NE,NG,PK,PA,PG,PY,PE,PH,PN,RE,KN,LC,VC,SN,SC,SL,SO,LK,SR,SZ,ZA,SG,TG,TH,TZ,TT,TO,TM,TV,VN,WF,VE,UG,UZ,UY,ST,SH,SD,TW,GQ,LR,DJ,CG,RW,ZM,ZW',
        'MODULE_SHIPPING_DP_COST_5' => '5:35.00,10:50.00,20:80.00',
        'MODULE_SHIPPING_DP_COUNTRIES_6' => 'DE',
        'MODULE_SHIPPING_DP_COST_6' => '5:6.70,10:9.70,20:13.00',
      );

      foreach( $prefill_values as $_key=>$_value ) {
        if ( isset($keys[$_key]) ) {
          $keys[$_key]['value'] = $_value;
        }
      }

      return $keys;
    }

    public function configure_keys()
    {
      $config = array (
        'MODULE_SHIPPING_DP_STATUS' =>
          array (
            'title' => 'Deutsche Post WorldNet',
            'value' => 'True',
            'description' => 'Wollen Sie den Versand über die deutsche Post anbieten?',
            'sort_order' => '0',
            'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
          ),
        'MODULE_SHIPPING_DP_HANDLING' =>
          array (
            'title' => 'Handling Fee',
            'value' => '0',
            'description' => 'Bearbeitungsgebühr für diese Versandart in Euro',
            'sort_order' => '0',
          ),
        'MODULE_SHIPPING_DP_TAX_CLASS' =>
          array (
            'title' => 'Steuersatz',
            'value' => '0',
            'description' => 'Wählen Sie den MwSt.-Satz für diese Versandart aus.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
            'set_function' => 'tep_cfg_pull_down_tax_classes(',
          ),
        'MODULE_SHIPPING_DP_ZONE' =>
          array (
            'title' => 'Versand Zone',
            'value' => '0',
            'description' => 'Wenn Sie eine Zone auswählen, wird diese Versandart nur in dieser Zone angeboten.',
            'sort_order' => '0',
            'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
            'set_function' => 'tep_cfg_pull_down_zone_classes(',
          ),
        'MODULE_SHIPPING_DP_SORT_ORDER' =>
          array (
            'title' => 'Reihenfolge der Anzeige',
            'value' => '0',
            'description' => 'Niedrigste wird zuerst angezeigt.',
            'sort_order' => '0',
          ),
      );
      for ($i = 1; $i <= $this->num_dp; $i ++) {
        $config['MODULE_SHIPPING_DP_COUNTRIES_'.$i] = array(
          'title' => 'DP Zone '.$i.' Countries',
          'value' => '',
          'description' => 'Comma separated list of two character ISO country codes that are part of Zone '.$i,
          'sort_order' => '0',
        );
        $config['MODULE_SHIPPING_DP_COST_'.$i] = array (
            'title' => 'DP Zone '.$i.' Shipping Table',
            'value' => '',
            'description' => 'Shipping rates to Zone '.$i.' destinations based on a range of order weights. Example: 0-3:8.50,3-7:10.50,... Weights greater than 0 and less than or equal to 3 would cost 14.57 for Zone '.$i.' destinations.',
            'sort_order' => '0',
        );
      }
      return $config;
    }

    public function describe_status_key()
    {
      return new ModuleStatus('MODULE_SHIPPING_DP_STATUS','True','False');
    }

    public function describe_sort_key()
    {
      return new ModuleSortOrder('MODULE_SHIPPING_DP_SORT_ORDER');
    }

  }

<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes;

class platform_config
{
  protected $id;
  protected $platform;
  protected $platform_address;
  protected $platform_config;
  protected $platform_settings;
  protected $catalogBaseUrlWithId = false;
  protected $urls = [];
  protected $platformWarehouses;

  function __construct($platform_id)
  {
    $this->id = $platform_id;
    $this->load();
  }

  public function getId()
  {
    return $this->id;
  }

  protected function load()
  {
    $get_platform_data_r = tep_db_query("SELECT * FROM ".TABLE_PLATFORMS." WHERE platform_id='".(int)$this->id."'");
    if (tep_db_num_rows($get_platform_data_r)>0 ) {
      $this->platform = tep_db_fetch_array($get_platform_data_r);
      if ($this->platform['default_platform_id'] > 0) {
          $default_platform = tep_db_fetch_array(tep_db_query(
            "select * from platforms " .
            "where platform_id=" . $this->platform['default_platform_id'] . " " .
            "LIMIT 1 "
          ));
          $this->platform['platform_url'] = $default_platform['platform_url'];
          $this->platform['platform_url_secure'] = $default_platform['platform_url_secure'];
          $this->platform['ssl_enabled'] = $default_platform['ssl_enabled'];
          
      }
      if ($this->platform['is_virtual'] == 1) {
          $default_platform = tep_db_fetch_array(tep_db_query(
            "select * from platforms " .
            "where is_default=1 " .
            "LIMIT 1 "
          ));
          $this->platform['platform_url'] = $default_platform['platform_url'];
          $this->platform['platform_url_secure'] = $default_platform['platform_url_secure'];
          $this->platform['ssl_enabled'] = $default_platform['ssl_enabled'];
      }
      
      if ($this->platform['is_default_contact'] == 1 && is_array($default_platform)) {
        $this->platform['platform_email_from'] = $default_platform['platform_email_from'];
        $this->platform['platform_email_address'] = $default_platform['platform_email_address'];
        $this->platform['platform_email_extra'] = $default_platform['platform_email_extra'];
        $this->platform['platform_telephone'] = $default_platform['platform_telephone'];
      }
      
      if ( empty($this->platform['platform_url_secure']) ) {
        $this->platform['platform_url_secure'] = $this->platform['platform_url'];
      }

      if ($this->platform['is_default_address'] == 1 && isset($default_platform['platform_id'])) {
        $get_address_book_r = tep_db_query(
          "SELECT entry_company_vat, ".
          " entry_company as company, ".
          " entry_street_address as street_address, entry_suburb as suburb, ".
          " entry_city as city, entry_postcode as postcode, ".
          " entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id, ".
          " entry_company_reg_number as reg_number, ".
          " lat as latitude, lng as longitude ".
          "FROM ".TABLE_PLATFORMS_ADDRESS_BOOK." ".
          "WHERE platform_id='".intval($default_platform['platform_id'])."' ".
          "ORDER BY IF(is_default=1,0,1) LIMIT 1"
        );
      } else {
        $get_address_book_r = tep_db_query(
          "SELECT entry_company_vat, ".
          " entry_company as company, ".
          " entry_street_address as street_address, entry_suburb as suburb, ".
          " entry_city as city, entry_postcode as postcode, ".
          " entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id, ".
          " entry_company_reg_number as reg_number, ".
          " lat as latitude, lng as longitude ".
          "FROM ".TABLE_PLATFORMS_ADDRESS_BOOK." ".
          "WHERE platform_id='".intval($this->platform['platform_id'])."' ".
          "ORDER BY IF(is_default=1,0,1) LIMIT 1"
        );
      }
      if ( tep_db_num_rows($get_address_book_r)>0 ) {
        $this->platform_address = tep_db_fetch_array($get_address_book_r);
      }

      $get_platform_urls_r = tep_db_query(
          "SELECT url_type, status, url, ssl_enabled, remote_server_config ".
          "FROM ".TABLE_PLATFORMS_URL." ".
          "WHERE platform_id='".intval($this->platform['platform_id'])."' ".
          " AND status=1 "
      );
      if ( tep_db_num_rows($get_platform_urls_r)>0 ) {
          while($platform_url = tep_db_fetch_array($get_platform_urls_r)){
              if ( empty($platform_url['url']) ) continue;
              $this->urls[] = $platform_url;
          }
      }

        if ($this->platform['default_platform_id'] > 0) {
            $get_platform_config_r = tep_db_query("SELECT configuration_key, configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE platform_id='".intval($this->platform['default_platform_id'])."'");
            if ( tep_db_num_rows($get_platform_config_r)>0 ) {
                while( $_platform_config = tep_db_fetch_array($get_platform_config_r) ){
                    $this->platform_config[$_platform_config['configuration_key']] = $_platform_config['configuration_value'];
                }
            }
        }
        $get_platform_config_r = tep_db_query("SELECT configuration_key, configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE platform_id='".intval($this->platform['platform_id'])."'");
        if ( tep_db_num_rows($get_platform_config_r)>0 ) {
          while( $_platform_config = tep_db_fetch_array($get_platform_config_r) ){
            $this->platform_config[$_platform_config['configuration_key']] = $_platform_config['configuration_value'];
          }
        }
        if ($this->platform['is_virtual'] == 1 || $this->platform['is_marketplace']) {
            $get_platform_config_r = tep_db_query("SELECT configuration_key, configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE platform_id='".intval($default_platform['platform_id'])."'");
            if ( tep_db_num_rows($get_platform_config_r)>0 ) {
                while( $_platform_config = tep_db_fetch_array($get_platform_config_r) ){
                    $this->platform_config[$_platform_config['configuration_key']] = $_platform_config['configuration_value'];
                }
            }
        }
        
        $this->platform_settings = new platform_settings($this->platform['platform_id']);        
    }
  }

  public function setBaseHostUrl($ssl=true)
  {
      $catalog_base = ($this->platform['ssl_enabled']==2 || ($ssl && $this->platform['ssl_enabled']))?('https://' . $this->platform['platform_url_secure'] . '/'):('http://' . $this->platform['platform_url'] . '/');
      $parsed_url = parse_url($catalog_base);
      \Yii::$app->urlManager->setHostInfo($parsed_url['scheme'].'://'.$parsed_url['host']);
      \Yii::$app->urlManager->setBaseUrl($parsed_url['path']);
  }

  public function getPlatformToDescription(){
      return $this->platform_settings->getPlatformToDescription();
  }

  public function getPlatformAddress(){
    return $this->platform_address;
  }
  
  public function getPlatformData(){
    return $this->platform;
  }
  
    public function getPlatformDataField($field) {
        return ( isset($this->platform[$field]) ? $this->platform[$field] : '' );
    }

  public function catalogBaseUrlWithId($use_id = false)
  {
    $this->catalogBaseUrlWithId = $use_id;
  }

  public function isCatalogBaseUrlWithId()
  {
    return $this->catalogBaseUrlWithId;
  }

  public function getPlatformCode()
  {
    return $this->platform['platform_code'];
  }

  public function isVirtual()
  {
      return !!$this->platform['is_virtual'];
  }
  
  public function isMarketPlace()
  {
      return !!$this->platform['is_marketplace'];
  }

  public function getCatalogBaseUrl($ssl=false)
  {
    $ssl_status = defined('ENABLE_SSL_CATALOG')?(ENABLE_SSL_CATALOG===true || ENABLE_SSL_CATALOG==='true'):ENABLE_SSL;

    if ( $this->isCatalogBaseUrlWithId() && defined('HTTPS_CATALOG_SERVER') ) {
      $catalog_base = ($ssl && $ssl_status) ? (HTTPS_CATALOG_SERVER . DIR_WS_CATALOG) : (HTTP_CATALOG_SERVER . DIR_WS_CATALOG);
    }else{
      $catalog_base = ($this->platform['ssl_enabled']==2 || ($ssl && $this->platform['ssl_enabled']))?('https://' . $this->platform['platform_url_secure'] . '/'):('http://' . $this->platform['platform_url'] . '/');
    }
    return $catalog_base;
  }

  public function getAdditionalUrls()
  {
      return $this->urls;
  }

  public function getImagesCdnUrl()
  {
      $cdn_server = '';
      foreach( $this->getAdditionalUrls() as $urlInfo ){
          if ( $urlInfo['url_type']=='/' || $urlInfo['url_type']=='/images' ) {
              if ($urlInfo['ssl_enabled']==0 && !\Yii::$app->request->getIsSecureConnection()){
                  $cdn_server = rtrim('http://'.$urlInfo['url'],'/').'/';
                  if ($cdn_server==$this->getCatalogBaseUrl()){
                      $cdn_server = '';
                  }
              }elseif ( $urlInfo['ssl_enabled']!=0 ) {
                  $cdn_server = rtrim('https://'.$urlInfo['url'],'/').'/';
                  if ($cdn_server==$this->getCatalogBaseUrl(true)){
                      $cdn_server = '';
                  }
              }
              if ( !empty($cdn_server) && $urlInfo['url_type']=='/images' ) break;
          }
      }
      return $cdn_server;
  }

  public function getAllowedCurrencies(){
    if (tep_not_null($this->platform['defined_currencies'])){
      return explode(',',$this->platform['defined_currencies']);
    }
    return false;
  }
  
  public function getDefaultCurrency(){
    if (tep_not_null($this->platform['default_currency'])){
      return $this->platform['default_currency'];
    }
    return false;
  }  

  public function getAllowedLanguages(){
    if (tep_not_null($this->platform['defined_languages'])){
      return explode(',',$this->platform['defined_languages']);
    }
    return false;
  }
  
  public function getDefaultLanguage(){
    if (tep_not_null($this->platform['default_language'])){
      return $this->platform['default_language'];
    }
    return false;
  }  
  
  public function checkNeedSocials(){
    return (bool)$this->platform['use_social_login'];
  }

  public function contactUsEmail()
  {
      if (!empty($this->platform['contact_us_email'])){
          return $this->platform['contact_us_email'];
      }else{
          return $this->platform['platform_email_address'];
      }
  }
    public function landingContactEmail()
    {
        if (!empty($this->platform['landing_contact_email'])){
            return $this->platform['landing_contact_email'];
        }else{
            return $this->platform['platform_email_address'];
        }
    }

  public function constant_up(){
    if ( !is_array($this->platform_config) ) return;
    foreach( $this->platform_config as $key=>$val ) {
      if ( !defined($key) ) define($key, $val);
    }
  }

  public function const_value($key, $default='')
  {
    if ( isset($this->platform_config[$key]) ) {
      return $this->platform_config[$key];
    }elseif ( $key=='STORE_NAME' ) {
      return $this->platform['platform_name'];
    }elseif ( $key=='STORE_OWNER' ) {
      return $this->platform['platform_owner'];
    }elseif ( $key=='EMAIL_FROM' ) {
      return $this->platform['platform_email_from'];
    }elseif ( $key=='STORE_OWNER_EMAIL_ADDRESS' ) {
      return $this->platform['platform_email_address'];
    }elseif ( $key=='STORE_ADDRESS' ) {
      if ( function_exists('\common\helpers\Address::address_format') ) {
        $formatted = \common\helpers\Address::address_format(max(1,$this->platform_address['format_id']),$this->platform_address,false,'',"\n");
        $formatted = preg_replace("/\n\s*/ms","\n",$formatted); // remove empty customer name
        return $formatted;
      }
      //return $this->platform_address;
    }elseif( $key=='SEND_EXTRA_ORDER_EMAILS_TO' ) {
      return $this->platform['platform_email_extra'];
    }

    return defined($key)?constant($key):$default;
  }

  public function getDefPlatformId()
  {
      if ($this->platform['is_marketplace']) {
          return (int)$this->platform['default_platform_id'];
      }
      return $this->getId();
  }

    public function getGoogleShopPlatformId($code)
    {
        $platform_id = 0;
        $configValue = $this->const_value('GOOGLE_BASE_SHOP_PLATFORM_ID');
        if ( preg_match('/^(.*):(\d+)$/', $configValue, $match) && strtolower($code) == strtolower($match[1]) ) {
            $platform_id = (int)$match[2];
        }
        return empty($platform_id)?$this->id:$platform_id;
    }
    
/* get vitual real platfrom_id by code*/
    public function getSattelitePlatformId($code){
        $_platfrom_id = $this->getGoogleShopPlatformId($code);
        if ($_platfrom_id == $this->id){ //
            if ($ext = \common\helpers\Acl::checkExtension('AdditionalPlatforms', 'allowed')){
                $sattelite = $ext::getSattelite($code);
                if ($sattelite){
                    $_platfrom_id = $sattelite['platform_id'];
                }
            }
        }
        return $_platfrom_id;
    }


    public function setConfigValue($key, $value)
    {
        if ( (int)$this->id==0 ) return false;
        $platformKeyCheck = tep_db_fetch_array(tep_db_query(
            "SELECT COUNT(*) AS c ".
            "FROM ".TABLE_PLATFORMS_CONFIGURATION." ".
            "WHERE configuration_key='".tep_db_input($key)."' AND platform_id='".(int)$this->id."'"
        ));
        if ( $platformKeyCheck['c']==0 ) {
            $template_r = tep_db_query(
                "SELECT * ".
                "FROM ".TABLE_CONFIGURATION." ".
                "WHERE configuration_key='".tep_db_input($key)."'"
            );
            if ( tep_db_num_rows($template_r)==0 ) return false;
            $template = tep_db_fetch_array($template_r);
            unset($template['configuration_id']);
            $template['platform_id'] = (int)$this->id;
            tep_db_perform(TABLE_PLATFORMS_CONFIGURATION, $template);
        }
        tep_db_query(
            "UPDATE ".TABLE_PLATFORMS_CONFIGURATION." ".
            "SET configuration_value='".tep_db_input($value)."', last_modified=NOW() ".
            "WHERE configuration_key='".tep_db_input($key)."' AND platform_id='".(int)$this->id."'"
        );
        return true;
    }

    public function getPrefix(){
      if (tep_not_null($this->platform['platform_prefix'])){
        return $this->platform['platform_prefix'];
      }
      return false;
    }

    public function assignedWarehouses()
    {
        if ( !is_array($this->platformWarehouses) ) {
            $this->platformWarehouses = [];
            $data_r = tep_db_query(
                "SELECT w.warehouse_id " .
                "FROM " . TABLE_WAREHOUSES . " w " .
                "  LEFT JOIN " . TABLE_WAREHOUSES_TO_PLATFORMS . " w2p ON w.warehouse_id = w2p.warehouse_id AND w2p.platform_id = '" . intval($this->id) . "' " .
                "WHERE IFNULL(w2p.status, w.status) = '1' " .
                "ORDER BY IFNULL(w2p.sort_order, w.sort_order), w.warehouse_name"
            );
            if ( tep_db_num_rows($data_r)>0 ) {
                while ($data = tep_db_fetch_array($data_r)){
                    $this->platformWarehouses[] = (int)$data['warehouse_id'];
                }
            }
        }
        return $this->platformWarehouses;
    }

}

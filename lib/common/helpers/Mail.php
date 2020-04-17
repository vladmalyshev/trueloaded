<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class Mail {

    public static $designTemplate = '';
    public static $templateParams = [];

    public static function get_parsed_email_template($template_key, $email_params = '', $language_id = -1, $platform_id = -1, $aff_id = -1, $designTemplate = '') {
        global $languages_id;
        if ($platform_id == -1){
            $platform_id = \common\classes\platform::currentId();
        } else {
            $platform_id = \common\classes\platform::validId($platform_id);
        }
        $data_query = tep_db_query("select ett.email_templates_subject, ett.email_templates_body, et.email_templates_id from " . TABLE_EMAIL_TEMPLATES . " et, " . TABLE_EMAIL_TEMPLATES_TEXTS . " ett where et.email_templates_id = ett.email_templates_id and et.email_templates_key = '" . tep_db_input($template_key) . "' and ett.language_id = '" . (int) ($language_id > 0 ? $language_id : $languages_id) . "' and ett.affiliate_id = '" . (int) ($aff_id >= 0 ? $aff_id : 0) . "' and et.email_template_type = '" . (EMAIL_USE_HTML != 'true' ? 'plaintext' : 'html') . "' and ett.platform_id = '" . $platform_id . "'");
        $data = tep_db_fetch_array($data_query);

        if ($designTemplate) {
            self::$designTemplate = $designTemplate;
        } else {
            self::$designTemplate = \common\models\EmailTemplatesToDesignTemplate::findOne([
                'email_templates_id' => $data['email_templates_id'],
                'platform_id' => $platform_id
            ])->email_design_template;
        }

        $params = [
            'STORE_NAME' => '',
            'HTTP_HOST' => '',
            'STORE_OWNER_EMAIL_ADDRESS' => '',
            'CUSTOMER_EMAIL' => '',
            'CUSTOMER_FIRSTNAME' => '',
            'NEW_PASSWORD' => '',
            'USER_GREETING' => '',
            'ORDER_NUMBER' => '',
            'ORDER_DATE_LONG' => '',
            'ORDER_DATE_SHORT' => '',
            'BILLING_ADDRESS' => '',
            'DELIVERY_ADDRESS' => '',
            'PAYMENT_METHOD' => '',
            'ORDER_COMMENTS' => '',
            'NEW_ORDER_STATUS' => '',
            'ORDER_TOTALS' => '',
            'PRODUCTS_ORDERED' => '',
            'ORDER_INVOICE_URL' => '',
            'COUPON_AMOUNT' => '',
            'COUPON_NAME' => '',
            'COUPON_DESCRIPTION' => '',
            'COUPON_CODE' => '',
            'TRACKING_NUMBER' => '',
            'TRACKING_NUMBER_URL' => '',
        ];
        if (is_array($email_params)) {
            foreach ($email_params as $key => $value) {
                $params[$key] = $value;
            }
        }

        self::$templateParams = $params;
        $data['email_templates_body'] = preg_replace_callback("/{{([^}]+)}}/", "self::removeEmptyKeysBox", $data['email_templates_body']);

        if (is_array($params) && count($params) > 0) {
            $patterns = array();
            $replace = array();
            foreach ($params as $k => $v) {
                $patterns[] = "(##" . preg_quote($k) . "##)";
                $replace[] = str_replace('$', '/$/', $v);
            }

            $data['email_templates_subject'] = str_replace('/$/', '$', preg_replace($patterns, $replace, $data['email_templates_subject']));
            $data['email_templates_body'] = str_replace('/$/', '$', preg_replace($patterns, $replace, $data['email_templates_body']));
        }

        return array($data['email_templates_subject'], $data['email_templates_body']);
    }

    public static function removeEmptyKeysBox($matches){
        $str = preg_replace_callback("/##([A-Za-z0-9_]+)##/", "self::removeEmptyKeys", $matches[0]);
        if (strlen($str) == strlen($matches[0])) {
            return $matches[1];
        } else {
            return '';
        }
    }

    public static function removeEmptyKeys($matches){
        if (strlen(self::$templateParams[$matches[1]])>1){
            return $matches[0];
        } else {
            return '';
        }
    }

    public static function get_email_templates_body($email_templates_id, $language_id, $platform_id = -1) {
        if ($platform_id == -1){
            $platform_id = \common\classes\platform::currentId();
        }
        $data_query = tep_db_query("select email_templates_body from " . TABLE_EMAIL_TEMPLATES_TEXTS . " where email_templates_id = '" . (int) $email_templates_id . "' and language_id = '" . (int) $language_id . "' and platform_id = '" . $platform_id . "'");
        $data = tep_db_fetch_array($data_query);
        return $data['email_templates_body'];
    }

    public static function get_email_templates_subject($email_templates_id, $language_id, $platform_id = -1) {
        if ($platform_id == -1){
            $platform_id = \common\classes\platform::currentId();
        }
        $data_query = tep_db_query("select email_templates_subject from " . TABLE_EMAIL_TEMPLATES_TEXTS . " where email_templates_id = '" . (int) $email_templates_id . "' and language_id = '" . (int) $language_id . "' and platform_id = '" . $platform_id . "'");
        $data = tep_db_fetch_array($data_query);
        return $data['email_templates_subject'];
    }

    public static function get_email_design_templates($email_templates_id, $platform_id = -1) {
        if ($platform_id == -1){
            $platform_id = \common\classes\platform::currentId();
        }
        $email_design_template = \common\models\EmailTemplatesToDesignTemplate::findOne([
            'email_templates_id' => $email_templates_id,
            'platform_id' => $platform_id
        ])->email_design_template;

        $theme_id = \common\models\PlatformsToThemes::findOne($platform_id)->theme_id;
        $theme_name = \common\models\Themes::findOne(['id' => $theme_id])->theme_name;
        $designTemplates = \common\models\ThemesSettings::find()
            ->select(['setting_value'])
            ->where([
                'theme_name' => $theme_name,
                'setting_group' => 'added_page',
                'setting_name' => 'email',
            ])
            ->asArray()
            ->all();

        $list = [];
        $list[] = [
            'name' => '',
            'title' => 'Main',
            'active' => $email_design_template ? true : false,
            'theme_name' => $theme_name
        ];
        foreach ($designTemplates as $designTemplate) {
            $active = false;
            $templateName = \common\classes\design::pageName($designTemplate['setting_value']);
            if ($templateName == $email_design_template) {
                $active = true;
            }
            $list[] = [
                'name' => $templateName,
                'title' => $designTemplate['setting_value'],
                'active' => $active,
                'theme_name' => $theme_name
            ];
        }

        return $list;
    }

    public static function emailParamsFromOrder($order)
    {
        /**
         * @var platform_config $platform_config
         */
        $_keep_platform_id = \Yii::$app->get('platform')->config()->getId();
        $platform_config = \Yii::$app->get('platform')->config($order->info['platform_id']);

        $email_params = array();
        $email_params['STORE_NAME'] = $platform_config->const_value('STORE_NAME');
        $email_params['ORDER_NUMBER'] = $order->order_id;
        if ( function_exists('tep_catalog_href_link') ) {
            $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('account/history-info', 'order_id=' . $order->order_id, 'SSL'/* , $store['store_url'] */));
        }else{
            $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_href_link('account/history-info', 'order_id=' . $order->order_id, 'SSL'/* , $store['store_url'] */));
        }

        $email_language = new \common\classes\language( \common\classes\language::get_code($order->info['language_id']) );
        $email_language->set_locale();
        $formats = $email_language->get_language_formats($order->info['language_id']);

        $DATE_FORMAT_LONG = (isset($formats['DATE_FORMAT_LONG'])?$formats['DATE_FORMAT_LONG']:(defined('DATE_FORMAT_LONG')?DATE_FORMAT_LONG:''));
        if ( !defined('DATE_FORMAT_SHORT') ) define('DATE_FORMAT_SHORT', (isset($formats['DATE_FORMAT_SHORT'])?$formats['DATE_FORMAT_SHORT']:'%d %b %Y'));

        $email_params['ORDER_DATE_LONG'] = \common\helpers\Date::date_long($order->info['date_purchased'], $DATE_FORMAT_LONG);
        $email_params['ORDER_DATE_SHORT'] = \common\helpers\Date::date_short($order->info['date_purchased']);
        $email_params['DELIVERY_ADDRESS'] = '';
        $email_params['BILLING_ADDRESS'] = \common\helpers\Address::address_format($order->billing['format_id'],$order->billing,0, '', "<br>");
        $email_params['DELIVERY_ADDRESS'] = '';
        if($order->content_type != 'virtual'){
            $email_params['DELIVERY_ADDRESS'] = \common\helpers\Address::address_format($order->delivery['format_id'],$order->delivery,0, '', "<br>");
        }
        $email_params['PAYMENT_METHOD'] = $order->info['payment_method'];
        $email_params['SHIPPING_METHOD'] = $order->info['shipping_method'];

        $email_params['ORDER_COMMENTS'] = '';

        if ( isset($order->info['order_status']) ){
            $email_params['NEW_ORDER_STATUS'] = \common\helpers\Order::get_order_status_name($order->info['order_status'], $order->info['language_id']);
        }elseif ( isset($order->info['orders_status']) ) {
            $email_params['NEW_ORDER_STATUS'] = \common\helpers\Order::get_order_status_name($order->info['orders_status'], $order->info['language_id']);
        }

        $email_params['CUSTOMER_FIRSTNAME'] = $order->customer['firstname'];

        $email_params['PRODUCTS_ORDERED'] = $order->getProductsHtmlForEmail();
        $email_params['ORDER_TOTALS'] = $order->getOrderTotalsHtmlForEmail();

        \Yii::$app->get('platform')->config($_keep_platform_id);

        return $email_params;
    }

/**
 * parses email template and sends email.
 * @param string $to_name
 * @param string $to_email_address
 * @param string $email_subject
 * @param string $email_text
 * @param string $from_email_name
 * @param string $from_email_address
 * @param array $email_params
 * @param array|string $headers
 * @param array $attachment [
                              'name' => $filename,
                              'file' => file_get_contents($filename)
                            ]
 * @return boolean
 */
    public static function send($to_name, $to_email_address, $email_subject, $email_text, $from_email_name, $from_email_address, $email_params = array(), $headers = '', $attachments=false, $settings = []) {
        if (SEND_EMAILS != 'true')
            return false;

        try {
            $message = \common\modules\email\Transport::getTransport();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $text = strip_tags(preg_replace('/<br( \/)?>/ims', "\n", $email_text));
        if (EMAIL_USE_HTML == 'true') {
            if (!$settings['add_br']) {
                $email_text = str_replace(array("\r\n", "\n", "\r"), '<br>', $email_text);
                if (strip_tags($email_text, '<a>') != $email_text) { //from template
                    $email_text = preg_replace('#(<br */?>\s*){3,}#i', '<br><br>', $email_text);
                }
            }

            $attr = self::$designTemplate ? 'page_name=' . self::$designTemplate : '';
            if (function_exists('tep_catalog_href_link')) {
                $contents = @file_get_contents(tep_catalog_href_link('email-template', $attr));
            } else {
                $contents = @file_get_contents(tep_href_link('email-template', $attr, 'NONSSL', false));
            }
            if (empty($contents)) {
                $contents = '##EMAIL_TEXT##';
            }
            $contents = str_replace(array("\r\n", "\n", "\r"), '', $contents);

            $email_subject = str_replace('$', '/$/', $email_subject);
            $email_text = str_replace('$', '/$/', $email_text);
            $search = array("'##EMAIL_TITLE##'i",
                "'##EMAIL_TEXT##'i");
            $replace = array($email_subject, $email_text);
            if (is_array($email_params) && count($email_params) > 0) {
                foreach ($email_params as $key => $value) {
                    $search[] = "'##" . $key . "##'i";
                    $replace[] = $value;
                }
            }
            $email_text = str_replace('/$/', '$', preg_replace($search, $replace, $contents));
			if (function_exists('tep_catalog_href_link')) {
				$_tmp_site_url= parse_url(tep_catalog_href_link('link'));
			} else {
				$_tmp_site_url = parse_url(tep_href_link('link'));
			}
            $HOST = $_tmp_site_url['scheme'] . '://' . $_tmp_site_url['host'];
            $PATH = rtrim(substr($_tmp_site_url['path'], 0, strpos($_tmp_site_url['path'], 'link')), '/');
            $email_text = preg_replace('/(<img[^>]+src=)"\/([^"]+)"/i', '$1"' . $HOST . '/$2"', $email_text);
            $email_text = preg_replace('/(<img[^>]+src=)"(?![a-z]{3,5}:\/\/)([^"]+)"/i', '$1"' . $HOST . $PATH . '/$2"', $email_text);
            //VL generally [a-z]{3,5} could be replaced either with https? (images by http(s) protocol in emails) or [a-z][a-z0-9\-+.]+ (by any protocol)
            $has_tag_p = (preg_match("/<p>/", $email_text) ? true : false);
            if ($has_tag_p) {
                $email_text = str_replace(array("\r\n", "\n", "\r"), '', $email_text);
                $email_text = preg_replace("/(<\/p>)(<br[\s\/]*>)(<p>)?/mi", "$1$3", $email_text);
            }

            $message->add_html($email_text, $text);
        } else {
            $message->add_text($text);
        }

        if (is_array($attachments)) {
          foreach ($attachments as $attachment) {
            if (!empty($attachment['file']) && !empty($attachment['name'])) {
              $message->add_attachment($attachment['file'], $attachment['name']);
            }
          }
        }

        $message->build_message();

        // {{ admin bcc
        if ( defined('ALL_EMAIL_BCC_COPY') && trim(ALL_EMAIL_BCC_COPY)!='' ) {
            $message->addBcc(trim(ALL_EMAIL_BCC_COPY));
        }
        // }} admin bcc
        $message->send($to_name, $to_email_address, $from_email_name, $from_email_address, $email_subject, $headers);
    }

    public static function sendPlain($to_name, $to_email_address, $email_subject, $email_text, $from_email_name, $from_email_address, $email_params = array(), $headers = '') {
      if (SEND_EMAILS != 'true') {
        return false;
      }

      try {
        $message = new \common\classes\email(array('X-Mailer: True Loaded Mailer'));
      } catch (Exception $e) {
        echo $e->getMessage();
      }

      $message->add_text($email_text);
      $message->build_message();
      // {{ admin bcc
      if ( defined('ALL_EMAIL_BCC_COPY') && trim(ALL_EMAIL_BCC_COPY)!='' ) {
        $message->addBcc(trim(ALL_EMAIL_BCC_COPY));
      }
      // }} admin bcc
      $message->send($to_name, $to_email_address, $from_email_name, $from_email_address, $email_subject, $headers);
    }

    public static function getTypeList($withAll = false) {
        global $languages_id;
        $ordersStatusType = [];
        if ($withAll) {
            $ordersStatusType[0] = TEXT_ALL;
        }
        $orders_status_types_query = tep_db_query("select type_id, type_name from email_template_types where language_id = '" . (int) $languages_id . "'");
        while ($orders_status_types = tep_db_fetch_array($orders_status_types_query)) {
            $ordersStatusType[$orders_status_types['type_id']] = $orders_status_types['type_name'];
        }
        return $ordersStatusType;
    }

    public static function getSmsTypeList($withAll = false)
    {
        $ordersStatusType = [];
        if ($withAll) {
            $ordersStatusType[0] = TEXT_ALL;
        }
        return $ordersStatusType;
    }

    public static function get_sms_templates_body($sms_templates_id, $language_id, $platform_id = -1)
    {
        if ($platform_id == -1) {
            $platform_id = \common\classes\platform::currentId();
        }
        $smsTemplatesTextsRecord = \common\models\SmsTemplatesTexts::find()
            ->where(['sms_templates_id' => (int)$sms_templates_id])
            ->andWhere(['language_id' => (int)$language_id])
            ->andWhere(['platform_id' => (int)$platform_id])
            ->asArray(true)->one();
        return (isset($smsTemplatesTextsRecord['sms_templates_body']) ? $smsTemplatesTextsRecord['sms_templates_body'] : '');
    }

    public static function get_sms_template_parsed($template_key, $email_params = '', $language_id = -1, $platform_id = -1, $affiliate_id = -1)
    {
        global $languages_id;
        $template_key = trim($template_key);
        $language_id = (int)$language_id;
        $platform_id = (int)$platform_id;
        $affiliate_id = (int)$affiliate_id;
        if ($platform_id == -1) {
            $platform_id = \common\classes\platform::currentId();
        } else {
            $platform_id = \common\classes\platform::validId($platform_id);
        }
        $smsTemplateArray = \common\models\SmsTemplatesTexts::find()->alias('stt')
            ->leftJoin(\common\models\SmsTemplates::tableName() . ' st', 'stt.sms_templates_id = st.sms_templates_id')
            ->where(['st.sms_templates_key' => $template_key])
            ->andWhere(['stt.platform_id' => $platform_id])
            ->andWhere(['stt.language_id' => (int)(($language_id > 0) ? $language_id : $languages_id)])
            ->andWhere(['stt.affiliate_id' => (int)(($affiliate_id > 0) ? $affiliate_id : 0)])
            ->asArray(true)->one();
        $params = [
            'STORE_NAME' => '',
            'HTTP_HOST' => '',
            'STORE_OWNER_EMAIL_ADDRESS' => '',
            'CUSTOMER_EMAIL' => '',
            'CUSTOMER_FIRSTNAME' => '',
            'NEW_PASSWORD' => '',
            'USER_GREETING' => '',
            'ORDER_NUMBER' => '',
            'ORDER_DATE_LONG' => '',
            'ORDER_DATE_SHORT' => '',
            'BILLING_ADDRESS' => '',
            'DELIVERY_ADDRESS' => '',
            'PAYMENT_METHOD' => '',
            'ORDER_COMMENTS' => '',
            'NEW_ORDER_STATUS' => '',
            'ORDER_TOTALS' => '',
            'PRODUCTS_ORDERED' => '',
            'ORDER_INVOICE_URL' => '',
            'COUPON_AMOUNT' => '',
            'COUPON_NAME' => '',
            'COUPON_DESCRIPTION' => '',
            'COUPON_CODE' => '',
            'TRACKING_NUMBER' => '',
            'TRACKING_NUMBER_URL' => '',
        ];
        if (is_array($email_params)) {
            foreach ($email_params as $key => $value) {
                $params[$key] = $value;
            }
        }
        self::$templateParams = $params;
        $smsTemplateArray['sms_templates_body'] = preg_replace_callback("/{{([^}]+)}}/", "self::removeEmptyKeysBox", $smsTemplateArray['sms_templates_body']);
        if (is_array($params) && count($params) > 0) {
            $patterns = array();
            $replace = array();
            foreach ($params as $k => $v) {
                $patterns[] = "(##" . preg_quote($k) . "##)";
                $replace[] = str_replace('$', '/$/', $v);
            }
            $smsTemplateArray['sms_templates_body'] = str_replace('/$/', '$', preg_replace($patterns, $replace, $smsTemplateArray['sms_templates_body']));
        }
        return $smsTemplateArray['sms_templates_body'];
    }
}

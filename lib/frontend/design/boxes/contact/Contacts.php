<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\contact;

use frontend\design\Info;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\JsonLd;

class Contacts extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {

        $data = Info::platformData();
        $address = $data;
        $address['name'] = '';
        $address['reg_number'] = '';

        if ($this->settings[0]['time_format'] == '24' && is_array($data['open'])){
            foreach ($data['open'] as $key => $item){
                $data['open'][$key]['time_from'] = date("G:i", strtotime($item['time_from']));
                $data['open'][$key]['time_to'] = date("G:i", strtotime($item['time_to']));
            }
        }

        $addressTxt =  \common\helpers\Address::address_format(\common\helpers\Address::get_address_format_id($data['country_id']), $address, 0, ' ', '<br>', true);
        if ($this->settings[0]['tag_street_address']) {
            $addressTxt = str_replace('<!--street start-->', '<' . $this->settings[0]['tag_street_address'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--street end-->', '</' . $this->settings[0]['tag_street_address'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_city']) {
            $addressTxt = str_replace('<!--city start-->', '<' . $this->settings[0]['tag_city'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--city end-->', '</' . $this->settings[0]['tag_city'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_state']) {
            $addressTxt = str_replace('<!--state start-->', '<' . $this->settings[0]['tag_state'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--state end-->', '</' . $this->settings[0]['tag_state'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_country']) {
            $addressTxt = str_replace('<!--country start-->', '<' . $this->settings[0]['tag_country'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--country end-->', '</' . $this->settings[0]['tag_country'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_post_code']) {
            $addressTxt = str_replace('<!--postcode start-->', '<' . $this->settings[0]['tag_post_code'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--postcode end-->', '</' . $this->settings[0]['tag_post_code'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_company']) {
            $addressTxt = str_replace('<!--company start-->', '<' . $this->settings[0]['tag_company'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--company end-->', '</' . $this->settings[0]['tag_company'] . '>', $addressTxt);
        }
        if ($this->settings[0]['tag_suburb']) {
            $addressTxt = str_replace('<!--suburb start-->', '<' . $this->settings[0]['tag_suburb'] . '>', $addressTxt);
            $addressTxt = str_replace('<!--suburb end-->', '</' . $this->settings[0]['tag_suburb'] . '>', $addressTxt);
        }


        self::jsonLdData($data, $this->settings[0]);


    return IncludeTpl::widget(['file' => 'boxes/contact/contacts.tpl', 'params' => [
        'data' => $data,
        'phone' => '+' . preg_replace("/[^0-9]/i", "", $data['telephone']),
        'address' => $addressTxt,
        'settings' => $this->settings
    ]]);

  }

  public static function jsonLdData($data, $settings){

      $address_format_id = \common\helpers\Address::get_address_format_id($data['country_id']);
      $addressFormat = \common\models\AddressFormat::findOne($address_format_id);
      $addressFormatArr = json_decode($addressFormat->address_format);
      $addressFormatArrFlat = [];
      foreach ($addressFormatArr as $row) {
          $addressFormatArrFlat = array_merge($addressFormatArrFlat, $row);
      }

      $ldAddress['@type'] = 'PostalAddress';
      if ($data['street_address'] && in_array('street_address', $addressFormatArrFlat)) {
          $ldAddress['streetAddress'] = $data['street_address'];
      }
      if ($data['city'] && in_array('city', $addressFormatArrFlat)) {
          $ldAddress['addressLocality'] = $data['city'];
      }
      if ($data['state'] && in_array('state', $addressFormatArrFlat)) {
          $ldAddress['addressRegion'] = $data['state'];
      }
      if ($data['postcode'] && in_array('postcode', $addressFormatArrFlat)) {
          $ldAddress['postalCode'] = $data['postcode'];
      }
      if ($data['country'] && in_array('country', $addressFormatArrFlat)) {
          $ldAddress['addressCountry'] = $data['country'];
      }
      if ($data['suburb'] && in_array('suburb', $addressFormatArrFlat)) {
          $ldAddress['addressLocality'] = $data['suburb'];
      }

      JsonLd::addData(['Organization' => [
          'address' => $ldAddress
      ]], ['Organization', 'address']);

      if ($data['company_vat'] && in_array('company_vat', $addressFormatArrFlat)) {
          JsonLd::addData(['Organization' => [
              'vatID' => $data['entry_company_vat']
          ]], ['Organization', 'vatID']);
      }

      if ($data['telephone']) {
          JsonLd::addData(['Organization' => [
              'telephone' => $data['telephone']
          ]], ['Organization', 'telephone']);
      }
      if ($data['email_address']) {
          JsonLd::addData(['Organization' => [
              'email' => $data['email_address']
          ]], ['Organization', 'email']);
      }
      if ($data['reg_number']) {
          JsonLd::addData(['Organization' => [
              'leiCode' => $data['reg_number']
          ]], ['Organization', 'leiCode']);
      }
      if ($data['entry_company_vat']) {
          JsonLd::addData(['Organization' => [
              'vatID' => $data['entry_company_vat']
          ]], ['Organization', 'vatID']);
      }

      $jsonOurs = [];
      foreach ($data['open'] as $key => $item) {
          $jsonOurs[] = [
              '@type' => 'OpeningHoursSpecification',
              'dayOfWeek' => $item['days_arr'],
              'opens' => date("G:i", strtotime($item['time_from'])),
              'closes' => date("G:i", strtotime($item['time_to'])),
          ];
      }

      JsonLd::addData(['Organization' => [
          'openingHoursSpecification' => $jsonOurs
      ]], ['Organization', 'openingHoursSpecification']);
  }
}
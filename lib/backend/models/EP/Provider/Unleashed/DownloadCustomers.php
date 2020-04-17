<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;

use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;

class DownloadCustomers implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list = [];
    protected $config = [];

    /**
     * @var Client
     */
    protected $client;
    protected $hasMoreData = false;
    protected $fetchPage = 1;

    protected $customerExists = null;


    function __construct($config)
    {
        $this->config = $config;

    }

    public function getProgress()
    {
        if ( $this->total_count>0 ) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }

    public function prepareProcess(Messages $message)
    {
        $this->fetchPage = 1;
        $this->hasMoreData = false;
        $this->client = new Client($this->config['client']['API_ID'],$this->config['client']['API_KEY']);

        $this->process_list = [];
        $this->fetchPage($message);

        reset($this->process_list);

        $message->info("Customers for update - ".$this->total_count);
    }

    protected function fetchPage(Messages $message)
    {
        $response = $this->client->get('Customers/'.$this->fetchPage)->send();
        $data = $response->getData();
        
        $Pagination = $data['Pagination'];
        $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        $this->total_count = (int)$Pagination['NumberOfItems'];
        foreach ($data['Items'] as $item){
            $this->process_list[$item['Guid']] = $item;
        }
        return true;
    }

    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $this->processCustomer( $guid, $this->process_list[$guid], $message);

        if (next($this->process_list)){
            return true;
        }elseif($this->hasMoreData){
            $this->process_list = [];
            $this->fetchPage++;
            $message->info('fetch page ' . $this->fetchPage);
            return $this->fetchPage($message);
        }
    }

    public function postProcess(Messages $message)
    {
      $message->info('Done ');
    }

    protected function processCustomer($guid, $data, Messages $message)
    {
//echo "#### input <PRE>" .print_r($data, 1) ."</PRE>";

      static::findCustomer($data, $data['Email']);
      if (empty($data['customerExists'])) {
        $data['Customers Status'] = 0;
        $data['Platform'] = \common\classes\platform::name(\common\classes\platform::defaultId());
      } else {
        $data['Customers Status'] = $data['customerExists']->customers_status;
      }
      $data['Is Guest?'] = 0;
      $keys = [
        'Email' => 'Customers Email Address',
        'CustomerCode' => 'Erp Customer Code',
        'ContactFirstName' => 'Customers Firstname',
        'ContactLastName' => 'Customers Lastname',
        'GSTVATNumber' => 'Company Vat',
        'SellPriceTier' => 'Group',
        'PhoneNumber' => 'Customers Telephone',

        'CustomerName' => ['set' => 'setCustomerName'],
        'Addresses' => ['set' => 'setAddresses'],
        'Currency' => ['set' => 'setCurrency'],
        'CreatedOn' => ['set' => 'setDateCreated'],


        //'Obsolete' => '',

      ];

      /*
[Addresses] => Array
        (
            [0] => Array
                (
                    [AddressType] => Postal
                    [AddressName] =>  Postal Address
                    [StreetAddress] => CALLEVA PARK ALDERMASTON
                    [StreetAddress2] => line 2
                    [Suburb] => Reading
                    [City] => City
                    [Region] => Florida
                    [Country] => United Kingdom
                    [PostalCode] => RG78NN
                    [IsDefault] =>
                )

            [1] => Array
                (
                    [AddressType] => Physical
                    [AddressName] => Physical Address
                    [StreetAddress] => 5 JUPITER HOUSE
                    [StreetAddress2] => CALLEVA PARK ALDERMASTON
                    [Suburb] => Reading
                    [City] => City
                    [Region] => Florida
                    [Country] => United Kingdom
                    [PostalCode] => RG78NN
                    [IsDefault] =>
                )

        )

    [TaxCode] => G.S.T.
    [TaxRate] => 0.15
    [CustomerCode] => VL_TEST
    [CustomerName] => Vlad Koshelev
    [GSTVATNumber] =>
    [BankName] =>
    [BankBranch] =>
    [BankAccount] =>
    [Website] =>
    [PhoneNumber] =>
    [FaxNumber] =>
    [MobileNumber] =>
    [DDINumber] =>
    [TollFreeNumber] =>
    [Email] => vkoshelev@holbi.co.uk
    [EmailCC] =>
    [Currency] => Array
        (
            [CurrencyCode] => NZD
            [Description] => New Zealand, Dollars
            [Guid] => 7d1782be-03d7-4753-b8ee-2f77b41af22b
            [LastModifiedOn] => DateTime Object
                (
                    [date] => 2019-07-07 23:33:37.000000
                    [timezone_type] => 3
                    [timezone] => Europe/London
                )

        )

    [Notes] =>
    [Taxable] => 1
    [XeroContactId] =>
    [SalesPerson] => Array
        (
            [FullName] => John Smith
            [Email] => johh.smith@unleashedsoftware.com
            [Obsolete] =>
            [Guid] => d47d36d2-dc7a-4920-b907-f18a7343cf48
            [LastModifiedOn] => DateTime Object
                (
                    [date] => 2017-07-09 16:46:00.000000
                    [timezone_type] => 3
                    [timezone] => Europe/London
                )

        )

    [DiscountRate] => 0
    [PrintPackingSlipInsteadOfInvoice] =>
    [PrintInvoice] =>
    [StopCredit] =>
    [Obsolete] =>
    [XeroSalesAccount] =>
    [XeroCostOfGoodsAccount] =>
    [SellPriceTier] => Sell Price Tier 1
    [SellPriceTierReference] => Array
        (
            [Reference] => SellPriceTier1
        )

    [CustomerType] => Wholesale
    [PaymentTerm] => 20th Month following
    [ContactFirstName] => Vlad
    [ContactLastName] => Koshelev
    [SourceId] =>
    [CreatedBy] => vladkoshelev@gmail.com
    [CreatedOn] => DateTime Object
        (
            [date] => 2019-07-10 14:50:29.000000
            [timezone_type] => 3
            [timezone] => Europe/London
        )

    [LastModifiedBy] => vladkoshelev@gmail.com
    [Guid] => 567cc0d4-e000-415d-ab35-c6481f9594d3
    [LastModifiedOn] => DateTime Object
        (
            [date] => 2019-07-12 20:30:57.000000
            [timezone_type] => 3
            [timezone] => Europe/London
        )

)


       */

        $secondChance = []; //not used now
        foreach ($keys as $fileField => $d) {
          if (isset($data[$fileField]) && is_array($d)) {

            if (isset($d['set']) && method_exists($this, $d['set'])) {
              $r = call_user_func_array(array($this, $d['set']), array(&$data, $fileField, &$message));
              if ($r !== true) {
                $message->info($r);
                return ;
              }
            } elseif (!is_array($d)) {
              $data[$d] = $data[$fileField];
            }
            $secondChance[$fileField] = $data[$fileField];
            unset($data[$fileField]);
          } elseif (isset($data[$fileField]) && !is_array($d)) {
            $data[$d] = $data[$fileField];
          }

        }

        unset($data['customerExists']);

        if ($data && is_array($data) && !empty($data['Customers Email Address'])) {
//$message->info("#### mapped<PRE>" .print_r($data, 1) ."</PRE>");
          $providerObj = new \backend\models\EP\Provider\Customers();

          $transform = new \backend\models\EP\Transform();
          $transform->setProviderColumns($providerObj->getColumns());
          $data = $transform->transform($data);
//$message->info( "#### transformed<PRE>" .print_r($data, 1) ."</PRE>");


          $providerObj->importRow($data, $message);
          $this->row_count++;
          return true;
        } elseif (empty($data['Customers Email Address'])) {
          $message->info('empty email - skipped' . implode(' ' , [$data['Erp Customer Code'], $data['Customers Firstname'], $data['Customers Lastname'] ]));
        }
        return false;

    }

  public static function setCurrency(&$data, $fileField) {
    $ret = true;
    //2do check exists
    $data['Currency'] = strtoupper($data[$fileField]['CurrencyCode']);
    unset($data[$fileField]);
    return $ret;
  }

  public static function setCustomerName(&$data, $fileField) {
    $ret = true;
    $data['Account Company'] = $data[$fileField];
    if (!empty($data[$fileField]) && (empty($data['Customers Firstname']) || empty($data['Customers Lastname'])) ) {
      $fn = $ln = '';
      if (empty($data['Customers Firstname']) && empty($data['Customers Lastname'])) {
        $tmp = array_map('trim', explode(' ', trim($data[$fileField]), 2));
        if (isset($tmp[0])) {
          $data['Customers Firstname'] = $tmp[0];
        }
        if (isset($tmp[1])) {
          $data['Customers Lastname'] = $tmp[1];
        }
      } else {
        if (!empty($data['Customers Firstname'])) {
          $data['Customers Lastname'] = trim(str_replace($data['Customers Firstname'], '', $data[$fileField]));
        } elseif (!empty($data['Customers Lastname'])) {
          $data['Customers Firstname'] = trim(str_replace($data['Customers Lastname'], '', $data[$fileField]));
        }
      }
      foreach(['Customers Firstname', 'Customers Lastname', 'Account Company'] as $k) {
        $data[$k] = substr($data[$k], 0, 31);
      }

      unset($data[$fileField]);
    }

    return $ret;
  }

  public static function setDateCreated(&$data, $fileField) {
    $ret = true;
    /*
[CreatedOn] => DateTime Object
        (
            [date] => 2016-05-15 03:56:28.000000
            [timezone_type] => 3
            [timezone] => Pacific/Auckland
        )
          */
    $data['Date Created'] = $data[$fileField]->format('Y-m-d H:i:s');
    unset($data[$fileField]);
    return $ret;
  }

  public static function setAddresses(&$data, $fileField) {
    $ret = true;/*
            [0] => Array
                (
                    [AddressType] => Postal
                    [AddressName] =>  Postal Address
                    [StreetAddress] => CALLEVA PARK ALDERMASTON
                    [StreetAddress2] => line 2
                    [Suburb] => Reading
                    [City] => City
                    [Region] => Florida
                    [Country] => United Kingdom
                    [PostalCode] => RG78NN
                    [IsDefault] =>
                )*/
    if ($data[$fileField] && is_array($data[$fileField]) && count($data[$fileField])>0) {
      $d = array_filter($data[$fileField], function ($el) { return !empty($el['IsDefault']); } );
      if (empty($d)) {
        $defaultKey = 0;
      } else {
        reset($d);
        $defaultKey = key($d);
      }
      $cnt = 0;
      foreach ($data[$fileField] as $k => $d) {
        /* useless Name in Unleashed pulldowns. ("warehouse" City etc)
        if (!empty($d['AddressName']) ) {
          $data['Company Name'] = $d['AddressName'];
          $tmp = array_map('trim', explode(' ', trim($d['AddressName']), 2));
          if (isset($tmp[0])) {
            $data['Address Firstname'] = $tmp[0];
          }
          if (isset($tmp[1])) {
            $data['Address Lastname'] = $tmp[1];
          }
        }
         */
        $data['Account Company'] = substr($data['Account Company'], 0, 31);
        if ($k == $defaultKey) {
          $prefix = 'Default ';
          $suffix = '';
          $data[$prefix . 'Company Name' . $suffix] = $data['Account Company'];
        } else {
          $cnt++;
          $prefix = '';
          $suffix = ' ' . $cnt;
        }

        if (!empty($d['StreetAddress'])) {
          $data[$prefix . 'Street address' . $suffix] = $d['StreetAddress'];
        }
        if (!empty($d['StreetAddress2'])) {
          $data[$prefix . 'Suburb' . $suffix] = $d['StreetAddress2'];
        }
        if (!empty($d['Suburb'])) {
          $data[$prefix . 'Suburb' . $suffix] .= ' ' . $d['Suburb'];
          $data[$prefix . 'Suburb' . $suffix] = trim($data[$prefix . 'Suburb' . $suffix]);
        }
        if (!empty($d['City'])) {
          $data[$prefix . 'City' . $suffix] = $d['City'];
        }
        if (!empty($d['Region'])) {
          $data[$prefix . 'State' . $suffix] = $d['Region'];
        }
        if (!empty($d['Country'])) {
          //2check (not ISO-2)
          $data[$prefix . 'Address Country' . $suffix] = $d['Country'];
        }
        if (!empty($d['PostalCode'])) {
          $data[$prefix . 'Postcode' . $suffix] = $d['PostalCode'];
        }

        if (empty($data[$prefix . 'Address Country' . $suffix])) {
          $PlatformAddress = \Yii::$app->get('platform')->getConfig(\common\classes\platform::defaultId())->getPlatformAddress();
          $info = \common\helpers\Country::get_country_info_by_id($PlatformAddress['country_id']);
          $data[$prefix . 'Address Country' . $suffix] = $info['countries_iso_code_2'];
        }

        if ( empty($data[$prefix . 'Address Firstname' . $suffix]) && (!empty($d['ContactFirstName']) || !empty($data['Customers Firstname']))) {
          $data[$prefix . 'Address Firstname' . $suffix] = $d['ContactFirstName'] . $data['Customers Firstname'];
        }

        if (empty($data[$prefix . 'Address Lastname' . $suffix]) && (!empty($d['ContactLastName']) || !empty($data['Customers Lastname']))) {
          $data[$prefix . 'Address Lastname' . $suffix] = $d['ContactLastName'] . $data['Customers Lastname'];
        }
      }


      unset($data[$fileField]);
    }
    return $ret;
  }


  public static function findCustomer(&$data, $email) {
    if (!isset($data['customerExists']) && !empty($email)) {
      $p = \common\models\Customers::findOne([ 'customers_email_address' => $email ]);
      if ($p) {
        $data['customerExists'] = $p;
      } else {
        $data['customerExists'] = false;
      }
    }
  }


}
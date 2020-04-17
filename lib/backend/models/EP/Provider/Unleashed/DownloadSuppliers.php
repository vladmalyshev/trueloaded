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

class DownloadSuppliers implements DatasourceInterface
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

    protected $supplierExists = null;


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

        $message->info("Suppliers for update - ".$this->total_count);
    }

    protected function fetchPage(Messages $message)
    {
        $response = $this->client->get('Suppliers/'.$this->fetchPage)->send();
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

        $this->processSupplier( $guid, $this->process_list[$guid], $message);

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

    protected function processSupplier($guid, $data, Messages $message)
    {
//echo "#### input <PRE>" .print_r($data, 1) ."</PRE>";

      static::findSupplier($data);
      if (empty($data['supplierExists'])) {
        $data['Status'] = 1;
      } else {
        $data['Customers Status'] = $data['supplierExists']['status'];
      }
      $keys = [
        'SupplierName' => 'Company Name',
        'SupplierCode' => 'Suppliers name',
        'Currency' => ['set' => 'setCurrency'],
        'GSTVATNumber' => 'Company VAT Number',
        'Notes' => 'Condition description',
        //'Obsolete' => ['set' => 'setStatus'], // not passed via API
      ];

      /*
{
      "SupplierCode": "PPP01",
      "SupplierName": "Paul's Produce & Particulars",
      "GSTVATNumber": null,
      "BankName": null,
      "BankBranch": null,
      "BankAccount": null,
      "Website": null,
      "PhoneNumber": null,
      "FaxNumber": null,
      "MobileNumber": null,
      "DDINumber": null,
      "TollFreeNumber": null,
      "Email": null,
      "Currency": {
        "CurrencyCode": "NZD",
        "Description": "New Zealand, Dollars",
        "Guid": "7d1782be-03d7-4753-b8ee-2f77b41af22b",
        "LastModifiedOn": "/Date(1562535217383)/"
      },
      "Notes": null,
      "Taxable": true,
      "XeroContactId": null,
      "LastModifiedBy": null,
      "CreatedOn": "/Date(1499611560146)/",
      "CreatedBy": "admin",
      "Guid": "d8d644af-cbd0-4647-bb9a-ee5129ac4312",
      "LastModifiedOn": "/Date(1562683560196)/"
    },

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

        unset($data['supplierExists']);

        if ($data && is_array($data) && !empty($data['Suppliers name'])) {
//$message->info("#### mapped<PRE>" .print_r($data, 1) ."</PRE>");
          $providerObj = new \backend\models\EP\Provider\Suppliers();

          $transform = new \backend\models\EP\Transform();
          $transform->setProviderColumns($providerObj->getColumns());
          $data = $transform->transform($data);
//$message->info( "#### transformed<PRE>" .print_r($data, 1) ."</PRE>");


          $providerObj->importRow($data, $message);
          $this->row_count++;
          return true;
        } elseif (empty($data['Suppliers name'])) {
          $message->info('empty Suppliers name - skipped' . implode(' ' , [$data['Company Name'] ]));
        }
        return false;

    }

  public static function setCurrency(&$data, $fileField) {
    $ret = true;
    //2do check exists
    $data['Default currency code'] = strtoupper($data[$fileField]['CurrencyCode']);
    unset($data[$fileField]);
    return $ret;
  }

  public static function setStatus(&$data, $fileField) {
    $ret = true;
    if ($data['Status'] == 1) {
      $d = ( empty($data[$fileField]) ? 0 : $data[$fileField] );
      $data['Status'] = in_array(strtolower($d), ['true', 'yes', 1])?0:1;
    }
    return $ret;
  }

  public static function findSupplier(&$d) {
    if (!isset($d['supplierExists']) ) {
      $q = \common\models\Suppliers::find()->andWhere([
        'or',
        ['suppliers_name' => [$d['SupplierCode'], $d['SupplierName']]],
        ['company' => [$d['SupplierCode'], $d['SupplierName']]],
      ]);
      if ($q->count()>0) {
        $data['supplierExists'] = $q->asArray()->one();
      } else {
        $data['supplierExists'] = false;
      }
    }
  }


}
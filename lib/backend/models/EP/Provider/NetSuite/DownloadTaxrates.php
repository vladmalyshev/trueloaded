<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Currencies Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\NetSuite;

use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadTaxrates implements DatasourceInterface
{

    protected $total_pages = 1;
    protected $current_page = 0;
    protected $process_list;

    protected $config = [];

    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $client;

    /**
     * remote_key => [local_key, static callback to transform]
     * @var array mapRemoteLocal
     */
    static protected $mapRL = [    ];
          /*       [0] => NetSuite\Classes\SalesTaxItem Object
                        (
                            [itemId] => CA-BUTTE
                            [displayName] =>
                            [description] =>
                            [rate] => 7.25%
                            [taxType] =>
                            [taxAgency] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => -100
                                    [externalId] =>
                                    [type] =>
                                    [name] => State Board of Equalization
                                )

                            [purchaseAccount] =>
                            [saleAccount] =>
                            [isInactive] =>
                            [effectiveFrom] =>
                            [validUntil] =>
                            [subsidiaryList] =>
                            [includeChildren] =>
                            [eccode] =>
                            [reverseCharge] =>
                            [parent] =>
                            [service] =>
                            [exempt] =>
                            [isDefault] =>
                            [excludeFromTaxReports] =>
                            [available] =>
                            [export] =>
                            [taxAccount] => NetSuite\Classes\RecordRef Object
                                (
                                    [internalId] => 37
                                    [externalId] =>
                                    [type] =>
                                    [name] => Sales Taxes Payable
                                )

                            [county] => BUTTE
                            [city] =>
                            [state] => CA
                            [zip] => ,95914,95916,95917,95926,95927,95928,95929,95930,95938,95940,95941,95942,95948,95954,95958,95965,95966,95967,95968,95969,95973,95974,95976,95978
                            [nexusCountry] =>
                            [customFieldList] =>
                            [internalId] => -165
                            [externalId] =>
                            [nullFieldList] =>
                        )*/

    function __construct($config)
    {
      if (empty($config['client']['email']) && isset($config['client']['username'])) {
        $config['client']['email'] = $config['client']['username'];
        unset($config['client']['username']);
      }
      if (empty($config['client']['app_id']) && isset($config['client']['appid'])) {
        $config['client']['app_id'] = $config['client']['appid'];
        unset($config['client']['appid']);
      }
      $this->config = $config;
    }

    public function allowRunInPopup()
    {
        return true;
    }

    public function getProgress()
    {
        if ( $this->total_pages>0 ) {
            $percentDone = min(100, ($this->current_page / $this->total_pages) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }


    public function prepareProcess(Messages $message)
    {
        // init client
        try {
          
          $this->client = new \NetSuite\NetSuiteService(array_merge(NetSuiteLink::$config, $this->config['client']));

        } catch (\Exception $ex) {
          throw new Exception('Configuration error');
        }

        // download remote ids and process it
        $this->fetchServerPage();

    }

    public function processRow(Messages $message)
    {

      if ( !is_array($this->process_list) ) return false;

      if ( !is_array($this->process_list) || count($this->process_list)==0 ) {
          return false;
      }

      $data = array_shift($this->process_list);
      $this->saveTaxrateInfo($message, $data);

      return $data;

    }

    public function postProcess(Messages $message)
    {

    }

    protected function fetchServerPage()
    {
        $this->process_list = [];
        $this->current_page++;

        echo '<pre>FETCH PAGE '; var_dump($this->current_page); echo '</pre>';
        $response = Helper::basicSearch($this->client, 'tax');
/*
$response->
        status
        totalRecords
        pageSize
        totalPages
        pageIndex
        recordList Record[]
  */
        if (isset($response->totalRecords) && isset($response->recordList)) {
            foreach ($response->recordList->record as $record) {
              $this->process_list[$record->internalId] = $record;
            }
        }
    }

    protected function saveTaxrateInfo(Messages $message, $taxrateInfo)
    {
        $taxrateInfo = json_decode(json_encode($taxrateInfo), true);
        $wdr = [];
//        Helper::applyMap(self::$mapRL, $taxrateInfo, $wdr);

        $wdr['remoteId'] = $taxrateInfo['internalId'];
        $wdr['description'] = $taxrateInfo['rate'] . ' ' . $taxrateInfo['itemId'] . ' ' . $taxrateInfo['displayName'] . ' ' . $taxrateInfo['description'] ;

        // just save these values in kv_storage.
        // link in admin tax rate controller (fill in _mapping)
        $tmp = Helper::getKeyValue($this->config['directoryId'], 'nsSaleTaxItem_' . $wdr['remoteId']);
        if (empty($tmp) || is_null($tmp)) {
          Helper::setKeyValue($this->config['directoryId'], 'nsSaleTaxItem_' . $wdr['remoteId'], $wdr['description']);
        }
    }

}

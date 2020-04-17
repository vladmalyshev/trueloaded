<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Datasource;

use backend\models\EP\DatasourceBase;
use backend\models\EP\Datasource\HolbiSoap;
use NetSuite;
use backend\models\EP\Provider\NetSuite\Helper;

class NetSuiteLink extends HolbiSoap
{

    public static $config = array(
                    // required -------------------------------------
                    "endpoint" => "2017_2",
                    "host"     => "https://webservices.netsuite.com",
                    "email"    => "",
                    "password" => "",
                    "role"     => "18",// 3 admin 18 full
                    "account"  => "",
                    "app_id"   => "",
                    // optional -------------------------------------
                    "logging"  => false,
                    "log_path" => "/var/www/myapp/logs/netsuite"
                 );

    public function getName()
    {
        return 'NetSuite WebServices';
    }




    public function getViewTemplate()
    {
        //return 'datasource/netsuite.tpl';
      return parent::getViewTemplate();
    }

    public static function getProviderList()
    {
        return [
          'NetSuiteLink\\DownloadSuppliers' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Import Suppliers',
                'class' => 'Provider\\NetSuite\\DownloadSuppliers',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
          'NetSuiteLink\\DownloadWarehouses' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Import Warehouses',
                'class' => 'Provider\\NetSuite\\DownloadWarehouses',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'NetSuiteLink\\DownloadProducts' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Import products',
                'class' => 'Provider\\NetSuite\\DownloadProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'NetSuiteLink\\DownloadGroups' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Import groups',
                'class' => 'Provider\\NetSuite\\DownloadGroups',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'NetSuiteLink\\DownloadCurrencies' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Import Currencies',
                'class' => 'Provider\\NetSuite\\DownloadCurrencies',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'NetSuiteLink\\DownloadTaxrates' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Download Tax Rates (NS Sales Tax Codes)',
                'class' => 'Provider\\NetSuite\\DownloadTaxrates',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'NetSuiteLink\\ExportOrders' => [
                'group' => 'NetSuite WebServices',
                'name' => 'Export Orders',
                'class' => 'Provider\\NetSuite\\ExportOrders',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
        ];
    }

    public function prepareConfigForView($configArray)
    {
      $configArray = parent::prepareConfigForView($configArray);
      $configArray['apitemplate'] = 'netsuite-api.tpl';
      
      return $configArray;
    }

    public function update($settings)
    {
        DatasourceBase::update($settings);

        try {
            $config = $this->getJobConfig();
            $client = $this->getClient($config['client']);
/* class as category
        $response = Helper::basicSearch($client, 'category');
        if (isset($response->totalRecords) && isset($response->recordList)) {
          $cats = [];
          foreach ($response->recordList->record as $record) {
            $record = json_decode(json_encode($record),true);
            $cats[$record['internalId']] = $record;
          }
          if (count($cats)>0) {
            Helper::$nsCategories = $cats;
            Helper::buildTree();
          }
echo "SSAa <PRE>" . print_r(Helper::$nsCategories,1) . "</pre>"; die;
        }
*/
//$t = Helper::basicSearch($client, 'product', ['internalIdNumber' => ['value' => '821', 'fieldType' => 'Long', 'operator' => 'equalTo']]);
//$t = Helper::basicSearch($client, 'product');

            //Helper::getOrderStatusesFromServer($client, $config);
//$t = Helper::get($client, 'salesOrder', 5874); //972
//$t = Helper::get($client, 'TaxItem', 113); //972
/*$t = Helper::get($client, 'serviceSaleItem', 18);*/
//$t = Helper::basicSearch($client, 'TaxType');
//$t = Helper::get($client, 'customer', 1582);
//$t = Helper::get($this->client, 'customList', 9);
//$t = Helper::getAll($this->client, 'salesTaxItem');
//$t= Helper::basicSearch($this->client, 'customer', ['email' => ['value' => 'atkach'] ]);
//$t= Helper::basicSearch($this->client, 'tax');
//$t = Helper::getCustomizationId($client);
//$tt = Helper::getList($client, $t->customizationRefList->customizationRef);
//echo "SSAa <PRE>" . print_r($tt,1) . "\n vvv ". print_r($t,1) . "</pre>"; die;
//$t = Helper::get($client, 'customer', 1395);
//echo "SSAa <PRE>" . print_r($t,1) . "</pre>"; die;
            //$t = Helper::getOrderStatuses($client);
/*            Helper::putOrderStatusesOnServer($client, $config);
            if ( isset($config['status_map_local_to_server']) && preg_grep('/^create_on/',$config['status_map_local_to_server']) ) {
                Helper::syncOrderStatuses($client, $this);
            }
*/

        }catch (\Exception $ex){
        }
    }

    public function getClient($clientConfig = '')
    {
        if ( !is_array($clientConfig) ) {
          $clientConfig = $this->settings['client'];
        }
        $saved = ["email"    => $clientConfig['username'],
                  "password" => $clientConfig['password'],
                  "account"  => $clientConfig['account'],
                  "app_id"   => $clientConfig['appid']];

        $client = false;

        if ( !empty($clientConfig['appid']) ) {
          try {
            $client = new NetSuite\NetSuiteService(array_merge(self::$config, $saved));
//$t = Helper::basicSearch($client, 'warehouse');
//echo "SSA <PRE>" . print_r($t,1) . "</pre>"; die;            
          } catch (\Exception $ex) {
              throw new \InvalidArgumentException($ex->getMessage());
          }

        } else {
          throw new \InvalidArgumentException('Invalid credentials');
        }
        return $client;
    }
    
    public function initRemoteData($configArray)
    {
        try {
            $client = $this->getClient($configArray['client']);
            if ( $client ) {
                Helper::syncOrderStatuses($client, $this);
                $serverStatuses = Helper::getOrderStatusesFromServer($client);
                if (is_array($serverStatuses)) {
                    $this->remoteData['order_statuses'] = [];
                    $this->remoteData['order_statuses_list'] = [];

                    $this->remoteData['order_statuses_list'] = $serverStatuses;
                    $group_name = '';

                    foreach ($serverStatuses as $serverStatus) {
                        if (strpos($serverStatus['id'], 'group') === 0) {
                            $group_name = $serverStatus['name'];
                            continue;
                        }
                        $this->remoteData['order_statuses'][$group_name][$serverStatus['_id']] = $serverStatus['name'];
                    }
                }
            }
        }catch (\Exception $ex){

        }
    }
}

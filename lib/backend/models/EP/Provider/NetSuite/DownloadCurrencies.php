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
use common\api\models\AR\Currencies;
use yii\helpers\ArrayHelper;
use NetSuite\Classes as NS;
use NetSuite\NetSuiteService;
use backend\models\EP\Datasource\NetSuiteLink;

class DownloadCurrencies implements DatasourceInterface
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
    static protected $mapRL = [
                    'name' => ['title'],
                    'symbol' => ['code'],
                    'displaySymbol' => ['symbol_left'],
                    'isInactive' => ['status', '_Not'],
                    'internalId' => ['currencies_id'],
/*
                            [isBaseCurrency] =>
                            [isInactive] =>
                            [overrideCurrencyFormat] =>
                            [displaySymbol] => £
                            [symbolPlacement] => _beforeNumber
                            [locale] => _unitedKingdomEnglish
                            [formatSample] => £1,234.56
                            [exchangeRate] => 1
                            [fxRateUpdateTimezone] =>
                            [inclInFxRateUpdates] =>
                            [currencyPrecision] => _two
                            [internalId] => 2
  */
    ];

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

          /*$ds = new NetSuiteLink();
          $this->client = &$ds->getClient($this->config['client']);
          */

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
      $this->updateLocalCurrencies($message, $data);

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
        $response = Helper::getAll($this->client, 'currency');
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

    protected function updateLocalCurrencies(Messages $message, $currenciesData)
    {
        $currenciesData = json_decode(json_encode($currenciesData), true);
        $wdr = [];
        Helper::applyMap(self::$mapRL, $currenciesData, $wdr);

        $remote_currencies_id = $wdr['currencies_id'];
        unset($wdr['currencies_id']);

        $local_currencies_id = $this->lookupLocalId($remote_currencies_id, $wdr);

        $create_local = false;
        $currencyObj = Currencies::findOne(['currencies_id'=>$local_currencies_id]);
        if ( !is_object($currencyObj) || empty($currencyObj->currencies_id) ) {
            $currencyObj = new Currencies();
            $create_local = true;
        }

        $currencyObj->importArray($wdr);
        $currencyObj->save();

        if ( $currencyObj->currencies_id ) {
            if ($create_local) {
                tep_db_perform('ep_holbi_soap_mapping', [
                    'ep_directory_id' => $this->config['directoryId'],
                    'mapping_type' => 'currencies',
                    'remote_id' => $remote_currencies_id,
                    'local_id' => $currencyObj->currencies_id,
                ]);
            }
        }
    }

    protected function lookupLocalId($remoteId, $currenciesData=[])
    {
      static $mapping = [];
        if ( !isset($mapping[$remoteId]) ) {
          $get_local_id_r = tep_db_query(
              "SELECT local_id ".
              "FROM ep_holbi_soap_mapping ".
              "WHERE ep_directory_id='".(int)$this->config['directoryId']."' ".
              " AND remote_id='".$remoteId."' AND mapping_type='currencies'"
          );
          if ( tep_db_num_rows($get_local_id_r)>0 ) {
              $_local_id = tep_db_fetch_array($get_local_id_r);
              tep_db_free_result($get_local_id_r);
              $mapping[$remoteId] = $_local_id['local_id'];
              return $_local_id['local_id'];
          } elseif (!empty($currenciesData['code']))  {
            $get_local_id_r = tep_db_query(
                "SELECT currencies_id as local_id ".
                " FROM " . TABLE_CURRENCIES .
                " WHERE code like '" . tep_db_input($currenciesData['code']) . "' "
            );

            if ($_local_id = tep_db_fetch_array($get_local_id_r)) {
              tep_db_free_result($get_local_id_r);
              $mapping[$remoteId] = $_local_id['local_id'];
              tep_db_query(
                "insert into  ep_holbi_soap_mapping ".
                "set ep_directory_id='".(int)$this->config['directoryId']."', ".
                " remote_id='".(int)$remoteId."', local_id='" . (int)$_local_id['local_id'] . "', mapping_type='currencies'"
                );

              return $_local_id['local_id'];
            }
          }
          return false;
        }
        return intval($mapping[$remoteId]);
    }

    protected function getCurrenciessSyncConfig( $productId, $configKey=null )
    {
        $datasourceConfig = $this->config['currencies'];
        $datasourceConfig['create_on_client'] = isset($datasourceConfig['create_on_client'])?!!$datasourceConfig['create_on_client']:true;
        $datasourceConfig['create_on_server'] = isset($datasourceConfig['create_on_server'])?!!$datasourceConfig['create_on_server']:false;
        $datasourceConfig['update_on_client'] = isset($datasourceConfig['update_on_client'])?!!$datasourceConfig['update_on_client']:true;
        $datasourceConfig['update_on_server'] = isset($datasourceConfig['update_on_server'])?!!$datasourceConfig['update_on_server']:false;


        if ( !is_null($configKey) ) {
            return isset($datasourceConfig[$configKey])?$datasourceConfig[$configKey]:null;
        }
        return $datasourceConfig;
    }

}

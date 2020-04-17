<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\HolbiSoap;


use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use common\api\models\AR\CatalogProperty;
use common\api\models\AR\EPMap;
use common\api\models\AR\Manufacturer;
use yii\helpers\ArrayHelper;

class SynchronizeBrands implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;

    protected $process_records_r;

    protected $config = [];

    protected $remote_brands = [];
    protected $local_not_linked_brands = [];

    /**
     * @var \SoapClient
     */
    protected $client;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function allowRunInPopup()
    {
        return true;
    }

    public function getProgress()
    {
        if ($this->total_count > 0) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        } else {
            $percentDone = 100;
        }
        return number_format($percentDone, 1, '.', '');
    }

    public function prepareProcess(Messages $message)
    {
        // init client
        try {
            $this->client = new \SoapClient(
                $this->config['client']['wsdl_location'],
                [
                    'trace' => 1,
                    //'proxy_host'     => "localhost",
                    //'proxy_port'     => 8080,
                    //'proxy_login'    => "some_name",
                    //'proxy_password' => "some_password",
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'stream_context' => stream_context_create([
                        'http' => [
                            //'header'  => "APIToken: $api_token\r\n",
                        ]
                    ]),
                ]
            );
            $auth = new \stdClass();
            $auth->api_key = $this->config['client']['department_api_key'];
            $soapHeaders = new \SoapHeader('http://schemas.xmlsoap.org/ws/2002/07/utility', 'auth', $auth, false);
            $this->client->__setSoapHeaders($soapHeaders);
        } catch (\Exception $ex) {
            throw new Exception('Soap Configuration error');
        }

        tep_db_query(
            "DELETE em FROM ep_holbi_soap_mapping em ".
            " LEFT JOIN ".TABLE_MANUFACTURERS." m ON m.manufacturers_id=em.local_id ".
            "WHERE em.ep_directory_id = '".intval($this->config['directoryId'])."' AND em.mapping_type = 'brand' ".
            " AND m.manufacturers_id IS NULL "
        );

        // {{ for put them to server in after process
        $this->local_not_linked_brands = [];
        $get_not_linked_local_r = tep_db_query(
            "SELECT m.manufacturers_id ".
            "FROM ".TABLE_MANUFACTURERS." m ".
            "  LEFT JOIN ep_holbi_soap_mapping em ".
            "    ON em.ep_directory_id = '".intval($this->config['directoryId'])."' AND em.mapping_type = 'brand' AND m.manufacturers_id=em.local_id ".
            "WHERE em.local_id IS NULL "
        );
        if ( tep_db_num_rows($get_not_linked_local_r)>0 ) {
            while($_not_linked_local = tep_db_fetch_array($get_not_linked_local_r)){
                $this->local_not_linked_brands[(int)$_not_linked_local['manufacturers_id']] = (int)$_not_linked_local['manufacturers_id'];
            }
        }
        // }}

        try {
            $current_page = 0;
            $this->remote_brands = [];
            do{
                $current_page++;
                $params = [
                    'searchConditions' => [
                    ],
                    'paging' => [
                        'page' => $current_page,
                    ]
                ];
                $response = $this->client->getManufacturers($params['searchConditions'], $params['paging']);
                if ($response && $response->status != 'ERROR' && isset($response->manufacturers_list) && isset($response->manufacturers_list->manufacturer)) {
                    if ( is_array($response->manufacturers_list->manufacturer) ) {

                    }elseif(is_object($response->manufacturers_list->manufacturer)){
                        $response->manufacturers_list->manufacturer = [$response->manufacturers_list->manufacturer];
                    }
                    foreach ($response->manufacturers_list->manufacturer as $manufacturer) {
                        $remote_brand = json_decode(json_encode($manufacturer), true);
                        if ( isset($remote_brand['date_added']) && $remote_brand['date_added']>1000 ) {
                            $remote_brand['date_added'] = date('Y-m-d H:i:s', strtotime($remote_brand['date_added']));
                        }
                        if ( isset($remote_brand['last_modified']) && $remote_brand['last_modified']>1000 ) {
                            $remote_brand['last_modified'] = date('Y-m-d H:i:s', strtotime($remote_brand['last_modified']));
                        }
                        if ( isset($remote_brand['info_array']) && isset($remote_brand['info_array']['info']) ) {
                            $_infos = ArrayHelper::isIndexed($remote_brand['info_array']['info'])?$remote_brand['info_array']['info']:[$remote_brand['info_array']['info']];
                            $_extract_description = [];
                            foreach ($_infos as $_info)
                            {
                                $_extract_description[$_info['language']] = $_info;
                            }
                            $remote_brand['infos'] = $_extract_description;
                        }
                        $this->remote_brands[$remote_brand['manufacturers_id']] = $remote_brand;
                    }
                }
                if ( $response->paging->page>=$response->paging->totalPages ) {
                    break;
                }
            }while(true);
        }catch (\Exception $ex){
            \Yii::info('getManufacturers SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            throw new Exception('Incomplete server Manufacturers fetched');
        }

        $this->total_count = count($this->remote_brands);

        reset($this->remote_brands);
    }

    public function processRow(Messages $message)
    {
        $remote_data = current($this->remote_brands);
        if ( !$remote_data ) return false;

        $local_id = $this->processRemoteBrand($remote_data);
        unset($this->local_not_linked_brands[(int)$local_id]);

        next($this->remote_brands);
        $this->row_count++;

        return true;
    }

    public function postProcess(Messages $message)
    {
        if ( count($this->local_not_linked_brands)>0 ) {
            foreach ($this->local_not_linked_brands as $local_not_linked_id ){
                $localObj = Manufacturer::findOne(['manufacturers_id' => $local_not_linked_id]);
                $data = $localObj->exportArray([]);
                unset($data['manufacturers_id']);

                if (!empty($data['date_added'])) {
                    $data['date_added'] = (new \DateTime($data['date_added']))->format(DATE_ISO8601);
                }
                if (!empty($data['last_modified'])) {
                    $data['last_modified'] = (new \DateTime($data['last_modified']))->format(DATE_ISO8601);
                }

                if ( isset($data['infos']) && is_array($data['infos']) ) {
                    $data['info_array'] = array('info'=>array());
                    foreach ($data['infos'] as $langCode=>$info){
                        $info['language'] = $langCode;
                        $data['info_array']['info'][] = $info;
                    }
                }

                try {
                    $response = $this->client->createManufacturer($data);
                    \Yii::info('createManufacturer [OK]:'."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                    if ( $response && $response->status!='ERROR' && $response->manufacturer && $response->manufacturer->manufacturers_id ){
                        $remote_id = $response->manufacturer->manufacturers_id;
                        tep_db_perform(
                            'ep_holbi_soap_mapping',
                            [
                                'ep_directory_id' => intval($this->config['directoryId']),
                                'mapping_type' => 'brand',
                                'remote_id' => $remote_id,
                                'local_id' => $local_not_linked_id,
                            ]
                        );
                    }
                }catch(\Exception $ex){
                    \Yii::info('createManufacturer SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
                }

            }
        }

    }

    protected function processRemoteBrand($remote_data)
    {
        $remote_id = $remote_data['manufacturers_id'];
        unset($remote_data['manufacturers_id']);

        $server_last_date = false;
        if ( isset($remote_data['date_added']) && $remote_data['date_added']>1000 ) {
            $server_last_date = $remote_data['date_added'];
        }
        if ( isset($remote_data['last_modified']) && $remote_data['last_modified']>1000 ) {
            if ( $server_last_date===false || $remote_data['last_modified']>$server_last_date ) {
                $server_last_date = $remote_data['last_modified'];
            }
        }

        $local_id = $this->getLocalBrandId($remote_id);

        if ( $local_id===false ) {
            // not mapped yet
            $searchName = $remote_data['manufacturers_name'];

            // check same name in parent
            $searchSimilar_r = tep_db_query(
                "SELECT m.manufacturers_id " .
                "FROM " . TABLE_MANUFACTURERS . " m " .
                "WHERE m.manufacturers_name='" .tep_db_input($searchName). "' ".
                "LIMIT 1"
            );
            if (tep_db_num_rows($searchSimilar_r) > 0) {
                $foundSimilar = tep_db_fetch_array($searchSimilar_r);
                $local_id = $foundSimilar['manufacturers_id'];

                $localLinkObj = Manufacturer::findOne(['manufacturers_id' => $local_id]);
                $local_modify_date = $localLinkObj->date_added;
                if ( !empty($localLinkObj->last_modified) && $localLinkObj->last_modified>1000 ) {
                    $local_modify_date = $localLinkObj->last_modified;
                }
                if ( $server_last_date>$local_modify_date ) {
                    // server last updated
                    $localLinkObj->importArray($remote_data);
                    $localLinkObj->save(false);
                }elseif($server_last_date<$local_modify_date){
                    // local property touched last, update server
                    $this->updateBrandOnServer($remote_id, $localLinkObj);
                }
            } else {
                $newBrand = new Manufacturer();
                $newBrand->importArray($remote_data);
                $newBrand->save(false);
                $newBrand->refresh();
                $local_id = $newBrand->manufacturers_id;
            }

            if ($local_id>0) {
                tep_db_perform(
                    'ep_holbi_soap_mapping',
                    [
                        'ep_directory_id' => intval($this->config['directoryId']),
                        'mapping_type' => 'brand',
                        'remote_id' => $remote_id,
                        'local_id' => $local_id,
                    ]
                );
            }
        }elseif( is_numeric($local_id) && $local_id>0 ){
            // mapped - update
            $localObj = Manufacturer::findOne(['manufacturers_id'=>$local_id]);
            if ( $localObj ) {
                $local_modify_date = $localObj->date_added;
                if ( !empty($localObj->last_modified) && $localObj->last_modified>1000 ) {
                    $local_modify_date = $localObj->last_modified;
                }
                if ( $server_last_date>$local_modify_date ) {
                    // server last updated
                    // existing
                    $localObj->importArray($remote_data);
                    $localObj->save(false);
                }elseif($server_last_date<$local_modify_date){
                    // local property touched last, update server
                    $this->updateBrandOnServer($remote_id, $localObj);
                }
            }else{
                $local_id = -1;
            }
        }

        return $local_id;
    }

    protected function updateBrandOnServer($remote_id, EPMap $brandObj)
    {

        $data = $brandObj->exportArray([]);
        $data['manufacturers_id'] = $remote_id;

        if (!empty($data['date_added'])) {
            $data['date_added'] = (new \DateTime($data['date_added']))->format(DATE_ISO8601);
        }
        if (!empty($data['last_modified'])) {
            $data['last_modified'] = (new \DateTime($data['last_modified']))->format(DATE_ISO8601);
        }

        if ( isset($data['infos']) && is_array($data['infos']) ) {
            $data['info_array'] = array('info'=>array());
            foreach ($data['infos'] as $langCode=>$info){
                $info['language'] = $langCode;
                $data['info_array']['info'][] = $info;
            }
            unset($data['infos']);
        }
        try {
            $result = $this->client->updateManufacturer($data);
            \Yii::info('updateManufacturer [OK]:'."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
        }catch(\Exception $ex){
            \Yii::info('updateManufacturer SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
        }

    }

    protected function getLocalBrandId($remoteId)
    {
        $localId = false;

        $getMappedId_r = tep_db_query(
            "SELECT local_id ".
            "FROM ep_holbi_soap_mapping ".
            "WHERE mapping_type='brand' ".
            " AND ep_directory_id ='".intval($this->config['directoryId'])."' ".
            " AND remote_id='".(int)$remoteId."' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMappedId_r)>0 ) {
            $_MappedId = tep_db_fetch_array($getMappedId_r);
            $localId = $_MappedId['local_id'];
        }

        return $localId;
    }

}
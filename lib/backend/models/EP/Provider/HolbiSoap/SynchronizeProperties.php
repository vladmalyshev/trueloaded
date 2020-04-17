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
use yii\helpers\ArrayHelper;

class SynchronizeProperties implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;

    protected $process_records_r;

    protected $config = [];

    protected $remote_properties = [];
    protected $not_processed_linked_ids = [];

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

        // mark removed locally
        tep_db_query(
            "UPDATE ep_holbi_soap_mapping em ".
            " LEFT JOIN ".TABLE_PROPERTIES." p ON p.properties_id=em.local_id ".
            " SET em.local_id=-1 ".
            "WHERE em.ep_directory_id = '".intval($this->config['directoryId'])."' AND em.mapping_type = 'property' ".
            " AND em.local_id>0 ".
            " AND p.properties_id IS NULL "
        );

        // post process fast cleanup - server removed properties
        $this->not_processed_linked_ids = [];
        $getCurrentLinkState_r = tep_db_query(
            "SELECT em.local_id, em.remote_id ".
            "FROM ep_holbi_soap_mapping em ".
            "WHERE em.ep_directory_id = '".intval($this->config['directoryId'])."' AND em.mapping_type = 'property' ".
            " AND em.local_id>0 "
        );
        if ( tep_db_num_rows($getCurrentLinkState_r)>0 ) {
            while( $link = tep_db_fetch_array($getCurrentLinkState_r) ){
                $this->not_processed_linked_ids[$link['remote_id']] = $link['local_id'];
            }
        }

        try {
            $current_page = 0;
            $this->remote_properties = [];
            do{
                $current_page++;
                $params = [
                    'searchConditions' => [
                    ],
                    'paging' => [
                        'page' => $current_page,
                    ]
                ];
                $response = $this->client->getCatalogProperties($params['searchConditions'], $params['paging']);
                if ($response && $response->status != 'ERROR' && isset($response->properties) && isset($response->properties->property)) {
                    if ( is_array($response->properties->property) ) {

                    }elseif(is_object($response->properties->property)){
                        $response->properties->property = [$response->properties->property];
                    }
                    foreach ($response->properties->property as $property) {
                        $property = json_decode(json_encode($property), true);
                        if ( isset($property['descriptions']) && isset($property['descriptions']['description']) ) {
                            $property['descriptions'] = ArrayHelper::isIndexed($property['descriptions']['description'])?$property['descriptions']['description']:[$property['descriptions']['description']];
                            $_extract_description = [];
                            foreach ($property['descriptions'] as $property_description)
                            {
                                $_extract_description[$property_description['language']] = $property_description;
                            }
                            $property['descriptions'] = $_extract_description;
                        }
                        $this->remote_properties[$property['properties_id']] = $property;
                    }
                }
                if ( $response->paging->page>=$response->paging->totalPages ) {
                    break;
                }
            }while(true);
        }catch (\Exception $ex){
            \Yii::info('getCatalogProperties SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
            throw new Exception('Incomplete server properties fetched');
        }
        $this->total_count = count($this->remote_properties);

        reset($this->remote_properties);
    }

    public function processRow(Messages $message)
    {
        $remote_property_data = current($this->remote_properties);
        if ( !$remote_property_data ) return false;

        try {
            $xx = $this->processRemoteProperty($remote_property_data);
        }catch (\Exception $ex){
            throw $ex;
        }

        if ( count($this->remote_properties)==0 ) {
            return false;
        }

        reset($this->remote_properties);
        $this->row_count++;

        return true;
    }

    public function postProcess(Messages $message)
    {

        if ( count($this->not_processed_linked_ids)>0 ) {
            $message->info('Remove properties - '.count($this->not_processed_linked_ids).' server properties has been removed');
            foreach ($this->not_processed_linked_ids as $remoteId=>$localId){
                if ( $localId<=0 ) continue;
                \common\helpers\Properties::remove_property($localId);
            }
        }
    }

    protected function processRemoteProperty($remote_property_data)
    {
        $remote_properties_id = $remote_property_data['properties_id'];
        $remote_parent_id = $remote_property_data['parent_id'];
        unset($remote_property_data['properties_id']);
        unset($remote_property_data['parent_id']);

        $server_last_date = false;
        if ( isset($remote_property_data['date_added']) && $remote_property_data['date_added']>1000 ) {
            $remote_property_data['date_added'] = date('Y-m-d H:i:s', strtotime($remote_property_data['date_added']));
            $server_last_date = $remote_property_data['date_added'];
        }
        if ( isset($remote_property_data['last_modified']) && $remote_property_data['last_modified']>1000 ) {
            $remote_property_data['last_modified'] = date('Y-m-d H:i:s', strtotime($remote_property_data['last_modified']));
            if ( $server_last_date===false || $remote_property_data['last_modified']>$server_last_date ) {
                $server_last_date = $remote_property_data['last_modified'];
            }
        }

        $local_properties_id = $this->getLocalPropertyMappedId($remote_properties_id);

        if ( $local_properties_id===false ) {
            // not mapped yet
            if ( $remote_parent_id==0 ) {
                $local_parent_id = 0;
            }else {
                $local_parent_id = $this->getLocalPropertyMappedId($remote_parent_id);
                if ( $local_parent_id===false && isset($this->remote_properties[$remote_parent_id]) ) {
                    $local_parent_id = $this->processRemoteProperty($this->remote_properties[$remote_parent_id]);

                    if ( !is_numeric($local_parent_id) ) $local_parent_id = -1;
                }
            }
            if ( $local_parent_id==-1 ) {
                tep_db_perform(
                    'ep_holbi_soap_mapping',
                    [
                        'ep_directory_id' => intval($this->config['directoryId']),
                        'mapping_type' => 'property',
                        'remote_id' => $remote_properties_id,
                        'local_id' => $local_properties_id,
                    ]
                );
            }else{
                if (isset($remote_property_data['descriptions'][DEFAULT_LANGUAGE])) {
                    $nameFromLanguage = DEFAULT_LANGUAGE;
                } else {
                    $nameFromLanguage = key($remote_property_data['descriptions']);
                }
                $searchName = $remote_property_data['descriptions'][$nameFromLanguage]['properties_name'];

                // check same name in parent
                $searchSimilar_r = tep_db_query(
                    "SELECT p.properties_id " .
                    "FROM " . TABLE_PROPERTIES . " p " .
                    " INNER JOIN " . TABLE_PROPERTIES_DESCRIPTION . " pd ON pd.properties_id=p.properties_id AND pd.properties_name='" . tep_db_input($searchName) . "' " .
                    "WHERE p.parent_id='" . (int)$local_parent_id . "' ".
                    "LIMIT 1"
                );
                if (tep_db_num_rows($searchSimilar_r) > 0) {
                    $foundSimilar = tep_db_fetch_array($searchSimilar_r);
                    $local_properties_id = $foundSimilar['properties_id'];

                    if ( $this->propertyUpdateAllowed() ) {
                        $localLinkObj = CatalogProperty::findOne(['properties_id' => $local_properties_id]);
                        $local_modify_date = $localLinkObj->date_added;
                        if (!empty($localLinkObj->last_modified) && $localLinkObj->last_modified > 1000) {
                            $local_modify_date = $localLinkObj->last_modified;
                        }
                        if ($server_last_date > $local_modify_date) {
                            // server last updated
                            // {{ don't update properties flag from server
                            unset($remote_property_data['products_groups']);
                            foreach ( array_keys($remote_property_data) as $propKey ){
                                if ( strpos($propKey, 'display_')===0 ) unset($remote_property_data[$propKey]);
                            }
                            // }} don't update properties flag from server
                            $localLinkObj->importArray($remote_property_data);
                            $localLinkObj->save(false);
                        } elseif ($server_last_date < $local_modify_date) {
                            // local property touched last, update server
                            $this->updatePropertyOnServer($remote_properties_id, $localLinkObj);
                        }
                    }
                } else {
                    $remote_property_data['parent_id'] = $local_parent_id;
                    $newProperty = new CatalogProperty();
                    $newProperty->importArray($remote_property_data);
                    $newProperty->save(false);
                    $newProperty->refresh();
                    $local_properties_id = $newProperty->properties_id;
                }

                if ($local_properties_id) {
                    tep_db_perform(
                        'ep_holbi_soap_mapping',
                        [
                            'ep_directory_id' => intval($this->config['directoryId']),
                            'mapping_type' => 'property',
                            'remote_id' => $remote_properties_id,
                            'local_id' => $local_properties_id,
                        ]
                    );
                }
            }
        }elseif( is_numeric($local_properties_id) && $local_properties_id>0 ){
            if ( $this->propertyUpdateAllowed() ) {
                // mapped - update
                $localPropertyObj = CatalogProperty::findOne(['properties_id' => $local_properties_id]);
                if ($localPropertyObj) {
                    $local_modify_date = $localPropertyObj->date_added;
                    if (!empty($localPropertyObj->last_modified) && $localPropertyObj->last_modified > 1000) {
                        $local_modify_date = $localPropertyObj->last_modified;
                    }
                    if ($server_last_date > $local_modify_date) {
                        // server last updated
                        // existing
                        // {{ don't update properties flag from server
                        unset($remote_property_data['products_groups']);
                        foreach ( array_keys($remote_property_data) as $propKey ){
                           if ( strpos($propKey, 'display_')===0 ) unset($remote_property_data[$propKey]);
                        }
                        // }} don't update properties flag from server
                        $localPropertyObj->importArray($remote_property_data);
                        $localPropertyObj->save(false);
                    } elseif ($server_last_date < $local_modify_date) {
                        // local property touched last, update server
                        $this->updatePropertyOnServer($remote_properties_id, $localPropertyObj);
                    }
                } else {
                    $local_properties_id = -1;
                }
            }
        }

        unset($this->remote_properties[$remote_properties_id]);

        if ( isset($this->not_processed_linked_ids[$remote_properties_id]) ) {
            unset($this->not_processed_linked_ids[$remote_properties_id]);
        }

        return $local_properties_id;
    }

    protected function updatePropertyOnServer($remote_properties_id, EPMap $propertyObj)
    {

        $propertyData = $propertyObj->exportArray([]);
        // {{ don't update properties flag on server
        unset($propertyData['products_groups']);
        foreach ( array_keys($propertyData) as $propKey ){
            if ( strpos($propKey, 'display_')===0 ) unset($propertyData[$propKey]);
        }
        // }} don't update properties flag on server
        $propertyData['properties_id'] = $remote_properties_id;
        unset($propertyData['parent_id']);
        unset($propertyData['descriptions']);

        if (!empty($propertyData['date_added'])) {
            $propertyData['date_added'] = (new \DateTime($propertyData['date_added']))->format(DATE_ISO8601);
        }
        if (!empty($propertyData['last_modified'])) {
            $propertyData['last_modified'] = (new \DateTime($propertyData['last_modified']))->format(DATE_ISO8601);
        }

        try {
            $result = $this->client->updateCatalogProperty($propertyData);
            \Yii::info('updateCatalogProperty [OK]:'."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
        }catch(\Exception $ex){
            \Yii::info('updateCatalogProperty SOAP[ERROR]:'.$ex->getMessage()."\n\n".$this->client->__getLastRequest()."\n\n".$this->client->__getLastResponse(), 'datasource');
        }

    }

    protected function getLocalPropertyMappedId($remoteId)
    {
        $localId = false;

        if ( $remoteId==0 ) return 0;

        $getMappedId_r = tep_db_query(
            "SELECT local_id ".
            "FROM ep_holbi_soap_mapping ".
            "WHERE mapping_type='property' ".
            " AND ep_directory_id ='".intval($this->config['directoryId'])."' ".
            " AND remote_id='".(int)$remoteId."' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($getMappedId_r)>0 ) {
            $_MappedId = tep_db_fetch_array($getMappedId_r);
            $localId = $_MappedId['local_id'];

            /*$checkExisting = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS check ".
                "FROM ".TABLE_PROPERTIES." ".
                "WHERE properties_id='".$localId."'"
            ));
            if ( $checkExisting['check']==0 ) {
                $localId = -1;
            }    */
        }

        return $localId;
    }

    protected function propertyUpdateAllowed()
    {
        return false;
    }

}
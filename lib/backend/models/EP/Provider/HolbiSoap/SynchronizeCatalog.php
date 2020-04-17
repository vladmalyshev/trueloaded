<?php
/**
 * Created by PhpStorm.
 * User: sancho
 * Date: 1/14/18
 * Time: 4:35 PM
 */

namespace backend\models\EP\Provider\HolbiSoap;


use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use common\api\models\AR\Categories;
use yii\helpers\ArrayHelper;

class SynchronizeCatalog implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;

    protected $process_records_r;

    protected $config = [];

    protected $remoteTree = false;

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
        }catch (\Exception $ex) {
            throw new Exception('Soap Configuration error');
        }

        try {
            $response = $this->client->getCategories();
            if ( $response && $response->status!='ERROR' ) {
                $this->remoteTree = [];
                if ( isset($response->categories) && !empty($response->categories->category) ) {
                    $categories = ArrayHelper::isIndexed($response->categories->category)?$response->categories->category:[$response->categories->category];
                    foreach ($categories as $category) {
                        $category = json_decode(json_encode($category),true);
                        $descriptions = [];
                        if ( isset($category['descriptions']) && isset($category['descriptions']['description']) ) {
                            $server_descriptions = ArrayHelper::isIndexed($category['descriptions']['description'])?$category['descriptions']['description']:[$category['descriptions']['description']];
                            foreach ($server_descriptions as $server_description) {
                                $descriptions[$server_description['language'].'_0'] = $server_description;
                            }
                        }
                        $category['descriptions'] = $descriptions;

                        if (!empty($category['date_added']) && $category['date_added']>1000) {
                            $category['date_added'] = date('Y-m-d H:i:s', strtotime($category['date_added']));
                        }
                        if (!empty($category['last_modified']) && $category['last_modified']>1000) {
                            $category['last_modified'] = date('Y-m-d H:i:s', strtotime($category['last_modified']));
                        }

                        $this->remoteTree[$category['categories_id']] = $category;
                    }
                }
            }
        }catch (\Exception $ex) {
            throw new Exception('Incomplete server categories fetched');
        }
        $server_categories_ids = array_keys($this->remoteTree);
        $server_categories_ids[] = 0;
        tep_db_query(
            "DELETE FROM ep_holbi_soap_link_categories ".
            "WHERE ep_directory_id='".$this->config['directoryId']."' ".
            "  AND remote_category_id NOT IN(".implode(',',$server_categories_ids).")"
        );

        if ( isset($this->config['products']['create_on_server']) && !!$this->config['products']['create_on_server'] ) {
            // update and create categories
            $this->process_records_r = tep_db_query(
                "SELECT c.categories_id, lc.remote_category_id, " .
                " c.ep_holbi_soap_disable_update, " .
                " IFNULL(c.last_modified, c.date_added) AS local_modify_time, " .
                " lc.server_last_modified, lc.client_processed_last_modified " .
                "FROM " . TABLE_CATEGORIES . " c " .
                " LEFT JOIN ep_holbi_soap_link_categories lc ON lc.ep_directory_id='" . $this->config['directoryId'] . "' AND lc.local_category_id=c.categories_id " .
                "WHERE 1 " .
                "ORDER BY c.categories_left"
            );
        }else{
            // only update categories
            $this->process_records_r = tep_db_query(
                "SELECT c.categories_id, lc.remote_category_id, " .
                " c.ep_holbi_soap_disable_update, " .
                " IFNULL(c.last_modified, c.date_added) AS local_modify_time, " .
                " lc.server_last_modified, lc.client_processed_last_modified " .
                "FROM " . TABLE_CATEGORIES . " c " .
                " INNER JOIN ep_holbi_soap_link_categories lc ON lc.ep_directory_id='" . $this->config['directoryId'] . "' AND lc.local_category_id=c.categories_id " .
                "WHERE 1 " .
                "ORDER BY c.categories_left"
            );
        }

    }

    public function processRow(Messages $message)
    {
        $data = tep_db_fetch_array($this->process_records_r);
        if ( is_array($data) ) {
            if ( empty($data['remote_category_id']) ) {
                $this->createCategoryOnServer($message, $data['categories_id'], $data['local_modify_time']);
                $this->row_count++;
            }else{
                if ( isset($this->remoteTree[$data['remote_category_id']]) ) {
                    $remote_data = $this->remoteTree[$data['remote_category_id']];
                    unset($this->remoteTree[$data['remote_category_id']]);

                    $server_last_modified = (!empty($remote_data['last_modified']) && $remote_data['last_modified']>1000)?$remote_data['last_modified']:$remote_data['date_added'];


                    if (!$data['ep_holbi_soap_disable_update']) {
                        if (!empty($server_last_modified) && $server_last_modified > (string)$data['server_last_modified']) {
                            // category updated on server - update local
                            $this->updateCategoryLocally($message, $data['categories_id'], $remote_data);
                            $this->row_count++;
                        } elseif ($data['local_modify_time'] > $data['client_processed_last_modified']) {
                            // locally updated
                            $this->updateCategoryOnServer($message, $data['categories_id'], $data['remote_category_id']);
                            $this->row_count++;
                        }
                    }
                }else{
                    $this->createCategoryOnServer($message, $data['categories_id'], $data['local_modify_time']);
                    $this->row_count++;
                }

            }
            //$this->row_count++;
        }

        return $data;
    }

    public function postProcess(Messages $message)
    {
        if ( count($this->remoteTree)>0 ) {
            //$message->info('Have '.count($this->remoteTree).' new categories on server');
            foreach ($this->remoteTree as $remote_category_id=>$remote_data) {
                $check_category_removed = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS c ".
                    "FROM ep_holbi_soap_link_categories ".
                    "WHERE ep_directory_id ='".$this->config['directoryId']."' ".
                    " AND remote_category_id='".(int)$remote_category_id."'"
                ));
                if ( $check_category_removed['c']>0 ) {
                    // category imported and removed locally
                    continue;
                }

                $server_last_modified = (!empty($remote_data['last_modified']) && $remote_data['last_modified']>1000)?$remote_data['last_modified']:$remote_data['date_added'];

                unset($remote_data['categories_id']);
                $remote_data['parent_id'] = Helper::getLocalCategoryId($this->config['directoryId'], $remote_data['parent_id']);

                unset($remote_data['last_modified']);

                if (!isset($remote_data['assigned_platforms']) || empty($remote_data['assigned_platforms'])){
                    $remote_data['assigned_platforms'] = [
                        ['platform_id'=>\common\classes\platform::defaultId()],
                    ];
                }

                $remote_data['manual_control_status'] = 0;
                // {{ automatically status
                if ( \common\helpers\Acl::checkExtension('AutomaticallyStatus', 'allowed') && isset($remote_data['categories_status']) ) {
                    $remote_data['AutoStatus'] = $remote_data['categories_status'];
                }
                unset($remote_data['categories_status']);
                // }} automatically status

                $categoryObj = new Categories();
                $categoryObj->importArray($remote_data);
                if ($categoryObj->save() ) {
                    $categoryObj->refresh();

                    $local_category_id = $categoryObj->categories_id;
                    $local_modify_time = $categoryObj->last_modified>1000?$categoryObj->last_modified:$categoryObj->date_added;
                    tep_db_perform('ep_holbi_soap_link_categories', [
                        'ep_directory_id' => $this->config['directoryId'],
                        'remote_category_id' => $remote_category_id,
                        'local_category_id' => $local_category_id,
                        'server_last_modified' => $server_last_modified,
                        'client_processed_last_modified' => $local_modify_time,
                    ]);
                    $this->row_count++;
                }
            }
        }
        $message->info('Processed '.$this->row_count.' records');
    }

    protected function createCategoryOnServer(Messages $message, $categories_id, $local_modify_time)
    {
        $message->info('Create category '.$categories_id);

        $categoryObj = Categories::findOne(['categories_id'=>$categories_id]);
        if ( $categoryObj ) {
            $exportData = $this->makeCategoryData($categoryObj);

            try {
                $response = $this->client->createCategory($exportData);
                //echo "\n\n".$this->client->__getLastRequest()."\n\n";
                if ($response && $response->status == 'ERROR') {
                    if (isset($response->messages) && isset($response->messages->message)) {
                        $messages = json_decode(json_encode($response->messages->message), true);
                        $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                        $messageText = '';
                        foreach ($messages as $messageItem) {
                            $messageText .= "\n" . ' * [' . $messageItem['code'] . '] ' . $messageItem['text'];
                        }
                        $message->info('Error: Category #' . $categories_id . $messageText);
                    }
                }else {
                    $server_last_modified = date('Y-m-d H:i:s', strtotime($response->category->date_added));
                    if ($response->category->last_modified > 1000) {
                        $_last_modified = date('Y-m-d H:i:s', strtotime($response->category->last_modified));
                        if ($_last_modified > $server_last_modified) {
                            $server_last_modified = $_last_modified;
                        }
                    }

                    if (Helper::getRemoteCategoryId($this->config['directoryId'], $categories_id)) {
                        tep_db_query(
                            "DELETE FROM ep_holbi_soap_link_categories " .
                            "WHERE ep_directory_id = '" . (int)$this->config['directoryId'] . "' AND local_category_id ='" . $categories_id . "'"
                        );
                    }
                    tep_db_perform('ep_holbi_soap_link_categories', [
                        'ep_directory_id' => $this->config['directoryId'],
                        'remote_category_id' => $response->category->categories_id,
                        'local_category_id' => $categories_id,
                        'server_last_modified' => $server_last_modified,
                        'client_processed_last_modified' => $local_modify_time,
                    ]);
                }
            }catch (\Exception $ex) {
                $message->info('Error: ' . $ex->getMessage());
                //throw $ex;
            }
        }
    }

    protected function updateCategoryOnServer(Messages $message, $categories_id, $remote_category_id)
    {
        $message->info('Update category '.$categories_id);
        $categoryObj = Categories::findOne(['categories_id'=>$categories_id]);
        if ( $categoryObj ) {
            $exportData = $this->makeCategoryData($categoryObj);

            $exportData['categories_id'] = $remote_category_id;

            try {
                $response = $this->client->updateCategory($exportData);
                if ($response && $response->status == 'ERROR') {
                    if (isset($response->messages) && isset($response->messages->message)) {
                        $messages = json_decode(json_encode($response->messages->message), true);
                        $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                        $messageText = '';
                        foreach ($messages as $messageItem) {
                            $messageText .= "\n" . ' * [' . $messageItem['code'] . '] ' . $messageItem['text'];
                        }
                        $message->info('Error: Category #' . $categories_id . $messageText);
                    }
                }else{
                    $server_last_modified = date('Y-m-d H:i:s', strtotime($response->category->date_added));
                    if ($response->category->last_modified > 1000) {
                        $_last_modified = date('Y-m-d H:i:s', strtotime($response->category->last_modified));
                        if ($_last_modified > $server_last_modified) {
                            $server_last_modified = $_last_modified;
                        }
                    }
                    $local_modify_time = $categoryObj->last_modified>1000?$categoryObj->last_modified:$categoryObj->date_added;
                    tep_db_query(
                        "UPDATE ep_holbi_soap_link_categories ".
                        "SET server_last_modified ='".tep_db_input($server_last_modified)."', client_processed_last_modified ='".tep_db_input($local_modify_time)."' ".
                        "WHERE ep_directory_id = '".$this->config['directoryId']."' ".
                        " AND remote_category_id ='".$remote_category_id."' AND local_category_id = '".$categories_id."'"
                    );
                }
            }catch (\Exception $ex) {
                $message->info('Error: ' . $ex->getMessage());
                //throw $ex;
            }
        }
    }

    protected function makeCategoryData(Categories $categoryObj)
    {
        $exportData = $categoryObj->exportArray([]);

        $exportDescription = [];
        foreach ($exportData['descriptions'] as $key=>$data) {
            list($lang, $tmp) = explode('_',$key);
            if ( (int)$tmp!=0 ) continue;
            $data['language'] = $lang;
            $exportDescription[] = $data;
        }

        $exportData['date_added'] = (new \DateTime($exportData['date_added']))->format(DATE_ISO8601);
        if ( $exportData['last_modified']>1000 ) {
            $exportData['last_modified'] = (new \DateTime($exportData['last_modified']))->format(DATE_ISO8601);
        }

        $exportData['descriptions'] = $exportDescription;
        unset($exportData['categories_id']);

        $exportData['parent_id'] = Helper::getRemoteCategoryId($this->config['directoryId'], $exportData['parent_id']);

        return $exportData;
    }

    protected function updateCategoryLocally(Messages $message, $categories_id, $remote_data)
    {
        $message->info("Update local category ".$categories_id);

        $server_last_modified = (!empty($remote_data['last_modified']) && $remote_data['last_modified']>1000)?$remote_data['last_modified']:$remote_data['date_added'];
        unset($remote_data['last_modified']);

        $remote_category_id = $remote_data['categories_id'];
        unset($remote_data['categories_id']);

        $remote_data['parent_id'] = Helper::getLocalCategoryId($this->config['directoryId'], $remote_data['parent_id']);

        $categoryObj = Categories::findOne(['categories_id'=>$categories_id]);
        if ( !is_object($categoryObj) ) {

            return;
        }

        //{{ same images
        if (!empty($remote_data['categories_image_source_url']) && strval($remote_data['categories_image'])==strval($categoryObj->categories_image) ){
            unset($remote_data['categories_image_source_url']);
        }
        if (!empty($remote_data['categories_image_2_source_url']) && strval($remote_data['categories_image_2'])==strval($categoryObj->categories_image_2) ){
            unset($remote_data['categories_image_2_source_url']);
        }
        //}} same images

        // {{ automatically status
        if ( \common\helpers\Acl::checkExtension('AutomaticallyStatus', 'allowed') && isset($remote_data['categories_status']) ) {
            $remote_data['AutoStatus'] = $remote_data['categories_status'];
        }
        unset($remote_data['categories_status']);
        // }} automatically status

        $categoryObj->importArray($remote_data);
        if ($categoryObj->save() ) {
            $categoryObj->refresh();

            $local_category_id = $categoryObj->categories_id;
            $local_modify_time = $categoryObj->last_modified>1000?$categoryObj->last_modified:$categoryObj->date_added;

            tep_db_query(
                "UPDATE ep_holbi_soap_link_categories ".
                "SET server_last_modified ='".tep_db_input($server_last_modified)."', client_processed_last_modified ='".tep_db_input($local_modify_time)."' ".
                "WHERE ep_directory_id = '".$this->config['directoryId']."' ".
                " AND remote_category_id ='".$remote_category_id."' AND local_category_id = '".$local_category_id."'"
            );
            /*
            tep_db_perform('ep_holbi_soap_link_categories', [
                'ep_directory_id' => $this->config['directoryId'],
                'remote_category_id' => $remote_category_id,
                'local_category_id' => $local_category_id,
                'server_last_modified' => $server_last_modified,
                'client_processed_last_modified' => $local_modify_time,
            ]);
            */
        }
    }

}
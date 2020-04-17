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
use common\api\models\AR\Customer;
use yii\helpers\ArrayHelper;

class SynchronizeCustomers implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;

    protected $process_records_r;

    protected $config = [];

    /**
     * @var \SoapClient
     */
    protected $client;

    private $processTarget = 'remote';
    private $remoteRequestData = false;
    private $remoteMaxModifyTime = false;
    private $localPage = -1;
    private $processPageArray = false;

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

        tep_db_query(
            "DELETE cl FROM ep_holbi_soap_link_customers cl ".
            "LEFT JOIN ".TABLE_CUSTOMERS." c ON cl.local_customers_id=c.customers_id ".
            "WHERE cl.ep_directory_id='".$this->config['directoryId']."' ".
            " AND c.customers_id IS NULL "
        );

        $checkServerFromDate = Helper::getKeyValue(
            $this->config['directoryId'],
            'SynchronizeCustomers/remoteMaxModifyTime'
        );

        if ( $checkServerFromDate ) {
            $this->remoteRequestData = [
                [
                    'searchCondition' => [
                        [
                            'operator' => '>',
                            'column' => 'time_modified',
                            'values' => [[ (new \DateTime($checkServerFromDate))->format(DATE_ISO8601) ]]
                        ]
                    ],
                ],
                [
                    'page' => 0,
                    //'perPage' => 50,
                ]
            ];
        }else{
            $this->remoteRequestData = [
                [

                ],
                [
                    'page' => 0,
                    //'perPage' => 50,
                ]
            ];
            //$this->processTarget = 'local';
        }

        if ( isset($this->config['customer']['ab_sync_client']) && $this->config['customer']['ab_sync_client']=='disable' ) {
            $this->processTarget = 'local';
        }

        Helper::updateCustomerModifyTime();

        if ($this->processTarget == 'local' && isset($this->config['customer']['ab_sync_server']) && $this->config['customer']['ab_sync_server']=='disable' ){
            throw new Exception('Customer synchronization disabled in setting');
        }

        $this->getProcessPage($message);
    }

    public function processRow(Messages $message)
    {
        if ( !is_array($this->processPageArray) ) return false;

        if ( count($this->processPageArray)==0 ) {
            $this->getProcessPage($message);
        }
        if ( !is_array($this->processPageArray) || count($this->processPageArray)==0 ) {
            return false;
        }

        $data = array_shift($this->processPageArray);
        if ( $this->processTarget=='local' ) {
            try {
                $this->processCustomer($message, $data['customers_id']);
                $this->row_count++;
            } catch (\Exception $ex) {
                $message->info('Error: Customer #' . $data['customers_id'] . ' - "' . $ex->getMessage() . '""');
            }
        }else{
            $this->updateLocalCustomer($message, $data);
        }

        return $data;
    }

    private function getProcessPage(Messages $message)
    {

        $this->processPageArray = false;
        if ($this->processTarget == 'remote' || $this->processTarget == 'remote_close_period'){
            $this->remoteRequestData[1]['page']++;

            try {
                $response = $this->client->searchCustomers($this->remoteRequestData[0], $this->remoteRequestData[1]);
            }catch (\Exception $ex){
                \Yii::error('searchCustomers fail: '."\n\n".$this->client->__getLastRequest(),'datasource');
                throw $ex;
            }
            if ($response && $response->status != 'ERROR')
            {
                if ($this->processTarget == 'remote' && $this->remoteRequestData[1]['page']==1) {
                    $message->info("Found ".$response->paging->totalRows." modified customer(s) on server");
                }

                if ( isset($response->customers) && isset($response->customers->customer) ) {
                    $this->processPageArray = (isset($response->customers->customer) && is_object($response->customers->customer)) ? [$response->customers->customer] : $response->customers->customer;
                    foreach ( $this->processPageArray as $customer ) {
                        $_modify_time = date('Y-m-d H:i:s', strtotime($customer->modify_time));
                        if ( $this->remoteMaxModifyTime===false ){
                            $this->remoteMaxModifyTime = $_modify_time;
                        }elseif($_modify_time>$this->remoteMaxModifyTime){
                            $this->remoteMaxModifyTime = $_modify_time;
                        }
                    }
                }

                if ( $this->processPageArray===false ) {
                    if ( $this->processTarget == 'remote' && $this->remoteMaxModifyTime!==false ) {
                        $this->processTarget = 'remote_close_period';
                        $this->remoteRequestData = [
                            [
                                'searchCondition' => [
                                    [
                                        'operator' => '=',
                                        'column' => 'time_modified',
                                        'values' => [[ (new \DateTime($this->remoteMaxModifyTime))->format(DATE_ISO8601) ]]
                                    ]
                                ],
                            ],
                            [
                                'page' => 0,
                                //'perPage' => 50,
                            ]
                        ];

                        $this->getProcessPage($message);
                    }elseif ($this->processTarget == 'remote_close_period' || ($this->processTarget == 'remote' && $this->remoteMaxModifyTime===false) ) {
                        $this->processTarget = 'local'; // finally process local to server
                    }
                }else{
                    return;
                }
            }else {
                return;
            }
        }
        if ($this->processTarget == 'local'){
            if (isset($this->config['customer']['ab_sync_server']) && $this->config['customer']['ab_sync_server']=='disable' ){
                return;
            }
            $this->localPage++;
            $customers_sql =
                "SELECT SQL_CALC_FOUND_ROWS c.customers_id " .
                "FROM " . TABLE_CUSTOMERS . " c " .
                " LEFT JOIN ep_holbi_soap_link_customers cl ON cl.ep_directory_id='" . $this->config['directoryId'] . "' AND cl.local_customers_id=c.customers_id " .
                "WHERE 1 " .
                " AND (cl.local_time_processed IS NULL OR cl.local_time_processed!=c._api_time_modified) " .
                "ORDER BY c.customers_id ".
                "LIMIT ".($this->localPage*200).", 200";

            $this->process_records_r = tep_db_query($customers_sql);
            $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
            if ( $this->localPage==0 ) {
                $this->total_count += $getRows['rows_count'];
                $message->info("Found ".$getRows['rows_count']." locally modified customer(s)");
            }
            if ( tep_db_num_rows($this->process_records_r)>0 ) {
                while ( $data = tep_db_fetch_array($this->process_records_r) ) {
                    $this->processPageArray[] = $data;
                }
            }else{
                return;
            }
        }
    }

    public function postProcess(Messages $message)
    {
        if ($this->remoteMaxModifyTime!==false) {
            Helper::setKeyValue(
                $this->config['directoryId'],
                'SynchronizeCustomers/remoteMaxModifyTime',
                $this->remoteMaxModifyTime
            );
        }
        $message->info('Processed '.$this->row_count.' records');
    }

    protected function processCustomer(Messages $message, $customers_id)
    {

        $customer = Customer::findOne(['customers_id'=>$customers_id]);

        if ( !$customer ) {
            throw new Exception("Customer load error");
        }
        if( empty($customer->customers_email_address) ) {
            throw new Exception("Customer email address empty");
        }

        $localCustomerId = $customer->customers_id;

        $localCustomer = $customer->exportArray([]);
        $allCountriesValid = true;
        foreach ($localCustomer['addresses'] as $localAB) {
            if ( empty($localAB['entry_country_iso2']) ) {
                $allCountriesValid = false;
                break;
            }
        }
        if ( !$allCountriesValid ) {
            throw new Exception("Customer export error - address with invalid country");
        }

        $remoteCustomerId = 0;

        $customerDataArray = Helper::makeCustomerRequestData($localCustomer);

        $serverCustomerModifyTime = false;
        $localCustomerModifyTime = $customer->_api_time_modified;
        try{
            $searchResult = $this->client->searchCustomer([
                'customers_email_address' => $customer->customers_email_address,
            ]);
            if ($searchResult && $searchResult->customers && $searchResult->customers->customer) {
                $remoteCustomerId = $searchResult->customers->customer->customers_id;

                if (isset($this->config['customer']['ab_sync_server']) && $this->config['customer']['ab_sync_server']=='append' && $customerDataArray['addresses'] && $customerDataArray['addresses']['address'] && count($customerDataArray['addresses']['address'])>0) {
                    $customerDataArray['addresses']['append'] = true;
                }
                $response = $this->client->updateCustomer($customerDataArray);
            } else {
                $response = $this->client->createCustomer($customerDataArray);
            }
            if ($response && $response->customer && $response->customer->customers_id) {
                $remoteCustomerId = $response->customer->customers_id;
                if ( $response->customer->modify_time ) {
                    $serverCustomerModifyTime = date('Y-m-d H:i:s', strtotime($response->customer->modify_time));
                }
            }
            if ( $response && $response->status=='ERROR' ) {
                if ( isset($response->messages) && isset($response->messages->message) ) {
                    $messages = json_decode(json_encode($response->messages->message), true);
                    $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                    $messageText = '';
                    foreach ($messages as $messageItem) {
                        $messageText .= "\n".' * ['.$messageItem['code'].'] '.$messageItem['text'];
                    }
                    $message->info('Error: Customer #'.$localCustomerId.$messageText);

                    $clientTalk = "\nRequest:\n".$this->client->__getLastRequest()."\nResponse:\n".$this->client->__getLastResponse()."\n\n";
                    \Yii::error('Server error:'.$messageText.$clientTalk,'datasource');
                }
            }

        }catch (\Exception $ex){
            $clientTalk = "\nRequest:\n".$this->client->__getLastRequest()."\nResponse:\n".$this->client->__getLastResponse()."\n\n";
            \Yii::error('Exception:'.$ex->getTraceAsString().$clientTalk,'datasource');
            throw $ex;
        }

        if ( $remoteCustomerId ) {
            tep_db_query(
                "DELETE FROM ep_holbi_soap_link_customers ".
                "WHERE ep_directory_id = '".$this->config['directoryId']."' ".
                " AND local_customers_id = '".(int)$localCustomerId."' "
            );
            $link_data = [
                'ep_directory_id' => $this->config['directoryId'],
                'local_customers_id' => (int)$localCustomerId,
                'remote_customers_id' => $remoteCustomerId,
                'local_time_processed' => $localCustomerModifyTime,
            ];
            if ( $serverCustomerModifyTime ) {
                $link_data['server_modify_time'] = $serverCustomerModifyTime;
            }
            tep_db_perform('ep_holbi_soap_link_customers',$link_data);
        }
        return $remoteCustomerId;

    }

    protected function updateLocalCustomer(Messages $message, $customerData)
    {
        //$message->info('Remote customer '.$customerData->customers_email_address);
        $customerData = json_decode(json_encode($customerData), true);

        unset($customerData['platform_name']);

        $addresses = isset($customerData['addresses']['address'])?$customerData['addresses']['address']:[];
        if ( count($addresses)>0 && !ArrayHelper::isIndexed($addresses) ) {
            $addresses = [$addresses];
        }
        foreach( array_keys($addresses) as $idx ) {
            unset($addresses[$idx]['address_book_id']);
        }
        $customerData['addresses'] = $addresses;

        $remote_customer_id = $customerData['customers_id'];
        unset($customerData['customers_id']);

        $local_customer_id = 0;
        $get_linked_local_customer_r = tep_db_query(
            "SELECT lc.local_customers_id, c._api_time_modified AS customer_modified_at, lc.local_time_processed ".
            "FROM ep_holbi_soap_link_customers lc ".
            " INNER JOIN ".TABLE_CUSTOMERS." c ON lc.local_customers_id=c.customers_id ".
            "WHERE lc.ep_directory_id='".$this->config['directoryId']."' AND lc.remote_customers_id='".$remote_customer_id."' ".
            "LIMIT 1"
        );
        if ( tep_db_num_rows($get_linked_local_customer_r)>0 ) {
            $local_customer_id_arr = tep_db_fetch_array($get_linked_local_customer_r);
            $local_customer_id = $local_customer_id_arr['local_customers_id'];
        }
        if ( !$local_customer_id ) {
            $get_local_by_email_r = tep_db_query(
                "SELECT customers_id ".
                "FROM ".TABLE_CUSTOMERS." ".
                "WHERE customers_email_address='".tep_db_input($customerData->customers_email_address)."' ".
                "LIMIT 1"
            );
            if ( tep_db_num_rows($get_local_by_email_r)>0 ) {
                $get_local_by_email = tep_db_fetch_array($get_local_by_email_r);
                $local_customer_id = $get_local_by_email['customers_id'];
            }
        }


        $create_local = false;
        $customerObj = Customer::findOne(['customers_id'=>$local_customer_id]);
        if ( !is_object($customerObj) || empty($customerObj->customers_id) ) {
            $customerObj = new Customer();
            $create_local = true;
        }
        if ( isset($this->config['customer']['ab_sync_client']) && $this->config['customer']['ab_sync_client']=='append' ) {
            $customerObj->indexedCollectionAppendMode('addresses', true);
        }

        $customerObj->importArray($customerData);
        $customerObj->save();
        if ( $customerObj->customers_id ) {
            Helper::updateCustomerModifyTime($customerObj->customers_id);
            $customerObj->refresh();

            if ($create_local) {
                tep_db_perform('ep_holbi_soap_link_customers', [
                    'ep_directory_id' => $this->config['directoryId'],
                    'remote_customers_id' => $remote_customer_id,
                    'local_customers_id' => $customerObj->customers_id,
                    'local_time_processed' => $customerObj->_api_time_modified,
                ]);
            } else {
                //customer_modified_at, local_time_processed
            }
        }
    }

}
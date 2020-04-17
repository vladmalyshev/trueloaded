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
use backend\models\EP\JobDatasource;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;

class ExportToSap implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_orders_r;

    protected $config = [];

    private $allow_update_order = true;

    /**
     * @var \SoapClient
     */
    protected $client;

    protected $send_order_ids;
    protected $limitOrderIds = [];

    function __construct($config)
    {
        $this->config = $config;
        if ( isset($this->config['order']['disable_order_update']) && $this->config['order']['disable_order_update']==1 ) {
            $this->allow_update_order = false;
        }
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
        if ( !isset($this->config['job_configure']) || empty($this->config['job_configure']['forceProcessOrders']) ) {
            throw new Exception('Orders not selected');
        }
        $this->limitOrderIds = $this->config['job_configure']['forceProcessOrders'];

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

        $keep_output = $message->output;
        $message->output = 'null';

        $message->info('* Update Orders on server');
        $updateOrdersOnServer = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'HolbiSoap\\ExportOrders',
        ]);

        $updateOrdersOnServer->job_configure['forceProcessOrders'] = $this->limitOrderIds;
        $updateOrdersOnServer->run($message);

        $message->info('* Track Changed Orders on server');
        $updateOrdersOnServer = new JobDatasource([
            'directory_id' => $this->config['directoryId'],
            'direction' => 'datasource',
            'job_provider' => 'HolbiSoap\\TrackOrders',
        ]);

        $updateOrdersOnServer->job_configure['forceProcessOrders'] = $this->limitOrderIds;
        $updateOrdersOnServer->run($message);
        $message->output = $keep_output;

        $message->info('* Process orders to SAP');
        $this->process_orders_r = tep_db_query(
            "SELECT o.orders_id, lo.remote_orders_id ".
            "FROM ".TABLE_ORDERS." o ".
            " INNER JOIN ep_holbi_soap_link_orders lo ON lo.local_orders_id=o.orders_id AND lo.ep_directory_id='".(int)$this->config['directoryId']."' ".
            "WHERE 1 ".
            " AND lo.remote_orders_id IS NOT NULL ".
            (is_array($this->limitOrderIds) && count($this->limitOrderIds)>0?" AND o.orders_id IN ('".implode("','",$this->limitOrderIds)."') ":'').
            "GROUP BY o.orders_id ".
            "ORDER BY o.orders_id "
        );
        $this->total_count = tep_db_num_rows($this->process_orders_r);
        $message->info("Found ".$this->total_count." orders");
        $this->send_order_ids = [];
        if (tep_db_num_rows($this->process_orders_r)>0) {
            while($send_order_id = tep_db_fetch_array($this->process_orders_r) ) {
                $this->send_order_ids[$send_order_id['orders_id']] = $send_order_id['remote_orders_id'];
            }
        }
    }

    public function processRow(Messages $message)
    {
        try {
            $response = $this->client->sendOrdersToSAP($this->send_order_ids);
            if( isset($response->messages) && isset($response->messages->message) ) {
                $response_messages = $response->messages->message;
                if (!is_array($response_messages)) $response_messages = [$response_messages];
                foreach ($response_messages as $response_message) {
                    $message->info("{$response_message->code}: {$response_message->text}");
                }
            }
            if( isset($response->export_messages) && !empty($response->export_messages) ) {
                $response_messages = $response->export_messages;
                if (!is_array($response_messages)) $response_messages = [$response_messages];
                foreach ($response_messages as $response_message) {
                    $message->info("{$response_message}");
                }
            }
        }catch (\Exception $ex){
            \Yii::error("sendOrdersToSAP Exception: ".$ex->getMessage()."\nREQ:\n".$this->client->__getLastRequest()."\n\n"."RES:\n".$this->client->__getLastResponse()."\n\n",'datasource');
            $message->info('sendOrdersToSAP error: '.$ex->getMessage());
        }
        return false;
    }

    public function postProcess(Messages $message)
    {
        $keep_output = $message->output;
        $message->output = 'null';
        try {
            $message->info('* Track Changed Orders on server');
            $updateOrdersOnServer = new JobDatasource([
                'directory_id' => $this->config['directoryId'],
                'direction' => 'datasource',
                'job_provider' => 'HolbiSoap\\TrackOrders',
            ]);

            $updateOrdersOnServer->job_configure['forceProcessOrders'] = $this->limitOrderIds;
            $updateOrdersOnServer->run($message);
        }catch (\Exception $ex){

        }
        $message->output = $keep_output;
    }

}
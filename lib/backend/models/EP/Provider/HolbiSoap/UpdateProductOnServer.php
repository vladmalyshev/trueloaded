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
use yii\helpers\ArrayHelper;

class UpdateProductOnServer implements DatasourceInterface
{

    protected $total_count = 0;
    protected $row_count = 0;

    protected $process_records_r;

    protected $config = [];

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

        $this->process_records_r  = tep_db_query(
            "SELECT p.products_id, lc.remote_products_id ".
            "FROM ".TABLE_PRODUCTS." p ".
            " LEFT JOIN ep_holbi_soap_link_products lc ON lc.ep_directory_id='".$this->config['directoryId']."' AND lc.local_products_id=p.products_id ".
            "WHERE 1 ".
            " AND lc.remote_products_id IS NOT NULL ".
            "ORDER BY p.products_id "
        );
    }

    public function processRow(Messages $message)
    {
        $data = tep_db_fetch_array($this->process_records_r);
        if ( is_array($data) ) {

            $this->updateProductOnServer($message, $data);

            $this->row_count++;
        }
        return $data;
    }

    public function postProcess(Messages $message)
    {
        $message->info('Processed '.$this->row_count.' records');
    }

    private function updateProductOnServer(Messages $message, $data)
    {
        $product = \common\api\models\AR\Products::findOne(['products_id'=>$data['products_id']]);
        $exportArray = $product->exportArray([]);

        $childCollection = array_flip($product->getChildCollectionNames());

        $productData = [];
        foreach( $exportArray as $key=>$value ) {
            if ( isset($childCollection[$key]) ) continue;
            $productData[$key] = $value;
        }
        $productData['products_id'] = $data['remote_products_id'];
        if ( isset($exportArray['descriptions']) ){
            $productData['descriptions'] = [];
            if ( is_array($exportArray['descriptions']) ) {
                foreach ($exportArray['descriptions'] as $cKey=>$cData) {
                    list( $langCode, $_tmp ) = explode('_',$cKey,2);
                    if ( (int)$_tmp!=0 ) continue;
                    $cData['language'] = $langCode;

                    $productData['descriptions'][] = $cData;
                }
            }
        }
        $dimensions = [];;
        foreach ( preg_grep('/_cm$/', array_keys($exportArray)) as $dimKey ) {
            $dimensions[$dimKey] = $exportArray[$dimKey];
        }
        if (count($dimensions)>0){
            $productData['dimensions'] = $dimensions;
        }
        $productData['prices'] = [
            //'products_id' => $productData['products_id'],
            'price_info' => \backend\models\EP\Provider\HolbiSoap\Helper::makeExportProductPrices($product->products_id),
        ];
        $productData['stock_info'] = null;
        $productData['assigned_categories'] = null;
        $productData['attributes'] = null;
        if ( isset($exportArray['attributes']) && is_array($exportArray['attributes']) && count($exportArray['attributes'])>0 ) {
            $productData['attributes']['attribute'] = [];
            foreach ($exportArray['attributes'] as $attribute){
                $exportAttribute = [
                    'options_name' => $attribute['options_name'],
                    'options_values_name' => $attribute['options_values_name'],
                    'products_options_sort_order' => $attribute['products_options_sort_order'],
                ];
                $options_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionId( $this->config['directoryId'], $attribute['options_id']);
                if ( $options_id!==false ) {
                    $exportAttribute['options_id'] = $options_id;
                    $options_values_id = \backend\models\EP\Provider\HolbiSoap\Helper::lookupRemoteOptionValueId( $this->config['directoryId'], $attribute['options_id'], $attribute['options_values_id']);
                    if ( $options_values_id!==false ) {
                        $exportAttribute['options_values_id'] = $options_values_id;
                    }
                }

                $productData['attributes']['attribute'][] = $exportAttribute;
            }
        }
        $productData['images'] = null;
        $productData['properties'] = null;
        $productData['xsells'] = null;
        $productData['documents'] = null;

        if ( isset($productData['products_date_added']) && $productData['products_date_added']>1000 ) {
            $productData['products_date_added'] = (new \DateTime($productData['products_date_added']))->format(DATE_ISO8601);
        }
        if ( isset($productData['products_last_modified']) && $productData['products_last_modified']>1000 ) {
            $productData['products_last_modified'] = (new \DateTime($productData['products_last_modified']))->format(DATE_ISO8601);
        }

        try{
            $response = $this->client->updateProduct($productData);
            if ( $response && $response->status=='ERROR' ) {
                $messageText = '';
                if (isset($response->messages) && isset($response->messages->message)) {
                    $messages = json_decode(json_encode($response->messages->message), true);
                    $messages = ArrayHelper::isIndexed($messages) ? $messages : [$messages];
                    $messageText = '';
                    foreach ($messages as $messageItem) {
                        $messageText .= "\n" . ' * [' . $messageItem['code'] . '] ' . $messageItem['text'];
                    }
                }
                $message->info("Update product #{$data['products_id']} error ".$messageText);
            }
        }catch (\Exception $ex){
            $message->info("Update product #{$data['products_id']} exception: ".$ex->getMessage());
        }
    }


}
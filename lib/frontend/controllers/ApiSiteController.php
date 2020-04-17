<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;


use backend\models\EP\Job;
use backend\models\EP\JobFile;
use backend\models\EP\Messages;
use common\api\models\Customer\CustomerSearch;
use common\api\models\Products\InventoryRef;
use common\api\models\Products\ProductRef;
use Yii;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Site controller
 */
class ApiSiteController extends Sceleton
{
    public $enableCsrfValidation = false;
    public $authenticated = false;

    public function actionIndex()
    {
        $this->layout = false;

    }

    public function actionV1()
    {
        $this->layout = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = [];

        $apiAction = Yii::$app->request->get('api_action','');
        $apiAction = preg_replace_callback('/[-_](.)/',function($match){ return strtoupper($match[1]); }, $apiAction);

        $apiAuth = Yii::$app->request->getHeaders()->get('api-auth','');
        if ( $apiAuth!='18ce3fdfa7065eeb84d3af22ee874ff5' ) {
            throw new ForbiddenHttpException("Unknown Api auth");
        }

        $actionHandler = new \common\api\v1\Action();
        if ( empty($apiAction) || !method_exists($actionHandler, $apiAction) ) {
            throw new BadRequestHttpException("Unknown Api action \"{$apiAction}\"");
        }
        Yii::$app->response->data = [
            'response' => call_user_func_array([$actionHandler,$apiAction],[]),
        ];
    }

    public function actionEasyPopulate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = [];

        $key = Yii::$app->request->get('key','');
        if ( $key!='18ce3fdfa7065eeb84d3af22ee874ff5' ){
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->data = [
                'error' => 'Auth key not valid'
            ];
            return;
        }
        $action = Yii::$app->request->get('action','run');

        switch ($action){
            case 'run':
                $job_id = Yii::$app->request->get('job_id',false);
                if ( is_numeric($job_id) ){
                    $job = Job::loadById($job_id);
                }else{
                    $export_provider = tep_db_prepare_input(Yii::$app->request->get('export_provider'));
                    $format = tep_db_prepare_input(Yii::$app->request->get('format','CSV'));
                    $selected_columns = tep_db_prepare_input(Yii::$app->request->get( 'selected_fields', '' ));
                    if ( !empty($selected_columns) ) {
                        $selected_columns = explode(',',$selected_columns);
                    }else{
                        $selected_columns = false;
                    }

                    $filter = tep_db_prepare_input(Yii::$app->request->get('filter'));
                    if ( !is_array($filter) ) $filter = [];
                    if ( !empty($filter['order']['date_from']) ) {
                        $value_time = date_create_from_format(DATE_FORMAT_DATEPICKER_PHP, $filter['order']['date_from']);
                        $filter['order']['date_from'] = '';
                        if ( $value_time ) {
                            $filter['order']['date_from'] = $value_time->format('Y-m-d');
                        }
                    }
                    if ( !empty($filter['order']['date_to']) ) {
                        $value_time = date_create_from_format(DATE_FORMAT_DATEPICKER_PHP, $filter['order']['date_to']);
                        $filter['order']['date_to'] = '';
                        if ( $value_time ) {
                            $filter['order']['date_to'] = $value_time->format('Y-m-d');
                        }
                    }
                    if ( empty($export_provider) ) {

                    }
                    $messages = new Messages();
                    $job = new JobFile();
                    //$exportJob->directory_id = $this->currentDirectory->directory_id;
                    $job->direction = 'export';
                    $job->file_name = 'php://output';
                    $job->job_provider = $export_provider;
                    $job->job_configure['export'] = [
                        'columns' => $selected_columns,
                        'filter' => $filter,
                        'format' => $format,
                    ];

                }

                if ( !is_object($job) ){
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    Yii::$app->response->data = [
                        'error' => 'Job not found'
                    ];
                    return;
                }

                Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;

                if ($job->job_configure['export']['format']=='ZIP'){
                    $mime_type = 'application/zip';
                }elseif ($job->job_configure['export']['format']=='CSV'){
                    $mime_type = 'application/vnd.ms-excel';
                }else {
                    $mime_type = FileHelper::getMimeTypeByExtension($job->file_name);
                    if ($mime_type == 'text/plain') {
                        $mime_type = 'application/vnd.ms-excel';
                    }
                }
                $messages = new Messages([
                    'job_id' => $job->job_id,
                    'output' => 'none',
                ]);
                $job->file_name = 'php://output';
                header('Content-Type: ' . $mime_type);
                header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');

                if (preg_match('@MSIE ([0-9].[0-9]{1,2})@', $_SERVER['HTTP_USER_AGENT'], $log_version)) {
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                } else {
                    header('Pragma: no-cache');
                }

                try {
                    $job->run($messages);
                }catch(\Exception $ex){
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    Yii::$app->response->data = [
                        'error' => $ex->getMessage(),
                    ];
                    return;
                }
                break;
        }
    }


    /**
     * @header
     * @return \common\api\models\GetProductListResponse
     * @throws \SoapFault
     * @soap
     */
    public function getProductList()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetProductListResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     *
     * @header
     * @param int Catalog Product Id
     * @return \common\api\models\GetProductResponse
     * @throws \SoapFault
     * @soap
     */
    public function getProduct($productId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetProductResponse();
            $response->setProductId($productId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get store currencies
     * @header
     * @return \common\api\models\GetCurrenciesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getCurrencies()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetCurrenciesResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get store payment
     * @header
     * @return \common\api\models\GetAvailablePaymentsResponse
     * @throws \SoapFault
     * @soap
     */
    public function getAvailablePayments()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetAvailablePaymentsResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get store xsell custom types
     * @header
     * @return \common\api\models\GetXsellTypesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getXsellTypes()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetXsellTypesResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get stock
     * @header
     * @param  \common\api\models\Products\InventoryRef
     * @return \common\api\models\GetStockResponse
     * @throws \SoapFault
     * @soap
     */
    public function getStock(InventoryRef $searchCondition)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetStockResponse();
            $response->setSearchCondition($searchCondition);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

//    /**
//     * Get price
//     * @header
//     * @param  \common\api\models\Products\ProductRef
//     * @return \common\api\models\GetStockResponse
//     * @throws \SoapFault
//     * @soap
//     */
//    public function getPrice(ProductRef $searchCondition)
//    {
//        if ( $this->authenticated ) {
//            $response = new \common\api\models\GetStockResponse();
//            $response->setSearchCondition($searchCondition);
//            $response->build();
//            return $response;
//        }else{
//            throw new \SoapFault('403','Wrong api key');
//        }
//    }

    /**
     * Search Customer
     * @header
     * @param  \common\api\models\Customer\CustomerSearch
     * @return \common\api\models\GetCustomerResponse
     * @throws \SoapFault
     * @soap
     */
    public function searchCustomer(CustomerSearch $searchCondition)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetCustomerResponse();
            $response->setSearchCondition($searchCondition);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    public function Security()
    {
        return false;
        //echo '<pre>'; var_dump(func_get_args()); echo '</pre>'; die;
    }

    /**
     * @param string $api_key
     * @return boolean result of auth
     * @soap
     */
    public function auth($api_key)
    {
        if ( defined('API_SITE_API_KEY') && API_SITE_API_KEY!='' && $api_key==API_SITE_API_KEY ){
            $this->authenticated = true;
        }
        return true;
    }

    public function actions()
    {
        return [
            'service' => [
                'class' => 'subdee\soapserver\SoapAction',
                'classMap'=>array(
                    'ProductListResponse' => '\\common\\api\\models\\GetProductListResponse',
                    'GetCurrenciesResponse' => '\\common\\api\\models\\GetCurrenciesResponse',
                    'ProductRef' => '\\common\\api\\models\\Products\\ProductRef',
                    'InventoryRef' => '\\common\\api\\models\\Products\\InventoryRef',
                    'CustomerSearch' => '\\common\\api\\models\\Customer\\CustomerSearch',

                ),
            ],
        ];
    }

    protected function setMeta($id, $params)
    {

    }

}

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
use Yii;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Site controller
 */
class ApiController extends Sceleton
{
    public function actionIndex()
    {
        $this->layout = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = [];

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
                        $value_time = date_create_from_format(DATE_FORMAT_DATEPICKER_PHP, \common\helpers\Date::checkInputDate($filter['order']['date_from']));
                        $filter['order']['date_from'] = '';
                        if ( $value_time ) {
                            $filter['order']['date_from'] = $value_time->format('Y-m-d');
                        }
                    }
                    if ( !empty($filter['order']['date_to']) ) {
                        $value_time = date_create_from_format(DATE_FORMAT_DATEPICKER_PHP, \common\helpers\Date::checkInputDate($filter['order']['date_to']));
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
}

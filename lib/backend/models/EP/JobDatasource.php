<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP;


use backend\models\EP\Provider\DatasourceInterface;

class JobDatasource extends Job
{
    public function canConfigureExport()
    {
        return false;
    }

    public function canConfigureImport()
    {
        return false;
    }

    public function canRun()
    {
        $this->checkIdle();
        if ($this->job_state == self::PROCESS_STATE_CONFIGURED || $this->job_state == self::PROCESS_STATE_IDLE){
            return true;
        }
        return false;
    }
    
    public function checkIdle(){
        
        $idle = tep_db_fetch_array(tep_db_query(
            "select (MINUTE(TIMEDIFF(last_cron_run, now()))) as minutes from ".TABLE_EP_JOB." ".
            "WHERE job_id='".intval($this->job_id)."' and job_state='in_progress'"
        ));
       
        if ($idle['minutes'] > 10){
            $this->job_state = self::PROCESS_STATE_IDLE;
        }
        return;
    }

    public function canRunInBrowser()
    {
        $can = false;
        try {
            $job = $this->getJobInstance();
            if ( method_exists($job,'allowRunInPopup') ) {
                $can = $job->allowRunInPopup();
            }
        }catch (\Exception $ex){}
        return $can;
    }

    public function runASAP()
    {
        $this->run_frequency = 1;
        tep_db_query(
            "UPDATE ".TABLE_EP_JOB." ".
            "SET run_frequency=1 ".
            "WHERE job_id='".intval($this->job_id)."'"
        );
    }

    protected function getJobInstance()
    {
        $directory = Directory::loadById($this->directory_id);
        if ( !is_object($directory) ) {
            throw new Exception('Not found job directory.');
        }
        $datasource = DataSources::getByName($directory->directory);
        if ( !is_object($datasource) ) {
            throw new Exception('Not found job datasource object.');
        }

        $datasourceProviderConfig = $datasource->getJobConfig();
        $datasourceProviderConfig['workingDirectory'] = $directory->filesRoot();
        $datasourceProviderConfig['directoryId'] = $this->directory_id;
        if ( is_array($this->job_configure) && !empty($this->job_configure) ) {
            $datasourceProviderConfig['job_configure'] = $this->job_configure;
        }

        $providers = new Providers();

        return $providers->getProviderInstance($this->job_provider, $datasourceProviderConfig);

    }

    public function run(Messages $messages)
    {
        try {
            $providerObj = $this->getJobInstance();
            if (property_exists($providerObj, 'job_id')){
                $providerObj->job_id = $this->job_id;
            }
        }catch (Exception $ex){
            $messages->info($ex->getMessage().' Exit job.');
        }

        $messages->command('start');

        try{

            if ( $providerObj instanceof DatasourceInterface ) {

                $messages->progress(0);

                $started = time();
                $idlePing = $started;
                $rowCounter = 0;
                $progressRowInform = 100;
                $lastInfoSayTime = $started;
                $lastProgress = 0;
                set_time_limit(0);

                $providerObj->prepareProcess($messages);

                while ($providerObj->processRow( $messages)) {
                    echo '.';

                    $rowCounter++;
                    $currentTime = time();
                    $percentProgress = $providerObj->getProgress();
                    if ( ((int)$percentProgress-$lastProgress)>1 || ($rowCounter % $progressRowInform)==0 || ($currentTime-$lastInfoSayTime)>60 ) {
                        $lastProgress = (int)$percentProgress;
                        if ( $percentProgress==0 ) {
                            $secondsForJob = round(($currentTime - $started) * 100 / 0.0001);
                        }else{
                            $secondsForJob = round(($currentTime - $started) * 100 / $percentProgress);
                        }
                        $timeLeft = 'Time left: '.gmdate('H:i:s',max(0,$secondsForJob - ($currentTime-$started)) );
                        if ( $currentTime!=$started ) {
                            $timeLeft .= ' ' . number_format($rowCounter / ($currentTime - $started), 1, '.', '') . ' Lines per second';
                        }
                        if ( $this->isAlive()===false ) {
                            // job removed;
                            // hmm.. postprocess or not?
                            echo "\nJob removed. Exit\n";
                            break;
                        }

                        $messages->progress($percentProgress, $timeLeft);

                        $idlePing = $currentTime;

                        set_time_limit(0);
                        $lastInfoSayTime = $currentTime;
                    }elseif( $this->job_id && $currentTime-$idlePing>60 ){
                        // workaround for idle state
                        tep_db_perform(TABLE_EP_JOB,array(
                            'last_cron_run' => date('Y-m-d H:i:s',$currentTime),
                            'job_state' => Job::PROCESS_STATE_IN_PROGRESS,
                        ), 'update', "job_id='".$this->job_id."'");
                        $idlePing = $currentTime;
                    }
                }

                $messages->progress(100);

                $providerObj->postProcess($messages);

            }
        }catch (\Exception $ex){
            //$messages->info($ex->getMessage());
            \Yii::error("Job exception:\n".$ex->getTraceAsString(),'datasource');
            throw $ex;
        }
    }

    public function jobFinished()
    {
        parent::jobFinished();

        $this->moveToProcessed();
    }


    public function moveToProcessed()
    {
        $new_job_directory_id = $this->directory_id;

        if (!parent::moveToProcessed()){
            \Yii::error("Move ".$this->file_name." to processed failed - renew skip",'datasource');
            return;
        }

        if ( is_array($this->job_configure) && isset($this->job_configure['oneTimeJob']) && $this->job_configure['oneTimeJob']===true ){
            // on time job
            return;
        }
        if ( !$this->isAlive() ) {
            \Yii::error("Move ".$this->file_name." to processed failed - current job not in db",'datasource');
            return;
        }

        $data_array = array(
            'directory_id' => $new_job_directory_id,
            'direction' => $this->direction,
            'file_name' => $this->file_name,
            'file_time' => 0,
            'file_size' => 0,
            'job_state' => 'configured',
            'job_provider' => $this->job_provider,
            'run_frequency' => $this->run_frequency,
            'run_time' => $this->run_time,
            'last_cron_run' => 'now()', //$this->last_cron_run,
        );
        // {{ restore time if run by admin request - Immediately or and custom job time modification
        $directory = Directory::loadById($this->directory_id);
        if ( is_object($directory) && $directory->directory_type==Directory::TYPE_PROCESSED ){
            $directory = $directory->getParent();
        }
        if ( is_object($directory) ) {
            $jobConfig = $directory->findConfigByFileName($this->file_name);
            if ( is_array($jobConfig) ) {
                if ( array_key_exists('run_frequency', $jobConfig) ) {
                    $data_array['run_frequency'] = $jobConfig['run_frequency'];
                }
                if ( array_key_exists('run_time', $jobConfig) ) {
                    $data_array['run_time'] = $jobConfig['run_time'];
                }
            }
        }
        // }} restore time

        tep_db_perform(TABLE_EP_JOB, $data_array);

    }

}
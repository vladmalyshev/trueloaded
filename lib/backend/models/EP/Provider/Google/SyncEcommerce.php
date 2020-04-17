<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Google;

use Yii;
use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use common\classes\language;
use backend\models\EP\Directory;
use yii\db\Query;

class SyncEcommerce implements DatasourceInterface {

    protected $total_count = 0;
    protected $row_count = 0;
    protected $orders_list;
    protected $config = [];
    protected $afterProcessFilename = '';
    protected $afterProcessFile = false;    
    public $job_id;    
    
    function __construct($config) {
        $this->config = $config;
    }

    public function allowRunInPopup() {
        return true;
    }

    public function getProgress() {
        if ($this->total_count > 0) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        } else {
            $percentDone = 100;
        }
        return number_format($percentDone, 1, '.', '');
    }

    public function prepareProcess(Messages $message) {
        
        $this->orders_list = array_keys(\common\models\Orders::find()->select(['orders_id'])->where(['date(date_purchased)' => new \yii\db\Expression(' date(curdate() -  interval 1 day)')])->asArray()->indexBy('orders_id')->all());
    
        $this->total_count = count($this->orders_list);
        
        $this->afterProcessFilename = tempnam($this->config['workingDirectory'], 'after_process');
        $this->afterProcessFile = fopen($this->afterProcessFilename, 'w+');
    }

    public function processRow(Messages $message) {
        set_time_limit(0);

        $item_number = current($this->orders_list);
        if (!$item_number)
            return false;
        try {
            $this->processOrder($item_number, false);
            
        } catch (\Exception $ex) {
            throw new \Exception('Processing order error ' . $ex->getMessage() . " Trace:".  $ex->getTraceAsString());
        }

        $this->row_count++;
        next($this->orders_list);
        return true;
    }

    public function postProcess(Messages $message) {
        return;
    }

    protected function processOrder($item_number, $useAfterProcess = false) {

        static $timing = [
            'soap' => 0,
            'local' => 0,
        ];
        $t1 = microtime(true);
        $t2 = microtime(true);
        $timing['soap'] += $t2 - $t1;

        $ess = new \common\components\google\GoogleEcommerceSS($item_number);
        if (!$ess->isOrderPlacedToAnalytics()){
            $result = $ess->pushDataToAnalytics();
        }

        $t3 = microtime(true);
        $timing['local'] += $t3 - $t2;
        //echo '<pre>';  var_dump($timing);    echo '</pre>';
    }

}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

chdir('../');
include('includes/application_top.php');

\common\helpers\Translation::init('admin/easypopulate');
\common\helpers\Translation::init('admin/main');
try {
    $messages = new \backend\models\EP\Messages([
        'output' => 'console',
    ]);

    $importJob = new \backend\models\EP\JobFile([
        'directory_id' => 2, //manual import
        'file_name' => 'customers_samples.csv',
        'direction' => 'import',
        'job_provider' => 'orders\\customers',
    ]);
    $importJob->run($messages);
    
    $importJob = new \backend\models\EP\JobFile([
        'directory_id' => 2, //manual import
        'file_name' => 'order_samples.csv',
        'direction' => 'import',
        'job_provider' => 'orders\\order',
    ]);
    $importJob->run($messages);
    
}catch (\Exception $ex){
    echo "err:".$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
}

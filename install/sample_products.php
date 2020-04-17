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

    $importJob = new \backend\models\EP\JobZipFile([
        'directory_id' => 2, //manual import
        'file_name' => 'catalog_samples.zip',
        'direction' => 'import',
        'job_provider' => 'auto',
    ]);
    $importJob->tryAutoConfigure();
    $importJob->run($messages);
    
    tep_db_query("INSERT IGNORE INTO platforms_products (platform_id, products_id) SELECT 1, products_id FROM products;");
    
    foreach (\common\models\Products::find()->all() as $product) {
        \common\helpers\Product::doCache($product->products_id);
    }
    
}catch (\Exception $ex){
    echo "err:".$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
}

<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\XTrader;

use Yii;
use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Tools;
use common\api\models\AR\Categories;
use common\api\models\AR\Products;
use common\classes\language;
use backend\models\EP\Directory;
use common\helpers\Seo;
use common\helpers\Tax;
use common\api\models\AR\Supplier;
use backend\models\EP\Provider\XTrader\helpers\ImageSource;
use yii\db\Query;

class ImportStock implements DatasourceInterface {

    protected $total_count = 0;
    protected $row_count = 0;
    protected $products_list;
    protected $config = [];
    protected $afterProcessFilename = '';
    protected $afterProcessFile = false;
    protected $brands;
    public $job_id;
    public $download_dir;
    public $xtrader_xml = "xtrade_stock.xml";
    public $tree = false;
    public $supplier_id;
    public $options = [];
    public $options_values = [];

    function __construct($config) {
        $this->config = $config;
        if (empty($this->config['location_stock']))
            throw new \Exception('xTrader Stock file is not detected');
        $this->downloadFile();
    }

    public function allowRunInPopup() {
        return true;
    }

    public function downloadFile() {
        $this->download_dir = DIR_FS_DOWNLOAD;
        try {
            file_put_contents($this->download_dir . $this->xtrader_xml, file_get_contents($this->config['location_stock']));
        } catch (\Exception $ex) {
            throw new \Exception("xTrader Stock file could not be downloaded");
        }
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
        global $lng;

        $this->config['assign_platform'] = \common\classes\platform::defaultId();

        $this->tree = simplexml_load_file($this->download_dir . $this->xtrader_xml);
        if ($this->tree) {
            if (count($this->tree->PRODUCT)) {
                for ($i = 0; $i < count($this->tree->PRODUCT); $i++) {
                    $this->products_list[] = $this->tree->PRODUCT[$i];
                }
                $this->total_count = count($this->products_list);
            }

            $_supplier = Supplier::find()->where(['suppliers_name' => $this->config['supplierName']])->one();
            if ($_supplier) {
                $this->supplier_id = $_supplier->suppliers_id;
            } else {
                $message->progress(100);
                $this->total_count = 0;
                throw new \Exception("Supplier is not detected");
            }

            $list = (new Query())->select(['products_options_id', 'products_options_name'])
                            ->from(TABLE_PRODUCTS_OPTIONS)
                            ->where(['language_id' => $lng->catalog_languages[DEFAULT_LANGUAGE]['id']])->all();

            if ($list) {
                $this->options = \yii\helpers\ArrayHelper::map($list, 'products_options_name', 'products_options_id');
            }

            $list = (new Query())->select(['products_options_values_id', 'products_options_values_name'])
                            ->from(TABLE_PRODUCTS_OPTIONS_VALUES)
                            ->where(['language_id' => $lng->catalog_languages[DEFAULT_LANGUAGE]['id']])->all();

            if ($list) {
                $this->options_values = \yii\helpers\ArrayHelper::map($list, 'products_options_values_name', 'products_options_values_id');
            }
        } else {
            $message->info("Stock file is empty");
            $message->progress(100);
            return false;
        }

        $tools = new Tools();
        $this->config['in_stock'] = $tools->lookupStockIndicationId('In Stock');
        $this->config['out_stock'] = $tools->lookupStockIndicationId('Currently out of Stock');

        $this->afterProcessFilename = tempnam($this->config['workingDirectory'], 'after_process');
        $this->afterProcessFile = fopen($this->afterProcessFilename, 'w+');
    }

    public function processRow(Messages $message) {
        set_time_limit(0);

        $remoteProduct = current($this->products_list);
        if (!$remoteProduct)
            return false;
        try {
            $this->processRemoteProduct($remoteProduct, false);
            tep_db_perform(TABLE_EP_JOB, array(
                'last_cron_run' => 'now()',
                    ), 'update', "job_id='" . $this->job_id . "'");
        } catch (\Exception $ex) {
            throw new \Exception('Processing product error ' . $ex->getMessage() . " Trace:".  $ex->getTraceAsString());
        }

        $this->row_count++;
        next($this->products_list);
        return true;
    }

    public function postProcess(Messages $message) {
        return;
    }

    public function getStockInfo($stock_value) {
        if (strpos($stock_value, 'In Stock') !== false) {
            $_stock = ['count' => 9999, 'indicator' => $this->config['in_stock'], 'terms' => $this->config['in_stock']];
        } else {
            $_stock = ['count' => 0, 'indicator' => $this->config['out_stock'], 'terms' => $this->config['out_stock']];
        }
        return $_stock;
    }

    protected function processRemoteProduct($remoteProduct, $useAfterProcess = false) {

        static $timing = [
            'soap' => 0,
            'local' => 0,
        ];
        $t1 = microtime(true);
        $t2 = microtime(true);
        $timing['soap'] += $t2 - $t1;


        $model = json_decode(json_encode($remoteProduct->attributes()->ITEM), true)[0];
        $localProduct = Products::find()->where(['products_model' => $model])->one();
        if ($localProduct) {
            if (count($remoteProduct->STOCK) == 1) { // hasn't inventory
                $stock_value = json_decode(json_encode($remoteProduct->STOCK[0]), true)[0];
                $_stock = $this->getStockInfo($stock_value);
                $localProduct->setAttribute('stock_indication_id', $_stock['indicator']);
                $localProduct->setAttribute('stock_delivery_terms_id', $_stock['terms']);
                $localProduct_iCollection = Products\Inventory::find()
                        ->where(['prid' => $localProduct->products_id])
                        ->orderBy(['products_id' => SORT_ASC,])
                        ->all(); // check has Inventory
                $total_amount = $_stock['count'];
                if (is_array($localProduct_iCollection) && count($localProduct_iCollection)){
                    $total_amount = 0;
                    foreach($localProduct_iCollection as $_inventory){
                        $_inventory->setAttribute('stock_indication_id', $_stock['indicator']);
                        $_inventory->setAttribute('stock_delivery_terms_id', $_stock['terms']);
                        $_inventory->setAttribute('products_quantity', $_stock['count']);
                        $_inventory->save(false);
                        $total_amount += $_stock['count'];
                    }
                }
                $localProduct->setAttribute('products_quantity', $total_amount);
                $localProduct->save(false);
            } else {
                $inStock = false;
                $total_amount = 0;
                for ($j = 0; $j < count($remoteProduct->STOCK); $j++) {
                    $s_atts = $remoteProduct->STOCK[$j]->attributes();
                    $inv = [];
                    foreach ($s_atts as $option_name => $value_name) {
                        $value_name = json_decode(json_encode($value_name), true)[0];
                        if (isset($this->options[$option_name]) && isset($this->options_values[$value_name])) {
                            $inv[$this->options[$option_name]] = $this->options_values[$value_name];
                        }
                    }
                    $uprid = \common\helpers\Inventory::get_uprid($localProduct->products_id, $inv);
                    $uprid = \common\helpers\Inventory::normalize_id($uprid);
                    if ($uprid && count($inv) > 0) {
                        $stock_value = json_decode(json_encode($remoteProduct->STOCK[$j]), true)[0];
                        $_stock = $this->getStockInfo($stock_value);
                        if ($_stock['count']) {
                            $inStock = true;
                        }
                        $localInventory = Products\Inventory::find()->where([
                                    'products_id' => $uprid,
                                    'prid' => $localProduct->products_id
                                ])->one();
                        if ($localInventory) {
                            $localInventory->setAttribute('stock_indication_id', $_stock['indicator']);
                            $localInventory->setAttribute('stock_delivery_terms_id', $_stock['terms']);
                            $localInventory->setAttribute('products_quantity', $_stock['count']);
                            $localInventory->save(false);
                            $total_amount += $_stock['count'];
                        }
                        if ($inStock) {
                            $localProduct->setAttribute('stock_indication_id', $this->config['in_stock']);
                            $localProduct->setAttribute('stock_delivery_terms_id', $this->config['in_stock']);
                        } else {
                            $localProduct->setAttribute('stock_indication_id', $this->config['out_stock']);
                            $localProduct->setAttribute('stock_delivery_terms_id', $this->config['out_stock']);
                        }
                        $localProduct->setAttribute('products_quantity', $total_amount);
                        $localProduct->save(false);
                    }
                }
            }
        }

        $t3 = microtime(true);
        $timing['local'] += $t3 - $t2;
        //echo '<pre>';  var_dump($timing);    echo '</pre>';
    }

}

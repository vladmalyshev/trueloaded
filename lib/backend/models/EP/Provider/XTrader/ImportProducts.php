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
use common\api\models\AR\Supplier;
use common\classes\language;
use backend\models\EP\Directory;
use common\helpers\Seo;
use common\helpers\Tax;
use backend\models\EP\Provider\XTrader\helpers\ImageSource;

class ImportProducts implements DatasourceInterface {

    protected $total_count = 0;
    protected $row_count = 0;
    protected $products_list;
    protected $config = [];
    protected $afterProcessFilename = '';
    protected $afterProcessFile = false;
    protected $brands;
    public $job_id;
    public $download_dir;
    public $xtrader_xml = "xtrade_catalog.xml";
    public $tree = false;
    public $supplier_id;
    public $current_category = 0;
    public $tax_rate;
    public $tools;
    public $currencies;

    function __construct($config) {
        $this->config = $config;
        if (empty($this->config['location']))
            throw new \Exception('xTrader catalog file is not detected');
        $this->downloadFile();
        $this->tax_rate = Tax::get_tax_rate_value($this->config['tax']);
        $this->tools = new Tools();
        $this->currencies = Yii::$container->get('currencies');
    }

    public function allowRunInPopup() {
        return true;
    }

    public function downloadFile() {
        $this->download_dir = DIR_FS_DOWNLOAD;
        try {
            file_put_contents($this->download_dir . $this->xtrader_xml, file_get_contents($this->config['location']));
        } catch (\Exception $ex) {
            throw new \Exception("xTrader catalog file could not be downloaded");
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
    
    public $supplier;

    public function prepareProcess(Messages $message) {

        $this->config['assign_platform'] = \common\classes\platform::defaultId();

        if (isset($this->config['truncate_products'])) {
            \common\helpers\Product::trunk_products();
        }

        $xml = simplexml_load_file($this->download_dir . $this->xtrader_xml);
        if ($xml) {
            $this->tree = $xml->CREATED->CATEGORY;
            if (count($this->tree)) {
                $this->products_list = [];
                for ($i = 0; $i < count($this->tree); $i++) {
                    $products = $this->tree[$i]->PRODUCT;
                    for ($j = 0; $j < count($products); $j++) {
                        $this->products_list[] = $products[$j];
                    }
                }

                $this->total_count = count($this->products_list);
                $this->current_category = 0;

                $this->brands = [];
                $_q = tep_db_query("select * from " . TABLE_MANUFACTURERS);
                if (tep_db_num_rows($_q)) {
                    while ($row = tep_db_fetch_array($_q)) {
                        $this->brands[$row['manufacturers_id']] = $row['manufacturers_name'];
                    }
                }

                if (empty($this->config['supplierName'])) {
                    $this->config['supplierName'] = 'xTrader';
                }
                
                $this->supplier = Supplier::find()->where(['suppliers_name' => $this->config['supplierName']])->one();
                if (!$this->supplier) {
                    $this->supplier = new Supplier();
                    $this->supplier->setAttribute('suppliers_name', $this->config['supplierName']);
                    $this->supplier->setAttribute('currencies_id', $this->currencies->currencies['DEFAULT_CURRENCY']['id']);
                    $this->supplier->save();
                }
                $this->supplier_id = $this->supplier->suppliers_id;
            }
        }

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
            throw new \Exception('Processing product error (' . $remoteProduct->MODEL . ') ' . $ex->getMessage() . " Trace:".  $ex->getTraceAsString());
        }

        $this->row_count++;
        next($this->products_list);
        return true;
    }

    public function postProcess(Messages $message) {
        return;
    }

    public function getOptionId($options_name) {
        static $cached = [];
        if (isset($cached[$options_name]))
            return $cached[$options_name];
        $option_id = $this->tools->get_option_by_name($options_name);
        $cached[$options_name] = $option_id;
        return $option_id;
    }

    public function getOptionValueId($option_id, $options_values_name) {
        static $cached = [];
        if (isset($cached[$options_values_name]))
            return $cached[$options_values_name];
        $option_value_id = $this->tools->get_option_value_by_name($option_id, $options_values_name);
        $cached[$options_values_name] = $option_value_id;
        return $option_value_id;
    }

    protected function processRemoteProduct($remoteProduct, $useAfterProcess = false) {

        static $timing = [
            'soap' => 0,
            'local' => 0,
        ];
        $t1 = microtime(true);
        $t2 = microtime(true);
        $timing['soap'] += $t2 - $t1;

        $localId = false;
        $localProduct = \common\api\models\AR\Products::find()
                        ->where(['products_model' => $remoteProduct->MODEL])->one();


        if (!$localProduct) {
            $localProduct = new \common\api\models\AR\Products();
        }

        if ($localProduct) {

            $localProduct->suppliers_id = $this->supplier_id;

            $importArray = $this->transformSimpleProduct($remoteProduct);

            if (!is_array($importArray) || !count($importArray))
                return false;

            $patch_platform_assign = false;
            if ($localProduct->isNewRecord) {
                $patch_platform_assign = true;
                $importArray['assigned_platforms'] = [
                    [
                        'platform_id' => $this->config['assign_platform'],
                    ]
                ];
            }
            $localProduct->importArray($importArray);

            if ($localProduct->validate()) {

                if ($localProduct->save()) {
                    if (/* $patch_platform_assign */ $this->config['assign_platform']) {
                        tep_db_query(
                                "INSERT IGNORE INTO platforms_products (platform_id, products_id) " .
                                "VALUES ('" . intval($this->config['assign_platform']) . "', '" . intval($localProduct->products_id) . "')"
                        );
                    }


                    //update supplier options and values
                    if (isset($importArray['attributes']) && is_array($importArray['attributes']) && !empty($importArray['attributes'])) {

                        foreach ($importArray['attributes'] as $_attributes) {
                            $option_id = $this->getOptionId($_attributes['options_name']);
                            if ($_attributes['supplier_option_id'] && $option_id) {
                                $sql = (new \yii\db\Query)->createCommand()->insert(TABLE_SUPPLIERS_PRODUCTS_OPTIONS, [
                                    'suppliers_id' => $localProduct->suppliers_id,
                                    'suppliers_products_options_id' => $_attributes['supplier_option_id'],
                                    'products_options_id' => $option_id,
                                ]);

                                $sql->setSql('INSERT IGNORE' . mb_substr($sql->rawSql, strlen('INSERT')));
                                $sql->execute();

                                $option_value_id = $this->getOptionValueId($option_id, $_attributes['options_values_name']);
                                if ($_attributes['supplier_option_id'] && $option_value_id) {

                                    $sql = (new \yii\db\Query)->createCommand()->insert(TABLE_SUPPLIERS_PRODUCTS_OPTIONS_VALUES, [
                                        'suppliers_id' => $localProduct->suppliers_id,
                                        'suppliers_products_options_values_id' => $_attributes['supplier_value_id'],
                                        'products_options_values_id' => $option_value_id,
                                        'suppliers_products_model' => $_attributes['products_model'],
                                    ]);
                                    $sql->setSql('INSERT IGNORE' . mb_substr($sql->rawSql, strlen('INSERT')));
                                    $sql->execute();
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($localProduct);

        $t3 = microtime(true);
        $timing['local'] += $t3 - $t2;
        //echo '<pre>';  var_dump($timing);    echo '</pre>';
    }

    public function attributes($product) {
        $newAttributes = [];
        $existed_options = [];
        if (is_array($product)) {
            if (isset($product['ATTRIBUTES']) && is_array($product['ATTRIBUTES']) && count($product['ATTRIBUTES'])) {
                if (isset($product['ATTRIBUTES']['@attributes'])) {
                    $_a = $product['ATTRIBUTES'];
                    $product['ATTRIBUTES'] = [];
                    $product['ATTRIBUTES'][0] = $_a;
                }

                for ($as = 0; $as < count($product['ATTRIBUTES']); $as++) {

                    $attributes = $product['ATTRIBUTES'][$as]['ATTRIBUTEVALUES'];
                    $options_name = trim($product['ATTRIBUTES'][$as]['@attributes']['NAME']);
                    $supplier_option_id = (int) $product['ATTRIBUTES'][$as]['@attributes']['ATTRIBUTEID'];

                    if (!is_array($existed_options[$options_name])) {
                        $existed_options[$options_name] = [];
                    }

                    if (is_array($attributes) && count($attributes)) {
                        for ($a = 0; $a < count($attributes); $a++) {
                            $values = $attributes[$a]['@attributes'];
                            $value_name = trim($values['TITLE']);
                            $supplier_value_id = (int) $values['VALUE'];

                            if (!in_array($value_name, $existed_options[$options_name])) {
                                $prefix = '+';
                                $_priceadjsut = trim($values['PRICEADJUST']);

                                if (substr($_priceadjsut, 0, 1) == '+') {
                                    $price = substr($_priceadjsut, 1);
                                } else {
                                    $price = $_priceadjsut;
                                }
                                if (is_null($price))
                                    $price = 0;
                                $newAttributes[] = [
                                    'options_name' => $options_name,
                                    'options_values_name' => $value_name,
                                    'options_values_price' => (float) $price,
                                    'price_prefix' => $prefix,
                                    'products_model' => $product['MODEL'],
                                    'prices' => [
                                        'attributes_group_price' => '-2',
                                    ],
                                    'supplier_option_id' => $supplier_option_id,
                                    'supplier_value_id' => $supplier_value_id,
                                ];
                                $existed_options[$options_name][] = $value_name;
                            }
                        }
                    }
                }
            }
        }
        return $newAttributes;
    }

    public function inventory($product) {
        $newInventory = [];
        if (is_array($product['attributes'])) {

            foreach ($product['attributes'] as $options) {
                $pattern = [];
                if (!is_array($pattern['attribute_map'])) {
                    $pattern['attribute_map'] = [];
                }
                $pattern['inventory_price'] += $options['options_values_price'];
                $pattern['attribute_map'][] = [
                    'options_name' => $options['options_name'],
                    'options_values_name' => $options['options_values_name'],
                ];

                $newInventory[] = $pattern;
            }
        }

        return $newInventory;
    }

    private function addImage($source, $is_default, $title) {
        return [
            'default_image' => $is_default,
            'image_status' => 1,
            'sort_order' => 0,
            'image_description' => [
                '00' => [
                    'image_source_url' => $source,
                    'orig_file_name' => pathinfo($source, PATHINFO_BASENAME),
                    'image_title' => (string) $title,
                ]
            ]
        ];
    }

    public function images($product) {
        $newImages = [];
        if (is_array($product)) {
            $is_default = 1;
            $main_image = $product['XIMAGE2'];
            if (empty($main_image)) {
                $main_image = $product['XIMAGE'];
            }
            if (empty($main_image)) {
                $main_image = $product['IMAGE'];
            }
            if (!empty($main_image)) {
                $source = ImageSource::getInstance($this->config)->loadResource($main_image);
                if ($source) {
                    $newImages[] = $this->addImage($source, $is_default, $product['NAME']);
                    $is_default = 0;
                }
            }

            for ($im = 5; $im > 2; $im--) {
                if (isset($product['XIMAGE' . $im]) && !empty($product['XIMAGE' . $im])) {
                    if (is_array($product['XIMAGE' . $im])) {
                        $big_image = $product['XIMAGE' . $im][0];
                    } else {
                        $big_image = $product['XIMAGE' . $im];
                    }
                    if (!empty($big_image)) {
                        $source = ImageSource::getInstance($this->config)->loadResource($big_image);
                        if ($source) {
                            $newImages[] = $this->addImage($source, $is_default, $product['NAME']);
                            if ($is_default)
                                $is_default = 0;
                        }
                    }
                }
            }

            for ($im = 1; $im < 4; $im++) {
                if (isset($product['BIGMULTI' . $im]) && !empty($product['BIGMULTI' . $im])) {
                    if (is_array($product['BIGMULTI' . $im])) {
                        $big_image = $product['BIGMULTI' . $im][0];
                    } else {
                        $big_image = $product['BIGMULTI' . $im];
                    }
                    if (!empty($big_image)) {
                        $source = ImageSource::getInstance($this->config)->loadResource($big_image);
                        if ($source) {
                            $newImages[] = $this->addImage($source, $is_default, $product['NAME']);
                            if ($is_default)
                                $is_default = 0;
                        }
                    }
                }
            }
        }
        return $newImages;
    }

    public function properties($product) {
        $properties = [];
        $known = ['LENGTH', 'LUBETYPE', 'CONDOMSAFE', 'LIQUIDVOLUMN', 'NUMBEROFPILLS', 'FASTENING', 'WASHING', 'INSERTABLE', 'DIAMETER', 'HARNESSCOMPATIBLE', 'ORINGCIRC',
            'ORINGDIAM', 'WIDTH', 'COLOUR', 'FLEXIBILITY', 'CONTROLLER', 'FORWHO', 'WHATISIT', 'FOR', 'MOTION', 'FEATURES', 'MISC', 'MATERIAL', 'STYLE', 'POWER', 'SIZE'];

        foreach ($known as $property_name) {
            if (isset($product[$property_name]) && !empty($product[$property_name])) {
                $properties[] = [
                    'name_path' => [
                        '*' => $property_name,
                    ],
                    'values' => [
                        '*' => $product[$property_name]
                    ]
                ];
            }
        }
        return $properties;
    }

    protected function transformSimpleProduct(&$product) {
        $product = json_decode(json_encode($product), true);

        $simple = [
            'products_model' => $product['MODEL'],
            'products_price' => Tax::get_untaxed_value($product['RRP'], $this->tax_rate),
            'products_weight' => $product['WEIGHT'],
            'products_tax_class_id' => $this->config['tax'],
            'manufacturers_name' => $product['BRAND'],
            'descriptions' => [
                '*' => [
                    'products_name' => $product['NAME'],
                    'products_description' => str_replace("nbsp", "", $product['DESCRIPTION']),
                ]
            ],
            'attributes' => $this->attributes($product),
            'images' => $this->images($product),
            'suppliers_product' => [
                [
                    'suppliers_price' => (float) $product['BPPRICE'],
                    'currencies_id' => $this->supplier->currencies_id,
                    'suppliers_model' => $product['MODEL'],
                    'suppliers_id' => $this->supplier_id,
                    'suppliers_margin_percentage' => (float) (( Tax::get_untaxed_value($product['RRP'], $this->tax_rate) / ($product['BPPRICE'] ? $product['BPPRICE'] : 1)) - 1) * 100
                ]
            ],
            'properties' => $this->properties($product),
        ];

        if (!empty($product['EAN'])) {
            $simple['products_ean'] = $product['EAN'];
        }

        $simple['inventory'] = $this->inventory($simple);
        $simple['products_status'] = 1;
        $simple['assigned_categories'] = [
            [
                'categories_id' => $this->config['categories_id'],
            ]
        ];
        //echo '<pre>';print_r($simple);die;


        return $simple;
    }

}

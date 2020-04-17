<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;

use \common\models\ProductsImages;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;

use \common\helpers\Suppliers;

class DownloadProducts implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list = [];
    protected $config = [];

    /**
     * @var Client
     */
    protected $client;
    protected $hasMoreData = false;
    protected $fetchPage = 1;

    protected $productExists = null;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function getProgress()
    {
        if ( $this->total_count>0 ) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }

    public function prepareProcess(Messages $message)
    {
        $this->fetchPage = 1;
        $this->hasMoreData = false;
        $this->client = new Client($this->config['client']['API_ID'],$this->config['client']['API_KEY']);

        $this->process_list = [];
        $this->fetchPage($message);

        reset($this->process_list);

        $message->info("Products for update - ".$this->total_count);
    }

    protected function fetchPage(Messages $message)
    {
        $response = $this->client->get('Products/'.$this->fetchPage, 'pageSize=700&includeObsolete=true&includeAttributes=true&modifiedSince=' . date('Y-m-d\TH:i:s', strtotime('-2 years')))->send();
        $data = $response->getData();
        
        $Pagination = $data['Pagination'];
        $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        $this->total_count = (int)$Pagination['NumberOfItems'];
        if (is_array($data['Items'])) {
          foreach ($data['Items'] as $item){
              $this->process_list[$item['Guid']] = $item;
          }
        }
        return true;
    }

    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $this->processProduct( $guid, $this->process_list[$guid], $message);

        if (next($this->process_list)){
            return true;
        }elseif($this->hasMoreData){
            $this->process_list = [];
            $this->fetchPage++;
            $message->info('fetch page ' . $this->fetchPage);
            return $this->fetchPage($message);
        }
    }

    public function postProcess(Messages $message)
    {
      $message->info('Done ');
    }

    protected function processProduct($guid, $data, Messages $message)
    {
      static $defaultPlatformName = null;
      if (is_null($defaultPlatformName)) {
        $defaultPlatformName = \common\classes\platform::name(\common\classes\platform::defaultId());
      }
      $data['productExists'] = null;
      $data['Products Status'] = 1;
      $data['Platform - ' . $defaultPlatformName] = 1;
      $keys = [
        'ProductCode' => 'Products Model', 
        'ProductDescription' => ['set' => 'setName'],
        'Barcode' => 'EAN',
        'Width' => [ 'set' => 'setSize' ],
        'Height' => [ 'set' => 'setSize' ],
        'Depth' => [ 'set' => 'setSize' ],
        'Weight' => 'Products Weight',
        'Obsolete' => ['set' => 'setStatus'],
        'Images' => ['set' => 'setImage'],
        'IsSellable' => ['set' => 'setListing'],
        'IsComponent' => ['set' => 'setListing'],
        'ProductGroup' => ['set' => 'setCategory'],
        'Supplier' => ['set' => 'setSupplier'],
        'XeroSalesTaxCode' => ['set' => 'findSetTaxClass'],
        'XeroSalesTaxRate' => ['set' => 'findSetTaxClass'],
        'TaxableSales' => ['set' => 'setTaxClass'],
        'DefaultSellPrice' => 'Products Price',
        //'IsAssembledProduct' => '',
        //'Obsolete' => '',
        'AttributeSet' => ['set' => 'setSatusFromEndDate'],

      ];
      for ($i=1;$i<11;$i++) {
        $keys['SellPriceTier' . $i] = ['set' => 'setPrice'];
      }

      /*
               [199] => Array
                (
                    [ProductCode] => CB-AK11-QUILT-Q
                    [ProductDescription] => Pacific Sleep Plush | Quilt | Queen
                    [PackSize] =>
                    [MinStockAlertLevel] => 0
                    [MaxStockAlertLevel] => 0
                    [ReOrderPoint] =>
                    [UnitOfMeasure] => Array
                        (
                            [Guid] => 26962d6f-3278-4b2a-bb83-680467513bc4
                            [Name] => EA
                        )

                    [NeverDiminishing] =>
                    [LastCost] => 74.8973
                    [DefaultPurchasePrice] => 0
                    [DefaultSellPrice] => 0
                    [CustomerSellPrice] =>
                    [AverageLandPrice] => 74.8973
                    [Notes] =>
    [Images] => Array
        (
            [0] => Array
                (
                    [Url] => https://unlappcdn.unleashedsoftware.com/a71631cd-9b97-46fd-95ad-4a425f35f491/5b4296e5-42cb-4e09-82c5-333caa96f540/Images/28a53528-676b-4236-ac7b-17068b278c31.png
                    [IsDefault] => 1
                )

        )

    [ImageUrl] => https://unlappcdn.unleashedsoftware.com/a71631cd-9b97-46fd-95ad-4a425f35f491/5b4296e5-42cb-4e09-82c5-333caa96f540/Images/28a53528-676b-4236-ac7b-17068b278c31.png
                    [SellPriceTier1] => Array
                        (
                            [Name] => Level 1
                            [Value] =>
                        )

                    [SellPriceTier10] => Array

                    [XeroTaxCode] =>
                    [XeroTaxRate] =>
                    [TaxablePurchase] => 1
                    [TaxableSales] => 1
      "XeroSalesTaxCode": "G.S.T.",
      "XeroSalesTaxRate": 0.15,
                    [IsComponent] => 1
                    [IsAssembledProduct] =>
                    [ProductGroup] => Array
                        (
                            [GroupName] => Quilt
                            [Guid] => 20ed2245-5db7-466c-951b-c656de961065
                            [LastModifiedOn] => DateTime Object
                                (
                                    [date] => 2016-05-14 05:09:45.000000
                                    [timezone_type] => 3
                                    [timezone] => Europe/London
                                )

                        )

                    [XeroSalesAccount] =>
                    [XeroCostOfGoodsAccount] =>
                    [PurchaseAccount] =>
                    [BinLocation] =>
                    [Supplier] => Array //// default only
                        (
                            [SupplierProductCode] =>
                            [SupplierProductDescription] =>
                            [SupplierProductPrice] =>
                            [Guid] => 271b9df9-8e4b-4c63-a9bc-987a0d2a1abc
                            [SupplierCode] => JLH Bedding
                            [SupplierName] => JLH Bedding
                        )

      "AttributeSet": {
        "Guid": "094c5e46-2f0f-407a-87d6-966f4f05f74c",
        "SetName": "Import Compressed",
        "Type": "Product",
        "Attributes": [
          {
            "Guid": "1b9493bb-dca5-47d4-8761-f9f0c9a3c6b7",
            "Name": "EndDate",
            "Value": "2115-07-10",
            "IsRequired": false
          },
          {
            "Guid": "596c7055-31eb-4b31-a425-bd9719f67691",
            "Name": "StartDate",
            "Value": "2019-07-01",
            "IsRequired": false
          }
        ],
        "CreatedBy": null,
        "CreatedOn": null,
        "LastModifiedBy": null,
        "LastModifiedOn": null
      },
                    [SourceId] =>
                    [SourceVariantParentId] =>
                    [IsSerialized] =>
                    [IsBatchTracked] =>
                    [CreatedBy] => gary_tse@outlook.com
                    [CreatedOn] => DateTime Object
                        (
                            [date] => 2016-05-14 07:52:32.000000
                            [timezone_type] => 3
                            [timezone] => Europe/London
                        )

                    [LastModifiedBy] => sales@dreamlandbedding.com
                    [Guid] => bad19560-8650-495c-b59c-37ceb0e2e0d8
                    [LastModifiedOn] => DateTime Object
                        (
                            [date] => 2019-01-06 21:42:06.000000
                            [timezone_type] => 3
                            [timezone] => Europe/London
                        )

                )

        )

)

       */

        $secondChance = []; //not used now
        foreach ($keys as $fileField => $d) {
          if (isset($data[$fileField]) && is_array($d)) {

            if (isset($d['set']) && method_exists($this, $d['set'])) {
              $r = call_user_func_array(array($this, $d['set']), array(&$data, $fileField, &$message, $this->config));
              if ($r !== true) {
                $message->info('Cant trnasform ' . $fileField . ' ' . $r);
                return ;
              }
            } elseif (!is_array($d)) {
              $data[$d] = $data[$fileField];
            }
            $secondChance[$fileField] = $data[$fileField];
            unset($data[$fileField]);
          } elseif (isset($data[$fileField]) && !is_array($d)) {
            $data[$d] = $data[$fileField];
          }

        }

        $updateNew = !is_array($data['productExists']);
        if ($updateNew) {
          $secondChance['Products Model'] = $data['Products Model'];
          $secondChance['ProductDescription'] = $data['Internal Name en'];
        }
        unset($data['productExists']);

        if ($data && is_array($data) && count($data) > 0) {
          try {
            $providerObj = new \backend\models\EP\Provider\Products();

            $transform = new \backend\models\EP\Transform();
            $transform->setProviderColumns($providerObj->getColumns());
            $data = $transform->transform($data);
//$message->info(print_r($data,1));

            $providerObj->importRow($data, $message);
            $this->row_count++;

          } catch (\Exception $e) {

            $message->info('Exception (parent) ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            \Yii::warning('unleashed product not imported Data:' . print_r($data) . ' ' . $e->getMessage() . ' ' . $e->getTraceAsString(), 'EP_Unleashed');
            return false;
          }          
          if ($updateNew) {
            self::setName($secondChance, 'ProductDescription', $message);
            self::setSupplier($secondChance, 'Supplier', $message);
          }

          return true;
        } else {
          $message->info('Cant transform product');
        }

        return false;

    }

  public static function setListing(&$data, $fileField) {
    $ret = true;
    if (!isset($data['Listing product?'])){
      $data['Listing product?'] = 1;
    }
    if ($data['Listing product?'] == 1) {
      $d = ( empty($data[$fileField]) ? 0 : $data[$fileField] );

      if ($fileField == 'IsComponent') {
        //components are sellable $data['Listing product?'] = in_array(strtolower($d), ['true', 'yes', 1])?0:1;
      } else {
        $data['Listing product?'] = !in_array(strtolower($d), ['true', 'yes', 1])?0:1;
      }
      unset($data[$fileField]);
    }
    return $ret;
  }

  public static function setTaxClass(&$data, $fileField, $message = null, $config = []) {
    $ret = true;
    $d = ( empty($data[$fileField]) ? 0 : $data[$fileField] );
//$message->info("setTaxClass \$fileField $fileField   " . $data['Products Model']. ' data ' . print_r($d, 1) . ' before ' . $data['Products Tax Class'] . ' config ' . $config['products']['default_tax_class']);
    if (empty($data['Products Tax Class'])) {
      $data['Products Tax Class'] = !in_array(strtolower($d), ['true', 'yes', 1])?'':$config['products']['default_tax_class'];
    }

    unset($data[$fileField]);
    return $ret;
  }

  public static function findSetTaxClass(&$data, $fileField, $message) {
    $ret = true;
    $d = ( empty($data[$fileField]) ? '' : $data[$fileField] );
    if (empty($data['Products Tax Class']) && !empty($d)) {
      $code = '';
      if ($fileField == 'XeroSalesTaxCode') {
        $list = \common\models\TaxClass::find()->andWhere(['like', 'tax_class_title', $d])->asArray()->all();
        if ($list && count($list)==1) {
          $code = $list[0]['tax_class_title'];
        }
      } else {// by rate ex 0.15
        $list = \common\models\TaxRates::find()
            ->joinWith('taxClass')->andWhere(['tax_rate' => round($d*100, 4)])
            ->asArray()->all();
        if ($list && count($list)==1 && isset($list[0]['taxClass']['tax_class_title'])) {
          $code = $list[0]['taxClass']['tax_class_title'];
        }
      }
      if (!empty($code)) {
        $data['Products Tax Class'] = $code;
      }
    }
//$message->info("findSetTaxClass \$fileField $fileField \$code $code " . $data['Products Model']. ' data ' . print_r($d, 1));
    unset($data[$fileField]);
    return $ret;
  }


  public static function setStatus(&$data, $fileField) {
    $ret = true;
    if ($data['Products Status'] == 1) {
      $d = ( empty($data[$fileField]) ? 0 : $data[$fileField] );
      $data['Products Status'] = in_array(strtolower($d), ['true', 'yes', 1])?0:1;
      $data['skip'] = ($data['Products Status'] == 0);
      unset($data[$fileField]);
    }
    return $ret;
  }

  public static function findProduct(&$data, $model) {
    if (is_null($data['productExists'] ) && !empty($model)) {
      $p = \common\models\Products::find()->andWhere(['products_model' => $model])
          ->select('products_id, products_model')
          ->asArray()->all();
      if ($p && count($p)==1) {
        $data['productExists'] = $p[0];
      } else {
        $data['productExists'] = false;
      }
    }
  }

  public static function setSupplier(&$data, $fileField, $message) {
    $ret = true;
    if (is_null($data['productExists']) && !empty($data['Products Model'])) {
      static::findProduct($data, $data['Products Model']);
    }
    $d = $data[$fileField];
    /*
                        [Supplier] => Array
                        (
                            [SupplierProductCode] =>
                            [SupplierProductDescription] =>
                            [SupplierProductPrice] =>
                            [Guid] => 271b9df9-8e4b-4c63-a9bc-987a0d2a1abc
                            [SupplierCode] => JLH Bedding
                            [SupplierName] => JLH Bedding
                        )
     */
    // add supplier as products to suppliers don't do it
    static $_supplierExists = [];
    if (!isset($_supplierExists[$d['Guid']])) {
      $q = \common\models\Suppliers::find()->andWhere([
        'or',
        ['suppliers_name' => [$d['SupplierCode'], $d['SupplierName']]],
        ['company' => [$d['SupplierCode'], $d['SupplierName']]],
      ]);
      if ($q->count()>0) {
        $_supplierExists[$d['Guid']] = true;
      } else {
        try {
          $q = new \common\models\Suppliers();
          $q->loadDefaultValues();
          $q->suppliers_name = $d['SupplierCode'];
          $q->company = $d['SupplierName'];
          $q->save();
        } catch (\Exception $ex) {
          $message->info('Could not save supplier:' . $d['SupplierName']);
          \Yii::warning($ex->getMessage() . ' ' . $ex->getTraceAsString() . ' ' . print_r($d, 1));
        }
      }
    }

    if ($data['productExists'] ) {
      static $defaultSupplierName = null;
      if (is_null($defaultSupplierName) ) {
        $defaultSupplierName = Suppliers::getSupplierName( Suppliers::getDefaultSupplierId() );
      }

      $sData = [];
      if (is_array($d) && count($d)) {
        $sData[] = [
          'Status (put active first)' => 1,
          'Suppliers name' => $d['SupplierCode'], //SupplierName
          'Suppliers Model' => ($d['SupplierProductCode']?$d['SupplierProductCode']:''),
          'Cost' => ($d['SupplierProductPrice']>0?$d['SupplierProductPrice']:$data['LastCost']),
          'Products Model' => $data['Products Model']
        ];
      }
      
      $sData[] = [
        'Status (put active first)' => (count($sData)>0?0:1),
        'Suppliers name' => $defaultSupplierName,
        'Suppliers Model' => '',
        'Cost' => (isset($data['LastCost']) ? $data['LastCost'] : 0),
        'Products Model' => $data['Products Model'],
      ];


      foreach ($sData as $sd) {
        $providerObj = new \backend\models\EP\Provider\SuppliersProducts();

        $transform = new \backend\models\EP\Transform();
        $transform->setProviderColumns($providerObj->getColumns());
        $sd = $transform->transform($sd);
        try {
          $providerObj->importRow($sd, $message);
        } catch (\Exception $e) {
          $message->info($e->getMessage());
        }
      }

    }
    return $ret;
  }
  
  public static function setProperties(&$data, &$propertiesValues, &$message) {
    if (is_null($data['productExists']) && !empty($data['Products Model'])) {
      static::findProduct($data, $data['Products Model']);
    }
    if ($data['productExists'] && is_array($propertiesValues) && count($propertiesValues)) {
      //find and assign properties
      $_idx = 0;
      foreach ($propertiesValues as $pvKey => $pvv) {
        $_idx++ ;
        $pvs = array_map('trim', explode('/', $pvv));
        if (count($pvs)>1) { //Water/Fire Proof
          $pvs[] = $pvv;
        }
        foreach ( $pvs as $pv ) {
          $pvq = \common\models\PropertiesValues::find()
              /*->andWhere([
                            'or',
                            ['values_text' => $pv],
                            ['values_alt' => $pv],
                          ])*/
              ->andWhere(['values_text' => $pv])
              ->select([
                  'values_id' => (new \yii\db\Expression('min(values_id)')),
                  'total' => (new \yii\db\Expression('count(distinct values_id)')),
                  'properties_id',
                  ])
              ->groupBy('properties_id');
          $chk = $pvq->asArray()->all();

          if (is_array($chk) && count($chk) == 1 && $chk[0]['total'] == 1) {
            \Yii::$app->db->createCommand('insert ignore properties_to_products (products_id, properties_id, values_id) values (:pid , :prid, :vid)', [
                ':pid' =>  $data['productExists']['products_id'],
                ':prid' =>  $chk[0]['properties_id'],
                ':vid' =>  $chk[0]['values_id'],
              ])->execute();
            unset($propertiesValues[$pvKey]);
            
          } else {
            if (!strpos($pv, '/') && ($_idx>1)) {
              $message->info('NOT UNIQUE/EMPTY/NEW PROPERTY idx ' . $_idx . ' ' . $pv . (!empty($chk[0]['total'])?' (' . $chk[0]['total'] . ') ' : '') );// . $pvq->createCommand()->rawSql
            }
            \Yii::warning('NOT UNIQUE/EMPTY/NEW PROPERTY ' . $pv . print_r($chk, 1), "unleashed_EP");
          }
        }
      }
    }
  }

  public static function setImage(&$data, $fileField, &$message) {
    if (is_null($data['productExists'] ) && !empty($data['Products Model'])) {
      static::findProduct($data, $data['Products Model']);
    }
    $imgs = $data[$fileField];
    /*
            [0] => Array
                (
                    [Url] => https://unlappcdn.unleashedsoftware.com/a71631cd-9b97-46fd-95ad-4a425f35f491/5b4296e5-42cb-4e09-82c5-333caa96f540/Images/28a53528-676b-4236-ac7b-17068b278c31.png
                    [IsDefault] => 1
                )
          */
    if ($data['productExists'] && is_array($imgs) && count($imgs)) {
      $providerObj = new \backend\models\EP\Provider\Images();
      $dir = new \backend\models\EP\Directory(['directory' => 'manual_import']);
      $path = $dir->filesRoot(\backend\models\EP\Directory::TYPE_IMAGES);
      $providerObj->setImagesDirectory($path);
      $filenames = [];
      foreach ($imgs as $img) {
        $filenames[] = tep_db_input(basename($img['Url']));
      }
      $piq = ProductsImages::find()->joinWith('description', false, ' inner join ')
      ->andWhere([
        ProductsImages::tableName() . '.products_id' => $data['productExists']['products_id'],
        \common\models\ProductsImagesDescription::tableName() . '.orig_file_name' => $filenames
          ])
      ->count();

      if (count($filenames) > $piq) {
        // if something new then we have to process all images
        foreach ($imgs as $img) {
          $filename = basename($img['Url']);

            $importImgs = [
              'Products Model' => $data['productExists']['products_model'],
              'Default Image' => (int)$img['IsDefault'],
              'Original filename Main' => $filename
            ];


            if (!is_file($path . $filename)) {
              $fp = fopen($path . $filename, 'w+');
              $ch = curl_init($img['Url']);
              curl_setopt($ch, CURLOPT_TIMEOUT, 50);
              // write curl response to file
              curl_setopt($ch, CURLOPT_FILE, $fp);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
              curl_exec($ch);
              curl_close($ch);
              fclose($fp);
            }

            $transform = new \backend\models\EP\Transform();
            $transform->setProviderColumns($providerObj->getColumns());
            $importImgs = $transform->transform($importImgs);

            $providerObj->importRow($importImgs, $message);

        }

        $providerObj->postProcess($message);
      }

    }
    return true;
  }

  public static function setName(&$data, $fileField, &$message) {
    $ret = true;
    $data['Internal Name en'] = $data[$fileField];

    $parts = array_map('trim', explode("|", $data[$fileField]));
    if (count($parts)>0) {
      // save original name
      $data['Products Name en'] = $parts[0];
      //collection property unset($parts[0]);
      static::setProperties($data, $parts, $message);
      if (count($parts)) {
        $data['Products Name en'] = implode(' | ', $parts);
      }
    } //else { //required if name should be changed to something
      $data['Products Name en'] = $data['Internal Name en'];
    //}
    unset($data[$fileField]);
    return $ret;
  }

  public static function setSize(&$data, $fileField, &$message) {
    $ret = true;
    $map = [
        'Width' => 'Width (cm)',
        'Height' => 'Height (cm)',
        'Depth' => 'Length (cm)'
      ];
    $data[$map[$fileField]] = $data[$fileField]*100;

    unset($data[$fileField]);
    return $ret;
  }

  public static function setPrice(&$data, $fileField) {
    $ret = true;
    /*
     [Name] => Level 1
     [Value] =>
     */
    $d = $data[$fileField];
    if (is_array($d) && !empty($d['Value'])) {
      $price = $d['Value'];
      if (!isset($data['Products Price'])) {
        $data['Products Price'] = $price;
      }
      $data['Products Price ' . trim($d['Name'])] = $price;
    }
    unset($data[$fileField]);
    return $ret;
  }

  public static function setSatusFromEndDate(&$data, $fileField, &$message) {
/*
 *         "Guid": "094c5e46-2f0f-407a-87d6-966f4f05f74c",
        "SetName": "Import Compressed",
        "Type": "Product",
        "Attributes": [
          {
            "Guid": "1b9493bb-dca5-47d4-8761-f9f0c9a3c6b7",
            "Name": "EndDate",
            "Value": "2115-07-10",
            "IsRequired": false
          },
          {
            "Guid": "596c7055-31eb-4b31-a425-bd9719f67691",
            "Name": "StartDate",
            "Value": "2019-07-01",
            "IsRequired": false
          }
        ],
        "CreatedBy": null,
        "CreatedOn": null,
        "LastModifiedBy": null,
        "LastModifiedOn": null
 */
    $ret = true;

    if (is_array($data[$fileField]['Attributes'])) {
      foreach ($data[$fileField]['Attributes'] as $d) {
        if ($d['Name'] == 'EndDate') {
          $date = strtotime($d['Value']);
          if ($date && date('Y', $date) > '2000' && ($date + 183*24*3600< time())) {
            $data['Products Status'] = 0;
          }
        }
      }
    }
    unset($data[$fileField]);
    return $ret;

  }
  
  public static function setCategory(&$data, $fileField) {
    $ret = true;
    $d = $data[$fileField];
    if (is_array($d) && !empty($d['GroupName'])) {
      $data['Categories_0'] = $d['GroupName'];
    }
    unset($data[$fileField]);
    return $ret;
  }

}
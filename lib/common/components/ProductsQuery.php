<?php
/*
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace common\components;
use yii\db\Query;
use common\classes\platform;
/**
 * ProductsQuery uber class... :,(
 * creates product query for all pages/modules according parameters
 * do something with filters
 * indextables (description and price) are for search and sort only (incorrect filter is not a big problem - availability should be correct)
 *
 * @author vlad
 */
class ProductsQuery {
  const PRESELECT_PRODUCST_IDS = true;
  const PRESELECT_PRODUCST_PRICE_IDS = true;
  const PRESELECT_PRODUCST_SEARCH_IDS = true;
  const PRESELECT_PRODUCST_CATEGORIES_IDS = true;
  protected $params = [
    'page' => 'catalog/all-products',
    'filters' => [],
    'currentPlatform' => true,
    'currentCategory' => true,
    'currentCustomerGroup' => true,
    'active' => true,
    'outOfStock' => true,
    'onlyWithImages' => false,
    'hasSubcategories' => false,
    'customAndWhere' => '',
    'get' => [], // get params to add filter and sort order
    'orderBy' => [],
    'limit' => 0,
    'anyExists' => false,
    'featuredTypeId' => false
  ];

  private $query = null;
  private $count = [];
  private $listProductIds = [];
  private $listProductPriceIds = [];
  private $listProductKeywordsIds = [];
  private $hiddenStockIndicationIds = null;
  private $backorderStockIndicationIds = null;
      
/**
 * set allowed parameters and apply default restrictions
 *  'page' => 'catalog/all-products',
 *
 *   'filters' => [
 *  'keywords' => ''
 * 'manufacturers' => N,[N]
 * 'categories' => N,[N]
 * 'price' => ['from => dd.dd,  'to' => dd.dd]

 *  'currentPlatform' => true,
 * 'currentCategory' => true,
 *  'currentCustomerGroup' => true,
 *  //'active' => true,
 *  'outOfStock' => true,
 *  'onlyWithImages' => false,
 *  'customAndWhere' => false,
 *
 *  'get' => [
 * 'keywords'
 * 
 * brand|manufacturers_id => [id]
 *
 * pfrom, pto  => dd.dd- price range
 *
 * cat => [id]
 *
 * pr(\d+)from => dd.dd,  pr(\d+)to => dd.dd, ^pr(\d+)$ => []
 *
 * ^at(\d+)$  => []
 *
 * ],
 *
 *  'orderBy' => [
 *
 * 'products_name' | 'bestsellers' | 'products_model' | 'products_date_added' | 'products_popularity' | 'products_weight' | 'manufacturers_name' | 'products_price' | 'products_quantity' | 'rand()
 *  => dir | 'FIELD (products_id, 5) DESC'
 * ],
 * 
 *  'limit' => 0
 *  'offset' => 0
 * @param array $params
 */
  public function __construct($params = []) {
    foreach ($params as $key => $value) {
      if (isset($this->params[$key])
          /*&& gettype($this->params[$key]) == gettype($value) nice to have - but often string/int instead of int/bool*/
          && (!is_array($this->params[$key]) || (is_array($this->params[$key]) && is_array($value) && !empty($value)) )
          ) {
        $this->params[$key] = $value;
      } elseif (!empty($value)) {
        \Yii::warning('Products query builder incorrect type: ' . $key . " " . gettype($this->params[$key]) . " != " . gettype($value));
      }
    }

    if (!empty($this->params['get'])) {
      $this->filtersFromGet();
    }
    $this->init();
  }


  public function init() {
    //$this->query = new Query();
    $this->query = \common\models\Products::find();
    //$this->query
    $this->query->alias('p')
        //->from(['p' => TABLE_PRODUCTS])
        ->select('p.products_id')
        /*->where([
          //'p.products_status'=> 1, //below \common\helpers\Product::getState(true);
          ])*/
        ;
    // hidden if -1 in customer group price
    if (CUSTOMERS_GROUPS_ENABLE == 'True') { //USE_MARKET_PRICES == 'True' || no disable by currency, it's by group only
      $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
      $currency_id = \Yii::$app->settings->get('currency_id');
      if ($customer_groups_id>0) {
        $this->query->andWhere(['not exists',
                               (new Query())->from(['pp' => TABLE_PRODUCTS_PRICES])
                                            ->where('p.products_id = pp.products_id')
                                            ->andWhere([
                                                    'pp.groups_id' => (int)$customer_groups_id,
                                                    'pp.currencies_id' => (USE_MARKET_PRICES == 'True' ? (int)$currency_id : '0'),
                                                    'pp.products_group_price' => -1
                                                  ])
            ]);
      }
      
      // new way to restrict in separate method

    }

    // hidden by stock indication flag
    if (is_null($this->hiddenStockIndicationIds )) {
      $this->hiddenStockIndicationIds = \common\classes\StockIndication::getHiddenIds();
    }
    if (!is_null($this->hiddenStockIndicationIds )) {
      $this->query->andWhere(['not in', 'stock_indication_id',
                         $this->hiddenStockIndicationIds
      ]);
    }

    if (defined('LISTING_SUB_PRODUCT') && LISTING_SUB_PRODUCT=='True' && !\frontend\design\Info::isTotallyAdmin()) {
      $this->query->andWhere('p.is_listing_product=1');
    }

    /** @var \common\extensions\CustomerProducts $ext  */
    if ($ext = \common\helpers\Acl::checkExtension('CustomerProducts', 'allowed')) {
      if($ext::allowed() && !\Yii::$app->user->isGuest) {
        if (\Yii::$app->user->identity->restrict_products) {
          $this->query->andWhere(['p.products_id' => \common\extensions\CustomerProducts\models\CustomerProducts::find()->where(['customer_id' => \Yii::$app->user->getId()])->select('product_id')->asArray()->column() ]);
        }
      }
    }

  }

  /**
   * parse get param and put them into filter
   */
  protected function filtersFromGet() {
    if (!empty($this->params['get']) && is_array($this->params['get'])) {
      foreach ($this->params['get'] as $key => $values) {
        $arr = [];
        if ($key == 'sort') {
          $values = trim($values);
          if (strlen($values)==1) {
            $values .= 'a';
          }
          if (strlen($values)==2) {
            $f = substr($values, 0, 1);
            $d = substr($values, 1);
            foreach (\common\helpers\Sorting::SORT_OPTIONS as $so) {
              if ($so[1] == $f) {
                $this->params['orderBy'] = [$so[2] => ($d=='d'?SORT_DESC:SORT_ASC)];
                break;
              }
            }

          }

        } elseif ($key == 'keywords') {
          $this->params['filters']['keywords'] = tep_db_prepare_input($values);
          
        } elseif ($key == 'brand') {
//brand
          if (!is_array($values)) {
            $values = [$values];
          }
          $this->params['filters']['manufacturers'] = array_map('intval', $values);
        } elseif ($key == 'manufacturers_id') {
//brand
          if (!is_array($values)) {
            $values = [$values];
          }
          $this->params['filters']['manufacturers'] = array_map('intval', $values);
        } elseif ($key == 'cat') {
// categories
          if (!is_array($values)) {
            $values = [$values];
          }
          $this->params['filters']['categories'] = array_map('intval', $values);
        } elseif ($key == 'pfrom' && (!defined('GROUPS_IS_SHOW_PRICE') || GROUPS_IS_SHOW_PRICE == true)) {
// Price from
            $values = str_replace(',', '.', preg_replace('/[^\d\.\,]/', '', $values));
            $this->params['filters']['price']['from'] = (float)$values;
        } elseif ($key == 'pto' && (!defined('GROUPS_IS_SHOW_PRICE') || GROUPS_IS_SHOW_PRICE == true)) {
//interval to
          $values = str_replace(',', '.', preg_replace('/[^\d\.\,]/', '', $values));
          $this->params['filters']['price']['to'] = (float)$values;
        }elseif (preg_match("/^pr(\d+)from$/", $key, $arr)) {
// Properties
//interval from
          $prop_id = (int)$arr[1];
          if ($prop_id > 0 ) {
            $this->params['filters']['properties'][$prop_id]['from'] = (float)$values;
            if ($this->params['filters']['properties'][$prop_id]['to'] && $this->params['filters']['properties'][$prop_id]['to']<(float)$values) {
              $this->params['filters']['properties'][$prop_id]['from'] = $this->params['filters']['properties'][$prop_id]['to'];
              $this->params['filters']['properties'][$prop_id]['to'] = (float)$values;
            }
            $this->params['filters']['properties'][$prop_id]['field'] = 'values_number';
          }
        } elseif (preg_match("/^pr(\d+)to$/", $key, $arr)) {
//interval to
          $prop_id = (int)$arr[1];
          if ($prop_id > 0 ) {
            $this->params['filters']['properties'][$prop_id]['to'] = (float)$values;
            if ($this->params['filters']['properties'][$prop_id]['from'] && $this->params['filters']['properties'][$prop_id]['from']>(float)$values) {
              $this->params['filters']['properties'][$prop_id]['to'] = $this->params['filters']['properties'][$prop_id]['from'];
              $this->params['filters']['properties'][$prop_id]['from'] = (float)$values;
            }
            $this->params['filters']['properties'][$prop_id]['field'] = 'values_number';
          }
        } elseif (preg_match("/^vpr(\d+)from(\d+)$/", $key, $arr)) {
            $prop_id = (int)$arr[1];
            $val_id = (int)$arr[2];
            if ($prop_id > 0 && $val_id > 0 && !empty($values)) {
                    $this->params['filters']['extra'][$prop_id]['field'] = 'extra_value';
                    $this->params['filters']['extra'][$prop_id]['from'][$val_id] = (float)$values;
            }
        } elseif (preg_match("/^vpr(\d+)to(\d+)$/", $key, $arr)) {
            $prop_id = (int)$arr[1];
            $val_id = (int)$arr[2];
            if ($prop_id > 0 && $val_id > 0 && !empty($values)) {
                    $this->params['filters']['extra'][$prop_id]['field'] = 'extra_value';
                    $this->params['filters']['extra'][$prop_id]['to'][$val_id] = (float)$values;
            }
        } elseif (preg_match("/^pr(\d+)$/", $key, $arr)) {
//flags Y|N && ids
          $prop_id = (int)$arr[1];
          if ($prop_id > 0 ) {
            $isFlag = false;
            if (is_array($values)) {
              $tmp = current($values);
              if (!is_array($tmp)) {
                $isFlag = in_array(strtoupper($tmp), ['Y', 'N']);
              }
            } else {
              $isFlag = in_array(strtoupper($values), ['Y', 'N']);
            }
            if ($isFlag) {
              if (!is_array($values)) {
                $values = [$values];
              }
              $this->params['filters']['properties'][$prop_id] =
                  [
                    'field' => 'values_flag',
                    'values' => array_map(function ($v) { return (strtoupper($v) == 'Y'?1:0); }, $values),
                  ];
            } else {
              $vList = [];if (is_array($values)) foreach($values as $_ix => $value){ if (preg_match("/[\d]+\,/", $value)){ $vList = array_merge($vList, array_map('intval', explode(",", $value))); } else { $vList = $values; }}
              if (!isset($this->params['filters']['properties'][$prop_id])) {
              $this->params['filters']['properties'][$prop_id] =
                  [
                    'field' => 'values_id',
                    'values' => array_map('intval', $vList),
                  ];
              } else {
                $this->params['filters']['properties'][$prop_id]['values'] = array_merge($this->params['filters']['properties'][$prop_id]['values'], array_map('intval', $vList));
              }
            }
          }
        } elseif (preg_match("/^at(\d+)$/", $key, $arr)) {
// Attributes selected
          $attr_id = (int)$arr[1];
          if ($attr_id > 0) {
              $this->params['filters']['attributes'][$attr_id] =
                  [
                    'values' => array_map('intval', $values),
                  ];
          }
        }
      }
    }
    unset($this->params['get']);
  }
    /**
 * build Query object with few (2) columns: products_id and columns from sort_order (mysql restriction)
 * and all restrictions from params
 * @return self
 */
  public function buildQuery($params = null) {
    if ($params === $this->params) {
      return $this;
    }

    if (!is_array($params)) {
      $params = $this->params;
    }

    if (empty($params['orderBy']) && empty($params['get']['sort'])) {
      if (!empty($params['currentCategory']) && (int)$this->getCurrentCategoryId()>0) {
        $cat = $this->getCurrentCategoryId();
      } else {
        $cat = 0;
      }
      $defSort = \common\helpers\Sorting::getDefaultSortOrder($cat);
      if (!empty($defSort)) {
        $_tmp = $this->params; //probably not required and it's ok to add orderby to current instance.....
        $this->params['get']['sort'] = $defSort;
        $this->filtersFromGet();
        if (!empty($this->params['orderBy'])) {
          $params['orderBy'] = $this->params['orderBy'];
        }
        $this->params = $_tmp;
      }
    }

    $this->init();
    if (is_array($params)) {
      foreach ($params as $k => $v) {
        $method = 'add' . ucfirst($k);
        if ($v!==false && method_exists($this, $method)){
          $this->{$method}($v);
        } elseif ($v!==false) {
          \Yii::warning("Build Query not exists $method ");
        }
      }
    }
    return $this;
  }
  
  public function addCurrentCustomerGroup() {
    if (!empty($this->params['currentCustomerGroup']) && $this->params['currentCustomerGroup']) {
      /* @var $ext \common\extensions\UserGroupsRestrictions\UserGroupsRestrictions */
      $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
      if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'isAllowed')) {
        if ($ext::isAllowed()) {
          $this->query->andWhere(['exists', \common\models\GroupsProducts::find()
                                            ->where('p.products_id = products_id')
                                            ->andWhere([
                                                    'groups_id' => (int)$customer_groups_id,
                                                  ])
            ]);

        }
      }
    }
  }

  public function addAnyExists() {
    //do nothing - only to skip caching
  }

  public function addGet() {
    //do nothing 
  }

  public function addFeaturedTypeId() {
    //do nothing 
  }

  public function addActive () {
    if (!empty($this->params['active']) && $this->params['active']) {
      /* @var $ext \common\extensions\ShowInactive\ShowInactive */
      if ($ext = \common\helpers\Acl::checkExtension('ShowInactive', 'getState')) {
        $this->query->andWhere($ext::getState(false));
      } else {
        $this->query->andWhere(' p.products_status = 1 ');
      }
    }

  }

  public function addOutOfStock () {

    if (!SHOW_OUT_OF_STOCK) {
      if (is_null($this->backorderStockIndicationIds)) {
        $this->backorderStockIndicationIds = \common\models\ProductsStockIndication::find()
            ->where(['allow_out_of_stock_checkout' => 1, 'allow_out_of_stock_add_to_cart' => 1])
            ->select('stock_indication_id')->asArray()->column();
      }
      $q = "p.stock_indication_id = 0 and p.products_quantity > 0";
      if (!is_null($this->backorderStockIndicationIds )) {
        $q = ['or',
              ['stock_indication_id' => $this->backorderStockIndicationIds],
              $q
            ];
      }
      $this->query->andWhere($q);
    }
 }

  public function addHasSubcategories() {

  }

/*vl2do */
  public function addOnlyWithImages () {

  }


  public function addLimit($limit = null) {
    if ((!empty($limit) && (int)$limit>0)||
        (!empty($this->params['limit']) && (int)$this->params['limit']>0)) {
      $this->query->limit((int)$limit>0?(int)$limit:$this->params['limit']);
    } else {
      //$this->query->limit(100000);
    }
    return $this;
  }
  public function addOffset($offset = null) {
    if ((!empty($offset) && (int)$offset>0)||
        (!empty($this->params['offset']) && (int)$this->params['offset']>0)) {
      $this->query->offset((int)$offset>0?(int)$offset:$this->params['offset']);
    }
    return $this;
  }
  public function addCustomAndWhere($params = null) {
    if (!$params) {
      $params = $this->params['customAndWhere'];
    }
    if (is_string($params)) {
      $params = trim($params);
    }
    if (!empty($params)) {
      if (is_array($params)) {
        $this->query->andWhere($params);
      } else {
        $this->query->andWhere($params);
      }
    }
  }

  public function addOrderBy($params = null) {
    if (empty($params)) {
      $params = $this->params['orderBy'];
    }
    if (!empty($params)) {
      //transform string to arrray
      if (!is_array($params) && !empty(trim($params))) {
        $params = explode(',', $params);
        if (is_array($params)) {
          $ar = [];
          foreach ($params as $v) {
            $v = trim($v);
            $offset = strpos($v, ' ');
            if ($offset) {
              $dir = strtolower(trim(substr($v, $offset+1)));
              $ar[substr($v, 0, $offset)] = ($dir=='desc' ? SORT_DESC : SORT_ASC);
            } else {
              $ar[$v] = SORT_ASC;
            }
          }
          $params = $ar;
        }
      }
      if (!empty($params)) {
        foreach ($params as $field => $direction) {
          $knownTable = false;
          switch ($field) {
            case 'products_name':
              $this->query->joinWith('listingName');
              $knownTable = $field;
              break;
            case 'bestsellers':
              $knownTable = '(p.products_ordered+p.popularity_bestseller)';
              break;
            case 'products_model':
            case 'products_date_added':
            case 'products_popularity':
            case 'products_weight': //2do (attributes)
              $knownTable = 'p.' . $field;
            break;
            case 'manufacturers_name':
              $this->query->joinWith('manufacturer');
              $knownTable = $field;
              break;
            case 'products_price':
              if (!defined('GROUPS_IS_SHOW_PRICE') || GROUPS_IS_SHOW_PRICE == true) {
                if (!isset($this->params['filters']['price']) || (self::PRESELECT_PRODUCST_PRICE_IDS)) {
                  $this->query->joinWith('listingPrice');
                }
                 //2do if $groups_id>0 - get discount

                if (DISPLAY_PRICE_WITH_TAX == 'true') {
                  $taxRates = \common\models\TaxRates::find()->asArray()->all();
                } else {
                  $taxRates = false;
                }


                $taxRateM = '';
                if ($taxRates) {
                  foreach($taxRates as $v) {
                    $taxRateM .= ' WHEN ' . (int)$v['tax_class_id'] . ' THEN 1+'. (float)  \common\helpers\Tax::get_tax_rate($v['tax_class_id'])/100;
                  }
                }
                $ext = \common\helpers\Acl::checkExtension('ProductPriceIndex', 'reindex');
                if ($ext && $ext::isEnabled()) {
                  $knownTable = [
                    'products_final_price' => new \yii\db\Expression(
                      'if(products_special_price_min>0, products_special_price_min, products_price_min) '
                      . ($taxRates?' * (case ifnull(' . \common\extensions\ProductPriceIndex\models\ProductPriceIndex::tableName() . '.products_tax_class_id,0) ' . $taxRateM . ' else 1 end)':'')
                    )
                    ];
                } else {
                  $knownTable = [
                    'products_final_price' => new \yii\db\Expression(
                      //'if(products_special_price>0, products_special_price, products_price) '
                      'products_price '
                      .  ($taxRates?' * (case products_tax_class_id ' . $taxRateM . ' else 1 end)':'')
                    )
                    ];
                }
              }
              break;
            case 'products_quantity': // most probably slow ... 2do dependant on per warehoouse availability
              $this->query->joinWith('deliveryTerm products_listing_delivery_term');
              $knownTable = 'if({{%products}}.products_quantity>0, {{%products}}.products_quantity, (if({{%products}}.stock_delivery_terms_id=0?"z",products_listing_delivery_term.sort_order))';
              break;
            case 'rand()':
              $knownTable = 'rand()';
              break;
            case 'gso':
                if ($this->params['currentCategory'] && $this->getCurrentCategoryId() > 0  && !$this->params['hasSubcategories']) {
                  if ($this->params['limit']<1000) {
                    $useIndex = ' USE INDEX (categories_id)';
                  } else {
                    $useIndex = '';
                  }
                  $this->query->innerJoin('{{%products_to_categories}} ' . $useIndex, '({{%products_to_categories}}.products_id=p.products_id) ')
                    ->andWhere(['{{%products_to_categories}}.categories_id' => $this->getCurrentCategoryId()])
                    ->addOrderBy('{{%products_to_categories}}.sort_order, {{%products_to_categories}}.products_id desc')
                    ->addSelect('{{%products_to_categories}}.sort_order, {{%products_to_categories}}.products_id ');
                } else {
                  //$this->query->joinWith('listingGlobalSort', false, 'inner join')
                  $this->query->joinWith('listingGlobalSort')
                        ->addOrderBy('{{%products_global_sort}}.sort_order desc')
                        ->addSelect('{{%products_global_sort}}.sort_order ');
                }
              break;
            default:
              if (strtoupper(substr(trim($field), 0, 6)) == 'FIELD ') {
                $this->query->addOrderBy([(new \yii\db\Expression($field))]);
              }
              break;
          }
          if ($knownTable) {
            if (is_array($knownTable)) {
              $f = (string) array_shift($knownTable);
            } else {
              $f = $knownTable;
            }
            $this->query->addOrderBy([$f => $direction]);
            $this->query->addSelect($knownTable);
          } /*else {
            $this->query->joinWith('listingGlobalSort')
                ->addOrderBy('{{%products_global_sort}}.sort_order desc')
                ->addSelect('{{%products_global_sort}}.sort_order ');

            if ($this->params['currentCategory'] && $this->getCurrentCategoryId() > 0) {
              $this->query->joinWith('getCategoriesList')
                ->andWhere(['{{%products_to_categories}}.categories_id' => $this->getCurrentCategoryId()])
                ->addOrderBy('{{%products_to_categories}}.sort_order ')
                ->addSelect('{{%products_global_sort}}.sort_order ');
            }

            $this->query->addOrderBy('products_date_added desc')
                ->addSelect('products_date_added ');
          }*/
        }
      }
    } else {
      if ($this->params['filters']['keywords'] && !empty($this->relevanceOrder)) {
        //2do relevance
        $this->query->joinWith('listingName');
        $this->query->addOrderBy((new \yii\db\Expression($this->relevanceOrder)));
        
      } else {

            if ($this->params['currentCategory'] && $this->getCurrentCategoryId() > 0  && !$this->params['hasSubcategories']) {
              if ($this->params['limit']<1000) {
                $useIndex = ' USE INDEX (categories_id)';
              } else {
                $useIndex = '';
              }
              $this->query->innerJoin('{{%products_to_categories}} ' . $useIndex, '({{%products_to_categories}}.products_id=p.products_id) ')
                ->andWhere(['{{%products_to_categories}}.categories_id' => $this->getCurrentCategoryId()])
                ->addOrderBy('{{%products_to_categories}}.sort_order, {{%products_to_categories}}.products_id desc')
                ->addSelect('{{%products_to_categories}}.sort_order, {{%products_to_categories}}.products_id ');
            } else {
              //$this->query->joinWith('listingGlobalSort', false, 'inner join')
              $this->query->joinWith('listingGlobalSort')
                    ->addOrderBy('{{%products_global_sort}}.sort_order desc')
                    ->addSelect('{{%products_global_sort}}.sort_order ');
            }
/*
            $this->query->addOrderBy('products_date_added desc')
                 ->addSelect('products_date_added');*/
      }
    }
    return $this;
  }
  protected function addAttributes($params = null) {
    if (empty($params)) {
      $params = $this->params['filters']['attributes'];
    }
    if (!empty($params)) {
      $atFilters = $params;
      foreach ($atFilters as $oId => $vIds) {
        $cntQ = \common\models\ProductsAttributes::find()->alias('pat');
        $condition = [
          'and',
          ['pat.options_id' => $oId],
        ];
        if (!empty($vIds['values']) ) {
          $condition[] = ['pat.options_values_id' => $vIds['values']];
          $cntQ->andWhere([
            'and',
            'p.products_id=pat.products_id',
            $condition
          ]);
          $this->query->andWhere(['exists',$cntQ]);
        }
      }
//echo $this->query->createCommand()->rawSql . ' addProperties <br>';
    }
  }
  
    protected function addExtra($params = null) {
        if (empty($params)) {
            $params = $this->params['filters']['extra'];
        }
        if (is_array($params)) {
            foreach ($params as $pId => $pV) {
                if (!isset($this->params['filters']['properties'][$pId])) {
                    continue;
                }
                $cntQ = \common\models\Properties2Propducts::find()->alias('pr2p');
                
                $condition = [
                    'and',
                    ['pr2p.properties_id' => $pId],
                ];
                if (isset($pV['from']) && is_array($pV['from'])) {
                    foreach ($pV['from'] as $vId => $from) {
                        if ($from > 0 && !empty($pV['field'])) {
                            $condition[] = [
                                'and',
                                ['pr2p.values_id' => $vId],
                                ['>=', $pV['field'], $from],
                            ];
                        }
                    }
                }
                if (isset($pV['to']) && is_array($pV['to'])) {
                    foreach ($pV['to'] as $vId => $to) {
                        if ($to > 0 && !empty($pV['field'])) {
                            $condition[] = [
                                'and',
                                ['pr2p.values_id' => $vId],
                                ['<=', $pV['field'], $to],
                            ];
                        }
                    }
                }
                
                
                $cntQ->andWhere([
                    'and',
                    'p.products_id=pr2p.products_id',
                    $condition
                ]);
                $this->query->andWhere(['exists',$cntQ]);
            }
        }
    }

    protected function addProperties($params = null) {
    if (empty($params)) {
      $params = $this->params['filters']['properties'];
    }
    if (!empty($params)) {
      $prFilters = $params;
      foreach ($prFilters as $oId => $vIds) {
        $valOk = $valRequired = false;
        $cntQ = \common\models\Properties2Propducts::find()->alias('pr2p');
        if (!empty($vIds['field']) && in_array($vIds['field'], ['values_number', 'values_number_upto', 'values_alt'])  ) {
          // for range the values table is required
          $valRequired = true;
        }
        $condition = [
          'and',
          ['pr2p.properties_id' => $oId],
        ];
        if (!empty($vIds['from']) && abs($vIds['from'])>0 && !empty($vIds['field'])) {
          $condition[] = ['>=',  $vIds['field'], $vIds['from']];
          $valOk = true;
        }
        if (!empty($vIds['to']) && abs($vIds['to'])>0 && !empty($vIds['field'])) {
          $condition[] = ['<=', $vIds['field'], $vIds['to']];
          $valOk = true;
        }
        if (!empty($vIds['values']) && !empty($vIds['field'])) {
          $condition[] = ['pr2p.' . $vIds['field'] => $vIds['values']];
          $valOk = true;
        }
        if ($valOk) {
          if ($valRequired) {
            $cntQ->joinWith(['propertiesValue'], false);
          }
          $cntQ->andWhere([
            'and',
            'p.products_id=pr2p.products_id',
            $condition
          ]);
          $this->query->andWhere(['exists',$cntQ]);
        }
      }
//echo $this->query->createCommand()->rawSql . ' addProperties <br>';
    }
  }

  public function addPrice($params = null) {
    if (defined('GROUPS_IS_SHOW_PRICE') && GROUPS_IS_SHOW_PRICE == false){
      return $this;
    }
    if (empty($params)) {
      $params = $this->params['filters']['price'];
    }
    if ($params['from']>0 || $params['to']>0) {
      if (self::PRESELECT_PRODUCST_PRICE_IDS) {
        $params = $this->params;
        unset($params['filters']);
        unset($params['orderBy']);
        $params['filters']['price'] = $this->params['filters']['price'];
      }
      self::cleanupParams($params);
      $pk = serialize($params);
// already have products_id list in cache for these parameters
      if (self::PRESELECT_PRODUCST_PRICE_IDS && is_array($this->listProductIds[$pk])) {
        $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
        return $this;
      }
      
     
      if (self::PRESELECT_PRODUCST_PRICE_IDS) {
        // need to save ids in cache - clone query params and build/execute/cache result of new query
        unset($params['filters']);
        $q = clone ($this);

        $q = $q->buildQuery($params);
        $q->query->joinWith('listingPrice', false, ' inner join ');
        $q = $q->getQuery();
        $params = $this->params['filters']['price'];
      } else {
        // join price tables to currenct query
        $q = $this->getQuery();
      }
      /* @var $q \common\models\queries\ProductsQuery  */

      
      
      if (USE_MARKET_PRICES == 'True') {
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        $currencies_id = (int)$currencies->currencies[$currency]['id'];
      } else {
        $currencies_id = 0;
      }
      $groups_id =  (int) \Yii::$app->storage->get('customer_groups_id');

/* @var $ext \common\extensions\ProductPriceIndex\ProductPriceIndex */
      $ext = \common\helpers\Acl::checkExtension('ProductPriceIndex', 'reindex');
      if ($ext && $ext::isEnabled()) {

        $tmp = [];
        if (DISPLAY_PRICE_WITH_TAX == 'true' ) {
          $taxRates = \common\extensions\ProductPriceIndex\models\ProductPriceIndex::find()
            ->andWhere([
              'currencies_id' => $currencies_id,
              'groups_id' => $groups_id,
              'products_status' => 1
             ])
            ->select('products_tax_class_id')
            ->distinct()
            ->asArray()->column();

          if ($taxRates)
          foreach ($taxRates as $v) {
            $r = 1+(float)\common\helpers\Tax::get_tax_rate($v)/100;
            if (!isset($tmp[$r])) {
              $tmp["$r"] = [];
            }
            $tmp["$r"][] = $v;
          }
        }
        if (count($tmp)==0) {
           $tmp["0"][] = 0;
        }

        if (count($tmp)>1) {
          $cond = [];
          foreach($tmp as $rate => $ids) {
            $each = [];
            if ($params['from']>0 ){
              $each[] = [
                'and',
                ['>=', 'products_price_min', $params['from']/($rate>0?$rate:1)],
                ['=', \common\extensions\ProductPriceIndex\models\ProductPriceIndex::tableName() . '.products_tax_class_id', $ids]
              ];
            }
            if ($params['to']>0 ){
              $each[] = [
                'and',
                ['<=', 'products_price_min', $params['to']/($rate>0?$rate:1)],
                ['=', \common\extensions\ProductPriceIndex\models\ProductPriceIndex::tableName() . '.products_tax_class_id', $ids]
              ];
            }
            if (count($each)==2) {
              $cond[] = ['and', $each[0], $each[1]];
  //            $cond[] = ['and', $each];
            } else {
              $cond[] = $each[0];
            }
          }
          array_unshift($cond, 'or');
//echo "#### <PRE>" .print_r($cond, 1) ."</PRE>"; die;

          $q->andWhere(
             $cond
           );

        } else {
          $rate = array_keys($tmp);
          if ($params['from']>0 ){
            $q->andWhere([
              '>=', 'products_price_min', $params['from']/($rate[0]>0?$rate['0']:1)
            ]);
          }
          if ($params['to']>0 ){
            $q->andWhere([
              '<=', 'products_price_min', $params['to']/($rate[0]>0?$rate['0']:1)
            ]);
          }
        }
//echo "<BR> price" . $q->createCommand()->rawSql;
      } else {
        //no flat price table - joined product_prices table (only, ignore attributes and inventory)
         //2do if $groups_id>0 - get discount

        $taxRates = \common\models\Products::find()
            ->andWhere([
              'products_status' => 1
             ])
            ->select('products_tax_class_id')
            ->distinct()
            ->asArray()->column();

        $tmp = [];
        if (DISPLAY_PRICE_WITH_TAX == 'true' && $taxRates) {
          foreach ($taxRates as $v) {
            $r = 1+(float)\common\helpers\Tax::get_tax_rate($v)/100;
            if (!isset($tmp[$r])) {
              $tmp["$r"] = [];
            }
            $tmp["$r"][] = $v;
          }
        }

        if (count($tmp)>1) {
          $cond = [];
          foreach($tmp as $rate => $ids) {
            $each = [];
            if ($params['from']>0 ){
              $each[] = [
                'and',
                ['>=', (new \yii\db\Expression('if(products_group_price>0, products_group_price,products_price)')), $params['from']/($rate>0?$rate:1)],
                ['=', 'p.products_tax_class_id', $ids]
              ];
            }
            if ($params['to']>0 ){
              $each[] = [
                'and',
                ['<=', (new \yii\db\Expression('if(products_group_price>0, products_group_price,products_price)')), $params['to']/($rate>0?$rate:1)],
                ['=', 'p.products_tax_class_id', $ids]
              ];
            }
            if (count($each)==2) {
              $cond[] = ['and', $each[0], $each[1]];
            } else {
              $cond[] = $each[0];
            }
          }
          array_unshift($cond, 'or');

          $q->andWhere(
             $cond
           );

        } elseif (count($tmp)>0) {
          $rate = array_keys($tmp);
          if ($params['from']>0 ){
            $q->andWhere([
              '>=', (new \yii\db\Expression('if(products_group_price>0, products_group_price,products_price)')), $params['from']/($rate[0]>0?$rate['0']:1)
            ]);
          }
          if ($params['to']>0 ){
            $q->andWhere([
              '<=', (new \yii\db\Expression('if(products_group_price>0, products_group_price,products_price)')), $params['to']/($rate[0]>0?$rate['0']:1)
            ]);
          }
        }

      }

      if (self::PRESELECT_PRODUCST_PRICE_IDS) {
//echo " <BR> $pk <br>" . $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->createCommand()->rawSql;
//temp kostyl for php 72
$s = \Yii::$app->db->createCommand($q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->createCommand()->rawSql)->queryColumn();
        //if ($q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->count() < 100000) {//
        if (count($s) < 100000) {//
          //$this->listProductIds[$pk] = $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->asArray()->column();
          $this->listProductIds[$pk] = $s;
          $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
  //echo " <BR><BR>" . $this->query->createCommand()->rawSql;
        //echo " count " . count($this->listProductIds[$pk]);
        //unset($this->listProductIds[$pk]);
        } else {
          $this->query->andWhere(['p.products_id' => $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")]);
        }
      }
    }

    return $this;
  }

  public function addKeywords($params = null) {
    if (empty($params)) {
      $params = $this->params['filters']['keywords'];
    }
    if (!empty($params)) {
//echo 'bfore <br>' . $this->query->createCommand()->rawSql . '<BR>';
       if (isset($this->params['filters']['keywords'])) {
            $this->params['filters']['keywords'] = (string)$this->params['filters']['keywords'];
       }
      if (self::PRESELECT_PRODUCST_SEARCH_IDS) {
        $params = $this->params;
        unset($params['filters']);
        unset($params['orderBy']);
        $params['filters']['keywords'] = $this->params['filters']['keywords'];
      }
      self::cleanupParams($params);
      $pk = serialize($params);

      if (self::PRESELECT_PRODUCST_SEARCH_IDS && is_array($this->listProductIds[$pk])) {
        $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
        return $this;
      }


      $searchBuilder = new \common\components\SearchBuilder('simple');
      $searchBuilder->parseKeywords($this->params['filters']['keywords']);

      $kws = $searchBuilder->getParsedKeywords();

      /// plain
      /* @var $ext \common\extensions\PlainProductsDescription\PlainProductsDescription */
      $ext = \common\helpers\Acl::checkExtension('PlainProductsDescription', 'reindex');
      if ($ext && $ext::isEnabled()) {
        if (defined('MSEARCH_ENABLE') && strtolower(MSEARCH_ENABLE)=='fulltext') {
          $kws = \common\extensions\PlainProductsDescription\PlainProductsDescription::validateKeywords($kws, true);
        } else {
          $kws = \common\extensions\PlainProductsDescription\PlainProductsDescription::validateKeywords($kws);
        }
        if (!is_array($kws)) {
          // all keywords too short or common
          return;
        }
      }

      if (self::PRESELECT_PRODUCST_SEARCH_IDS) {
        unset($params['filters']);
        $q = clone ($this);

        $q = $q->buildQuery($params);
        $q->query->joinWith('listingName', false);
        $q = $q->getQuery();
        $params = $kws;
      } else {
        $q = $this->getQuery();
      }
      /* @var $q \common\models\queries\ProductsQuery  */


      /// plain
      if ($ext && $ext::isEnabled()) {

        if (defined('MSEARCH_ENABLE') && strtolower(MSEARCH_ENABLE)=='fulltext') {
  
          if (is_array($params)) {
            $q->andWhere('match( {{%plain_products_name_search}}.search_details ) against(:kw)', [':kw' => implode(' ', $params)]);
            $this->relevanceOrder = \Yii::$app->db->createCommand('match( {{%plain_products_name_search}}.search_details ) against(:kw)', [':kw' => implode(' ', $params)])->rawSql;
          }
          
        } else {
          //always like by "search" field

          $params = \common\extensions\PlainProductsDescription\PlainProductsDescription::validateKeywords($params);

          if (is_array($params)) {
            $f = ['like', '{{%plain_products_name_search}}.search_details', $params];
            //highest/extra relevance by name (all keywords in the name)
            $relevanceF = ['like', '{{%plain_products_name_search}}.products_name', $params];

            $tmp = $tmpF = [];
          
            foreach ($params as $param) {
              $tmp[] = \Yii::$app->db->createCommand('-100/if(LOCATE( :kw , ' . $f[1] . ')>0, LOCATE( :kw , ' . $f[1] . '), -100)', [':kw' => $param])->rawSql;
              $tmpF[] = \Yii::$app->db->createCommand($relevanceF[1] . ' '. $relevanceF[0] . ' :kw', [':kw' => '%'. $param . '%'])->rawSql;
            }

            if (defined('MSEARCH_ENABLE')  && (strtolower(MSEARCH_ENABLE)=='true' || strtolower(MSEARCH_ENABLE)=='soundex')) {
              //+ or like by soundex field
              $fs = ['like', '{{%plain_products_name_search}}.search_soundex', $params];
              $tmps = \common\extensions\PlainProductsDescription\PlainProductsDescription::getSoundex(implode(' ', $params), false);
              if (is_array($tmps )) {
                $tmps = array_map(function ($el) {return ',' . $el . ',';}, $tmps );
                $f = ['or',
                      $f,
                      ['like', '{{%plain_products_name_search}}.search_soundex', $tmps]
                     ];
                foreach ($tmps as $param) {
                  $tmp[] = \Yii::$app->db->createCommand('-10/if(LOCATE( :kw , ' . $fs[1] . ')>0, LOCATE( :kw , ' . $fs[1] . '), -10)', [':kw' => $param])->rawSql;
                }
              }
            }

            $this->relevanceOrder = '(' . implode(' and ', $tmpF) . ') desc, (' .implode(' + ', $tmp) . ')';

            $q->andWhere($f);
          }

        }

      } else {
      // else - exists in description
        if (defined('MSEARCH_ENABLE') && strtolower(MSEARCH_ENABLE)=='fulltext') {
          $q->andWhere('match( {{%products_description}}.products_name ) against(:kw) or match( {{%products_description}}.products_description) against(:kw)', [':kw' => $params]);

        } else {
          //like by name and description in the description table
          $f = ['like', 'concat({{%products_description}}.products_name, {{%products_description}}.products_description)', $params];

          $q->andWhere($f);

          if (is_array($params)) {
            $f = ['like', '{{%products_description}}.products_description', $params];
            $relevanceF = ['like', '{{%products_description}}.products_name', $params];

            $tmp = $tmpF = [];

            foreach ($params as $param) {
              $tmp[] = \Yii::$app->db->createCommand('LOCATE( :kw , ' . $f[1] . ')', [':kw' => $param])->rawSql;
              $tmpF[] = \Yii::$app->db->createCommand($relevanceF[1] . ' '. $relevanceF[0] . ' :kw', [':kw' => '%'. $param . '%'])->rawSql;
            }

            $this->relevanceOrder = '(' . implode(' and ', $tmpF) . ') desc, (' .implode(' + ', $tmp) . ')';
          }
          
        }

      }
//echo " <BR><BR>" . $q->createCommand()->rawSql . ' ' . $this->relevanceOrder;

      if (self::PRESELECT_PRODUCST_SEARCH_IDS) {
//echo " <BR><BR> $pk <br>" . $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->createCommand()->rawSql;
        if ($q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->count() < 100000) {//
          $this->listProductIds[$pk] = $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")->asArray()->column();
          $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
  //echo " <BR><BR>" . $this->query->createCommand()->rawSql;
        //echo " count " . count($this->listProductIds[$pk]);
        //unset($this->listProductIds[$pk]);
        } else {
          $this->query->andWhere(['p.products_id' => $q->offset(null)->limit(null)->orderBy(null)->select("p.products_id")]);
        }
        return $this;

      }

    }
  }

  public function addFilters($params = null) {
    if (empty($params)) {
      $params = $this->params['filters'];
    }
    if (!empty($params)) {
      foreach ($params as $k => $v) {
        $method = 'add' . ucfirst($k);
        if ($v!==false && method_exists($this, 'add' . ucfirst($k))){
          $this->$method($v);
        } else {
          \Yii::warning("AddFilter not exists " . 'add' . ucfirst($k) );
        }
      }
    }
  }

  public function addCurrentPlatform($params = null) {
    if (empty($params)) {
      $params = $this->params['currentPlatform'];
    }
    if ($params) {
      $this->query->andWhere(['exists',
                              (new Query())->from(['plp' => TABLE_PLATFORMS_PRODUCTS])
                                           ->where('p.products_id = plp.products_id')
                                           ->andWhere(['plp.platform_id' => platform::currentId()])
                            ]);
    }
  }
/**
 * Sub-query: selected categories or incl. their sub-categories (according theme settings)
 * @param array|int $current_category_id
 * @return QueryBuilder
 */
  private function childrenCategoriesQuery($current_category_id = []) {
    if (!is_array($current_category_id)) {
      $current_category_id = [$current_category_id];
    }

    sort($current_category_id);
    $pk = implode(',', $current_category_id);

    if (self::PRESELECT_PRODUCST_CATEGORIES_IDS && is_array($this->listProductIds[$pk])) {
      $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
      return $this;
    }

    $p2cQuery = (new Query())->from(['p2c_cur' => TABLE_PRODUCTS_TO_CATEGORIES])
                              ->innerJoin(['c1' => TABLE_CATEGORIES], 'c1.categories_id=p2c_cur.categories_id and c1.categories_status=1');

    if (\frontend\design\Info::themeSetting('show_products_from_subcategories')) {
      $p2cQuery->innerJoin(['c2' => TABLE_CATEGORIES], 'c1.categories_left>=c2.categories_left and c1.categories_right<=c2.categories_right')
          ->andWhere(['c2.categories_id' => $current_category_id])
          ->andWhere(['c2.categories_status' => 1]);
    } else {
      $p2cQuery->andWhere(['c1.categories_id' => $current_category_id]);
      $p2cQuery->andWhere(['c1.categories_status' => 1]);
    }

    if (!$this->params['anyExists'] && self::PRESELECT_PRODUCST_CATEGORIES_IDS  && $this->listProductIds[$pk] = $p2cQuery->select('p2c_cur.products_id')->distinct()->count()<100000){
      $this->listProductIds[$pk] = $p2cQuery->select('p2c_cur.products_id')->distinct()->column();
      $this->query->andWhere(['p.products_id' => $this->listProductIds[$pk]]);
    } else {
      $p2cQuery->andWhere('p.products_id = p2c_cur.products_id');
      $this->query->andWhere(['exists', $p2cQuery]);
    }

    return $this;
  }

  public function addCurrentCategory($params = null) {
    if (empty($params)) {
      $params = $this->params['currentCategory'];
    }
    if ($params && $this->getCurrentCategoryId() > 0) {
      $this->childrenCategoriesQuery($this->getCurrentCategoryId());
    }
  }

  public function addCategories($categories_ids = []) {
    if (is_array($categories_ids) && count($categories_ids)> 0) {
      $categories_ids = array_map('intval', $categories_ids);
      $this->childrenCategoriesQuery($categories_ids);
    }
  }

  public function addManufacturers($manufacturers_ids = []) {
    if (!empty($manufacturers_ids)) {
      if (!is_array($manufacturers_ids) ) {
        $manufacturers_ids = [$manufacturers_ids];
      }
      $manufacturers_ids = array_map('intval', $manufacturers_ids);
      $this->query->andWhere(['in', 'p.manufacturers_id', $manufacturers_ids]);
    }
  }

  public function addPage($page = null) {
    if (empty($page)) {
      $page = $this->params['page'];
    }
    switch ($page) {
      case 'catalog/all-products':
      default:
        break;
      case 'catalog/sales':
      case 'sales':
          $this->query->andWhere(['exists', \common\models\Specials::find()
                                            ->where('p.products_id = products_id')
                                            ->andWhere([
                                                    'status' => 1,
                                                  ])
            ]);
        break;
/*VL2do */
      case 'catalog/featured-products':
      case 'featured-products':
      case 'featured':
/*2do */
          if (tep_session_is_registered('affiliate_ref') && $_SESSION['affiliate_ref'] != '') {
            $ids = [(int)$_SESSION['affiliate_ref'], 0];
          } else {
            $ids = 0;
          }
          if (!empty($this->params['featuredTypeId'])) {
            if (is_array($this->params['featuredTypeId'])) {
              $typeId = array_map('intval', $this->params['featuredTypeId']);
            } else {
              $typeId = intval($this->params['featuredTypeId']);
            }

          } else {
            $typeId = 0;
          }
 /**/
          $this->query->andWhere(['exists', \common\models\Featured::find()
                                            ->where('p.products_id = products_id')
                                            ->andWhere([
                                                    'status' => 1,
                                                    'affiliate_id' => $ids,
                                                    'featured_type_id' => $typeId
                                                  ])
            ]);

        break;
    }
  }

  public function getQuery() {
    return $this->query;
  }
  public function getParams() {
    return $this->params;
  }
  /**
   *
   * @param type $db
   * @return array [0 => pid1, 1 => pid2, ...]
   */
  public function allIds($db = null) {
    $r = $this->getQuery()->column($db);
    return $r;
  }
  /**
   *
   * @param type $db
   * @return array [0 => [products_id => pid1], ...]
   */
  public function all($db = null) {
    $r = $this->getQuery()->all($db);
    return $r;
  }

  /**
   *
   * @param string $q
   * @param DBConnection $db nice to have - now could be problem
   * @return int
   */
  public function count($q="*", $db = null) {
    $key = $q;
    if (empty($this->count[$key])) {
      $this->count[$key] = $this->getQuery()->count($q, $db);
    }
    return $this->count[$key];
  }


  static protected function cleanupParams(&$params){
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        if (is_array($value)) {
          self::cleanupParams($params[$key]);
        }
      }
      $params = array_filter($params);
    }
  }

  /**
   *
   */
  private function filterProductsIds($params = null) {
    if (!$params) {
      $params = $this->params;
    }

    self::cleanupParams($params);
    $pk = serialize($params);

    if (self::PRESELECT_PRODUCST_IDS && is_array($this->listProductIds[$pk])) {
      return $this->listProductIds[$pk];
    }
    
    $r = $this->buildQuery($params)->getQuery()->offset(null)->limit(null)->orderBy(null)->select("p.products_id");
    if (!self::PRESELECT_PRODUCST_IDS) {
      return $r;
    } else {
      if ($r->count()> 100000) {
        return $r;
      } else {
        $this->listProductIds[$pk] = $r->column();
        return $this->listProductIds[$pk];
      }
    }
  }

/////////   FILTERS /////////
/**
 * filter product on listing: Attributes (called from getFiltersArray)
 * @param array $setting
 * @return array
 */
  protected function getAttributeFiltersArray($setting) {
    $name = $setting['get'];
    $ids = $setting['ids'];
    $params = $this->params;
    unset($params['limit']);
    unset($params['offset']);
    unset($params['orderBy']);
    if (isset($params['filters']['attributes'])) {
      $attrFilters = $params['filters']['attributes'];
      //unset($params['filters']['attributes']);
    } else {
      $attrFilters = [];
    }
    $q = \common\models\ProductsAttributes::find()->alias('pa')->joinWith(['productsOptions', 'productsOptionsValues'], false)
        ->andWhere(\common\models\ProductsOptions::tableName() . '.display_filter=1')
        ->andWhere(['pa.options_id' => $ids])
        ->select('pa.options_id, pa.options_values_id, products_options_name, products_options_values_name')
        ->addSelect('{{%products_options}}.products_options_sort_order, products_options_values_sort_order') //mysql requires sort columns in select
        ->addSelect(['count' => new \yii\db\Expression('count(distinct pa.products_id)')])
        ->orderBy('{{%products_options}}.products_options_sort_order, products_options_name, products_options_values_sort_order, products_options_values_name')
        ->groupBy('pa.options_values_id, products_options_values_name, products_options_name, products_options_values_sort_order')
        ->indexBy(function ($row) { return $row['options_id'] . '_' . $row['options_values_id']; } );
    if (count($attrFilters)>0) {
      $cntQ = clone $q;
    }

    $q->andWhere(['pa.products_id' => $this->filterProductsIds($params)]);
      //echo $q->createCommand()->rawSql . '<br>';
    $r = $q->asArray()->all();
    unset($q);
      //echo "#### <PRE>" .print_r($r, 1) ."</PRE>";
    if (count($attrFilters)>0) {
        //update q-ty
        foreach ($attrFilters as $optionId => $valueIds) {
          $other = $attrFilters;
          unset($other[$optionId]);
          if (is_array($other)) {
            $q = clone $cntQ;
            $params['filters']['attributes'] = $other;
            $q->andWhere([
              'pa.products_id' => $this->filterProductsIds($params),
              'pa.options_id' => $optionId
                ]);

//echo "<br>$optionId sub " .  $q->createCommand()->rawSql . '<br><br>';
            $rQ = $q->asArray()->all();
            if ($rQ && is_array($rQ)) {
              foreach ($rQ as $key => $value) {
                if (isset($r[$key])) {
                  $r[$key]['count'] = $value['count'];
                }
              }
            }
            unset($rQ);
          }
        }
      }
      $ret = [];
      foreach ($r as $v) {
        if (!isset($ret[$v['options_id']])) {
          $ret[$v['options_id']] =
              [
                'title' => $v['products_options_name'],
                'name' => $name . $v['options_id'],
                'type' => 'boxes',
                'params' => (isset($attrFilters[$v['options_id']]['values'])?($attrFilters[$v['options_id']]['values']): []),
              ];
        }
        $ret[$v['options_id']]['values'][$v['options_values_id']] =
            [
                'id'=> $v['options_values_id'],
                'text'=> $v['products_options_values_name'],
                'count'=> $v['count'],
                'selected'=> (is_array($attrFilters[$v['options_id']]['values'])?in_array($v['options_values_id'], $attrFilters[$v['options_id']]['values']):false),
            ];
      }
      unset($r);
      $sOrder = array_flip($ids);
      $retSorted = [];
      foreach ($ret as $k => $v) {
        if (is_array($v['values'])) {
          //((!defined('DISPLAY_ONE_VALUE_FILTER') || DISPLAY_ONE_VALUE_FILTER == 'True' || count($values_array) > 1 || $any_selected) && count($values_array) > 0 && $products_count > 0) {
          uasort($v['values'], ['self', 'cmpFilterValues']);
          if ((!defined('DISPLAY_ONE_VALUE_FILTER') || DISPLAY_ONE_VALUE_FILTER == 'True' || count($v['values']) > 1 || !empty($v['params'])) ) {
            $retSorted[$sOrder[$k]] = $v;
          }
        }
      }
      return $retSorted;
  }

  /**
   *
   * @param type $setting
   * @return array indexed by sort order
   */
  protected function getPropertyFiltersArray($setting) {
    $name = $setting['get'];
    $ids = $setting['ids'];
    if (empty($ids)) {
      return [];
    }
    $params = $this->params;
//echo "<PRE style='position:absolute;left:65%;top:0;z-index:100; width:35%'>" . __FILE__ .":". __LINE__ . print_r($params, 1) . "</PRE>";

    unset($params['limit']);
    unset($params['offset']);
    unset($params['orderBy']);
    if (!empty($params['filters']['properties'])) {
      $prFilters = $params['filters']['properties'];
      //unset($params['filters']['properties']);
    } else {
      $prFilters = [];
    }

    $q = \common\models\Properties2Propducts::find()->alias('pp')->joinWith(['properties', 'propertiesDescription', 'propertiesValue'], false)
        ->andWhere('{{%properties}}.display_filter=1')
        ->andWhere(['pp.properties_id' => $ids])
        ->select('pp.properties_id, pp.values_id, min(pp.extra_value) as extra_value_min, max(pp.extra_value) as extra_value_max, range_select, extra_values, properties_type, properties_units_title, decimals, pp.values_flag, properties_name, properties_description, properties_color, properties_image, values_number, values_number_upto, values_color')
        ->addSelect(['values_text' => new \yii\db\Expression('if(length(values_alt) > 0, values_alt, values_text)')]) //could be very slow
//        ->addSelect('values_text, values_alt')
        ->addSelect('{{%properties}}.sort_order, properties_values.sort_order') //mysql requires sort columns in select
        ->addSelect(['count' => new \yii\db\Expression('count(distinct pp.products_id)')])
        ->groupBy('pp.properties_id, pp.values_id, properties_type, properties_units_title, decimals, pp.values_flag, properties_name, properties_description, properties_color, properties_image, values_number, values_number_upto, values_color')
        ->addGroupBy('{{%properties}}.sort_order, properties_values.sort_order')
        ->addGroupBy(['values_text' => new \yii\db\Expression('if(length(values_alt) > 0, values_alt, values_text)')])
        ->addOrderBy('{{%properties}}.sort_order, properties_name,  properties_values.sort_order, values_number, values_text')
//        ->addGroupBy('values_text, values_alt')
        ->indexBy(function ($row) { return $row['properties_id'] . '_' . ($row['values_id']>0?$row['values_id']:$row['values_flag']) ; } )
        ;
    if (count($prFilters)>0) {
      $cntQ = clone ($q);
    } 
    $q->andWhere(['pp.products_id' => $this->filterProductsIds($params)]);


//echo $q->createCommand()->rawSql . '<br>';
    $r = $q->asArray()->all();
    unset($q);
//echo "#### <PRE STYle='position:absolute; width:50%; top:0; left:50%; z-index:100'>" .print_r($r, 1) ."</PRE>";

    if (count($prFilters)>0) {
      //update q-ty
      foreach ($prFilters as $optionId => $valueIds) {
        $other = $prFilters;
        unset($other[$optionId]);
        if (is_array($other) && !empty($prFilters[$optionId]['values'])) {
          $q = clone $cntQ;
          $params['filters']['properties'] = $other;
          $q->andWhere(['pp.products_id' => $this->filterProductsIds($params) ])
            ->andWhere(['pp.properties_id' => $optionId]);
//echo $q->createCommand()->rawSql . '<BR><BR>' . $optionId . "<BR>";
          $rr =  $q->asArray()->all();
//echo __LINE__ . "#### CNT <PRE STYle='position:absolute; width:40%; top:0; left:60%; z-index:100'>" .print_r($rr, 1) .print_r($r, 1) ."</PRE>";
//some values could be unavailable with other properties selected. (probably need to select that selected values separately)
///VL2do add all alt ids (separate AR method)...
          $vids = \yii\helpers\ArrayHelper::getColumn($rr, 'values_id');
          if (!empty(array_diff($prFilters[$optionId]['values'], $vids))) {
            $q = clone $cntQ;
            $params['filters']['properties'] = $other;
            $q->andWhere(['pp.values_id' => array_diff($prFilters[$optionId]['values'], $vids) ])
              ->addSelect(['count' => new \yii\db\Expression('0')])
              ->andWhere(['pp.properties_id' => $optionId]);
            $rrr =  $q->asArray()->all();
          } else {
            $rrr = [];
          }
          $r = array_merge($r, $rr, $rrr);
          unset($rr);
          unset($rrr);
        }
      }
    }

//echo __LINE__ . "#### CNT <PRE STYle='position:absolute; width:40%; top:0; left:0; z-index:100'>" .print_r($r, 1) ."</PRE>";
      $uniqTexts = $ret = [];
      foreach ($r as $v) {
        if (!isset($ret[$v['properties_id']])) {
          $ret[$v['properties_id']] =
              [
                'title' => $v['properties_name'] . (tep_not_null($v['properties_units_title']) ? '<span class="units-title"> <span class="units-title-text">' . $v['properties_units_title'] . '</span></span>' : ''),
                'name' => $name . $v['properties_id'],
                'color' => $v['properties_color'],
                'image' => $v['properties_image'],
                'type' => ( ($v['range_select'] == 1 && $v['extra_values'] == 1) ? 'extra' : 'boxes'),
                'params' => (isset($prFilters[$v['properties_id']]['values'])?($prFilters[$v['properties_id']]['values']): []),
              ];
          //if (in_array($v['properties_type'], ['number', 'interval'])) {          }
          if ($v['properties_type'] == 'number') {
            $ret[$v['properties_id']]['type'] = 'slider';
            $ret[$v['properties_id']]['step'] = (float) number_format(pow(10, - $v['decimals']), $v['decimals']);
            $ret[$v['properties_id']]['paramfrom'] = ($prFilters[$v['properties_id']]['from']!=0?$prFilters[$v['properties_id']]['from']:'');
            $ret[$v['properties_id']]['paramto'] = ($prFilters[$v['properties_id']]['to']!=0?$prFilters[$v['properties_id']]['to']:'');
            $ret[$v['properties_id']]['min'] = 0;
            $ret[$v['properties_id']]['max'] = 0;
          }
        }
        if (in_array($v['properties_type'], ['number', 'interval'])) {
          $v['values_text'] = (float) number_format($v['values_number'], $v['decimals']);
          if ($ret[$v['properties_id']]['min'] == 0 || $ret[$v['properties_id']]['min']>$v['values_text'] ) {
            $ret[$v['properties_id']]['min'] = $v['values_text'];
          }
          if ($ret[$v['properties_id']]['max'] == 0 || $ret[$v['properties_id']]['max']<$v['values_text'] ) {
            $ret[$v['properties_id']]['max'] = $v['values_text'];
          }
          if ($ret[$v['properties_id']]['paramfrom']>0 && (float)$ret[$v['properties_id']]['paramfrom'] < $ret[$v['properties_id']]['min'] ) {
            //'paramfrom' => (float) ($_GET[$name . 'from'] > 0 && $_GET[$name . 'from'] > $min_value ? $_GET[$name . 'from'] : ''),
            $ret[$v['properties_id']]['paramfrom'] =  $ret[$v['properties_id']]['min'];
          }
          if ($ret[$v['properties_id']]['paramto']> 0 && (float)$ret[$v['properties_id']]['paramto'] > $ret[$v['properties_id']]['max']) {
            //'paramto' => (float) ($_GET[$name . 'to'] > 0 && $_GET[$name . 'to'] < $max_value ? $_GET[$name . 'to'] : ''),
            $ret[$v['properties_id']]['paramto'] =  $ret[$v['properties_id']]['max'];
          }
        } elseif (in_array($v['properties_type'], ['flag']) && $v['values_id'] == 0) {
          if (is_array($prFilters[$v['properties_id']]['values'])){
            if (in_array(1, $prFilters[$v['properties_id']]['values'])){
              $prFilters[$v['properties_id']]['values'][] = 'Y';
              $prFilters[$v['properties_id']]['values'][] = 'y';
            }
            if (in_array(0, $prFilters[$v['properties_id']]['values'])){
              $prFilters[$v['properties_id']]['values'][] = 'N';
              $prFilters[$v['properties_id']]['values'][] = 'n';
            }
          }
          if ($v['values_flag']) {
            $v['values_id'] = 'Y';
            $v['values_text'] = TEXT_YES;
          } else {
            $v['values_id'] = 'N';
            $v['values_text'] = TEXT_NO;
          }
        }
        if ($v['properties_type'] != 'number') {
          if (!isset($uniqTexts[$v['properties_id'] . '_' . $v['values_text']])) {
            $uniqTexts[$v['properties_id'] . '_' . $v['values_text']] = $v['values_id'];
          $ret[$v['properties_id']]['values'][$v['values_id']] =
              [
                  'id'=> $v['values_id'],
                  'text'=> $v['values_text'],
                  'color'=> $v['values_color'],
                  'count'=> $v['count'],
                  'selected'=> (is_array($prFilters[$v['properties_id']]['values'])?in_array($v['values_id'], $prFilters[$v['properties_id']]['values']):''),
                  'paramfrom' => (isset($params['filters']['extra'][$v['properties_id']]['from'][$v['values_id']]) ? $params['filters']['extra'][$v['properties_id']]['from'][$v['values_id']] : ''),
                  'paramto' => (isset($params['filters']['extra'][$v['properties_id']]['to'][$v['values_id']]) ? $params['filters']['extra'][$v['properties_id']]['to'][$v['values_id']] : ''),
                  'min' => $v['extra_value_min'],
                  'max' => $v['extra_value_max'],
                  'sort_order' => $v['sort_order'] ,
              ];
          } else {
            $tmp = $uniqTexts[$v['properties_id'] . '_' . $v['values_text']];
            $ret[$v['properties_id']]['values'][$tmp]['id'] .= ',' . $v['values_id'];
            $ret[$v['properties_id']]['values'][$tmp]['count'] += $v['count'];
            if (empty($ret[$v['properties_id']]['values'][$tmp]['selected'])) {
              $ret[$v['properties_id']]['values'][$tmp]['selected'] = (is_array($prFilters[$v['properties_id']]['values'])?in_array($v['values_id'], $prFilters[$v['properties_id']]['values']):'');
            }
          }
        }
      }
      unset($r);
      $sOrder = array_flip($ids);
      $retSorted = [];
      foreach ($ret as $k => $v) {
        if (is_array($v['values'])) {
          //((!defined('DISPLAY_ONE_VALUE_FILTER') || DISPLAY_ONE_VALUE_FILTER == 'True' || count($values_array) > 1 || $any_selected) && count($values_array) > 0 && $products_count > 0) {
          uasort($v['values'], ['self', 'cmpFilterValues']);
          if ((!defined('DISPLAY_ONE_VALUE_FILTER') || DISPLAY_ONE_VALUE_FILTER == 'True' || count($v['values']) > 1 || !empty($v['params'])) ) {
            $retSorted[$sOrder[$k]] = $v;
          }
        } else {
          $retSorted[$sOrder[$k]] = $v;
        }

      }

      return $retSorted;
  }

/**
 *
 * @param array $setting
 * @return array indexed by sort order
 */
  protected function getBrandFiltersArray ($setting) {
    $name = $setting['get'];
    $pos = $setting['position'];
    $params = $this->params;
    if (!empty($params['filters']['manufacturers'])) {
      $prFilters = $params['filters']['manufacturers'];
      unset($params['filters']['manufacturers']);
      unset($params['orderBy']);

    } else {
      $prFilters = [];
    }

    $q = clone ($this->buildQuery($params)->getQuery());

    $q->offset(null)->limit(null)->orderBy(null)
          ->innerJoin(['m' => TABLE_MANUFACTURERS], 'p.manufacturers_id = m.manufacturers_id')
          ->select('m.manufacturers_id, m.manufacturers_name ')
          ->addSelect([
            'products_count' => (new \yii\db\Expression('count(distinct p.products_id)'))
          ])
          ->groupBy('m.manufacturers_id, m.manufacturers_name ')
        ;


    $r = $q->asArray()->all();

    $manufacturers_array = [];
    if (is_array($r )) {
      foreach ($r as $manufacturers) {
          $manufacturers_array[$manufacturers['manufacturers_id']] = array(
              'id' => $manufacturers['manufacturers_id'],
              'text' => $manufacturers['manufacturers_name'],
              'count' => (int)$manufacturers['products_count'],
              'selected' => (is_array($prFilters)?in_array($manufacturers['manufacturers_id'], $prFilters):''),
          );
      }
    }

    if (count($prFilters)>count($manufacturers_array)) {
      $missed = array_diff($prFilters, array_keys($manufacturers_array));

      $q =  (new \yii\db\Query())->from(['m' => TABLE_MANUFACTURERS])
          ->select('m.manufacturers_id, m.manufacturers_name ')
        ->andWhere(['m.manufacturers_id' => $missed])
        ;
      $r = $q->all();

      if ($r) {
        foreach ($r as $manufacturers) {
          $manufacturers_array[$manufacturers['manufacturers_id']] = array(
              'id' => $manufacturers['manufacturers_id'],
              'text' => $manufacturers['manufacturers_name'],
              'count' => 0,
              'selected' => (is_array($prFilters)?in_array($manufacturers['manufacturers_id'], $prFilters):''),
          );
        }
      }
    }
    uasort($manufacturers_array, ['self', 'cmpFilterValues']);

    $retSorted = [];
    if (!empty($manufacturers_array)) {
      $retSorted[$pos] = [
        'title' => TEXT_BRAND,
        'name' => $name,
        'type' => 'boxes',
        'values' => $manufacturers_array,
        'params' => $prFilters,
      ];
    }

    return $retSorted;
  }


/**
 *
 * @param array $setting
 * @return array indexed by sort order
 */
  protected function getCategoryFiltersArray ($setting) {
    $name = $setting['get'];
    $pos = $setting['position'];
    $params = $this->params;
    if (!empty($params['filters']['categories'])) {
      $prFilters = $params['filters']['categories'];
      unset($params['filters']['categories']);
      unset($params['orderBy']);

    } else {
      $prFilters = [];
    }
//echo "<PRE style='position:absolute;left:65%;top:0;z-index:100; width:35%'>" . __FILE__ .":". __LINE__ . print_r($prFilters, 1) . "</PRE>";

    $q = \common\models\Categories::find()->withProductIds()->active()->withListDescription()
        ->andWhere(['products_id' => $this->filterProductsIds($params)])
        ->select('{{%categories}}.categories_id, categories_name  ')
        ->addSelect([
          'products_count' => (new \yii\db\Expression('count(distinct products_id)'))
        ])
        ->groupBy('{{%categories}}.categories_id, categories_name  ')
        ;
//echo '<BR>' . $q->createCommand()->rawSql;
    $r = $q->asArray()->all();

    $ret = [];
    if ($r) {
      foreach ($r as $cats) {
        $ret[$cats['categories_id']] = array(
            'id' => $cats['categories_id'],
            'text' => $cats['categories_name'],
            'count' => (int)$cats['products_count'],
            'selected' => (is_array($prFilters)?in_array($cats['categories_id'], $prFilters):''),
        );
      }
    }

    if (count($prFilters)>count($ret)) {
      $missed = array_diff($prFilters, array_keys($ret));
      $q = \common\models\Categories::find()->active()->withListDescription()
        ->select('{{%categories}}.categories_id, categories_name  ')
        ->andWhere(['{{%categories}}.categories_id' => $missed])
        ;
      $r = $q->asArray()->all();

      if ($r) {
        foreach ($r as $cats) {
          $ret[$cats['categories_id']] = array(
              'id' => $cats['categories_id'],
              'text' => $cats['categories_name'],
              'count' => 0,
              'selected' => (is_array($prFilters)?in_array($cats['categories_id'], $prFilters):''),
          );
        }
      }

    }

    uasort($ret, ['self', 'cmpFilterValues']);
//echo "<PRE STYle='position:absolute; width:40%; top:200; left:60%; z-index:100'>" . __FILE__ .':' . __LINE__ . " #### " .print_r($ret, 1) ."</PRE>";
    $retSorted = [];
    if (!empty($ret)) {
      $retSorted[$pos] = [
        'title' => TEXT_CATEGORY,
        'name' => $name,
        'type' => 'boxes',
        'values' => $ret,
        'params' => $prFilters,
      ];
    }

    return $retSorted;
  }

/**
 *
 * @param array $setting
 * @return array indexed by sort order
 */
  protected function getKeywordsFiltersArray ($setting) {
    $name = $setting['get'];
    $pos = $setting['position'];
    $retSorted = [];
    $retSorted[$pos] = [
        'title' => TEXT_KEYWORDS,
        'name' => $name,
        'type' => 'input',
        'params' => (!empty($this->params['filters'][$name])?$this->params['filters'][$name]:''),
    ];

    return $retSorted;
  }


/**
 *
 * @param array $setting
 * @return array indexed by sort order
 */
  protected function getPriceFiltersArray ($setting) {
    if (defined('GROUPS_IS_SHOW_PRICE') && GROUPS_IS_SHOW_PRICE == false){
      return [];
    }
    $name = $setting['get'];
    $pos = $setting['position'];
    $currencies = \Yii::$container->get('currencies');

    $min_price = $max_price = 0;
    $container = \Yii::$container->get('products');
    $params = $this->params;
    unset($params['filters']['price']);
    unset($params['orderBy']);

    /* @var $ext \common\extensions\ProductPriceIndex\ProductPriceIndex */
    $ext = \common\helpers\Acl::checkExtension('ProductPriceIndex', 'reindex');
    if ($ext && $ext::isEnabled()) {
      $ext::checkUpdateStatus();

      $ranges = \common\extensions\ProductPriceIndex\models\ProductPriceIndex::getRange(['products' => $this->filterProductsIds($params)]);
//echo "<PRE STYle='position:absolute; width:40%; top:0; left:60%; z-index:100'>" . __FILE__ .':' . __LINE__ . " #### " .print_r( /*$ranges*/$this->filterProductsIds($params), 1) ."</PRE>";
      foreach ($ranges as $products) {
        if ($products['min_special'] >0 && $products['min_special'] < $products['min']) {
          $price = $products['min_special'];
        } else {
          $price = $products['min'];
        }
        $price = $currencies->display_price_clear($price, \common\helpers\Tax::get_tax_rate($products['products_tax_class_id']));

        if ($min_price == 0 || $price < $min_price) {
            $min_price = (float) $price;
        }

        if ($products['max_special'] > 0 && $products['max_special'] > $products['max']) {
          $price = $products['max_special'];
        } else {
          $price = $products['max'];
        }
        $price = $currencies->display_price_clear($price, \common\helpers\Tax::get_tax_rate($products['products_tax_class_id']));
        if ($max_price == 0 || $price > $max_price) {
            $max_price = (float) $price;
        }

      }
    } else {

      $r = $this->buildQuery($params)->getQuery()->offset(null)->limit(null)->orderBy(null)
          ->select("p.products_id, p.products_tax_class_id, p.products_price ")
          ->asArray() -> all();

      foreach ($r as $products) {
        $container->loadProducts($products);
        $special_price = \common\helpers\Product::get_products_special_price($products['products_id']);
        $price = \common\helpers\Product::get_products_price($products['products_id'], 1, $products['products_price']);
        if ($special_price) {
            $price = $special_price;
        }
        $price = $currencies->display_price_clear($price, \common\helpers\Tax::get_tax_rate($products['products_tax_class_id']));
        if ($min_price == 0 || $price < $min_price) {
            $min_price = (float) $price;
        }
        if ($max_price == 0 || $price > $max_price) {
            $max_price = (float) $price;
        }
      }
    }


    $retSorted = [];
    if ($max_price > $min_price) {
      $retSorted[$pos] = [
        'title' => TEXT_PRICE,
        'name' => $name,
        'type' => 'slider',
        'step' => 1,
        'min' => (int) max(0, floor($min_price)),
        'max' => (int) max(0, ceil($max_price)),
        'min_price' => $min_price,
        'max_price' => $max_price,
        'paramfrom' => ($this->params['filters']['price']['from'] > 0 && $this->params['filters']['price']['from'] > $min_price ? $this->params['filters']['price']['from'] : ''),
        'paramto' => ($this->params['filters']['price']['to'] > 0 && $this->params['filters']['price']['to'] < $max_price ? $this->params['filters']['price']['to'] : ''),
      ];
    } else {
        $retSorted[$pos] = [
            'name' => 'price_data',
            'min_price' => $min_price,
            'max_price' => $max_price,
        ];
    }
    
    return $retSorted;
  }


  public function getFiltersArray($settings) {
    $ret = $exclude = [];
    if (is_array($settings)) {
      $toProcess = [];
      foreach ($settings as $n => $f) {
        switch ($f['filters_type']) {
            case 'keywords':
              $toProcess['keywords'] = ['position' => $n, 'get' => 'keywords'];
              $exclude[] = 'keywords';
              break;
            case 'price':
              $toProcess['price'] = ['position' => $n, 'get' => 'p'];
              $exclude[] = 'pfrom';
              $exclude[] = 'pto';
              break;
            case 'category':
              $toProcess['category'] = ['position' => $n, 'get' => 'cat'];
              $exclude[] = 'cat';
              break;
            case 'brand':
              $toProcess['brand'] = ['position' => $n, 'get' => 'brand'];
              $exclude[] = 'brand';
              break;
            case 'attribute':
              $toProcess['attribute']['get'] = 'at';
              $toProcess['attribute']['ids'][$n] = $f['options_id'];
              $exclude[] = 'at' . $f['options_id'];
              break;
            case 'property':
              $toProcess['property']['get'] = 'pr';
              $toProcess['property']['ids'][$n] = $f['properties_id'];
              $exclude[] = 'pr' . $f['properties_id'];
              $exclude[] = 'pr' . $f['properties_id'] . 'from';
              $exclude[] = 'pr' . $f['properties_id'] . 'to';
              
              /*$pvq = \common\models\PropertiesValues::find()
                      ->select(['values_id', 'properties_id'])
                      //->where(['properties_id' => $f['properties_id']])
                      ->groupBy('values_id');
              foreach ($pvq->asArray()->all() as $pvr) {
                  $exclude[] = 'vpr' . $pvr['properties_id'] . 'from' . $pvr['values_id'];
                  $exclude[] = 'vpr' . $pvr['properties_id'] . 'to' . $pvr['values_id'];
              }*/
              //$exclude[] = 'vpr' . $f['properties_id'] . 'from';
              //$exclude[] = 'vpr' . $f['properties_id'] . 'to';
              break;
        }
      }
      
      $pvq = \common\models\PropertiesValues::find()
                ->select(['values_id', 'properties_id'])
                ->groupBy('values_id');
        foreach ($pvq->asArray()->all() as $pvr) {
            $exclude[] = 'vpr' . $pvr['properties_id'] . 'from' . $pvr['values_id'];
            $exclude[] = 'vpr' . $pvr['properties_id'] . 'to' . $pvr['values_id'];
        }
        
      foreach ($toProcess as $method => $params) {
        if (method_exists($this, 'get' . ucfirst($method) . 'FiltersArray')) {
          $f = $this->{'get' . ucfirst($method) . 'FiltersArray'}($params);
          if (!empty($f)) {
//            $ret = array_merge($ret, $f);
            $ret += $f;
          }
        } else {
          \Yii::warning('method get' . ucfirst($method) . 'FiltersArray' . ' does not exist ');
        }
      }
// echo "<PRE style='position:absolute;left:65%;top:0;z-index:100; width:35%'>" . __FILE__ .":". __LINE__ . print_r($ret, 1) . "</PRE>";


      ksort($ret , SORT_NUMERIC);
    }


    return ['exclude'=> $exclude, 'filters' => $ret];
  }
  
  /**
   * order by: selected, with count>0, sort_order, text
   * @param array $a
   * @param array $b
   * @return int
   */
  public static function cmpFilterValues($a, $b) {
    //selected, count reverse order; text, sort order - usual
    $soa = (!empty($a['sort_order'])?$a['sort_order']:0);
    $sob = (!empty($b['sort_order'])?$b['sort_order']:0);
    $ret = 0;
    if ($a['selected'] == $b['selected']) {
        $a['count'] = ($a['count'] > 0);
        $b['count'] = ($b['count'] > 0);
        if ($a['count'] == $b['count']) {
          if ($soa==$sob) {
            if ($a['text'] == $b['text']) {
              //already $ret=0
            } elseif ( ($a['text'] < $b['text'])) {
              $ret = -1;
            } else {
              $ret = 1;
            }
          } elseif ($soa<$sob) {
            $ret = -1;
          } else {
            $ret = 1;
          }
        } elseif ( $a['count'] > $b['count'] ) {
          $ret = -1;
        } else {
          $ret = 1;
        }
    } elseif ((int)$a['selected'] > (int)$b['selected']) {
      $ret = -1;
    } else {
      $ret = 1;
    }
    return $ret;
  }

  public function getCount() {
    $save = $this->params['orderBy'];
    $this->params['orderBy'] = ['fake' => 1];
    $cnt = $this->buildQuery()->getQuery()->count();
    $this->params['orderBy'] = $save;
    return $cnt;
  }

  private function getCurrentCategoryId() {
    global $current_category_id;
    return intval($current_category_id);
  }
  
}

<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components;

use Yii;

/**
 * Search Bulder class.

 */
class SearchBuilder {

    private $_addtionalSearch = null;
    private $_userKeywordsContainError = false;
    private $_searchKeywords = [];
    private $_parsedKeywords = [];
    private $_msearchKeywords = [];
    private $_relevance_keywords = [];
    private $_search_in_description = false;
    private $_search_internal = false;
    private $_typeSearch = 'simple';
    public $replaceWords = [];
    public $relevanceWords = [];

    public function __construct($typeSeach = 'simple') {
        if (defined('SEARCH_BY_ELEMENTS')) {
            $this->_addtionalSearch = array_map('trim', explode(",", SEARCH_BY_ELEMENTS));
        }
        $this->_typeSearch = $typeSeach;
    }

    public function setSearchInDesc($value = false) {
        $this->_search_in_description = (bool) $value;
    }
    
    public function useSearchInDesc() {
        return $this->_search_in_description;
    }
    
    public function setSearchInternal($value = false) {
        $this->_search_internal = (bool) $value;
    }

    public function useSearchInternal() {
        return $this->_search_internal;
    }

    public $categoriesArray = [];
    public $gapisArray = [];
    public $productsArray = [];    
    public $manufacturesArray = [];
    public $informationArray = [];
    
    public $searchInProperty = false;
    public $searchInAttributes = false;

    public function prepareRequest(string $keywords) {
        if (tep_not_null($keywords)) {
            if (defined('MSEARCH_ENABLE') && strtolower(MSEARCH_ENABLE)=='soundex') {
                if (!\common\helpers\Output::parse_search_string($keywords, $this->_searchKeywords, false) || !\common\helpers\Output::parse_search_string($keywords, $this->_msearchKeywords, MSEARCH_ENABLE)) {
                    $this->_userKeywordsContainError = true;
                }
            } else {
                if (!\common\helpers\Output::parse_search_string($keywords, $this->_searchKeywords)) {
                    $this->_userKeywordsContainError = true;
                }
            }
        }
        if ($this->_userKeywordsContainError) {
            $this->_searchKeywords = [$keywords];
            $this->_msearchKeywords = [$keywords];
        }
        
        $this->prepareKeywordsRequest();
    }
    
    public function parseKeywords(string $keywords) {
      $this->_parsedKeywords = [];
      if (tep_not_null($keywords)) {
        $keywords = trim(strtolower($keywords));
        $keywords = preg_replace(array('/[\(\)\'`]/', '/"/'), array(' ', ' " '), $keywords);
        $pieces = preg_split('/[\s]+/', $keywords,-1,PREG_SPLIT_NO_EMPTY);

        $started = false;
        $phrase = '';

        foreach ($pieces as $kw) {
          if ($kw == '"') {
            if ($started) {
              $started = false;
              $this->_parsedKeywords[] = trim($phrase);
              $phrase = '';
            } else {
              $started = true;
            }
          } elseif (!$started) {
            $this->_parsedKeywords[] = $kw;
          } else {
            $phrase .= ' ' . $kw;
          }

        }
        if ($phrase!='') {
          // not closed "
          $this->_parsedKeywords[] = trim($phrase);
        }
      }

    }

    public function prepareKeywordsRequest(){
        if (sizeof($this->_searchKeywords) > 0) {
            for ($i = 0, $n = sizeof($this->_searchKeywords); $i < $n; $i++) {
                switch ($this->_searchKeywords[$i]) {
                    //case '(':
                    //case ')':
                    case 'and':
                    case 'or':
                        $this->productsArray['regulator'] = $this->_searchKeywords[$i];
                        if ($this->_typeSearch != 'simple') {
                            $this->categoriesArray['regulator'] = $this->informationArray['regulator'] = $this->manufacturesArray['regulator'] = $this->gapisArray['regulator'] = $this->_searchKeywords[$i];
                        }
                        break;
                    default:

                        $keyword = $this->_searchKeywords[$i];
                        $this->replaceWords[] = $this->_searchKeywords[$i];
                        $this->relevanceWords[] = $this->_searchKeywords[$i];

                        $pArray = ['or',
                            ['like', 'if(length(pd1.products_name), pd1.products_name, pd.products_name)', $keyword],
                            ['like', 'm.manufacturers_name', $keyword],
                            ['like', 'if(length(pd1.products_head_keywords_tag), pd1.products_head_keywords_tag, pd.products_head_keywords_tag)', $keyword],
                        ];
                        if ($this->useSearchInDesc()) {
                            $pArray[] = ['like', 'if(length(pd1.products_description), pd1.products_description, pd.products_description)', $keyword];
                        }
                        
                        if ($this->useSearchInternal()) {
                            $pArray[] = ['like', 'if(length(pd1.products_internal_name), pd1.products_internal_name, pd.products_internal_name)', $keyword];
                        }
                        
                        if (defined('MSEARCH_ENABLE') && strtolower(MSEARCH_ENABLE)=='soundex' && isset($this->_msearchKeywords[$i])) {
                            $mkeyword = $this->_msearchKeywords[$i];
                            if (!empty($mkeyword)){
                                $pArray[] = ['like', 'if(length(pd1.products_name_soundex), pd1.products_name_soundex, pd.products_name_soundex)', $mkeyword];
                                if ($this->useSearchInDesc()) {
                                    $pArray[] = ['like', 'if(length(pd1.products_description_soundex), pd1.products_description_soundex, pd.products_description_soundex)', $mkeyword];
                                }
                            }
                        }

                        $this->_checkProductAdditionalFileds($pArray, $keyword);

                        if (defined('SHOW_GOOGLE_KEYWORD_PRODUCTS') && SHOW_GOOGLE_KEYWORD_PRODUCTS == 'True') {
                            $pArray[] = ['like', 'gs.gapi_keyword', $keyword];
                        }
                        
                        if (PRODUCTS_PROPERTIES == 'True' && $this->searchInProperty) {
                            global $languages_id;
                            $pArray[] = ['and',
                                    ['pvk.language_id' => (int)$languages_id ],
                                    ['like', 'pvk.values_text', $keyword]
                                ];
                        }

                        if ($this->searchInAttributes) {
                            global $languages_id;
                            $pArray[] = ['and',
                                    ['pok.language_id' => (int)$languages_id],
                                    ['povk.language_id' => (int)$languages_id],
                                    ['like', 'povk.products_options_values_name', $keyword]
                                ];
                        }

                        $this->productsArray[] = $pArray;

                        if ($this->_typeSearch != 'simple') {
                            $this->gapisArray[] = ['like', 'gs.gapi_keyword', $keyword];

                            $this->categoriesArray[] = ['or',
                                ['like', 'if(length(cd1.categories_name), cd1.categories_name, cd.categories_name)', $keyword],
                                ['like', 'if(length(cd1.categories_description), cd1.categories_description, cd.categories_description)', $keyword]
                            ];

                            $this->manufacturesArray[] = ['like', 'manufacturers_name', $keyword];

                            $this->informationArray[] = ['or',
                                ['like', 'if(length(i1.info_title), i1.info_title, i.info_title)', $keyword],
                                ['like', 'if(length(i1.description), i1.description, i.description)', $keyword],
                                ['like', 'if(length(i1.page_title), i1.page_title, i.page_title)', $keyword]
                            ];
                        }
                        break;
                }
            }
        }
    }
    
    public function getParsedKeywords(){
        return $this->_parsedKeywords;
    }

    public function getSearchKeywords(){
        return $this->_searchKeywords;
    }
    
    public function getMsSearchKeywords(){
        return $this->_msearch_keywords;
    }

    protected function _checkProductAdditionalFileds(&$pArray, $keyword) {
        if (is_array($this->_addtionalSearch) && count($this->_addtionalSearch)) {
            foreach ($this->_addtionalSearch as $item) {
                switch ($item) {
                    case 'SKU':
                        $pArray[] = ['like', 'p.products_model', $keyword];
                        break;
                    case 'ASIN':
                        $pArray[] = ['like', 'p.products_asin', $keyword];
                        break;
                    case 'EAN':
                        $pArray[] = ['like', 'p.products_ean', $keyword];
                        break;
                    case 'UPC':
                        $pArray[] = ['like', 'p.products_upc', $keyword];
                        break;
                    case 'ISBN':
                        $pArray[] = ['like', 'p.products_isbn', $keyword];
                        break;
                }
                if (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') {
                    $_ids = $this->getInventoryIds($keyword, $item);
                    if (is_array($_ids) && count($_ids))
                        $pArray[] = ['in', 'p.products_id', $_ids];
                }
            }
        }
    }
    
    protected function getInventoryIds($keyword, $searchIn){
        static $_cache = [];
        if (!isset($_cache[$keyword. '_' . $searchIn])){
            $iQuery = \common\models\Inventory::find()->select('prid')->distinct();
            switch($searchIn){
                case 'SKU':
                        $iQuery->orWhere(['like', 'products_model', $keyword]);
                        break;
                    case 'ASIN':
                        $iQuery->orWhere(['like', 'products_asin', $keyword]);
                        break;
                    case 'EAN':
                        $iQuery->orWhere(['like', 'products_ean', $keyword]);
                        break;
                    case 'UPC':
                        $iQuery->orWhere(['like', 'products_upc', $keyword]);
                        break;
                    case 'ISBN':
                        $iQuery->orWhere(['like', 'products_isbn', $keyword]);
                        break;
                    default:
                        return [];
                    break;
            }
            $ids = \yii\helpers\ArrayHelper::getColumn($iQuery->asArray()->all(), 'prid');
            $_cache[$keyword. '_' . $searchIn] = $ids;
        }
        return $_cache[$keyword. '_' . $searchIn];
    }

    public function getCategoriesArray($toString = true) {
        if ($toString) {
            return $this->_toString($this->categoriesArray);
        } else {
            return $this->_getArrayToModel($this->categoriesArray);
        }
    }
    
    private function _getArrayToModel(array $array){
        if (isset($array['regulator'])){
            $regulator = $array['regulator'];
            unset($array['regulator']);
        } else {
            $regulator = 'and';
        }
        array_unshift($array, $regulator);
        return $array;
    }

    public function getProductsArray($toString = true) {
        if ($toString) {
            return $this->_toString($this->productsArray);
        } else {
            return $this->_getArrayToModel($this->productsArray);
        }
    }
    
    public function getInformationsArray($toString = true) {
        if ($toString) {
            return $this->_toString($this->informationArray);
        } else {
            return $this->_getArrayToModel($this->informationArray);
        }
    }

    public function getManufacturersArray($toString = true) {
        if ($toString) {
            return $this->_toString($this->manufacturesArray);
        } else {
            return $this->_getArrayToModel($this->manufacturesArray);
        }
    }

    public function getGoogleKeywordsArray($toString = true) {
        if ($toString) {
            return $this->_toString($this->gapisArray);
        } else {
            return $this->_getArrayToModel($this->gapisArray);
        }
    }

    private function _toString(array $arrays) {
        
        if (empty($arrays)) return '';
        
        if (isset($arrays['regulator'])) {
            $reg = $arrays['regulator'];
            unset($arrays['regulator']);
        } else {
            $reg = ' and ';
        }
        $qBuilder = Yii::$app->getDb()->getQueryBuilder();
        $params = [];
        $result = [];
        if (!empty($arrays)) {
          foreach ($arrays as $array) {
              $result[] = $qBuilder->buildCondition($array, $params);// . (count($array) == count($array, COUNT_RECURSIVE) ? '' : ') ');
          }
        } else {// no search words, only and/or
          $result[] = 0;
        }
        $subQuery = '(' . implode(') ' . $reg . ' (', $result) . ')';
        $subQuery = Yii::$app->getDb()->createCommand($subQuery, $params)->rawSql;

        return " and (" . $subQuery . ") ";
    }

    private function _prepareSqlParams(&$values) {
        foreach ($values as $key => &$value) {
            $value = "'" . tep_db_input(tep_db_prepare_input($value)) . "'";
        }
    }

}

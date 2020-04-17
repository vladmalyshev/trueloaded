<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use backend\models\ProductNameDecorator;
use Yii;
use common\classes\platform;

class StocktakingCostController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_STOCK_COST'];
    public $exc_cat_id = array();
    
    public function __construct($id, $module = null) {        
        \common\helpers\Translation::init('admin/stocktaking-cost');
        $this->exc_cat_id[1] = 45;
        $this->exc_cat_id[2] = 41;
        parent::__construct($id, $module);
    }
    

    public function actionIndex() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->selectedMenu = array('reports', 'stocktaking-cost');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('stocktaking-cost/index'), 'title' => BOX_REPORTS_STOCK_COST);
        $this->view->headingTitle = BOX_REPORTS_STOCK_COST;

        $platforms = platform::getList(false);
        
        $excluded_categories_query = tep_db_query(
            "SELECT cd.categories_name ".
            "FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd ".
            "WHERE cd.language_id = FLOOR($languages_id) AND cd.affiliate_id=0 AND cd.categories_id IN (". implode(",", $this->exc_cat_id) . ")"
          );
          $exc_cat_name = array();
          $i= 0;
          while ($excluded_categories = tep_db_fetch_array($excluded_categories_query)) {
              $exc_cat_name[$i] = $excluded_categories['categories_name'];
              $i++;
           }
     
            
        $this->view->CostTable = $this->getTable();
        return $this->render('index', [
                    'platforms' => $platforms,
                    'first_platform_id' => platform::firstId(),
                    'default_platform_id' => platform::defaultId(),
                    'isMultiPlatforms' => platform::isMulti(),
                    'excluded' => EXCLUDE_ID . implode(", ", $exc_cat_name),
		  ]);
    }
    
    public function getTable(){
        return array(
            array(
                'title' => TABLE_HEADING_CATEGORY,
                'not_important' => 0,
                'width' => '23%'
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS,
                'not_important' => 0,
                'width' => '33%',
            ),
            array(
                'title' => TABLE_HEADING_QUANTITY,
                'not_important' => 0,
                'width' => '5%'
            ),
            array(
                'title' => TABLE_HEADING_PP,
                'not_important' => 0,
                'width' => '5%'
            ),
            array(
                'title' => TABLE_HEADING_TPP,
                'not_important' => 0,
                'width' => '5%'
            ),
            array(
                'title' => TABLE_HEADING_SP,
                'not_important' => 0,
                'width' => '5%'
            ),
            array(
                'title' => TABLE_HEADING_TSP,
                'not_important' => 0,
                'width' => '5%'
            ),
        );
    }
    
    public function build(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        $responseList = [];
        $head = new \stdClass();
        $included_categories_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name
				FROM " . TABLE_CATEGORIES . " c  
				LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON c.categories_id = cd.categories_id
				WHERE cd.language_id = FLOOR($languages_id) AND cd.affiliate_id=0
				AND c.categories_id NOT IN (" . implode(",", $this->exc_cat_id) . ")");

        $inc_cat = array();
        while ($included_categories = tep_db_fetch_array($included_categories_query)) {
          $inc_cat[] = array (
             'id' => $included_categories['categories_id'],
             'parent' => $included_categories['parent_id'],
             'name' => $included_categories['categories_name']);
          }
        $cat_info = array();
        for ($i=0; $i<sizeof($inc_cat); $i++)
          $cat_info[$inc_cat[$i]['id']] = array (
            'parent'=> $inc_cat[$i]['parent'],
            'name'  => $inc_cat[$i]['name'],
            'path'  => $inc_cat[$i]['id'],
            'link'  => '',
            'cleanlink'  => ''
             );

        for ($i=0; $i<sizeof($inc_cat); $i++) {
          $cat_id = $inc_cat[$i]['id'];
          while ($cat_info[$cat_id]['parent'] != 0){
            $cat_info[$inc_cat[$i]['id']]['path'] = $cat_info[$cat_id]['parent'] . '_' . $cat_info[$inc_cat[$i]['id']]['path'];
            $cat_id = $cat_info[$cat_id]['parent'];
            }
          $link_array = explode('_', $cat_info[$inc_cat[$i]['id']] ['path']);
          for ($j=0; $j<sizeof($link_array); $j++) {
            $cat_info[$inc_cat[$i]['id']]['link'] .= '&nbsp;<a target="_blank" href="' . tep_href_link('categories', 'listing_type=category&category_id=' . $link_array[$j]) . '"><nobr>' . $cat_info[$link_array[$j]]['name'] . '</nobr></a>&nbsp;&raquo;&nbsp;';
            $cat_info[$inc_cat[$i]['id']]['cleanlink'].= $cat_info[$link_array[$j]]['name'] . '/';
            }
          }


        $products_query = tep_db_query("SELECT p.products_id, p.products_quantity, sp.specials_new_products_price, p.products_price, p2c.categories_id, min(sup.suppliers_price) as suppliers_price, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name FROM " .TABLE_PRODUCTS." p
                         LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id 
                         LEFT JOIN ".TABLE_SPECIALS ." sp ON p.products_id = sp.products_id 
                         LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.products_id = p2c.products_id 
                         LEFT JOIN ".TABLE_SUPPLIERS_PRODUCTS." sup ON sup.products_id = p.products_id 
                         WHERE p.products_status = 1 
                         AND pd.language_id = FLOOR($languages_id) AND pd.platform_id='".intval(\common\classes\platform::defaultId())."'
                         AND p2c.categories_id not in (" . implode(",", $this->exc_cat_id) . ")
                         group by p.products_id
                         ORDER BY p2c.categories_id, pd.products_name");

			
        $stock=0;
        $stockp=0;
        $tot=0;
        $totp=0;
        $memory = 0;
        $_counted_pids = array();
        $row = -1;
        while($products = tep_db_fetch_array($products_query)) {
         //if ($products['categories_id'] != $exc_cat_id && ($products['categories_id'] != $exc_cat_id2 )) {
          $cost_item= ($products['suppliers_price']*$products['products_quantity']);
          if ($products['specials_new_products_price']) { //if it's on special
                $products['products_price'] = $products['specials_new_products_price']; //show the special price 
          }				
          $price_item= ($products['products_price']*$products['products_quantity']);
          $check_id = $products['categories_id'];
          $qt_item = $products['products_quantity'];

            if ($check_id != $memory) {
                $row++;
                $head->row[] = $row;
                $responseList[] = [
                    '<div class="orange"></div>',
                    '<div class="orange"></div>',
                    '<div class="orange">'.$tqqt_item.'</div>',
                    '',
                    $tqcost_item,
                    '',
                    $tqprice_item,
                ];
                $tqqt_item = 0;
                $tqcost_item = 0;
                $tqprice_item = 0;
            }

            $tqqt_item += $qt_item;
            $tqcost_item += $cost_item;
            $tqprice_item += $price_item;
            if ( !in_array($products['products_id'], $_counted_pids) ) {
              $tqt_item += $qt_item; //
              $tcost_item += $cost_item;//
              $tprice_item += $price_item;//  
            }
            $_counted_pids[] = $products['products_id'];
            $responseList[] = [
                (($memory == $products['categories_id'])? '': $cat_info[$products['categories_id']]['link']),
                '<a target="_blank" href="' . tep_href_link('categories/productedit', 'pID=' . $products['products_id'], 'NONSSL') . '">' . $products['products_name'] . "</a>",
                $products['products_quantity'],
                round($products['suppliers_price'],2),
                $cost_item,
                round($products['products_price'],2),
                $price_item,
            ];
            $row++;
            $tot += $stock;
            $totp += $stockp;


            if ($old_categorie != $cat_info[$products['categories_id']]['cleanlink']) {
                $csv_cat = $cat_info[$products['categories_id']]['cleanlink'];
                $old_categorie = $csv_cat;
            }

              $csv_accum .= 	strtr($csv_cat, "',", "  ") . $csv_separator .
		strtr($products['products_name'], "',", "  ") . $csv_separator .
		$products['products_quantity'] . $csv_separator .
		round($products['suppliers_price'],2) . $csv_separator .
		$cost_item . $csv_separator .
		round($products['products_price'],2) . $csv_separator .
		$price_item . $csv_separator . "\n" ;
            $csv_cat = '';		
            //} // end exclude cat_id
              $memory = $products['categories_id'];
        }
        if ($check_id = $memory) {
            $responseList[] = [
                '',
                '',
                $tqqt_item,
                '',
                $tqcost_item,
                '',
                $tqprice_item,
                ];
            $responseList[] = [
                '',
                '',
                $tqt_item,
                '',
                $tcost_item,
                '',
                $tprice_item,
                ];
            $row++;
            $head->row[] = $row;
            $head->last[] = ++$row;
        $tqqt_item = 0;
        $tqcost_item = 0;
        $tqprice_item = 0;
    }
    
    $head->list = [
        TABLE_HEADING_TQT_ITEM => $tqt_item,
        TABLE_HEADING_TCOST_PURCHASE_PRICE => $tcost_item,
        TABLE_HEADING_TCOST_SALE_PRICE => $tprice_item
    ];
     return ['responseList' => $responseList, 'head' => $head];
    }

    public function actionList() {        

        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        $current_page_number = ($start / $length) + 1;
        $platform_id = Yii::$app->request->get('platform_id');

        $data = $this->build();
        $responseList = $data['responseList'];
                
        $response = array(
            'draw' => $draw,
            'recordsTotal' => count($responseList),
            'recordsFiltered' => count($responseList),
            'data' => $responseList,
            'head' => $data['head']
        );
        echo json_encode($response);
    }

    public function actionExport() {
        $data = $this->build();
        $head = $this->getTable();
        
        $writer = new \backend\models\EP\Formatter\CSV('write', array(), 'expo.csv');
        $a = [];
        foreach($head as $m){
          $a[] = $m['title'];
        }        
        $writer->write_array($a);
        
        foreach($data['responseList'] as $row){
            $newArray = array_map(function($v){
                $vv = trim(strip_tags($v));
                $vv = str_replace(['&nbsp;&raquo;&nbsp;', '&nbsp;', ], [' / ', '', ], $vv);
                return $vv;
            }, $row);
             $writer->write_array($newArray);
        }
        exit();
    }

}

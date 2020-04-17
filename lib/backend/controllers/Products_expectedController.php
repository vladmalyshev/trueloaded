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

class Products_expectedController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_CATALOG_PRODUCTS_EXPECTED'];
    
        public function actionIndex()
        {
            //\common\helpers\Translation::init('admin/products_expected');
			
            $this->selectedMenu        = array( 'reports', 'products_expected' );
            $this->navigation[]        = array( 'link' => Yii::$app->urlManager->createUrl( 'products_expected/index' ), 'title' => HEADING_TITLE );
            $this->view->headingTitle  = HEADING_TITLE;
            $this->view->productsTable = array(
                array(
                    'title'         => TABLE_HEADING_PRODUCTS,
                    'not_important' => 0
                ),
                array(
                    'title'         => TABLE_HEADING_DATE_EXPECTED,
                    'not_important' => 0
                ),
            );
            
            return $this->render( 'index' );
        }
        
       public function actionList()
        {
          $languages_id = \Yii::$app->settings->get('languages_id');
          $draw = Yii::$app->request->get('draw', 1);
          $search = Yii::$app->request->get('search', '');
          $start = Yii::$app->request->get('start', 0);
          $length = Yii::$app->request->get('length', 10);
          $search_where = '';
          if (tep_not_null($search['value'])){
            $search_where = " and (pd.products_name like '%" .tep_db_input($search['value']). "%' or pd.products_internal_name like '%" . tep_db_input($search['value']) . "%' or pd.products_description like '%" .tep_db_input($search['value']). "%') ";
          }
          $products_query_raw = "select pd.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_date_available from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p where p.products_id = pd.products_id and to_days(p.products_date_available) > 0 and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id='".intval(\common\classes\platform::defaultId())."' " . $search_where . " order by p.products_date_available DESC";

          $current_page_number = ($start / $length) + 1;          
          $products_split = new \splitPageResults($current_page_number, $length, $products_query_raw, $products_query_numrows);
          $products_query = tep_db_query($products_query_raw);
          $responseList = array();
          while ($products = tep_db_fetch_array($products_query)) {
            $responseList[] = array($products['products_name']. tep_draw_hidden_field('products_id', $products['products_id']),
                                    \common\helpers\Date::date_short($products['products_date_available'])
                                    );

          }
          
        $response = array(
            'draw' => $draw,
            'recordsTotal' => $products_query_numrows,
            'recordsFiltered' => $products_query_numrows,
            'data' => $responseList,
        );
        echo json_encode($response);  
          
        }

    }
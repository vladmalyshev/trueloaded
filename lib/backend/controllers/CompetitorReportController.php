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

use Yii;
use common\models\Competitors;
use common\models\CompetitorsProducts;
use common\models\Products;

class CompetitorReportController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_COMPETITOR'];
    public $selectedType;

    public function __construct($id, $module = null) {
        $this->selectedType = Yii::$app->request->get('type', 'cheaper');
        \common\helpers\Translation::init('admin/competitors');
        \common\helpers\Translation::init('admin/categories');
        parent::__construct($id, $module);        
    }

    public function actionIndex() {

        $this->selectedMenu = array('reports', 'competitor-report');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('competitor-report/index'), 'title' => BOX_REPORTS_COMPETITOR);
        $this->view->headingTitle = BOX_REPORTS_COMPETITOR;        

        $this->view->filter = new \stdClass();
        
        $this->view->filter->types = [
            'all' => TEXT_ALL,
            'cheaper' => TEXT_CHEAPER,
        ];
                
        $this->view->reportTable = [
            [
                'title' => TABLE_HEADING_PRODUCTS_MODEL,
                'not_important' => 0,
            ],
            [
                'title' => TEXT_COMPETITOR . ' '. TABLE_HEADING_PRODUCTS_MODEL,
                'not_important' => 0,
            ],
            [
                'title' => TEXT_COMPETITOR. ' '. TEXT_PRODUCTS_NAME,
                'not_important' => 0,
            ],
            [
                'title' => TEXT_OUR_PRICE,
                'not_important' => 0,
            ],
            [
                'title' => TEXT_COMPETITOR. ' '. TEXT_LEGEND_PRICE,
                'not_important' => 0,
            ],
            [
                'title' => TABLE_HEADING_COMPETITOR_NAME,
                'not_important' => 0,
            ],
            [
                'title' => TABLE_HEADING_COMPETITOR_CURRENCY,
                'not_important' => 0,
            ],
            [
                'title' => TEXT_URL,
                'not_important' => 0,
            ],
            [
                'title' => TABLE_HEADING_LAST_MODIFIED,
                'not_important' => 0,
            ],
            [
                'title' => TABLE_HEADING_STATUS,
                'not_important' => 0,
            ],
        ];
        
        return $this->render('index', [
            
        ]);
        
    }
    
    public function actionList(){
        $currencies = Yii::$container->get('currencies');
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        
        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);
        if (isset($output['type']) && !empty($output['type'])){
            $this->selectedType = $output['type'];
        }        
        
        $competitorsProducts = CompetitorsProducts::find()                
                ->from(['cp' => CompetitorsProducts::tableName()])                
                ->joinWith('product p')
                ->joinWith('competitor c')
                ->joinWith('currency cc')
                ->where('cp.status=1');
        
        if ($this->selectedType == 'cheaper'){
            $competitorsProducts->andWhere("(cp.products_price * cc.value) < ( p.products_price + (p.products_price * tr.tax_rate) / 100)");
        }                
        
        $search = '';
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
          $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
          $competitorsProducts->andFilterWhere([
              'or',
                ['like', 'cp.products_model', $keywords],
                ['like', 'p.products_model', $keywords],
                ['like', 'cp.products_name', $keywords],
                ['like', 'c.competitors_name', $keywords],
          ]);
        }

        $current_page_number = ($start / $length) + 1;
        $responseList = array();

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
          switch ($_GET['order'][0]['column']) {
            case 0:
              $competitorsProducts->orderBy("competitors_name " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            case 1:
                $competitorsProducts->orderBy("competitors_site " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            case 2:
                $competitorsProducts->orderBy("competitors_currency " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
            default:
                $competitorsProducts->orderBy("competitors_currency " . tep_db_prepare_input($_GET['order'][0]['dir']));
              break;
          }
        } else {
          $competitorsProducts->orderBy("p.products_model");
        }

        $list = $competitorsProducts->limit($length)->offset($start)->all();
        //echo '<pre>';print_r($list);
        if ($list){
            foreach($list as $product){
                $compare = ($product->products_price * $product->currency->value) < ($product->product->products_price + ($product->product->products_price * $product->product->taxRate->tax_rate) / 100);
                $url = (!empty($product->products_url)? $product->products_url : rtrim($product->competitor->competitors_site, '/') . '/' . ltrim($product->products_url_short, '/'));
                $responseList[] = array(
                    '<a href="'.Yii::$app->urlManager->createUrl(['categories/productedit', 'pID' => $product->products_id]).'" target="_blank">' . $product->product->products_model. '</a>',
                    $product->products_model,
                    $product->products_name,
                    $currencies->display_price($product->product->products_price, $product->product->taxRate->tax_rate),
                    $currencies->format($product->products_price / ($product->currency->value? $product->currency->value:1), true, $product->products_currency) . ' (' . 
                    $currencies->format($product->products_price / ($product->currency->value? $product->currency->value:1), true, '')
                    . ')',
                    $product->competitor->competitors_name,
                    $product->competitor->competitors_currency,
                    '<a href="'.$url.'" target="_blank">' . $url . '</a>',
                    date(DATE_FORMAT, strtotime($product->last_modified)),
                    ( $compare ? '<div class="smile odd red">&#9785;':'<div class="smile odd green">&#9786;') . '</div>',
                );
            }
        }    

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $competitorsProducts->count(),
            'recordsFiltered' => $competitorsProducts->count(),
            'data' => $responseList
        );
        echo json_encode($response);
        
    }
    
}

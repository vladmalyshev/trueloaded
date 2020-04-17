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
use common\components\google\GoogleAnalytics;
use common\models\GoogleKeywords;
use common\models\GoogleKeywordProducts;
/**
 * default controller to handle user requests.
 */
class Google_keywordsController extends Sceleton {

    public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_GOOGLE_ANALYTIC_TOOLS', 'BOX_GOGLE_KEYWORDS'];
    
    private $analytics;
    
    public function __construct($id, $module = null, \common\components\GoogleTools $googleTools) {
        $this->analytics = $googleTools->getAnalyticsProvider();
        \common\helpers\Translation::init('admin/google_keywords');
        parent::__construct($id, $module);
    }

    public function actionIndex() {

        $this->selectedMenu = array('marketing', 'ga_tools', 'google_keywords');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('google_keywords/index'), 'title' => HEADING_TITLE);

        $this->view->headingTitle = HEADING_TITLE;
        
        $this->view->keywordTable = [
            array(
                'title' => TABLE_HEADING_KEYWORDS,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_VISIT,
                'not_important' => 0,
            ),
            array(
                'title' => TABLE_HEADING_PRODUCTS,
                'not_important' => 0,
            ),
        ];
        
        $this->view->row_id = Yii::$app->request->get('row_id', 0);
        
        $first = \common\classes\platform::firstId();
        
        return $this->render('index', [
                    'platforms' => \common\classes\platform::getList(false),
                    'first_platform_id' => $first,
                    'isMultiPlatform' => \common\classes\platform::isMulti(),
                    'view_id' => $this->analytics->getViewId($first)
        ]);
    }
    
    public function actionList(){
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        
        parse_str(Yii::$app->request->get('filter',''), $output);
        

        $responseList = [];
        if ($length == -1)
            $length = 10000;
        $recordsTotal = 0;
        
        $keywords = GoogleKeywords::find()->where(['platform_id' => $output['platform']]);
        
        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords->andWhere(['like', 'gapi_keyword', $_GET['search']['value']]);
        }
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $keywords->orderBy('gapi_keyword ' . $_GET['order'][0]['dir']);
                    break;
                case 1:
                    $keywords->orderBy('gapi_views ' . $_GET['order'][0]['dir']);
                    break;
                default:
                    $keywords->orderBy('gapi_keyword ');
                    break;
            }
        } else {
            $keywords->orderBy('gapi_keyword ');
        }
                
        $recordsTotal = $keywords->count();
        $keywords->limit($length)->offset($start);
        $rows = $keywords->all();
        if (is_array($rows)) foreach ($rows as $key => $keyword) {
            $responseList[] = array(
                $keyword->getAttribute('gapi_keyword') . '<input class="cell_identify" type="hidden" value="' . $keyword->getAttribute('gapi_id') . '">',
                $keyword->getAttribute('gapi_views'),
                $keyword->getProducts()->count()
            );
        }

        $response = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $responseList,
        ];
        echo json_encode($response);
    }

    public function actionGetView() {
        $platform_id = Yii::$app->request->get('platform_id', 0);
        echo json_encode(['view_id' => $this->analytics->getViewId($platform_id), 'settings' => $this->analytics->getFileKey($platform_id)]);
        exit();
    }
    
    public function actionPreview(){
        $gapi_id = Yii::$app->request->post('gapi_id', 0);
        $keyword = GoogleKeywords::find()->where(['gapi_id' => $gapi_id])->with('products')->one();
        $this->view->row_id = Yii::$app->request->post('row_id', 0);
        return $this->renderAjax('view',[
            'keyword' => $keyword 
        ]);
    }
        
    public function actionTrash(){
        $platform_id = Yii::$app->request->post('platform_id', 0);
        //delete over object!!
        $keywords = GoogleKeywords::findAll(['platform_id' => $platform_id]);
        if ($keywords){
            foreach ($keywords as $key ){
                $key->delete();
            }
        }
        //GoogleKeywords::deleteAll(['platform_id' => $platform_id]);
        echo 'ok';
    }

    public function actionFetchWords() {

        $platform_id = Yii::$app->request->post('platform', 0);
        $start_date = Yii::$app->request->post('start_date', '');

        if (!empty($start_date)) {
            $start_date = date("Y-m-d", strtotime(str_replace("/", "-", $start_date)));
        }
        $end_date = Yii::$app->request->post('end_date', '');
        if (!empty($end_date)) {
            $end_date = date("Y-m-d", strtotime(str_replace("/", "-", $end_date)));
        }
        
        try{
            $ga = new GoogleAnalytics($this->analytics->getFileKey($platform_id), $this->analytics->getViewId($platform_id));            
        } catch(\Exception $ex){
            $message['error'] = $ex->getMessage();
        }
        if (is_object($ga)){
            $analytics = $ga->prepareReporting();

            $filters = [
                [
                    'date_range' => [$start_date, $end_date],
                    'dimensions' => ['ga:searchKeyword'],
                    'metrics' => [],
                ],
                [
                    'date_range' => [$start_date, $end_date],
                    'dimensions' => ['ga:keyword'],
                    'metrics' => ['ga:organicSearches'],
                ]
            ];
            
            $message = [];
            try {
                foreach($filters as $filetr){
                    $response = $ga->getReport($filetr);
                    if (is_object($response) && property_exists($response, 'reports')) {
                        if ($this->save($response->reports, $platform_id)){
                            $message['success'] = 'Google keywords have been updated';
                        } else {
                            $message['info'] = 'No report details';
                        }
                    } else {
                        $message['info'] = 'No report details';
                    }
                }
            } catch (\Exception $ex) {
                $message['error'] = 'Error getting report';
            }
        }
        echo json_encode($message);
        exit();
    }

    public function save($reports, $platform_id) {
        $exclusion = ['(not set)', '(not provided)'];
        if (is_array($reports) && count($reports)) {
            for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
                $report = $reports[$reportIndex];
                $header = $report->getColumnHeader();
                $dimensionHeaders = $header->getDimensions();
                $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
                $rows = $report->getData()->getRows();

                for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                    $row = $rows[$rowIndex];
                    $dimensions = $row->getDimensions();
                    $metrics = $row->getMetrics();

                    for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                        if (in_array($dimensions[$i], $exclusion))
                            continue;
                        //$dimensions[$i] = substr($dimensions[$i], 50);
                        $dimensions[$i] = strip_tags($dimensions[$i]);
                        if (preg_match("/^https?:\/\//", $dimensions[$i])) continue;
                        $keyword = GoogleKeywords::find()->where(['gapi_keyword' => $dimensions[$i]])->one();

                        if (!$keyword) {
                            $keyword = new GoogleKeywords();
                            $keyword->setAttribute('gapi_keyword', $dimensions[$i]);
                        }

                        $values = $metrics[$i];
                        $keyword->setAttribute('gapi_views', $values->getValues()[0]);
                        $keyword->setAttribute('platform_id', $platform_id);
                        if ($keyword->validate()) {
                            try{
                                $keyword->save();
                            } catch (\Exception $ex) {
                                mail('akoshelev@holbi.co.uk','save',print_r($ex,1));
                            }
                            
                        }
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
    
    public function actionEdit() {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('admin/categories');
        
        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('marketing', 'ga_tools', 'google_keywords');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('google_keywords/index'), 'title' => HEADING_TITLE);

        $gapi_id = Yii::$app->request->get('gID', 0);
        $row_id = Yii::$app->request->get('row_id', 0);
        
        if (!$gapi_id){
            return $this->redirect('index');
        }
        
        $keyword = GoogleKeywords::find()->where(['gapi_id' => $gapi_id])->with('products')->one();
        
        if (is_array($keyword->products) && count($keyword->products)){
            foreach($keyword->products as $i => $data){
                $product = tep_db_fetch_array(tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id = '" . (int)$data->products_id . "' "));
                $data->setAttribute('products_name', $product['products_name']);
                $data->setAttribute('image', \common\classes\Images::getImage($product['products_id'], 'Small'));
                $data->setAttribute('price', $currencies->format(\common\helpers\Product::get_products_price($product['products_id'])));
                $data->setAttribute('status_class', ($product['products_status'] == 0 ? 'dis_prod' : ''));
            }
        }        
        
        return $this->render('edit',[
            'keyword' => $keyword,
            'row_id' => $row_id,
        ]);        
    }

    public function actionProductsUpdate() {
        
        $gapi_id = Yii::$app->request->post('gapi_id');
        $products_id = Yii::$app->request->post('products_id', array());
        $row_id = Yii::$app->request->post('row_id', 0);

        GoogleKeywordProducts::deleteAll(['gapi_id' => $gapi_id]);
        $products_id = array_unique($products_id);
        foreach ($products_id as $sort_order => $products_id) {
            $product = new GoogleKeywordProducts();
            $product->setAttribute('gapi_id', $gapi_id);
            $product->setAttribute('products_id', $products_id);
            $product->setAttribute('sort', $sort_order);
            if ($product->validate()){
                $product->save();
            }
        }        

        return $this->redirect(Yii::$app->urlManager->createUrl(['google_keywords/index', 'row_id' => $row_id]));
    }

    public function actionProductSearch() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $q = Yii::$app->request->get('q');

        $products_string = '';

        $categories = \common\helpers\Categories::get_category_tree(0, '', '0', '', true);
        foreach ($categories as $category) {
            $products_query = tep_db_query("select distinct p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p.products_id = p2c.products_id LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id where p2c.categories_id = '" . $category['id'] . "' and pd.language_id = '" . (int) $languages_id . "' and p.products_pctemplates_id = '0' and (p.products_model like '%" . tep_db_input($q) . "%' or pd.products_name like '%" . tep_db_input($q) . "%' or pd.products_internal_name like '%" . tep_db_input($q) . "%') group by p.products_id order by p.sort_order, pd.products_name limit 0, 100");
            if (tep_db_num_rows($products_query) > 0) {
                $products_string .= '<optgroup label="' . $category['text'] . '">';
                while ($products = tep_db_fetch_array($products_query)) {
                    $products_string .= '<option value="' . $products['products_id'] . '" ' . ($products['products_status'] == 0 ? ' class="dis_prod"' : '') . '>' . $products['products_name'] . '</option>';
                }
                $products_string .= '</optgroup>';
            }
        }

        echo $products_string;
    }

    public function actionNewProduct() {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $this->layout = false;

        $products_id = (int) Yii::$app->request->post('products_id');
        $query = tep_db_query("select p.products_id, ".ProductNameDecorator::instance()->listingQueryExpression('pd','')." AS products_name, p.products_status from " . TABLE_PRODUCTS_DESCRIPTION . " pd," . TABLE_PRODUCTS . " p where language_id = '" . (int)$languages_id . "' and platform_id = '".intval(\common\classes\platform::defaultId())."' and p.products_id = '" . (int)$products_id . "' and p.products_id =  pd.products_id limit 1");
        if (tep_db_num_rows($query) > 0) {
            $data = tep_db_fetch_array($query);
        } else {
            $data = array();
        }
        $currencies = Yii::$container->get('currencies');

        if (count($data) > 0) {
            $product = new GoogleKeywordProducts();
            $product->setAttribute('products_id', $data['products_id']);
            $product->setAttribute('products_name', $data['products_name']);
            $product->setAttribute('image', \common\classes\Images::getImage($data['products_id'], 'Small'));
            $product->setAttribute('status_class', ($data['products_status'] == 0 ? 'dis_prod' : ''));
            $product->setAttribute('price', $currencies->format(\common\helpers\Product::get_products_price($product['products_id'])));
            
            return $this->renderAjax('new-product.tpl', [
                        'product' => $product,
            ]);
        }
    }
    
    public function actionDelete(){
        $gapi_id = Yii::$app->request->post('gapi_id');
        \common\helpers\Translation::init('admin/adminmembers');
        $message = '';
        $message = WARN_UNKNOWN_ERROR;
        if ($gapi_id){
            $keyword = GoogleKeywords::find()->where(['gapi_id' => $gapi_id])->one();            
            if ($keyword){
                $keyword->delete();
                $message = 'Removed';
            }            
        }
        echo json_encode(['message' => $message]);
        exit();
    }

}

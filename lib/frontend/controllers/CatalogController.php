<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use common\classes\events\frontend\attributes\productAttributesInfo\ProductAttributesInfoEvent;
use common\components\Customer;
use common\extensions\UserGroupsRestrictions\UserGroupsRestrictions;
use common\helpers\CategoriesDescriptionHelper;
use common\helpers\Manufacturers;
use common\models\Customers;
use Yii;
use common\classes\Images;
use yii\base\ErrorException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;
use frontend\design\SplitPageResults;
use frontend\design\ListingSql;
use frontend\design\boxes\Listing;
use common\classes\design;
use common\helpers\Product;
use common\classes\platform;
use common\helpers\Sorting;
use common\models\Product as Products;
/*use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;*/

/**
 * Site controller
 */
class CatalogController extends Sceleton
{

    public function actionIndex()
    {
        global $_SESSION, $languages_id, $current_category_id;
        global $breadcrumb;
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');

        if ($current_category_id > 0) {

            // Get the category name and description

            $groupJoin = false;
            $groupWhere = false;
            if (UserGroupsRestrictions::isAllowed()) {
                $groupJoin = true;
                $groupWhere = true;
            }
            $category = CategoriesDescriptionHelper::getCategoriesDescriptionList($current_category_id, $languages_id, (int) $_SESSION['affiliate_ref'], $customer_groups_id, $groupJoin, $groupWhere);
            if (!$category) {
                throw new NotFoundHttpException('Page not found.');
            }
            if ( is_array($category) ) {
                \common\helpers\Seo::showNoindexMetaTag($category['noindex_option'], $category['nofollow_option']);
                if (!empty($category['rel_canonical'])) {
                    \app\components\MetaCannonical::instance()->setCannonical($category['rel_canonical']);
                }else{
                    \app\components\MetaCannonical::instance()->setCannonical(['catalog/index','cPath'=>str_replace('cPath=','',\common\helpers\Categories::get_path($current_category_id))]);
                }
                $parent_categories = array($category['id']);
                \common\helpers\Categories::get_parent_categories($parent_categories, $parent_categories[0]);
                $bcPath = '';
                foreach(array_reverse($parent_categories) as $_cid){
                    $bcPath.=( !empty($bcPath)?'_':'').$_cid;
                    $breadcrumb->add(\common\helpers\Categories::get_categories_name($_cid), tep_href_link('catalog/index', 'cPath='.$bcPath, 'NONSSL'));
                }
            }

        } elseif ($_GET['manufacturers_id'] > 0) {

            // Get the manufacturer name and image
            $category = Manufacturers::getCategoriesDescriptionList($languages_id, (int)$_GET['manufacturers_id']);
            \app\components\MetaCannonical::instance()->setCannonical( ['catalog/index', 'manufacturers_id'=>$category['id']] );
            $breadcrumb->add($category['categories_name'], tep_href_link('catalog/index', 'manufacturers_id='.$category['id'], 'NONSSL'));

        }else{
            \app\components\MetaCannonical::instance()->setCannonical( '/' );
            return Yii::$app->runAction('index/index');
        }

        $category['img'] = Yii::$app->request->baseUrl . '/images/' . $category['categories_image'];
        if (!is_file(Yii::getAlias('@webroot') . '/images/' . $category['categories_image'])){
            $category['img'] = 'no';
        }

        if ($_GET['manufacturers_id']) {
          $category_p = 0;
          if (defined('ALWAYS_SHOW_FILTERS_ON_BRAND_PAGE') && ALWAYS_SHOW_FILTERS_ON_BRAND_PAGE == 'True') {
            $noFiltersTo = 'products';
          }
        } else {
          $category_parent = \common\models\Categories::find()
              ->select(['count(*) AS total'])
              ->where(['parent_id' => (int)$current_category_id])
              ->andWhere(['categories_status' => 1])
              ->asArray()
              ->one();
          $category_p = $category_parent['total'];
        }


        $search_results = Info::widgetSettings('Listing', 'items_on_page');
        if (!$search_results) $search_results = SEARCH_RESULTS_1;

        $view = array();
        $view[] = $search_results * 1;
        $view[] = $search_results * 2;
        $view[] = $search_results * 4;
        $view[] = $search_results * 8;

        $q = new \common\components\ProductsQuery([
          'get' => \Yii::$app->request->get(),
          'hasSubcategories' => $category_p,
        ]);
        $cnt = $q->getCount();
        \Yii::$app->set('productsFilterQuery', $q);

        $params = array(
          'listing_split' => SplitPageResults::make($q->buildQuery()->getQuery(), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'*', 'page', $cnt)->withSeoRelLink(),
          'this_filename' => 'catalog'
        );

        if (!empty($noFiltersTo)) {
          $page_name = $noFiltersTo;
        } else {
          $page_name = \frontend\design\Categories::pageName($current_category_id, $category_p);
        }

        $this->view->page_name = $page_name;
        
        /*$option = tep_db_fetch_array(tep_db_query("select noindex_option, nofollow_option, rel_canonical from categories_description where categories_id = '" . $current_category_id . "'"));
        \common\helpers\Seo::showNoindexMetaTag($option['noindex_option'], $option['nofollow_option']);
        if (!empty($option['rel_canonical'])) {
            \app\components\MetaCannonical::instance()->setCannonical($option['rel_canonical']);
        }*/
        $params['page_name'] = $page_name;

        if (Yii::$app->request->get('onlyProducts')) {
            $this->layout = false;
            return Listing::widget([
                'params' => $params,
                'settings' => array_merge(Info::widgetSettings('Listing', false, $page_name), ['onlyProducts' => true])
            ]);
        }

        if (Yii::$app->request->get('fbl')) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing', false, $page_name)]);
        }

        return $this->render('index.tpl', [
          'category' => $category,
          'category_parent' => $category_p,
          'params' => $params,
          'page_name' => $page_name
        ]);
    }


    public function actionProduct()
    {
        global $breadcrumb, $cPath_array, $languages_id;


        $params = Yii::$app->request->get();

        if (!isset($_SESSION['viewed_products']) || !is_array($_SESSION['viewed_products'])){
            $_SESSION['viewed_products'] = [];
        }
        
        (new \common\components\Popularity())->updateProductVisit($params['products_id']);
        
        /*if ( isset($_SESSION['viewed_products'][(int)$params['products_id']]) ) {
            unset($_SESSION['viewed_products'][(int)$params['products_id']]);
        }
        $_SESSION['viewed_products'][(int)$params['products_id']] = (int)$params['products_id'];
        */
        if ( count($_SESSION['viewed_products'])>40 ) {
            // {{ fastest way remove first pid
            reset($_SESSION['viewed_products']);
            $_removeProductId = current($_SESSION['viewed_products']);
            unset($_SESSION['viewed_products'][$_removeProductId]);
            // }} fastest way remove first pid
        }

        $check_status = 1;
        if (Info::isAdmin()){
                $check_status = 0;
        }
        if ( !isset($params['products_id']) || !Product::check_product($params['products_id'], $check_status, true) ) {
            throw new NotFoundHttpException('Page not found.');
        }
        if ($extClass = \common\helpers\Acl::checkExtensionAllowed('ObsoleteProducts', 'allowed')) {
            if ($redirectURL = $extClass::getRedirectObsoleteProductURL($params['products_id'])) {
                return $this->redirect($redirectURL);
            }
        }
        $message = '';
        if ($_SESSION['product_info']) {
            $message = $_SESSION['product_info'];
            unset($_SESSION['product_info']);
        }
        $products = Yii::$container->get('products');
        $product = $products->loadProducts(['products_id' => $params['products_id']])->getProduct($params['products_id']);
        
        $review_write_now = 0;
        if ( Yii::$app->request->getPathInfo()=='reviews/write' ) {
            $review_write_now = 1;
        }else{
            if (!is_array($cPath_array)) {
                $cPath_array = explode("_", Product::get_product_path($params['products_id'], 1, true));
            }
            if (isset($cPath_array)) {
                for ($i=0, $n=sizeof($cPath_array); $i<$n; $i++) {
                    $categories_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . (int)$cPath_array[$i] . "' and language_id = '" . (int)$languages_id . "'");
                    if (tep_db_num_rows($categories_query) > 0) {
                        $categories = tep_db_fetch_array($categories_query);
                        //$breadcrumb->add($categories['categories_name'], tep_href_link(FILENAME_DEFAULT, 'cPath=' . implode('_', array_slice($cPath_array, 0, ($i+1)))));
			$breadcrumb->add($categories['categories_name'], tep_href_link('catalog/index', 'cPath='.implode('_', array_slice($cPath_array, 0, ($i+1)))));
                    } else {
                        break;
                    }
                }
            }
            $breadcrumb->add($product['products_name'],tep_href_link(FILENAME_PRODUCT_INFO,'products_id='.$params['products_id']));
        }
        global $cart;
        if ($cart->in_cart($params['products_id'])/*Info::checkProductInCart($params['products_id'])*/){
            $message .= '<div>' . TEXT_ADDED_1 . ' <a href="' . tep_href_link(FILENAME_SHOPPING_CART) . '">' . TEXT_ADDED_2 . '</a>. <a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING) . '">' . TEXT_ADDED_3 . '</a>. ' . TEXT_ADDED_4 . '</div>';
        }
        
        $product_in_orders = 0;
        if (!Yii::$app->user->isGuest){
            $query = tep_db_fetch_array(tep_db_query("select count(op.products_id) as in_orders from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " op where o.orders_id = op.orders_id and o.customers_id = " . (int)Yii::$app->user->getId() . " and op.products_id = '" . (int)$params['products_id'] . "'"));
            $product_in_orders = $query['in_orders'];
            if ($product_in_orders == 1){
                $message .= '<div>' . TEXT_YOU_BOUGHT_THIS_ITEM . '</div>';
            }
            if ($product_in_orders > 1){
                $message .= '<div>' . TEXT_PURCHASED_MORE_THAN . '</div>';
            }
        }
        

        \Yii::$app->getView()->registerMetaTag([
            'property' => 'og:type',
            'content' => 'product'
        ],'og:type');
        \Yii::$app->getView()->registerMetaTag([
            'property' => 'og:url',
            'content' => tep_href_link('catalog/product', 'products_id=' . $params['products_id'])
        ],'og:url');

        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;
                
        \common\helpers\Seo::showNoindexMetaTag($product['noindex_option'], $product['nofollow_option']);
        if (!empty($product['rel_canonical'])) {
            \app\components\MetaCannonical::instance()->setCannonical($product['rel_canonical']);
        }else{
            \app\components\MetaCannonical::instance()->setCannonical(['catalog/product','products_id'=>(int)$params['products_id']]);
        }

        return $this->render('product.tpl', [
          'action' => tep_href_link('catalog/product', \common\helpers\Output::get_all_get_params(array('action')) . 'action=add_product'),
          'products_id' => $params['products_id'],
          'products_prid' => \common\helpers\Inventory::get_prid($params['products_id']),
          'review_write_now' => $review_write_now,
          'message' => $message,
          'page_name' => $page_name
        ]);
    }

    public function actionProductAttributes()
    {
        \common\helpers\Translation::init('catalog/product');

        $params = tep_db_prepare_input(Yii::$app->request->get());
        $products_id = tep_db_prepare_input(Yii::$app->request->get('products_id'));
        $attributes = tep_db_prepare_input(Yii::$app->request->get('id', array()));
        $type = Yii::$app->request->get('type', 'product');
        $options_prefix = '';

        if ($type=='listing' || $type == 'productListing') {
          $listid = tep_db_prepare_input(Yii::$app->request->get('listid', array()));
          if (!empty($listid[$products_id])) {
            $attributes = $listid[$products_id];
            $options_prefix = 'list';
          } elseif (!empty($listid)) {
            $attributes = $listid;
            $options_prefix = 'list';
          }
          if (!empty($params['listqty']) && is_array($params['listqty']) && count($params['listqty'])==1) {
            $params['qty'] = $params['listqty'][0];
            unset($params['listqty']);
          }
        }

        if (!$attributes && strpos($products_id, '{') !== false){
            \common\helpers\Inventory::normalize_id($products_id, $attributes);
        }

        global $cPath_array;

        $page_name = Yii::$app->request->get('page_name');
        if (!$page_name) {
            $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
            $this->view->page_name = $page_name;
        }

        $noAttr = false;
        if (is_array($attributes) && count($attributes) == 0) {
            $noAttr = true;
        }
        
        $details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);

        if ($noAttr) {
            $runAttributes = false;
            foreach ($details['attributes_array'] as $attr) {
                if ( $attr['selected'] ) {
                    $attributes[$attr['id']] = $attr['selected'];
                }else {
                    $attributes[$attr['id']] = $attr['options'][0]['id'];
                }
            }
            if ( $runAttributes ) {
                $details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);
            }
        }
        
        if ($productDesigner = \common\helpers\Acl::checkExtensionAllowed('ProductDesigner', 'allowed')){
            $productDesigner::productAttributes($details, $attributes, Yii::$app->request->get());
        }

        Yii::$container->get('products')->loadProducts(['products_id' => $details['current_uprid']])
                ->attachDetails($details['current_uprid'], ['attributes_array' => $details['attributes_array']]);
        try {
            $event = new ProductAttributesInfoEvent(
                $details,
                Yii::$app->user->isGuest ? false : \Yii::$app->user->getId()
            );
            \Yii::$container->get('eventDispatcher')->dispatch($event);
            $details = $event->getProductAttributes();
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }


        $product = Yii::$container->get('products')->getProduct($details['current_uprid']);
        if (Yii::$app->request->isAjax) {
            if ($product && $product['settings']->show_attributes_quantity){
                return \frontend\design\boxes\product\MultiInventory::widget();
            } else {
                if ($ext = \common\helpers\Acl::checkExtension('SupplierPurchase', 'allowed')){
                    if ($ext::allowed()){
                        $details['sp_collection'] = \common\extensions\SupplierPurchase\SupplierPurchase::getSpCollection($details['current_uprid']);
                    }
                }
                $details['image_widget'] = \frontend\design\boxes\product\Images::widget(['params'=>['uprid'=>$details['current_uprid']], 'settings' => \frontend\design\Info::widgetSettings('product\Images', false, 'product')]);
                if ($type == 'productListing'){
                    $details['product_attributes'] = \frontend\design\IncludeTpl::widget([
                        'file' => 'boxes/listing-product/element/attributes.tpl',
                        'params' => [
                            'product' => [
                                'product_attributes_details' => $details,
                                'product_has_attributes' => true,
                                'products_id' => $products_id,
                            ],
                        ]
                    ]);
                } elseif ($type == 'listing'){
                    $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/listing-product/attributes.tpl', 'params' => ['attributes' => $details['attributes_array'], 'isAjax' => true, 'products_id' => $products_id, 'options_prefix' => $options_prefix]]);
                } else {
                    $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/attributes.tpl', 'params' => ['attributes' => $details['attributes_array'], 'isAjax' => true]]);
                }
                $details['product_name'] = $product['products_name'];
                return json_encode($details);
            }
        } else {
            if (count($details['attributes_array']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/attributes.tpl', 'params' => ['attributes' => $details['attributes_array'], 'isAjax' => false, 'product' => $product]]);
            } else {
                return '';
            }
        }
    }

    public function actionProductNotify()
    {
        \common\helpers\Translation::init('catalog/product');

        $params = tep_db_prepare_input(Yii::$app->request->get());
        $suppliers_id = (int) $params['supplier_id'];
        // Inventory widget bof
        if (strpos($params['uprid'], '{') !== false) {
          $attrib = array();
          $ar = preg_split('/[\{\}]/', $params['uprid']);
          for ($i=1; $i<sizeof($ar); $i=$i+2) {
            if (isset($ar[$i+1])) {
              $attrib[$ar[$i]] = $ar[$i+1];
            }
          }
          $params['id'] = $attrib;
        }
        // Inventory widget eof
        $uprid = tep_db_input(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid($params['products_id'], $params['id'])));
        if (empty($params['id'])) {
            $check_item = tep_db_fetch_array(tep_db_query("select products_id, products_quantity from " . TABLE_PRODUCTS . " where products_id = '{$uprid}' limit 1"));
            $out_of_stock = $check_item['products_id'] && !(\common\helpers\Warehouses::get_products_quantity($uprid, 0, $suppliers_id) > 0);
            $item_found = $check_item['products_id'];
        } else {
            $check_item = tep_db_fetch_array(tep_db_query("select inventory_id, products_quantity from " . TABLE_INVENTORY . " where products_id like '{$uprid}' limit 1"));
            $out_of_stock = $check_item['inventory_id'] && !(\common\helpers\Warehouses::get_products_quantity($uprid, 0, $suppliers_id) > 0);
            $item_found = $check_item['inventory_id'];
        }
        if ($out_of_stock) {
            $products_notify_name = tep_db_input(tep_db_prepare_input($params['name']));
            $products_notify_email = tep_db_input(tep_db_prepare_input($params['email']));
            //$check_notify = tep_db_fetch_array(tep_db_query("select * from " . TABLE_PRODUCTS_NOTIFY . " where products_notify_products_id like '{$uprid}' and products_notify_email = '{$products_notify_email}' and suppliers_id = '{$suppliers_id}' limit 1"));
            $check_notify = \common\models\ProductsNotify::find()
                ->select('products_notify_id')
                ->andWhere([
                  'products_notify_products_id' => $uprid,
                  'products_notify_email' => $products_notify_email,
                  'suppliers_id' => $suppliers_id
                ])
                ->andWhere('products_notify_sent is null')
                ->one();
            if (!$check_notify['products_notify_id']) {
                tep_db_query("insert into " . TABLE_PRODUCTS_NOTIFY . " set products_notify_products_id = '{$uprid}', products_notify_email = '{$products_notify_email}', products_notify_name = '{$products_notify_name}', products_notify_customers_id = '". Yii::$app->user->getId()."', suppliers_id = '{$suppliers_id}', products_notify_date = now(), products_notify_sent = null");
                return YOU_WILL_BE_NOTIFIED;
            } else {
                return YOU_ALREADY_GOT_NOTIFY;
            }
        } else {
            return ($item_found ? ITEM_IS_IN_STOCK : ITEM_NOT_FOUND);
        }
    }

    public function actionProductRequestForQuote()
    {
        $messageStack = \Yii::$container->get('message_stack');

        \common\helpers\Translation::init('catalog/product');

        $params = tep_db_prepare_input(Yii::$app->request->post());
        if ( !Yii::$app->user->isGuest ) {
          $customer_info = tep_db_fetch_array(tep_db_query(
            "SELECT customers_firstname, customers_email_address FROM ".TABLE_CUSTOMERS." WHERE customers_id='".(int)Yii::$app->user->getId()."'"
          ));
          $customers_name = $customer_info['customers_firstname'];
          $customers_email = $customer_info['customers_email_address'];
        }else{
          $customers_name = $params['name'];
          $customers_email = $params['email'];
        }

        $check_error = false;
        if (strlen($customers_name) < ENTRY_FIRST_NAME_MIN_LENGTH) {
          $check_error = true;
          $messageStack->add(sprintf(NAME_IS_TOO_SHORT, ENTRY_FIRST_NAME_MIN_LENGTH), 'rfq_send');
        }
        if ( empty($customers_email) || !\common\helpers\Validations::validate_email($customers_email) ) {
          $check_error = true;
          $messageStack->add(ENTER_VALID_EMAIL, 'rfq_send');
        }
        if ( empty($params['message']) ) {
          $check_error = true;
          $messageStack->add(REQUEST_MESSAGE_IS_TOO_SHORT, 'rfq_send');
        }
        if ( $check_error ) {
          return $messageStack->output('rfq_send');
        }else {
          $uprid = tep_db_input(\common\helpers\Inventory::normalize_id(\common\helpers\Inventory::get_uprid($params['products_id'], $params['id'])));
          $product_name = Product::get_products_name($uprid);
          if ( strpos($uprid,'{')!==false ) {
            $check_item = tep_db_fetch_array(tep_db_query("select products_name from " . TABLE_INVENTORY . " where products_id like '{$uprid}' limit 1"));
            if( !empty($check_item['products_name']) ) {
              $product_name = $check_item['products_name'];
            }
          }


          $email_params = array();
          $email_params['STORE_NAME'] = STORE_NAME;
          $email_params['STORE_OWNER_EMAIL_ADDRESS'] = STORE_OWNER_EMAIL_ADDRESS;
          $email_params['CUSTOMER_NAME'] = $customers_name;
          $email_params['CUSTOMER_EMAIL'] = $customers_email;
          $email_params['PRODUCT_NAME'] = $product_name;
          $email_params['PRODUCT_URL'] = tep_href_link('catalog/product', 'products_id=' . $uprid);
          $email_params['REQUEST_MESSAGE'] = $params['message'];

          list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Request for quote', $email_params);

          \common\helpers\Mail::send(
            STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS,
            $email_subject, $email_text,
            $customers_name, $customers_email
          );

          return REQUEST_FOR_QUOTE_MESSAGE_SENT;
        }
    }

    public function actionSearch()
    {

        return '';
    }


    public function actionSearchSuggest()
    {
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $languages_id = \Yii::$app->settings->get('languages_id');
        $currency_id = \Yii::$app->settings->get('currency_id');
        $response = array();

        if (isset($_GET['keywords']) && $_GET['keywords'] != '') {
            
            $searchBuilder = new \common\components\SearchBuilder('complex');
            
            $keywords = tep_db_prepare_input($_GET['keywords']);
            
            $searchBuilder->setSearchInDesc(SEARCH_IN_DESCRIPTION == 'True');
            $searchBuilder->prepareRequest($_GET['keywords']);
            
            
            $_SESSION['keywords'] = $keywords;
            //Add slashes to any quotes to avoid SQL problems.
            // $search = preg_replace("/\//",'',tep_db_input(tep_db_prepare_input($_GET['keywords'])));  //???
            $search = $keywords;

            $replace_keywords = $searchBuilder->replaceWords;

            $sql_manufacturers = "select *, if(position('" . tep_db_input($search) . "' IN manufacturers_name), position('" . tep_db_input($search) . "' IN manufacturers_name), 100) as pos from " . TABLE_MANUFACTURERS . " where 1 " . $searchBuilder->getManufacturersArray() . " order by pos limit 0, 3";

            $sql_information = "select i.information_id, if(length(i1.info_title), i1.info_title, i.info_title) as info_title,  (if(length(i1.info_title), if(position('" . tep_db_input($search) . "' IN i1.info_title), position('" . tep_db_input($search) . "' IN i1.info_title), 100), if(position('" . tep_db_input($search) . "' IN i.info_title), position('" . tep_db_input($search) . "' IN i.info_title), 100))) as pos, 1 as is_category  from " . TABLE_INFORMATION . " i LEFT join " . TABLE_INFORMATION . " i1 on i.information_id = i1.information_id ".(platform::activeId()?" AND i1.platform_id='".platform::currentId()."' ":'')." and i1.affiliate_id = '" . (int)$_SESSION['affiliate_ref'] . "' and i1.languages_id='" . $languages_id ."'  where i.visible = 1 " . ($_SESSION['affiliate_ref']>0?" and i1.affiliate_id is not null ":'') . " and i.affiliate_id = 0 ".(platform::activeId()?" AND i.platform_id='".platform::currentId()."' ":'')." and i.languages_id = '" . $languages_id . "' " . $searchBuilder->getInformationsArray() . " order by pos limit 0, 3" ;

            reset($replace_keywords);
            foreach ($replace_keywords as $k => $v)
            {
                $patterns[] = "/" . preg_quote($v) . "/i";
                $replace[] = str_replace('$', '/$/', '<span class="typed">' . $v . '</span>');
            }

            $re = array();
            foreach ($replace_keywords as $k => $v)
                $re[] = preg_quote($v);
            $re = "/(" . join("|", $re) . ")/i";
            $replace = '<span class="typed">\1</span>';

            $ssboDef = ['Products', 'Categories', 'Manufacturers', 'Information'];
            $ssbo = [];
            if (defined('SEARCH_SUGGEST_BLOCKS_ORDER')) {
              $tmp = array_map('trim', explode(',', constant('SEARCH_SUGGEST_BLOCKS_ORDER')));
              foreach ($tmp as $t) {
                if (in_array($t, $ssboDef)) {
                  $ssbo[] = $t;
                }
              }
            }
            if (empty($ssbo)) {
              $ssbo = $ssboDef;
            }


            $mResponse = [];
            if (in_array('Manufacturers', $ssbo)) {
              $manufacturers_query = tep_db_query($sql_manufacturers);
              while ($manufacturers_array = tep_db_fetch_array($manufacturers_query)) {
                  $mResponse[] = array(
                      'type' => BOX_HEADING_MANUFACTURERS,
                      'type_class' => 'brands',
                      'link' => tep_href_link('catalog', 'manufacturers_id=' . $manufacturers_array['manufacturers_id']),
                      'image' => DIR_WS_IMAGES . $manufacturers_array['manufacturers_image'],
                      'title' => preg_replace($re, $replace, strip_tags($manufacturers_array['manufacturers_name'])),
                  );
              }
            }

            $iResponse = [];
            if (in_array('Information', $ssbo)) {
              $info_query = tep_db_query($sql_information);
              while ($info_array = tep_db_fetch_array($info_query)) {
                  $iResponse[] = array(
                    'type' => TEXT_INFORMATION,
                    'type_class' => 'info-suggest',
                    'link' => tep_href_link('info', 'info_id=' . $info_array['information_id']),
                    'title' => preg_replace($re, $replace, strip_tags($info_array['info_title'])),
                  );
              }
            }

            $cResponse = [];
            if (in_array('Categories', $ssbo)) {
              $cArray = \common\helpers\Categories::searchCategoryTreePlain($keywords, 0);
              foreach($cArray  as $info_array) {
                  $cResponse[] = array(
                    'type' => TEXT_CATEGORIES,
                    'type_class' => 'categories',
                    'link' => tep_href_link('catalog', 'cPath=' . $info_array['cPath']),
                    'title' => preg_replace($re, $replace, strip_tags($info_array['text'])),
                    'extra' =>  (!empty($info_array['parents']) && is_array($info_array['parents'])?
                            '<span class="brackets">(</span>' . implode('<span class="comma-sep">, </span>', \yii\helpers\ArrayHelper::getColumn($info_array['parents'], 'text')) .
                            '<span class="brackets">)</span>':''),
                  );
              }
            }

            $pResponse = [];
            if (in_array('Products', $ssbo)) {
              $q = new \common\components\ProductsQuery([
                'filters' => ['keywords' => $keywords],
                'limit' => 10
              ]);

              $products = Info::getListProductsDetails($q->buildQuery()->allIds());
              foreach ($products as $product_array) {
                  $pResponse[] = array(
                    'type' => TEXT_PRODUCTS,
                    'type_class' => 'products',
                    'link' => tep_href_link('catalog/product', 'products_id=' . $product_array['products_id']),
                    'image' => Images::getImageUrl($product_array['products_id'], 'Small'),
                    'title' => preg_replace($re, $replace, strip_tags($product_array['products_name'])),
                  );
              }
            }

            foreach ($ssbo as $t) {
              switch ($t) {
                case 'Products':
                  $response = array_merge($response, $pResponse);
                  break;
                case 'Manufacturers':
                  $response = array_merge($response, $mResponse);
                  break;
                case 'Information':
                  $response = array_merge($response, $iResponse);
                  break;
                case 'Categories':
                  $response = array_merge($response, $cResponse);
                  break;
              }
            }

        }

        return $this->render('search.tpl', ['list' => $response]);
    }

    public function actionSpecials(){
        return $this->redirect(Yii::$app->urlManager->createUrl(["catalog/sales"])) ;
    }
    
    public function actionSales(){
        global $breadcrumb;

        $breadcrumb->add(NAVBAR_TITLE,tep_href_link(FILENAME_SPECIALS));

        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) $search_results = SEARCH_RESULTS_1;

        $q = new \common\components\ProductsQuery([
          'get' => \Yii::$app->request->get(),
          'page' => 'sales'
        ]);
        $cnt = $q->getCount();
        \Yii::$app->set('productsFilterQuery', $q);

        $params = array(
          'listing_split' => SplitPageResults::make($q->buildQuery()->getQuery(), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'*', 'page', $cnt)->withSeoRelLink(),
          'this_filename' => FILENAME_SPECIALS
          /*,
          'sorting_options' => $sorting,
          'sorting_id' => Info::sortingId(),*/
        );
        if (Yii::$app->request->get('onlyFilter')) {
            if ($ext = \common\helpers\Acl::checkExtension('ProductPropertiesFilters', 'inFilters')) {
                if ($ext::allowed()) {
                    return $ext::inFilters($params, []);
                }
            }
        }

        if (Yii::$app->request->get('onlyProducts')) {
            $this->layout = false;
            return Listing::widget([
                'params' => $params,
                'settings' => array_merge(Info::widgetSettings('Listing', false, $page_name), ['onlyProducts' => true])
            ]);
        }
        
        if ($_GET['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }
      return $this->render('specials.tpl', ['params' => ['params'=>$params]]);
		}

    public function actionFeaturedProducts(){
        global $breadcrumb;

        $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_FEATURED_PRODUCTS));

        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) $search_results = SEARCH_RESULTS_1;

        $q = new \common\components\ProductsQuery([
          'get' => \Yii::$app->request->get(),
          'page' => 'featured'
        ]);

        $cnt = $q->getCount();
        \Yii::$app->set('productsFilterQuery', $q);

        $params = array(
          'listing_split' => SplitPageResults::make($q->buildQuery()->getQuery(), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'*', 'page', $cnt)->withSeoRelLink(),
          'this_filename' => FILENAME_FEATURED_PRODUCTS
          /*,
          'sorting_options' => $sorting,
          'sorting_id' => Info::sortingId(),*/
        );
        if (Yii::$app->request->get('onlyFilter')) {
            if ($ext = \common\helpers\Acl::checkExtension('ProductPropertiesFilters', 'inFilters')) {
                if ($ext::allowed()) {
                    return $ext::inFilters($params, []);
                }
            }
        }

        if (Yii::$app->request->get('onlyProducts')) {
            $this->layout = false;
            return Listing::widget([
                'params' => $params,
                'settings' => array_merge(Info::widgetSettings('Listing', false, $page_name), ['onlyProducts' => true])
            ]);
        }

        if ($_GET['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }
        return $this->render('featured-products.tpl', ['params' => ['params'=>$params]]);
    }

    public function actionProductsNew(){
        global $breadcrumb;

        $breadcrumb->add(NAVBAR_TITLE,tep_href_link(FILENAME_PRODUCTS_NEW));

        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) {
          $search_results = SEARCH_RESULTS_1;
        }
        
        $q = new \common\components\ProductsQuery([
          'orderBy' => ['products_date_added' => SORT_DESC],
          'get' => \Yii::$app->request->get(),
        ]);
        $cnt = $q->getCount();
        \Yii::$app->set('productsFilterQuery', $q);

        $params = array(
          'listing_split' => SplitPageResults::make($q->buildQuery()->getQuery(), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'*', 'page', $cnt)->withSeoRelLink(),
          'this_filename' => FILENAME_PRODUCTS_NEW,
          'sorting_id' => \Yii::$app->request->get('sort') ?? 'dd',
        );
        if (Yii::$app->request->get('onlyFilter')) {
            if ($ext = \common\helpers\Acl::checkExtension('ProductPropertiesFilters', 'inFilters')) {
                if ($ext::allowed()) {
                    return $ext::inFilters($params, []);
                }
            }
        }

        if (Yii::$app->request->get('onlyProducts')) {
            $this->layout = false;
            return Listing::widget([
                'params' => $params,
                'settings' => array_merge(Info::widgetSettings('Listing', false, $page_name), ['onlyProducts' => true])
            ]);
        }

        if ($_GET['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }
        return $this->render('products-new.tpl', ['params' => ['params'=>$params]]);
    }

    public function actionAllProducts(){
        global $breadcrumb;

        $breadcrumb->add(NAVBAR_TITLE,tep_href_link(FILENAME_ALL_PRODUCTS));

        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) {
          $search_results = SEARCH_RESULTS_1;
        }

        $q = new \common\components\ProductsQuery([
          'get' => \Yii::$app->request->get(),
        ]);
        $cnt = $q->getCount();
        \Yii::$app->set('productsFilterQuery', $q);

        $params = array(
          'listing_split' => SplitPageResults::make($q->buildQuery()->getQuery(), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'*', 'page', $cnt)->withSeoRelLink(),
          'this_filename' => FILENAME_ALL_PRODUCTS
          /*,
          'sorting_options' => $sorting,
          'sorting_id' => Info::sortingId(),*/
        );

        if ($_GET['page_name']) {
            $page_name = $_GET['page_name'];
        } else {
            $page_name = 'products';
        }

        if (Yii::$app->request->get('onlyFilter')) {
            if ($ext = \common\helpers\Acl::checkExtension('ProductPropertiesFilters', 'inFilters')) {
                if ($ext::allowed()) {
                    return $ext::inFilters($params, []);
                }
            }
        }

        if (Yii::$app->request->get('onlyProducts')) {
            $this->layout = false;
            return Listing::widget([
                'params' => $params,
                'settings' => array_merge(Info::widgetSettings('Listing', false, $page_name), ['onlyProducts' => true])
            ]);
        }

        if ($_GET['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }

        return $this->render('all-products.tpl', [
            'page_name' => $page_name,
            'params' => [
                'params'=>$params,
                'type' => 'catalog',
            ]]);
    }

    public function actionAdvancedSearch()
    {
        global $breadcrumb, $languages_id;
        $messageStack = \Yii::$container->get('message_stack');
        $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_ADVANCED_SEARCH));

        $messages_search = '';
        if ($messageStack->size('search') > 0) {
            $messages_search = $messageStack->output('search');
        }
        $controls = array(
          'keywords' => tep_draw_input_field('keywords', '', ''),
          'search_in_description' => tep_draw_checkbox_field('search_in_description', '1', SEARCH_IN_DESCRIPTION == 'True', 'id="search_in_description"'),
          'categories' => tep_draw_pull_down_menu('categories_id', \common\helpers\Categories::get_categories(array(array('id' => '', 'text' => TEXT_ALL_CATEGORIES)))),
           'inc_subcat' => tep_draw_checkbox_field('inc_subcat', '1', true, 'id="include_subcategories"'),
           'manufacturers' => '',
           'price_from' => tep_draw_input_field('pfrom'),
           'price_to' => tep_draw_input_field('pto'),
           //'date_from' => tep_draw_input_field('dfrom', '', 'placeholder="' . \common\helpers\Output::output_string(DOB_FORMAT_STRING) . '"'),
           //'date_to' => tep_draw_input_field('dto', '', 'placeholder="' . \common\helpers\Output::output_string(DOB_FORMAT_STRING) . '"'),
        );

        $site_manufacturers = \common\helpers\Manufacturers::get_manufacturers();
        if ( count($site_manufacturers)>0 ) {
           $site_manufacturers = array_merge(array(array('id' => '', 'text' => TEXT_ALL_MANUFACTURERS)), $site_manufacturers);
           $controls['manufacturers'] = tep_draw_pull_down_menu('manufacturers_id', $site_manufacturers);
        }

        $searchable_properties = array();
        if (PRODUCTS_PROPERTIES == 'True') {
			
			$p_types = array_keys(\common\helpers\PropertiesTypes::getTypes('search'));
			
            $properties_yes_no_array = array(array('id' => '', 'text' => OPTION_NONE), array('id' => 'true', 'text' => OPTION_TRUE), array('id' => 'false', 'text' => OPTION_FALSE));
            $properties_query = tep_db_query("select pr.properties_id, pr.properties_type, prd.properties_name, prd.properties_description, pr.multi_choice, pr.decimals from " . TABLE_PROPERTIES_DESCRIPTION . " prd, " . TABLE_PROPERTIES . " pr where pr.properties_id = prd.properties_id and prd.language_id = '" . (int)$languages_id . "' and pr.properties_type in ('".implode("', '", $p_types)."') and pr.display_search = 1 order by pr.sort_order, prd.properties_name");
            if (tep_db_num_rows($properties_query) > 0) {//need to do
                while ($properties_array = tep_db_fetch_array($properties_query)) {
                    $properties_array['control'] = '';
					
                    switch ($properties_array['properties_type']){
                        case 'text':
						case 'number':
						case 'interval':
						
							$properties_values_query = tep_db_query("select values_id, values_text, values_number, values_number_upto, values_alt from " . TABLE_PROPERTIES_VALUES . " where properties_id = '" . (int)$properties_array['properties_id'] . "' and language_id = '" . (int)$languages_id . "' order by " . ($properties_array['properties_type'] == 'number' || $properties_array['properties_type'] == 'interval' ? 'values_number' : 'values_text'));

							if ($properties_array['multi_choice']){
								$f = 'tep_draw_checkbox_field';
							} else {
								$f = 'tep_draw_radio_field';
							}
							
							if (tep_db_num_rows($properties_values_query)){
								while ($property_values = tep_db_fetch_array($properties_values_query)){//echo '<pre>';print_r($property_values);
									if ($properties_array['properties_type'] == 'interval'){
										$properties_array['control'] .= $f($properties_array['properties_id']) . (float)number_format($property_values['values_number'], $properties_array['decimals']) . ' - ' . (float)number_format($property_values['values_number_upto'], $properties_array['decimals']);
									} elseif($properties_array['properties_type'] == 'number'){
										$properties_array['control'] .= $f($properties_array['properties_id'] ) .(float)number_format($property_values['values_number'], $properties_array['decimals']);
									} else {
										$properties_array['control'] .= $f($properties_array['properties_id'] ) .  $property_values['values_text'];
									}
								}
							}
								
					
                        break;
                        case 'flag':
                            $properties_array['control'] .= tep_draw_pull_down_menu($properties_array['properties_id'], $properties_yes_no_array);
                            break;
                    }
                    $searchable_properties[] = $properties_array;
                }
            }
        }
        return $this->render('advanced_search.tpl', [
          'messages_search' => $messages_search,
          'controls' => $controls,
          'searchable_properties' => $searchable_properties,
          'search_result_page_link' => tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT,'','NONSSL'),

            //'params' => ['params'=>$params]
          ]);
    }

    public function actionManufacturers(){
      return Yii::$app->runAction('catalog/brands');
    }
    
    public function actionBrands()
    {
        global $breadcrumb;
        \common\helpers\Translation::init('catalog/manufacturers');
        $breadcrumb->add(NAVBAR_TITLE, \Yii::$app->urlManager->createUrl(['catalog/brands']));
        $page_name = 'manufacturers';
        $params = array();
        $params['page_name'] = $page_name;

        return $this->render('manufacturers.tpl',[
          'params' => $params,
          'page_name' => $page_name
        ]);
    }

    public function actionCompare()
    {
        $compare = Yii::$app->request->get('compare');
        $currencies = Yii::$container->get('currencies');

        $error_text = '';
        if (!is_array($compare) || count($compare) < 2 || count($compare) > 4) {
            $error_text = TEXT_PLEASE_SELECT_COMPARE;
        } else {
            $properties_array = array();
            $values_array = array();
            $properties_query = tep_db_query("select p.properties_id, if(p2p.values_id > 0, p2p.values_id, p2p.values_flag) as values_id from " . TABLE_PROPERTIES_TO_PRODUCTS . " p2p, " . TABLE_PROPERTIES . " p where p2p.properties_id = p.properties_id and p.display_compare = '1' and p2p.products_id in ('" . implode("','", array_map('intval', $compare)) . "')");
            while ($properties = tep_db_fetch_array($properties_query)) {
                if (!in_array($properties['properties_id'], $properties_array)) {
                    $properties_array[] = $properties['properties_id'];
                }
                $values_array[$properties['properties_id']][] = $properties['values_id'];
            }
            $properties_tree_array = \common\helpers\Properties::generate_properties_tree(0, $properties_array, $values_array);

            $products_data_array = array();
            foreach ($compare as $products_id) {
                $products_arr = tep_db_fetch_array(tep_db_query("select products_id, products_model, products_price, products_tax_class_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'"));
                $products_data_array[$products_id]['id'] = $products_id;
                $products_data_array[$products_id]['model'] = $products_arr['products_model'];
                $products_data_array[$products_id]['name'] = Product::get_products_name($products_id);
                $special_price = Product::get_products_special_price($products_id);
                if ($special_price){
                  $products_data_array[$products_id]['price_old'] = $currencies->display_price(Product::get_products_price($products_id, 1, $products_arr['products_price']), \common\helpers\Tax::get_tax_rate($products_arr['products_tax_class_id']));
                  $products_data_array[$products_id]['price_special'] = $currencies->display_price($special_price, \common\helpers\Tax::get_tax_rate($products_arr['products_tax_class_id']));
                } else {
                  $products_data_array[$products_id]['price'] = $currencies->display_price(Product::get_products_price($products_id, 1, $products_arr['products_price']), \common\helpers\Tax::get_tax_rate($products_arr['products_tax_class_id']));
                }
                $products_data_array[$products_id]['link'] = tep_href_link('catalog/product', 'products_id=' . $products_id);
                $products_data_array[$products_id]['link_buy'] = tep_href_link('catalog/product', 'action=buy_now&products_id=' . $products_id);
                $products_data_array[$products_id]['image'] = Images::getImageUrl($products_id, 'Small');

                $properties_array = array();
                $values_array = array();
                $properties_query = tep_db_query("select p.properties_id, if(p2p.values_id > 0, p2p.values_id, p2p.values_flag) as values_id from " . TABLE_PROPERTIES_TO_PRODUCTS . " p2p, " . TABLE_PROPERTIES . " p where p2p.properties_id = p.properties_id and p.display_compare = '1' and p2p.products_id = '" . (int)$products_id . "'");
                while ($properties = tep_db_fetch_array($properties_query)) {
                    if (!in_array($properties['properties_id'], $properties_array)) {
                        $properties_array[] = $properties['properties_id'];
                    }
                    $values_array[$properties['properties_id']][] = $properties['values_id'];
                }
                $products_data_array[$products_id]['properties_tree'] = \common\helpers\Properties::generate_properties_tree(0, $properties_array, $values_array);
            }

            foreach ($properties_tree_array as $properties_id => $property) {
                $values_array = array();
                foreach ($products_data_array as $products_id => $products_data) {
                    if (is_array($products_data['properties_tree'][$properties_id]['values'])) {
                      $values_array[] = trim(implode(' ', $products_data['properties_tree'][$properties_id]['values']));
                    } else {
                      $values_array[] = '';
                    }
                }
                $unique_values_array = array_unique($values_array);
                if (count($unique_values_array) > 1 /* || trim($unique_values_array[0]) == '' */) {
                    $properties_tree_array[$properties_id]['vary'] = true;
                } else {
                    $properties_tree_array[$properties_id]['vary'] = false;
                }
            }
        }

        return $this->render('compare.tpl', [
            'error_text' => $error_text,
            'products_data_array' => $products_data_array,
            'properties_tree_array' => $properties_tree_array,
            'standAlonePage' => !Yii::$app->request->isAjax,
        ]);
    }

    public function actionGiftCard()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        \common\helpers\Translation::init('js');
        $product_info = tep_db_fetch_array(tep_db_query("select p.products_id, p.products_tax_class_id, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description), pd1.products_description, pd.products_description) as products_description from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int)$languages_id ."' and pd1.platform_id = '".(int)Yii::$app->get('platform')->config()->getPlatformToDescription()."', " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_model = 'VIRTUAL_GIFT_CARD' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id ."' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."'"));        
        
        if ( !($product_info['products_id'] > 0) ) {
          return $this->redirect(Yii::$app->urlManager->createUrl('/'));
        }
        $messageStack = \Yii::$container->get('message_stack');
        $currencies = Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        
        if (isset($_GET['action']) && ($_GET['action'] == 'add_gift_card')) {
          $virtual_gift_card = tep_db_fetch_array(tep_db_query("select virtual_gift_card_basket_id, products_price as gift_card_price, virtual_gift_card_recipients_name, virtual_gift_card_recipients_email, virtual_gift_card_message, virtual_gift_card_senders_name from " . TABLE_VIRTUAL_GIFT_CARD_BASKET . " where length(virtual_gift_card_code) = 0 and virtual_gift_card_basket_id = '" . (int)preg_replace("/\d+\{0\}/","", Yii::$app->request->get('products_id')) . "' and products_id = '" . (int)$product_info['products_id'] . "' and currencies_id = '" . (int)$currencies->currencies[$currency]['id'] . "' and " . (!Yii::$app->user->isGuest ? " customers_id = '" . (int)Yii::$app->user->getId() . "'" : " session_id = '" . Yii::$app->getSession()->get('gift_handler') . "'")));
          
          $gift_card_price = tep_db_prepare_input($_POST['gift_card_price']);
          $virtual_gift_card_recipients_name = tep_db_prepare_input($_POST['virtual_gift_card_recipients_name']);
          $virtual_gift_card_recipients_email = tep_db_prepare_input($_POST['virtual_gift_card_recipients_email']);
          $virtual_gift_card_confirm_email = tep_db_prepare_input($_POST['virtual_gift_card_confirm_email']);
          $virtual_gift_card_message = tep_db_prepare_input($_POST['virtual_gift_card_message']);
          $virtual_gift_card_senders_name = tep_db_prepare_input($_POST['virtual_gift_card_senders_name']);
          $gift_card_design = tep_db_prepare_input($_POST['gift_card_design']);

          $error = false;

          if (strlen($virtual_gift_card_recipients_email) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $error = true;
            $messageStack->add(ENTRY_RECIPIENTS_EMAIL_ERROR, 'virtual_gift_card');
          }

          if (!\common\helpers\Validations::validate_email($virtual_gift_card_recipients_email)) {
            $error = true;
            $messageStack->add(ENTRY_RECIPIENTS_EMAIL_CHECK_ERROR, 'virtual_gift_card');
          }

          if ($virtual_gift_card_recipients_email != $virtual_gift_card_confirm_email) {
            $error = true;
            $messageStack->add(ENTRY_CONFIRM_EMAIL_ERROR, 'virtual_gift_card');
          }
          
          $send_card_date_value='00-00-0000';
          if ((int)$_POST['send_card_date']>0){
            $send_card_date = (int)$_POST['send_card_date'];
            $_date = \common\helpers\Date::prepareInputDate($_POST['send_card_date_value']);
            if (strtotime($_date)<time()){
                $error = true;
                $messageStack->add(ENTRY_THE_SEND_CART_DATE_ERROR, 'virtual_gift_card');
            }else {
                $send_card_date_value=date('Y-m-d',strtotime($_date));
            }
          }
          

          if ($error == false) {
            $_price = \common\models\VirtualGiftCardPrices::find()->where(['products_id' => $product_info['products_id'], 'currencies_id' => $currencies->currencies[$currency]['id'], 'products_price' => $gift_card_price])->one();
            $sql_data_array = array('customers_id' => Yii::$app->user->getId(),
                                    'session_id' => Yii::$app->user->getId() > 0 ? '' : Yii::$app->getSession()->get('gift_handler'),
                                    'currencies_id' => $currencies->currencies[$currency]['id'],
                                    'products_id' => $product_info['products_id'],
                                    'products_price' => $gift_card_price,
                                    'products_discount_price' => $_price->products_discount_price,
                                    'virtual_gift_card_recipients_name' => $virtual_gift_card_recipients_name,
                                    'virtual_gift_card_recipients_email' => $virtual_gift_card_recipients_email,
                                    'virtual_gift_card_message' => $virtual_gift_card_message,
                                    'virtual_gift_card_senders_name' => $virtual_gift_card_senders_name,
                                    'send_card_date' => $send_card_date_value,
                                    'virtual_gift_card_code' => '',
                                    'gift_card_design' => $gift_card_design);

            if ($virtual_gift_card['virtual_gift_card_basket_id'] > 0) {
              tep_db_perform(TABLE_VIRTUAL_GIFT_CARD_BASKET, $sql_data_array, 'update', "virtual_gift_card_basket_id = '" . (int)$virtual_gift_card['virtual_gift_card_basket_id'] . "'");
            } else {
              tep_db_perform(TABLE_VIRTUAL_GIFT_CARD_BASKET, $sql_data_array);
            }

            return $this->redirect(Yii::$app->urlManager->createUrl('shopping-cart/'));
          }
        }
        if (Yii::$app->user->isGuest && !Yii::$app->getSession()->has('gift_handler')){
            Yii::$app->getSession()->set('gift_handler', Yii::$app->getSecurity()->generateRandomString());
        }
        $params = [];
        
        return $this->render('gift-card.tpl', ['params' => $params]);
    }

    public function actionGift(){

        $this->layout = false;

        $page_name = 'gift_card';
        $params = tep_db_prepare_input(Yii::$app->request->get());
        if ($params['page_name']){

            $templates = \common\models\ThemesSettings::find()
                ->select(['setting_value'])
                ->where([
                    'theme_name' => THEME_NAME,
                    'setting_group' => 'added_page',
                    'setting_name' => 'gift_card',
                ])
                ->asArray()
                ->all();

            foreach ($templates as $template) {
                if (\common\classes\design::pageName($template['setting_value']) == $params['page_name']) {
                    $page_name = $params['page_name'];
                }
            }

        }

        $page_name = \common\classes\design::pageName($page_name);
        return $this->render('gift.tpl', [
            'page_name' => $page_name,
            'params' => ['absoluteUrl' => true]
        ]);
    }

    public function actionGetPrice() {
        $this->layout = false;
        $params = tep_db_prepare_input(Yii::$app->request->post());
        if (empty($params['pid'])) {
          return ;
        }
        /** @var \common\extensions\PackUnits\PackUnits $ext */
        if ($ext = \common\helpers\Acl::checkExtension('PackUnits', 'getPricePack')) {
            $listid = tep_db_prepare_input(Yii::$app->request->post('listid', array()));
            if (is_array($listid[$params['pid']])) {
              $params['id'] = $listid[$params['pid']];
            } elseif (!empty($listid)) {
              $params['id'] = $listid;
            }
            $ext::getPricePack(0, false, $params);
        }
        else {
          \common\helpers\Translation::init('catalog/product');

          $products_id = $params['pid'];
          $attributes = ($params['id']?$params['id']:[]);
          $listid = tep_db_prepare_input(Yii::$app->request->post('listid', array()));
          if (!empty($listid[$products_id])) {
            $attributes = $listid[$products_id];
          } elseif (!empty($listid)) {
            $attributes = $listid;
          }

          $type = ($params['type']?$params['type']:'product');
          if (!$attributes && strpos($products_id, '{') !== false){
              \common\helpers\Inventory::normalize_id($products_id, $attributes);
          }
/*
          global $cPath_array;

          $page_name = Yii::$app->request->get('page_name');
          if (!$page_name) {
              $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
              $this->view->page_name = $page_name;
          }
*/
          $noAttr = false;
          if (is_array($attributes) && count($attributes) == 0) {
              $noAttr = true;
          }

          $details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);

          if ($noAttr) {
              foreach ($details['attributes_array'] as $attr) {
                  $attributes[$attr['id']] = $attr['options'][0]['id'];
              }
              $details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);
          }

          Yii::$container->get('products')->loadProducts(['products_id' => $details['current_uprid']])
                  ->attachDetails($details['current_uprid'], ['attributes_array' => $details['attributes_array']]);
          try {
              $event = new ProductAttributesInfoEvent(
                  $details,
                  Yii::$app->user->isGuest ? false : \Yii::$app->user->getId()
              );
              \Yii::$container->get('eventDispatcher')->dispatch($event);
              $details = $event->getProductAttributes();
          } catch (\Exception $e) {
              \Yii::error($e->getMessage());
          }


          $product = Yii::$container->get('products')->getProduct($details['current_uprid']);

          if (0 && $product && $product['settings']->show_attributes_quantity){
              return \frontend\design\boxes\product\MultiInventory::widget();
          } else {
/*              $details['image_widget'] = \frontend\design\boxes\product\Images::widget(['params'=>['uprid'=>$details['current_uprid']], 'settings' => \frontend\design\Info::widgetSettings('product\Images', false, 'product')]);
              if ($type == 'listing'){
                  $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/listing-product/attributes.tpl', 'params' => ['attributes' => $details['attributes_array'], 'isAjax' => true, 'products_id' => $products_id]]);
              } else {
                  $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/attributes.tpl', 'params' => ['attributes' => $details['attributes_array'], 'isAjax' => true]]);
              }
              $details['product_name'] = $product['products_name'];*/
              return json_encode($details);
          }


        }
    }

    public function actionProductInventory()
    {
        \common\helpers\Translation::init('catalog/product');
        $params = Yii::$app->request->get();
        global $cPath_array;
        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;

        $params = Yii::$app->request->get();
        $products_id = Yii::$app->request->get('products_id');
        $inv_uprid = Yii::$app->request->get('inv_uprid');        

        $details = \common\helpers\Inventory::getDetails($products_id, $inv_uprid, $params);

        if (Yii::$app->request->isAjax) {
//            $details['image_widget'] = \frontend\design\boxes\product\Images::widget(['params'=>['uprid'=>$details['current_uprid']], 'settings' => \frontend\design\Info::widgetSettings('product\Images', false, 'product')]);
            $details['product_inventory'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/inventory.tpl', 'params' => ['inventory' => $details['inventory_array'], 'isAjax' => true]]);
            return json_encode($details);
        } else {
            if (count($details['inventory_array']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/inventory.tpl', 'params' => ['inventory' => $details['inventory_array'], 'isAjax' => false,]]);
            } else {
                return '';
            }
        }
    }

    public function actionProductBundle()
    {
        \common\helpers\Translation::init('catalog/product');

        $params = Yii::$app->request->get();
        $products_id = tep_db_prepare_input(Yii::$app->request->get('products_id'));
        $attributes = tep_db_prepare_input(Yii::$app->request->get('id', array()));
        global $cPath_array;
        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;

        $attributes_details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);
        $details = \common\helpers\Bundles::getDetails($params, $attributes_details);        

        if (Yii::$app->request->isAjax) {
            $details['product_bundle'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/bundle.tpl', 'params' => ['products' => $details['bundle_products'], 'isAjax' => true]]);
            $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/attributes.tpl', 'params' => ['attributes' => $attributes_details['attributes_array'], 'isAjax' => true]]);
            return json_encode($details);
        } else {
            if (is_array($details['bundle_products']) && count($details['bundle_products']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/bundle.tpl', 'params' => ['products' => $details['bundle_products'], 'isAjax' => false,]]);
            } else {
                return '';
            }
        }
    }

    public function actionProductConfigurator()
    {
        \common\helpers\Translation::init('catalog/product');
        
        $params = Yii::$app->request->get();
        global $cPath_array;
        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;

        $params = tep_db_prepare_input(Yii::$app->request->get());
        $products_id = tep_db_prepare_input(Yii::$app->request->get('products_id'));
        $attributes = tep_db_prepare_input(Yii::$app->request->get('id', array()));

        $attributes_details = \common\helpers\Attributes::getDetails($products_id, $attributes, $params);
        Yii::$container->get('products')->loadProducts(['products_id' => $attributes_details['current_uprid']]);
        $details = \common\helpers\Configurator::getDetails($params, $attributes_details);
        $product = Yii::$container->get('products')->getProduct($details['current_uprid']);
        if (Yii::$app->request->isAjax) {
            $details['product_configurator'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/configurator.tpl', 'params' => ['settings' => Info::widgetSettings('product\\Configurator'), 'elements' => $details['configurator_elements'], 'pctemplates_id' => $details['pctemplates_id'], 'isAjax' => true]]);
            if ($product['settings']->show_attributes_quantity){
                $details['product_attributes'] = \frontend\design\boxes\product\MultiInventory::widget();
            } else 
                $details['product_attributes'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/attributes.tpl', 'params' => ['settings' => Info::widgetSettings('product\\Configurator'), 'attributes' => $attributes_details['attributes_array'], 'isAjax' => true, 'product' => $product]]);
            return json_encode($details);
        } else {
            if (isset($details['configurator_elements']) && count($details['configurator_elements']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/configurator.tpl', 'params' => ['settings' => Info::widgetSettings('product\\Configurator'), 'elements' => $details['configurator_elements'], 'pctemplates_id' => $details['pctemplates_id'], 'isAjax' => false, 'product' => $product ]]);
            } else {
                return '';
            }
        }
    }

    public function actionProductConfiguratorInfo()
    {

        global $cPath_array;
        $languages_id = \Yii::$app->settings->get('languages_id');
        $params = Yii::$app->request->get();
        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;
        
        \common\helpers\Translation::init('catalog/product');

        $params = Yii::$app->request->get();

        if ($params['tID'] > 0 && $params['eID'] > 0) {
            $product_info_values = array();
            $product_info_sql = "select p.products_id, p.products_status, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description), pd1.products_description, pd.products_description) as products_description, p.products_model, p.products_image, pd.products_url, p.products_price, p.products_tax_class_id, p.products_date_added, p.products_date_available, p.manufacturers_id from " . TABLE_PRODUCTS_TO_PCTEMPLATES_TO_ELEMENTS . " ppe, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id = '" . (int)$languages_id . "' and pd1.platform_id = '" . (int)Yii::$app->get('platform')->config()->getPlatformToDescription() . "' where p.products_id = ppe.products_id and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and ppe.pctemplates_id = '" . (int)$params['tID'] . "' and ppe.elements_id = '" . (int)$params['eID'] . "'";
            if ($params['pID'] > 0) {
                $product_info_sql .= " and p.products_id = '" . (int) $params['pID'] . "'";
            }
            $product_info_query = tep_db_query($product_info_sql);
            while ($product_info = tep_db_fetch_array($product_info_query)) {
                $product_info_values[] = $product_info;
            }
            return IncludeTpl::widget(['file' => 'boxes/product/pc_info.tpl', 'params' => ['product_info_values' => $product_info_values]]);
        } else {
            return '';
        }
    }
    
    public function getHerfLang($platforms_languages){
        $except = [];
        if (isset($_GET['products_id'])){
            $pages = tep_db_query("select products_seo_page_name as seo_page_name, language_id from " . TABLE_PRODUCTS_DESCRIPTION . " where platform_id = '".(int)Yii::$app->get('platform')->config()->getPlatformToDescription()."' and products_id = '" . (int)$_GET['products_id'] . "' and language_id in (" . implode(",", array_values($platforms_languages)) . ")");
            $except[] = $_GET['products_id'];
        } else if (isset($_GET['cPath'])){
            $ex = explode("_", $_GET['cPath']);
            $pages = tep_db_query("select categories_seo_page_name as seo_page_name, language_id from " . TABLE_CATEGORIES_DESCRIPTION . " where affiliate_id = 0 and categories_id = '" . (int)$ex[count($ex)-1] . "' and language_id in (" . implode(",", array_values($platforms_languages)) . ")");
            $except[] = $_GET['cPath'];
        } else {
            $list = [];
            if (is_array($platforms_languages)){
                $route = Yii::$app->urlManager->parseRequest(Yii::$app->request);
                foreach($platforms_languages as $pl){
                    $list[$pl] = (isset($route[0])?$route[0]:$this->getRoute());
                }
            }
            return $list;
        }
        
        $list = [];
        if (tep_db_num_rows($pages)){
            while($page = tep_db_fetch_array($pages)){
                $list[$page['language_id']] = [$page['seo_page_name'], $except];
            }
        }
        return $list;
    }

    public function actionProductCollection()
    {
        \common\helpers\Translation::init('catalog/product');

        $params = Yii::$app->request->get();
        global $cPath_array;
        $page_name = \frontend\design\Product::pageName($params['products_id'], $cPath_array);
        $this->view->page_name = $page_name;

        $details = \common\helpers\Collections::getDetails($params);

        if (Yii::$app->request->isAjax) {
            $details['product_collection'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/collection.tpl',
                'params' => [
                    'products' => $details['all_products'],
                    'product' => $details['curr_product'],
                    'chosenProducts' => $details['collection_products'],
                    'old' => $details['collection_full_price'],
                    'price' => $details['collection_full_price'],
                    'special' => $details['collection_discount_price'],
                    'save' => $details['collection_discount_percent'],
                    'savePrice' => $details['collection_save_price'],
                    'isAjax' => true
                ]
            ]);
            return json_encode($details);
        } else {
            if (isset($details['all_products']) && count($details['all_products']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/collection.tpl',
                    'params' => [
                        'products' => $details['all_products'],
                        'product' => $details['curr_product'],
                        'chosenProducts' => $details['collection_products'],
                        'old' => $details['collection_full_price'],
                        'price' => $details['collection_full_price'],
                        'special' => $details['collection_discount_price'],
                        'save' => $details['collection_discount_percent'],
                        'savePrice' => $details['collection_save_price'],
                        'isAjax' => false
                    ]
                ]);
            } else {
                return '';
            }
        }
    }

    public function actionFreeSamples() {
        global $breadcrumb;

        $breadcrumb->add(NAVBAR_TITLE,tep_href_link('catalog/free-samples'));

        $sorting = Sorting::getSortingList();
        
        $sorting[] = array('id' => 'da', 'title' => TEXT_BY_DATE . ' &darr;');
        $sorting[] = array('id' => 'dd', 'title' => TEXT_BY_DATE . ' &uarr;');

        $search_results = Info::widgetSettings('Listing', 'items_on_page', 'products');
        if (!$search_results) $search_results = SEARCH_RESULTS_1;
        
        $params = array(
          'listing_split' => SplitPageResults::make(ListingSql::query(array('filename' => 'catalog/free-samples', 'only_samples' => true)), (isset($_SESSION['max_items'])?$_SESSION['max_items']:$search_results),'p.products_id')->withSeoRelLink(),
          'this_filename' => 'catalog/free-samples',
          'sorting_options' => $sorting,
          'sorting_id' => Info::sortingId(),
        );

        if ($_GET['fbl']) {
            $this->layout = 'ajax.tpl';
            return Listing::widget(['params' => $params, 'settings' => Info::widgetSettings('Listing')]);
        }

        if ($_GET['page_name']) {
            $page_name = $_GET['page_name'];
        } else {
            $page_name = 'products';
        }

        return $this->render('free-samples.tpl', [
            'page_name' => $page_name,
            'params' => [
                'params'=>$params,
                'type' => 'catalog',
        ]]);
    }

    public function actionProductCustomBundle()
    {
        \common\helpers\Translation::init('catalog/product');

        $params = Yii::$app->request->get();

        $details = \common\helpers\CustomBundles::getDetails($params);

        if (Yii::$app->request->isAjax) {
            $details['product_custom_bundle'] = \frontend\design\IncludeTpl::widget(['file' => 'boxes/product/custom-bundle.tpl',
                'params' => [
                    'products' => $details['all_products'],
                    'chosenProducts' => $details['custom_bundle_products'],
                    'old' => $details['custom_bundle_full_price'],
                    'price' => $details['custom_bundle_full_price'],
                    'isAjax' => true
                ]
            ]);
            return json_encode($details);
        } else {
            if (isset($details['all_products']) && count($details['all_products']) > 0) {
                return IncludeTpl::widget(['file' => 'boxes/product/custom-bundle.tpl',
                    'params' => [
                        'products' => $details['all_products'],
                        'chosenProducts' => $details['custom_bundle_products'],
                        'old' => $details['custom_bundle_full_price'],
                        'price' => $details['custom_bundle_full_price'],
                        'isAjax' => false
                    ]
                ]);
            } else {
                return '';
            }
        }
    }

    public function actionProductListing()
    {
        $page_name = Yii::$app->request->get('page_name', 'productListing');
        Info::addBoxToCss('quantity');
        Info::addBoxToCss('products-listing');

        return $this->render('product-listing.tpl', [
            'page_name' => $page_name,
            'params' => []
        ]);
    }
}

<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

$NoSessionStart = true;
define('TEMPORARY_STOCK_ENABLE', 'false');
require_once("includes/application_top.php");

require_once 'lib/frontend/design/Info.php';
require_once 'lib/common/classes/Images.php';
define('DIR_WS_THEME', 'themes/theme-1');

$platform_code = '';
$google_platform_id = \common\classes\platform::activeId();
\common\helpers\Translation::init('main');
(new\common\classes\platform_config(\common\classes\platform::defaultId()))->constant_up();

$activePlatformIds = array_map(function($item){
    return (int)$item['id'];
}, \common\classes\platform::getList(true));

if ( preg_match('/^(.*):(\d+)$/', GOOGLE_BASE_SHOP_PLATFORM_ID, $match) ) {
    $platform_code = $match[1];
    if ( in_array((int)$match[2], $activePlatformIds) ) {
        $google_platform_id = (int)$match[2];
    }
}
$feed_language_id = intval(\common\classes\language::defaultId());

$renew = isset($_GET['renew']);
$feed_cache_directory = Yii::getAlias('@runtime/feed_cache/');
// {{ prepare feed cache directory
$cached_filename = Yii::getAlias('@runtime/feed_cache/google_base_'.(int)$feed_language_id.'_.txt');
if ( !is_dir($feed_cache_directory) ) {
    try{
        \yii\helpers\FileHelper::createDirectory($feed_cache_directory,0777);
    }catch (\Exception $ex ){ }
}else{
    foreach(glob(dirname($cached_filename).'/google_base*') as $cached_feed_file){
        if ( strtotime('now')>filemtime($cached_feed_file) ){
            unlink($cached_feed_file);
        }
    }
}

// }} prepare feed cache directory


define('GOOGLE_BASE_FILE', "google_base_export.txt");
define('GOOGLE_CSV_DELIMITER', "\t");
define('GOOGLE_CSV_SURROUND', '"');
define('GOOGLE_CSV_EOL', "\r\n");

set_time_limit(30 * 60);

$escape_values = array();
$safe_strings = array();
$escape_values[] = "\r";
$safe_strings[] = '\r';
$escape_values[] = "\n";
$safe_strings[] = '\n';
$escape_values[] = "\"";
$safe_strings[] = '""';

header('Content-type: text/csv; charset=utf-8');

$output = fopen("php://output", "w");
if ($output) {
    $fields = array(
        "title" => "get_name",
        "description" => "get_description",
        "link" => "make_url",
        "thumbnail" => "make_thumbnail_url",
        "image_link" => "make_image_url",
        "id" => "products_id",
        "expiration_date" => "make_expiration_date",
        "price" => "get_price",
        "sale_price" => "get_sale_price",
        "sale_price_effective_date" => "get_sale_effective_date",
        //"currency" => "get_currency",
        "shipping" => "get_shipping",
        "model_number" => "products_model",
        "quantity" => "products_quantity",
        "weight" => "products_weight",
        "condition" => "get_condition",
        "brand" => "manufacturers_name",
        "mpn" => "products_model",
        "gtin" => "get_products_gtin",
        "availability" => "availability",
        "product_type" => "get_google_product_type",
        "google_product_category" => "get_google_product_category",
    );

    if ( GOOGLE_BASE_FIELD_LIST!='' ) {
        $selectedFields = explode(",", GOOGLE_BASE_FIELD_LIST);
        foreach ($fields as $key => $value) {
            if (!in_array($key, $selectedFields)) {
                unset($fields[$key]);
            }
        }
    }
    $sale_price_present = isset($fields['sale_price']);
    $gtin_columns = explode(',',(defined('GOOGLE_BASE_GTIN_CONFIG')?GOOGLE_BASE_GTIN_CONFIG:'upc,ean') );
    $gtin_columns = preg_grep('/^-/',$gtin_columns,PREG_GREP_INVERT);

    $multi_join_limit_platforms = '';
    $limit_platform_categories = '';
    if ( \common\classes\platform::isMulti() ) {
        $multi_join_limit_platforms =
            " inner join " . TABLE_PLATFORMS_PRODUCTS . " p2p on p2p.products_id = p.products_id and p2p.platform_id = '" . \common\classes\platform::currentId() . "' ".
            " inner join " . TABLE_PLATFORMS_PRODUCTS . " p2pg on p2pg.products_id = p.products_id and p2pg.platform_id = '" . $google_platform_id . "' ";
        $limit_platform_categories =
            " inner join " . TABLE_PLATFORMS_CATEGORIES . " plc on plc.categories_id = p2c.categories_id AND plc.platform_id = '" . \common\classes\platform::currentId() . "' ";
    }

    $query_sql = ("
			select	p.products_id, p.products_model, m.manufacturers_name, if(products_quantity > 0, products_quantity, 100) products_quantity, 
			    if(products_quantity > 0,'in stock', 'out of stock') as availability, pd.google_product_category, p.google_product_type,
                IF(LENGTH(p.products_upc)>0,p.products_upc,p.products_ean) as products_gtin,
                p.products_upc,p.products_ean,p.products_isbn, 
					p.products_tax_class_id, cd.categories_name, p2c.categories_id, p.products_image_med, if(pda.products_name is NULL or pda.products_name = '', pd.products_name, pda.products_name) products_name, pda.products_description_short description_short_affiliate, 
					pd.products_description_short description_short, pda.products_description description_affiliate, pd.products_description description, 
					products_weight, IFNULL(s.specials_new_products_price, p.products_price) AS products_price
			from	" . TABLE_PRODUCTS . " p
			{$multi_join_limit_platforms} 
                                        inner join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on p2c.products_id = p.products_id    
                                         {$limit_platform_categories}
					left join " . TABLE_SPECIALS . " s on ( s.products_id = p.products_id AND ( ( (s.expires_date > CURRENT_DATE) OR (s.expires_date = 0) ) AND ( s.status != 0 ) ) )
					left join " . TABLE_PRODUCTS_DESCRIPTION . " pda on pda.products_id = p.products_id and pda.language_id = '{$feed_language_id}' and pda.platform_id = '" . (int)Yii::$app->get('platform')->getConfig($google_platform_id)->getPlatformToDescription() . "'
					left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on pd.products_id = p.products_id and pd.language_id = '{$feed_language_id}' and pd.platform_id = '".\common\classes\platform::defaultId()."'
					left join " . TABLE_MANUFACTURERS . " m on m.manufacturers_id = p.manufacturers_id
					left join " . TABLE_CATEGORIES . " c on c.categories_id = p2c.categories_id
					left join " . TABLE_CATEGORIES_DESCRIPTION . " cd on cd.categories_id = c.categories_id and cd.affiliate_id = 0 and cd.language_id = '{$feed_language_id}'
			where	p.products_status != 0 " . \common\helpers\Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . "
				and	c.categories_status != 0
			group	by p.products_id
		");


    $query = tep_db_query($query_sql);
    $rows_number = tep_db_num_rows($query);

    $cached_filename = $feed_cache_directory.'google_base_'.md5($query_sql).'_'.md5(json_encode($fields)).'_'.$rows_number.'.txt';
    if ( !$renew && is_file($cached_filename) ){
        //header('Content-type: text/csv; charset=utf-8');
        readfile($cached_filename);
        die;
    }

    $cached_output = false;
    if ( $cached_filename ) {
        $cached_output = @fopen($cached_filename.'.tmp', "w");
    }

    $counter = 0;
    $header = false;
    while ($row = tep_db_fetch_array($query)) {
        $skip_row = false;
        $data = array();
        foreach ($fields as $field => $source)
            if (key_exists($source, $row))
                $data[$field] = strip_tags($row[$source]);
            elseif (function_exists($source)) {
                $data[$field] = strip_tags($source($row));
                if ( ($field=='price' || $field=='image_link' || $field=='title') && strlen($data[$field])==0 ) $skip_row = true;
            } else {
                print_r($row);
                trigger_error("Unknown source: $source", E_USER_ERROR);
            }

        if ( $skip_row ) continue;
        foreach ($data as $key => $value)
            $data[$key] = str_replace($escape_values, $safe_strings, $value);

        if (!$header) {
            $header = array_keys($data);
            $feed_data_string = GOOGLE_CSV_SURROUND . join(GOOGLE_CSV_SURROUND . GOOGLE_CSV_DELIMITER . GOOGLE_CSV_SURROUND, $header) . GOOGLE_CSV_SURROUND . GOOGLE_CSV_EOL;
            fwrite($output, $feed_data_string);
            if ($cached_output) fwrite($cached_output, $feed_data_string);
        }
        $feed_data_string = GOOGLE_CSV_SURROUND . join(GOOGLE_CSV_SURROUND . GOOGLE_CSV_DELIMITER . GOOGLE_CSV_SURROUND, $data) . GOOGLE_CSV_SURROUND . GOOGLE_CSV_EOL;
        fwrite($output, $feed_data_string);
        if ($cached_output) fwrite($cached_output, $feed_data_string);

        ShowProgress($counter++ / $rows_number);
    }

    fclose($output);
    if ($cached_output) {
        fclose($cached_output);
        if ( filesize($cached_filename.'.tmp')==0 ){
            @unlink($cached_filename.'.tmp');
        }else {
            @rename($cached_filename . '.tmp', $cached_filename);
            @touch($cached_filename, strtotime('+1 day'));
        }
    }
//		rename("feeds/google.tmp", "feeds/" . GOOGLE_BASE_FILE);
//		copy("feeds/" . GOOGLE_BASE_FILE, "ftp://" . GOOGLE_BASE_FTP_USER . ":" . GOOGLE_BASE_FTP_PASSWORD . "@" . GOOGLE_BASE_FTP_SERVER . "/" . GOOGLE_BASE_FILE);
}

Message("Done       ");

function make_url(&$product) {
    global $platform_code;
    return tep_href_link(FILENAME_PRODUCT_INFO, "products_id=" . $product["products_id"] .(empty($platform_code)?'':'&code='.$platform_code), 'NONSSL', false);
}

function get_name(&$product) {
    $name = trim($product["products_name"]);
    if ( $product["products_name"]==strtoupper($product["products_name"]) ) {
        $name = ucwords(strtolower($name));
    }

    $manufacturer = trim($product["manufacturers_name"]);
    if ($manufacturer && stripos($name, $manufacturer) !== 0)
        $name = "$manufacturer $name";

    return $name;
}

function make_thumbnail_url(&$product) {
    $image_url = common\classes\Images::getImageUrl($product["products_id"]);
    if ( strpos($image_url,'/')===0 ) {
        return rtrim(HTTP_SERVER,'/') . $image_url;
    }
    return preg_match('@^[^:]{3,5}://@',$image_url)?$image_url:(HTTP_SERVER . DIR_WS_HTTP_CATALOG . $image_url);
}

function make_image_url(&$product) {
    $image_url = common\classes\Images::getImageUrl($product["products_id"], 'Large');
    if ( strpos($image_url,'/img/na.png')!==false ) {
        // * Don't use a logo or icon instead of an actual product image.
        // https://support.google.com/merchants/answer/6324350?hl=en&ref_topic=6324338
        return '';
    }
    if ( strpos($image_url,'/')===0 ) {
        return rtrim(HTTP_SERVER,'/') . $image_url;
    }
    return preg_match('@^[^:]{3,5}://@',$image_url)?$image_url:(HTTP_SERVER . DIR_WS_HTTP_CATALOG . $image_url);
}

function make_expiration_date() {
    return strftime("%Y-%m-%d", strtotime("+28 day"));
}

function get_currency() {
    return Yii::$app->get('platform')->config()->getDefaultCurrency() ?? DEFAULT_CURRENCY;
}

function get_price(&$product_data) {
    $currencies = \Yii::$container->get('currencies');

    $product = \Yii::$container->get('products')->loadProducts(['products_id'=>$product_data["products_id"]])->getProduct($product_data["products_id"]);
    $product_price = $product['products_price'];
    if ( floatval($product_price)<=0 ) return '';

    return $currencies->calculate_price(\common\helpers\Tax::add_tax_always($product_price, $product['tax_rate']), 0) . " " .get_currency();
}

function get_sale_price(&$product_data) {
    $currencies = \Yii::$container->get('currencies');

    $product = \Yii::$container->get('products')->loadProducts(['products_id'=>$product_data["products_id"]])->getProduct($product_data["products_id"]);
    $product_price = $product['special_price'];
    if ( floatval($product_price)<=0 ) return '';

    return $currencies->calculate_price(\common\helpers\Tax::add_tax_always($product_price, $product['tax_rate']), 0) . " " .get_currency();
}

function get_sale_effective_date(&$product_data) {
    $effective_date = '';
    $product = \Yii::$container->get('products')->loadProducts(['products_id'=>$product_data["products_id"]])->getProduct($product_data["products_id"]);
    if ( (int)$product['special_start_date']>1990 && strtotime($product['special_expiration_date'])>time() ) {
        $start_date = strtotime($product['special_start_date']);
        $end_date = strtotime($product['special_expiration_date']);
        if ($end_date > $start_date) {
            $effective_date = date('Y-m-d\TH:i:sO', $start_date) . '/' . date('Y-m-d\TH:i:sO', $end_date);
        }
    }
    return $effective_date;
}

function get_description(&$product) {
    // https://support.google.com/merchants/answer/6324468?hl=en&ref_topic=6324338
    foreach (array($product["description"], $product["description_short"]) as $description)
        if ($description) {
            $description = preg_replace('/\s+/ims', ' ', strip_tags(str_replace(['>','&nbsp;'],['> ',' '], $description)));
            if (strlen($description) > 5000) {
                $description = substr($description, 0, 4995);
                $description = substr($description, 0, strrpos($description, ' ')) . " ...";
            }
            break;
        }

    return trim($description);
}

function get_shipping(&$product){
    try{
        $cart = new \common\classes\shopping_cart();
        $cart->reset(true);
        $cart->add_cart($product['products_id'],1,'',false);

        $manager = \common\services\OrderManager::loadManager();
        $manager->loadCart($cart);
        $manager->setModulesVisibility(['shop_order']);

        $order = $manager->createOrderInstance('\common\classes\Order');
        $order->cart();
        $order->delivery['country_id'] = Yii::$app->get('platform')->config()->const_value('STORE_COUNTRY');
        $order->delivery['country']['id'] = $order->delivery['country_id'];
        static $country_info = null;
        if ( !is_array($country_info) ) {
            $country_info = \common\helpers\Country::get_country_info_by_id($order->delivery['country_id']);
        }
        $order->delivery['country']['iso_code_2'] = $country_info['countries_iso_code_2'];
        $order->delivery['country']['iso_code_3'] = $country_info['countries_iso_code_3'];

        $quotes = $manager->getAllShippingQuotes(true);
        $cheapest_shipping_array = $manager->getShippingCollection()->cheapest();

        if( is_array( $cheapest_shipping_array ) ) {
            $cost = $cheapest_shipping_array['cost'];
            if ( array_key_exists('cost_inc_tax', $cheapest_shipping_array) ) {
                $cost = $cheapest_shipping_array['cost_inc_tax'];
            }elseif ( isset($cheapest_shipping_array['tax']) ) {
                $cost = \common\helpers\Tax::add_tax_always($cost,$cheapest_shipping_array['tax']);
            }
            return $order->delivery['country']['iso_code_2'].':::'.number_format($cost,2,'.','').' '.get_currency();
        }
    } catch (Exception $ex) {
    }
    return '';
}

function get_condition() {
    return 'new';
}
function get_products_gtin(&$product)
{
    global $gtin_columns;
    $gtin = '';
    foreach ($gtin_columns as $gtin_column){
        if ( isset($product['products_'.$gtin_column]) && !empty($product['products_'.$gtin_column]) ){
            $gtin = $product['products_'.$gtin_column];
            break;
        }
    }
    return $gtin;
}

function ShowProgress($Progress, $Precision = 1) {
//		static $previous_step_percent_completed, $rotor_state;
//		$rotor = "|/-\\";
//		
//		$percent_completed = sprintf("%.{$Precision}f", round(($Progress * 100), 1));
//		if ($previous_step_percent_completed != time())
//		{
//			$rotor_state = ($rotor_state + 1) % 4;
//			echo "\r" . $rotor[$rotor_state] . " $percent_completed% ";
//			$previous_step_percent_completed = time();
//		}
}

function Message($text) {
//		echo "\r$text\r\n";
}

function get_google_product_category(&$product) {
    $google_product_category = "";
    if ($product['google_product_category'] != "")
        $google_product_category = $product['google_product_category'];
    elseif ($product['google_product_type'] > 0) {
        $google_product_category = get_google_product_category_parent($product['google_product_type']);
    } elseif ($product['categories_id'] > 0) {
        $google_product_category = get_google_product_category_parent($product['categories_id']);
    }
    return $google_product_category;
}

function get_google_product_category_parent($categories_id) {
    global $feed_language_id;
    $google_product_category = "";
    $query = "select c.parent_id, cd.google_product_category, c.google_product_type from " . TABLE_CATEGORIES . " c inner join ".TABLE_CATEGORIES_DESCRIPTION." cd ON cd.categories_id=c.categories_id AND cd.language_id='".(int)$feed_language_id."' and cd.affiliate_id=0 where c.categories_id='" . $categories_id . "'";
    $result = tep_db_query($query);
    $array = tep_db_fetch_array($result);

    if (isset($array['google_product_type']) && !empty($array['google_product_type'])) {        
        $queryGoogleCategories =
          "select categories_id, category_name " .
          "from " . TABLE_GOOGLE_CATEGORIES . " c " .
          "where c.language_id='".(int)$feed_language_id . "' and c.categories_id='".(int) $array['google_product_type']."'";
        $resultGoogleCategories = tep_db_query($queryGoogleCategories);
        $arrayGoogleCategories = tep_db_fetch_array($resultGoogleCategories);
        $google_product_category = \common\helpers\GoogleCategories::getCategoryHierarchy($arrayGoogleCategories['categories_id'], '', $feed_language_id);
    } elseif ($array['google_product_category'] != "")
        $google_product_category = $array['google_product_category'];
    elseif ($array['parent_id'] > 0)
        $google_product_category = get_google_product_category_parent($array['parent_id']);
    return $google_product_category;
}

function get_google_product_type(&$product) {
    global $feed_language_id;
    $google_product_type = "";
    $categories = array();
    if ($product['google_product_type'] > 0) {
        \common\helpers\Categories::get_parent_categories($categories, $product['google_product_type']);
        $categories = array_reverse($categories);
        $categories[] = $product['google_product_type'];
    } else {
        \common\helpers\Categories::get_parent_categories($categories, $product['categories_id']);
        $categories = array_reverse($categories);
        $categories[] = $product['categories_id'];
    }
    if (count($categories) > 0) {
        for ($cat = 0; $cat < count($categories); $cat++) {
            if ($google_product_type != "")
                $google_product_type .= " > ";
            $google_product_type .= \common\helpers\Categories::get_categories_name($categories[$cat], $feed_language_id);
        }
    }
    return $google_product_type;
}

function tep_get_category_name($category_id) {
    global $feed_language_id;
    $affiliate_id = (int) $_SESSION['affiliate_ref'];
    $query = "select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . (int) $category_id . "' and language_id = '" . (int) $feed_language_id . "' and affiliate_id = '" . (int) $affiliate_id . "'";
    $category_query = tep_db_query($query);
    $category = tep_db_fetch_array($category_query);
    return $category['categories_name'];
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\PdfCatalogues;

use Yii;
use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;
use backend\models\EP\Exception;
use backend\models\EP\Tools;

class PdfCatalogGen implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;
    protected $pdf_catalogues_list = [];

    protected $config = [];

    protected $startJobServerGmtTime = '';
    protected $useModifyTimeCheck = true;
    protected $isErrorOccurredDuringCheck = false;

    public function __construct($config)
    {
        if (!defined('TABLE_PDF_CATALOGUES')) {
            define('TABLE_PDF_CATALOGUES', 'pdf_catalogues');
        }
        if (!defined('TABLE_PDF_CATALOGUES_TO_PRODUCTS')) {
            define('TABLE_PDF_CATALOGUES_TO_PRODUCTS', 'pdf_catalogues_to_products');
        }
        if (!defined('TABLE_PDF_CATALOGUES_TO_CATEGORIES')) {
            define('TABLE_PDF_CATALOGUES_TO_CATEGORIES', 'pdf_catalogues_to_categories');
        }
    }

    public function allowRunInPopup()
    {
        return false;
    }

    public function getProgress()
    {
        if ($this->total_count > 0) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        } else {
            $percentDone = 100;
        }
        return number_format($percentDone, 1, '.', '');
    }

    public function prepareProcess(Messages $message)
    {
        $pdf_catalogues_query = tep_db_query("select pdf_catalogues_id, pdf_catalogues_name, is_generated from " . TABLE_PDF_CATALOGUES . " where 1");
        while ($pdf_catalogues = tep_db_fetch_array($pdf_catalogues_query)) {
            $brochureName = 'brochure/' . ($pdf_catalogues['pdf_catalogues_name'] ? $pdf_catalogues['pdf_catalogues_name'] : date('F Y') . ' Catalogue') . '.pdf';
            if (!file_exists(DIR_FS_CATALOG . $brochureName)) {
                $this->pdf_catalogues_list[] = $pdf_catalogues;
            } elseif (!$pdf_catalogues['is_generated']) {
                $this->pdf_catalogues_list[] = $pdf_catalogues;
            }
        }

        if (count($this->pdf_catalogues_list) == 0) {
            $message->info('No data');
            return false;
        } else {
            $this->total_count = count($this->pdf_catalogues_list);
        }
    }

    public function processRow(Messages $message)
    {
        set_time_limit(0);
        $platformDefault = tep_db_fetch_array(tep_db_query("select * from platforms where is_default = 1 limit 1"));
        define('STORE_NAME', $platformDefault['platform_name']);
        define('STORE_OWNER', $platformDefault['platform_owner']);
        define('EMAIL_FROM', $platformDefault['platform_email_from']);
        define('STORE_OWNER_EMAIL_ADDRESS', $platformDefault['platform_email_address']);
        define('SEND_EXTRA_ORDER_EMAILS_TO', $platformDefault['platform_email_extra']);
        $get_store_country_config_r = tep_db_query("SELECT entry_country_id, entry_zone_id FROM platforms_address_book WHERE platform_id = '" . $platformDefault['platform_id'] . "' AND is_default = 1 LIMIT 1");
        if (tep_db_num_rows($get_store_country_config_r) > 0) {
            $_store_country_config = tep_db_fetch_array($get_store_country_config_r);
            define('STORE_COUNTRY', $_store_country_config['entry_country_id']);
            define('STORE_ZONE', $_store_country_config['entry_zone_id']);
        }
        $configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
        while ($configuration = tep_db_fetch_array($configuration_query)) {
            define($configuration['cfgKey'], $configuration['cfgValue']);
        }
        define('THEME_NAME', 'theme-1');

        $lng = new \common\classes\language();
        $lng->load_vars();

        \Yii::$app->set('session', 'yii\web\Session');
        \Yii::setAlias('@webCatalogImages', DIR_WS_IMAGES);
        \Yii::$container->setSingleton('products', '\common\components\ProductsContainer');

        if ( is_array($this->pdf_catalogues_list) && $this->pdf_catalogues_list[$this->row_count] ) {
            $message->info($brochureName = self::generatePdfCatalogue($this->pdf_catalogues_list[$this->row_count]['pdf_catalogues_id']));
            if (file_exists(DIR_FS_CATALOG . $brochureName)) {
                \common\helpers\Mail::send(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $this->pdf_catalogues_list[$this->row_count]['pdf_catalogues_name'], \common\helpers\Output::get_clickable_link(tep_href_link($brochureName)), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
            $this->row_count++;
            return true;
        } else {
            return false;
        }
    }

    public function postProcess(Messages $message)
    {
        return;
    }

    public static function generatePdfCatalogue($pdf_catalogues_id)
    {
        global $languages_id;
        \common\helpers\Translation::init('main');
        define('DIR_WS_THEME', 'themes/theme-1');

        $currencies = \Yii::$container->get('currencies');
        \Yii::$app->settings->set('currency', DEFAULT_CURRENCY);

        $pdf_catalogues = tep_db_fetch_array(tep_db_query("select pdf_catalogues_id, pdf_catalogues_name, show_out_of_stock, show_product_link from " . TABLE_PDF_CATALOGUES . " where pdf_catalogues_id = '" . (int)$pdf_catalogues_id . "'"));

        $platformList = \common\classes\platform::getList(false);

        if ($pdf_catalogues['pdf_catalogues_id'] > 0)
        {
            define('SHOW_OUT_OF_STOCK', $pdf_catalogues['show_out_of_stock']);

            set_time_limit(0);
            $root_category_id = 0;

            $count = 1;

            $categoriesArray = [];

            $categories_query = tep_db_query("select c.categories_id, cd.categories_name, cd.categories_description, c.categories_image_2 as categories_image, c.parent_id from " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES . " c inner join " . TABLE_PDF_CATALOGUES_TO_CATEGORIES . " pc2c on c.categories_id = pc2c.categories_id  and pc2c.pdf_catalogues_id = '" . (int)$pdf_catalogues['pdf_catalogues_id'] . "' where c.parent_id = '" . (int)$root_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and c.categories_status = '1' and cd.affiliate_id = '0' order by c.parent_id, sort_order, categories_name");
            while ($categories = tep_db_fetch_array($categories_query)) {
                $categories['categories_description'] = \common\classes\TlUrl::replaceUrl($categories['categories_description']);

                $productsArray = [];

                $categories_array = array($categories['categories_id'] => $categories['categories_id']);
                \common\helpers\Categories::get_subcategories($categories_array, $categories['categories_id'], false);

                $listing_sql_array = \frontend\design\ListingSql::get_listing_sql_array('catalog/all-products');
                //to do for different platforms

                $listing_sql = "
      select
        p.products_id,
        p.products_model,
        p.order_quantity_minimal, p.order_quantity_step, p.order_quantity_max, p.stock_indication_id,
        if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name,
        if(length(pd.products_seo_page_name) > 0, pd.products_seo_page_name, p.products_seo_page_name) as products_seo_page_name,
        p.is_virtual,
        p.manufacturers_id,
        m.manufacturers_name,
        p.products_price,
        p.products_tax_class_id,
        IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price,
        IF(s.status, s.specials_new_products_price, p.products_price) as final_price
      from
        " . $listing_sql_array['from'] . "
        " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c,
        " . TABLE_PRODUCTS_DESCRIPTION . " pd,
        " . TABLE_PRODUCTS . " p
          inner join 
            " . TABLE_PDF_CATALOGUES_TO_PRODUCTS . " pc2p on p.products_id = pc2p.products_id
              and pc2p.pdf_catalogues_id = '" . (int)$pdf_catalogues['pdf_catalogues_id'] . "'
          left join
            " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id
              and pd1.language_id = '" . (int)$languages_id . "'
              and pd1.platform_id = '".intval(\common\classes\platform::currentId())."'
          left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id
          left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id
          " . $listing_sql_array['left_join'] . "
      where
        p2c.products_id = p.products_id
        and pd.products_id = p.products_id
        and pd.platform_id = '".intval(\common\classes\platform::defaultId())."'
        and pd.language_id = '" . (int)$languages_id . "'
        " . $listing_sql_array['where'] . "
        " . (count($categories_array) > 0 ? " and p2c.categories_id in ('" . implode("','", array_map('intval', $categories_array)) . "') " : '') . "
      group by p.products_id order by  p.products_model, products_name";

                $products_listing_query = tep_db_query($listing_sql);
                if (tep_db_num_rows($products_listing_query) > 0) {
                    while ($listing = tep_db_fetch_array($products_listing_query)) {
                        $productArrayItem = [];

                        $productArrayItem['products_name'] = $listing['products_name'];
                        $productArrayItem['products_model'] = $listing['products_model'];
                        $productArrayItem['products_id'] = $listing['products_id'];

                        if (\common\helpers\Product::get_products_special_price($listing['products_id'])) {

                            $productArrayItem['products_price'] = $currencies->display_price(\common\helpers\Product::get_products_special_price($listing['products_id']), \common\helpers\Tax::get_tax_rate($listing['products_tax_class_id']));
                            $productArrayItem['products_price_old'] = $currencies->display_price(\common\helpers\Product::get_products_price($listing['products_id'], 1, $listing['products_price']), \common\helpers\Tax::get_tax_rate($listing['products_tax_class_id']));

                        } else {
                            $productArrayItem['products_price'] = $currencies->display_price(\common\helpers\Product::get_products_price($listing['products_id'], 1, $listing['products_price']), \common\helpers\Tax::get_tax_rate($listing['products_tax_class_id']));
                            $productArrayItem['products_price_old'] = '';
                        }

                        $img = \common\classes\Images::getImageUrl($listing['products_id'], 'Small');
                        if (substr($img, 0, strlen(DIR_WS_CATALOG)) == DIR_WS_CATALOG) {
                            $img = substr($img, strlen(DIR_WS_CATALOG));
                        }
                        $size = \common\helpers\Image::getNewSize(DIR_FS_CATALOG . $img, 175, 175);

                        $productArrayItem['image'] = '<img src="' . HTTP_SERVER . DIR_WS_CATALOG . str_replace(' ', '%20', $img) . '" width="' . (int)$size[0] . '" height="' . (int)$size[1] . '" border="0">';

                        if ($pdf_catalogues['show_product_link']) {
                            $productArrayItem['products_link'] = '';
                            $check_platform = tep_db_fetch_array(tep_db_query("select group_concat(platform_id separator ',') as platform_ids from " . TABLE_PLATFORMS_PRODUCTS . " where products_id = '" . (int)$listing['products_id'] . "' group by products_id"));
                            foreach ($platformList as $platform) {
                                if (in_array($platform['id'], explode(',', $check_platform['platform_ids']))) {
                                    if ($listing['products_seo_page_name']) {
                                        $productArrayItem['products_link'] = 'http://' . $platform['platform_url'] . '/' . $listing['products_seo_page_name'];
                                    } else {
                                        $productArrayItem['products_link'] = 'http://' . $platform['platform_url'] . '/catalog/product?products_id=' . $listing['products_id'];
                                    }
                                    break;
                                }
                            }
                            if ($productArrayItem['products_link']) {
                                $productArrayItem['products_name'] = '<a href="' . $productArrayItem['products_link'] . '">' . $productArrayItem['products_name'] . '</a>';
                                $productArrayItem['image'] = '<a href="' . $productArrayItem['products_link'] . '">' . $productArrayItem['image'] . '</a>';
                            }
                        }

                        $productsArray[] = $productArrayItem;
                    }
                }
                $count++;

                $categoriesArray[] = [
                    'name' => 'pdf',
                    'params' => [
                        'language_id' => $languages_id,
                        'categoryName' => $categories['categories_name'],
                        'categoryImage' => $categories['categories_image'],
                        'categoryDescription' => $categories['categories_description'],
                        'products' => $productsArray
                    ],
                ];
            }


            $pdfArray = [];
            $pdfArray[] = [
                'name' => 'pdf_cover',
                'params' => [
                    'language_id' => $languages_id
                ],
            ];

            foreach ($categoriesArray as $item) {
                $pdfArray[] = $item;
            }

            $theme = tep_db_fetch_array(tep_db_query("select t.theme_name from " . TABLE_THEMES . " t, " . TABLE_PLATFORMS_TO_THEMES . " p2t where p2t.is_default = 1 and p2t.theme_id = t.id and p2t.platform_id = '" . (int)$platformList[0]['id'] . "'"));

            $brochureName = 'brochure/' . ($pdf_catalogues['pdf_catalogues_name'] ? $pdf_catalogues['pdf_catalogues_name'] : date('F Y') . ' Catalogue') . '.pdf';
            \backend\design\PDFBlock::widget([
                'pages' => $pdfArray,
                'params' => [
                    'theme_name' => $theme['theme_name'],
                    'document_name' => DIR_FS_CATALOG . $brochureName,
                    'subject' => 'Catalog',
                    'destination' => 'F',
                    'showTOC' => true, //add table of content
                    'pageNumberTOC' => 2, //page number where this TOC should be inserted (leave empty for current page)
                    'showHeader' => true,
                    'showFooter' => true,
                    'pdf_margin_top' => 60,
                    'pdf_margin_left' => 30,
                    'pdf_margin_right' => 30,
                    'pdf_margin_bottom' => 60,
                ]
            ]);

            if (file_exists(DIR_FS_CATALOG . $brochureName)) {
                tep_db_query("update " . TABLE_PDF_CATALOGUES . " set is_generated = '1' where pdf_catalogues_id = '" . (int)$pdf_catalogues['pdf_catalogues_id'] . "'");
                return $brochureName;
            }
        }
    }
}
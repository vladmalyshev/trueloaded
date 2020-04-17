<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\ListingSql;
use frontend\design\SplitPageResults;
use frontend\design\Info;
use backend\design\Style;
use common\classes\design;

class ProductListing extends Widget
{

    public $id;
    public $file;
    public $params;
    public $settings;
    public $products;
    public static $styles;
    public static $listType;

    public function init()
    {
        parent::init();

        Info::includeJsFile('boxes/ProductListing');
        Info::includeJsFile('boxes/ProductListing/applyItemData');
        Info::includeJsFile('boxes/ProductListing/applyItemImage');
        Info::includeJsFile('boxes/ProductListing/applyItemPrice');
        Info::includeJsFile('boxes/ProductListing/applyItemAttributes');
        Info::includeJsFile('boxes/ProductListing/applyItemBuyButton');
        Info::includeJsFile('boxes/ProductListing/applyItemQtyInput');
        Info::includeJsFile('boxes/ProductListing/applyItemCompare');
        Info::includeJsFile('boxes/ProductListing/applyItemWishlistButton');
        Info::includeJsFile('boxes/ProductListing/applyItem');
        Info::includeJsFile('boxes/ProductListing/carousel');
        Info::includeJsFile('boxes/ProductListing/updateAttributes');
        Info::includeJsFile('boxes/ProductListing/addProductToCart');
        Info::includeJsFile('boxes/ProductListing/alignItems');
        Info::includeJsFile('boxes/ProductListing/productListingCols');
        Info::includeJsFile('boxes/ProductListing/fbl');

        Info::includeJsFile('reducers/products');
        Info::includeJsFile('reducers/productListings');
        Info::includeJsFile('reducers/widgets');

        Info::includeJsFile('modules/helpers/getUprid');

        Info::includeExtensionJsFile('Quotations/js/productListing');
        Info::includeExtensionJsFile('Samples/js/productListing');
        Info::addBoxToCss('quantity');
        Info::addBoxToCss('slick');

        Info::addJsData(['GROUPS_DISABLE_CHECKOUT' => GROUPS_DISABLE_CHECKOUT]);
    }

    public function run()
    {
        global $wish_list;
        $productList = [];

        self::$listType = Info::listType($this->settings[0]);

        $itemStructure = self::getItemData(self::$listType);

        $cssClass = 'products-listing product-listing';

        if ($this->settings[0]['col_in_row'] && Info::get_gl() == 'grid'){
            $cssClass .= ' cols-' . $this->settings[0]['col_in_row'];
        } else {
            $cssClass .= ' cols-1';
        }
        $cssClass .= ' list-' . self::$listType;
        $cssClass .= ' w-list-' . self::$listType;

        Info::addBlockToWidgetsList('list-' . self::$listType);
        Info::addBlockToPageName(self::$listType);
        Info::addBoxToCss('products-listing');

        $html = '';
        foreach ($this->products as $product){
            $productList[$product['products_id']] = [
                'products_id' => $product['products_id']
            ];
            $html .= '<div class="item" data-id="' . $product['products_id'] . '" data-name="' . $product['products_id'] . '">';
            $html .= self::createItem($itemStructure, $product, $this->settings);
            $html .= '</div>';
            Info::addJsData(['products' => [
                $product['products_id'] => [
                    'products_id' => $product['products_id'],
                    'image' => $product['image'],
                    'image_alt' => $product['image_alt'],
                    'image_title' => $product['image_title'],
                    'srcset' => $product['srcset'],
                    'sizes' => $product['sizes'],
                    'products_name' => $product['products_name'],
                    'link' => $product['link'],
                    'is_virtual' => $product['is_virtual'],
                    'stock_indicator' => $product['stock_indicator'],
                    'product_has_attributes' => $product['product_has_attributes'],
                    'isBundle' => !$product['products_status_bundle'],
                    'bonus_points_price' => floor($product['bonus_points_price']),
                    'bonus_points_cost' => floor($product['bonus_points_cost']),
                    'product_in_cart' => $product['product_in_cart'],
                    'show_attributes_quantity' => $product['show_attributes_quantity'],
                    'link' => $product['link'],
                    'in_wish_list' => $product['in_wish_list'],
                    'price' => [
                        'current' => $product['price'],
                        'special' => $product['price_special'],
                        'old' => $product['price_old'],
                    ],
                ]
            ]]);
            if ($product['in_wish_list']) {
                Info::addJsData(['productListings' => [
                    'wishlist' => ['products' => [
                        $product['products_id'] => '1'
                    ]]
                ]]);
            }
        }

        Info::addJsData(['productListings' => [
            $this->settings['listing_type'] => [
                'productListing' => $productList,
                'itemElements' => self::itemElements($itemStructure)
            ],
        ]]);

        if ($this->settings['mainListing']) {
            Info::addJsData(['productListings' => [
                'mainListing' => $this->id
            ]]);
        }
        Info::addJsData(['widgets' => [
            $this->id => [
                'listingName' => $this->settings['listing_type'],
                'listingType' => self::$listType,
                'listingTypeCol' => $this->settings[0]['listing_type'],
                'listingTypeRow' => $this->settings[0]['listing_type_rows'],
                'listingTypeB2b' => $this->settings[0]['listing_type_b2b'],
                'colInRow' => $this->settings[0]['col_in_row'],
                'colInRowSizes' => $this->settings['visibility']['col_in_row'],
                'colInRowCarousel' => $this->settings['colInRowCarousel'],
                'productListingCols' => $this->settings[0]['col_in_row'],
                'listingSorting' => Yii::$app->request->get('sort', ''),
                'productsOnPage' => (int)$this->params['listing_split']->number_of_rows_per_page ?? '',
                'pageCount' => (int)$this->params['listing_split']->current_page_number ?? '',
                'numberOfProducts' => (int)$this->params['listing_split']->number_of_rows ?? '',
                'fbl' => ($this->settings[0]['fbl'] && !Info::isAdmin() ? 1 : 0),
                'viewAs' => $this->settings[0]['view_as'],
        ]]]);

        Info::addJsData(['tr' => [
            'BOX_HEADING_COMPARE_LIST' => BOX_HEADING_COMPARE_LIST
        ]]);

        if ($this->settings['onlyProducts']) {

            $returnData = [
                'entryData' => Info::$jsGlobalData,
                'html' => $html,
                'css' => self::getStyles()
            ];
            return json_encode($returnData);
        } else {
            return '<div class="' . $cssClass . '" data-listing-name="' . $this->settings['listing_type'] . '" data-listing-type="' . self::$listType . '">' . $html . '</div>';
        }
    }

    public static function createItem($itemStructure, $product, $settings = [])
    {
        $html = '';
        foreach ($itemStructure as $element) {
            if ($element['name'] == 'BlockBox') {
                $html .= '<div class="type-' . $element['type'] . ' BlockBox ' . $element['class'] . '">';
                foreach ($element['children'] as $col) {
                    if ($element['type'] != 1) $html .= '<div class="col">';
                    $html .= self::createItem($col, $product, $settings);
                    if ($element['type'] != 1) $html .= '</div>';
                }
                $html .= '</div>';
            } else {
                if (self::isSwitchOff($element['name'], $settings)) {
                    continue;
                }
                $html .= '<div class="' . $element['name'] . ' ' . $element['class'] . '">';
                $html .= IncludeTpl::widget([
                    'file' => 'boxes/listing-product/element/' . $element['name'] . '.tpl',
                    'params' => [
                        'settings' => $settings,
                        'product' => $product,
                        'element' => $element,
                    ]
                ]);
                $html .= '</div>';
            }
        }
        return $html;
    }

    public static function getStyles(){
        return \backend\design\Style::getStylesWrapper(self::$styles);
    }

    public static function getItemElements($name)
    {
        self::$listType = $name;
        $itemStructure = self::getItemData($name);

        return self::itemElements($itemStructure);
    }

    private static function itemElements($itemStructure)
    {
        $elements = [];

        foreach ($itemStructure as $element) {
            if ($element['name'] == 'BlockBox') {
                foreach ($element['children'] as $col) {
                    $elements = array_merge($elements, self::itemElements($col));
                }
            } else {
                $elements[$element['name']] = $element['name'];
            }
        }

        return $elements;
    }

    private static function getItemData($name)
    {
        static $cache = [];
        if ($cache[$name]) {
            return $cache[$name];
        }

        $elementsArray = [];
        $elements = \common\models\DesignBoxes::find()->where([
            'block_name' => $name,
            'theme_name' => THEME_NAME,
        ])->asArray()->orderBy('sort_order')->all();

        foreach ($elements as $element) {
            $item = [];
            $item['name'] =  str_replace('productListing\\', '', $element['widget_name']);

            $settings = [];
            $visibility = [];
            $settingsQuery = \common\models\DesignBoxesSettings::find()->where([
                'box_id' => $element['id'],
            ])->asArray()->all();
            foreach ($settingsQuery as $set) {
                if ($set['visibility'] > 0){
                    $visibility[$set['visibility']][$set['language_id']][$set['setting_name']] = $set['setting_value'];
                } else {
                    $settings[$set['language_id']][$set['setting_name']] = $set['setting_value'];
                    if (!in_array($set['setting_name'], Style::$attributesHaveRules)
                        && !in_array($set['setting_name'], Style::$attributesNoRules)
                        && !in_array($set['setting_name'], Style::$attributesHasMeasure)
                    ) {
                        $item['settings'][$set['language_id']][$set['setting_name']] = $set['setting_value'];
                    }
                }
            }

            $type = $settings[0]['block_type'];
            $block_id = $element['id'];
            if ($settings[0]['style_class']) {
                $item['class'] = $settings[0]['style_class'];
            } elseif ($element['widget_name'] == 'BlockBox') {
                $item['class'] = 'bb-' . $element['id'];
            }

            self::fieldStyles($item, $settings, $visibility);

            if ($element['widget_name'] == 'BlockBox') {
                $item['children'] = [];
                $item['type'] = $type;
                if ($type == 2 || $type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 9 || $type == 10 || $type == 11 || $type == 12){
                    $item['children'][] = self::getItemData('block-' . $block_id);
                    $item['children'][] = self::getItemData('block-' . $block_id . '-2');
                } elseif ($type == 3 || $type == 8 || $type == 13){
                    $item['children'][] = self::getItemData('block-' . $block_id);
                    $item['children'][] = self::getItemData('block-' . $block_id . '-2');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-3');
                } elseif ($type == 14){
                    $item['children'][] = self::getItemData('block-' . $block_id);
                    $item['children'][] = self::getItemData('block-' . $block_id . '-2');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-3');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-4');
                } elseif ($type == 15){
                    $item['children'][] = self::getItemData('block-' . $block_id);
                    $item['children'][] = self::getItemData('block-' . $block_id . '-2');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-3');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-4');
                    $item['children'][] = self::getItemData('block-' . $block_id . '-5');
                } elseif ($type == 1){
                    $item['children'][] = self::getItemData('block-' . $block_id);
                }
            }
            $elementsArray[] = $item;
        }
        $cache[$name] = $elementsArray;

        return $elementsArray;
    }

    private static function isSwitchOff($elementName, $settings){
        switch ($elementName) {
            case 'name':           return $settings[0]['show_name'] ? true : false;
            case 'image':          return $settings[0]['show_image'] ? true : false;
            case 'stock':          return $settings[0]['show_stock'] ? true : false;
            case 'description':    return $settings[0]['show_description'] ? true : false;
            case 'model':          return $settings[0]['show_model'] ? true : false;
            case 'properties':     return $settings[0]['show_properties'] ? true : false;
            case 'rating':         return $settings[0]['show_rating'] ? true : false;
            case 'ratingCounts':   return $settings[0]['show_rating_counts'] ? true : false;
            case 'price':          return $settings[0]['show_price'] ? true : false;
            case 'bonusPoints':    return $settings[0]['show_bonus_points'] ? true : false;
            case 'buyButton':      return $settings[0]['show_buy_button'] ? true : false;
          //case 'quoteButton':    return $settings[0]['show_qty_input'] ? true : false;
          //case 'sampleButton':   return $settings[0][''] ? true : false;
            case 'qtyInput':       return $settings[0]['show_qty_input'] ? true : false;
            case 'viewButton':     return $settings[0]['show_view_button'] ? true : false;
            case 'wishlistButton': return $settings[0]['show_wishlist_button'] ? true : false;
            case 'compare':        return $settings[0]['show_compare'] ? true : false;
            case 'attributes':     return $settings[0]['show_attributes'] ? true : false;
            case 'paypalButton':   return $settings[0]['show_paypal_button'] ? true : false;
            case 'amazonButton':   return $settings[0]['show_amazon_button'] ? true : false;
            default: return false;
        }
    }

    private static function fieldStyles($item, $settings, $visibility){
        $name = '.' . $item['name'] . ($item['class'] ? '.' . $item['class'] : '');
        $mediaArr = \common\models\ThemesSettings::find()
            ->where([
                'theme_name' => THEME_NAME,
                'setting_name' => 'media_query',
            ])->orderBy('setting_value')->asArray()->all();

        $style = Style::getAttributes($settings[0]);
        $hover = Style::getAttributes($visibility[1][0]);
        $active = Style::getAttributes($visibility[2][0]);
        $before = Style::getAttributes($visibility[3][0]);
        $after = Style::getAttributes($visibility[4][0]);
        if ($style) {
            self::$styles[0] .= '.list-' . self::$listType . ' ' . $name . '{' . $style . '}';
        }
        if ($hover) {
            self::$styles[0] .= '.list-' . self::$listType . ' ' . $name . ':hover{' . $hover . '}';
        }
        if ($active) {
            self::$styles[0] .= '.list-' . self::$listType . ' ' . $name . '.active{' . $active . '}';
        }
        if ($before) {
            self::$styles[0] .= '.list-' . self::$listType . ' ' . $name . ':before{' . $before . '}';
        }
        if ($after) {
            self::$styles[0] .= '.list-' . self::$listType . ' ' . $name . ':after{' . $after . '}';
        }
        foreach ($mediaArr as $item2){
            $style = Style::getAttributes($visibility[$item2['id']][0]);
            if ($style){
                self::$styles[$item2['id']] .= '.list-' . self::$listType . ' ' . $name . '{' . $style . '}';
            }
            if ($visibility[$item2['id']][0]['schema']){
                self::$styles[$item2['id']] .= \backend\design\Style::schema(
                    $visibility[$item2['id']][0]['schema'],
                    '.list-' . self::$listType . ' ' . $name
                );
                //self::$styles[$item2['id']] .= $this->schema($visibility[$item2['id']][0]['schema'], $item['id']);
            }
        }
    }
}

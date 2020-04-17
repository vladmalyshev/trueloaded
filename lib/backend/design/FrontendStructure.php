<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design;

use Yii;
use common\models\ThemesStyles;
use common\classes\design;

class FrontendStructure
{
    private static $fielded = false;

    private static $groups = [
        'home' => [
            'name' => 'home',
            'title' => TEXT_HOME,
            'types' => ['home'],
        ],
        'catalog' => [
            'name' => 'catalog',
            'title' => TEXT_CATALOG,
            'types' => ['product', 'catalog'],
        ],
        'productListing' => [
            'name' => 'productListing',
            'title' => TEXT_PRODUCT_LISTING_ITEMS,
            'types' => ['productListing'],
        ],
        'informations' => [
            'name' => 'informations',
            'title' => TEXT_INFORMATIONS,
            'types' => ['inform', 'contact', 'delivery-location', 'delivery-location-default', 'promotions', 'sitemap', 'reviews'],
        ],
        'account' => [
            'name' => 'account',
            'title' => TEXT_ACCOUNT,
            'types' => ['account', 'inform'],
        ],
        'cart' => [
            'name' => 'cart',
            'title' => TEXT_CART,
            'types' => ['cart', 'quote', 'sample', 'wishlist'],
        ],
        'checkout2' => [
            'name' => 'checkout2',
            'title' => TEXT_STEPS_CHECKOUT,
            'types' => ['checkout'],
        ],
        'checkout' => [
            'name' => 'checkout',
            'title' => TEXT_CHECKOUT,
            'types' => ['checkout'],
        ],
        'checkoutQuote2' => [
            'name' => 'checkoutQuote2',
            'title' => TEXT_QUOTE_CHECKOUT,
            'types' => ['checkout'],
        ],
        'checkoutQuote' => [
            'name' => 'checkoutQuote',
            'title' => TEXT_QUOTE_CHECKOUT,
            'types' => ['checkout'],
        ],
        'checkoutSample' => [
            'name' => 'checkoutSample',
            'title' => TEXT_SAMPLE_CHECKOUT,
            'types' => ['checkout'],
        ],
        'orders' => [
            'name' => 'orders',
            'title' => IMAGE_ORDERS,
            'types' => ['invoice', 'packingslip', 'orders'],
        ],
        'emails' => [
            'name' => 'emails',
            'title' => TEXT_EMAIL_GIFT_CARD,
            'types' => ['email', 'gift', 'gift_card'],
        ],
        'pdf' => [
            'name' => 'pdf',
            'title' => PDF_CATALOG,
            'types' => ['pdf'],
        ],
        'components' => [
            'name' => 'components',
            'title' => TEXT_COMPONENTS,
            'types' => ['components'],
        ],
    ];

    private static $types = [
        'home' => [
            'mainGroup' => 'home',
            'mainAction' => '',
        ],
        'product' => [
            'mainGroup' => 'catalog',
            'mainAction' => '',
        ],
        'catalog' => [
            'mainGroup' => 'catalog',
            'mainAction' => '',
        ],
        'categories' => [
            'mainGroup' => 'catalog',
            'mainAction' => '',
        ],
        'products' => [
            'mainGroup' => 'catalog',
            'mainAction' => '',
        ],
        'productListing' => [
            'mainGroup' => 'productListing',
            'mainAction' => '',
        ],
        'inform' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'contact' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'delivery-location' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'delivery-location-default' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'promotions' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'sitemap' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'reviews' => [
            'mainGroup' => 'informations',
            'mainAction' => '',
        ],
        'account' => [
            'mainGroup' => 'account',
            'mainAction' => '',
        ],
        'cart' => [
            'mainGroup' => 'cart',
            'mainAction' => '',
        ],
        'quote' => [
            'mainGroup' => 'cart',
            'mainAction' => '',
        ],
        'sample' => [
            'mainGroup' => 'cart',
            'mainAction' => '',
        ],
        'wishlist' => [
            'mainGroup' => 'cart',
            'mainAction' => '',
        ],
        'checkout' => [
            'mainGroup' => 'checkout',
            'mainAction' => '',
        ],
        'invoice' => [
            'mainGroup' => 'orders',
            'mainAction' => '',
        ],
        'packingslip' => [
            'mainGroup' => 'orders',
            'mainAction' => '',
        ],
        'orders' => [
            'mainGroup' => 'orders',
            'mainAction' => '',
        ],
        'email' => [
            'mainGroup' => 'emails',
            'mainAction' => '',
        ],
        'gift' => [
            'mainGroup' => 'emails',
            'mainAction' => '',
        ],
        'gift_card' => [
            'mainGroup' => 'emails',
            'mainAction' => '',
        ],
        'pdf' => [
            'mainGroup' => 'pdf',
            'mainAction' => '',
        ],
        'components' => [
            'mainGroup' => 'components',
            'mainAction' => '',
        ]
    ];

    private static $unitedTypes = [
        ['inform', 'contact', 'sitemap'],
        ['delivery-location', 'delivery-location-default'],
        ['cart', 'quote', 'sample', 'wishlist'],
        ['invoice', 'packingslip', 'orders'],
        ['catalog', 'categories', 'products'],
    ];

    private static $pages = [
        'home' => [
            'action' => '',
            'name' => 'home',
            'page_name' => 'home',
            'title' => TEXT_HOME,
            'type' => 'home',
            'group' => 'home'
        ],
        'product' => [
            'action' => 'catalog/product',
            'name' => 'product',
            'page_name' => 'product',
            'title' => TEXT_PRODUCT,
            'type' => 'product',
            'group' => 'catalog'
        ],
        'categories' => [
            'action' => '',
            'name' => 'categories',
            'page_name' => 'categories',
            'title' => TEXT_LISTING_CATEGORIES,
            'type' => '',
            'group' => ''
        ],
        'products' => [
            'action' => '',
            'name' => 'products',
            'page_name' => 'products',
            'title' => TEXT_LISTING_PRODUCTS,
            'type' => '',
            'group' => ''
        ],
        '' => [
            'action' => '',
            'name' => '',
            'page_name' => '',
            'title' => '',
            'type' => '',
            'group' => ''
        ],
    ];

    private static function init()
    {
        if (self::$fielded) return false;

        if (Yii::$app->request->get('theme_name')) {
            $theme_name = Yii::$app->request->get('theme_name');
        } elseif (defined("THEME_NAME")) {
            $theme_name = THEME_NAME;
        } else {
            return false;
        }

        $addedPages = \common\models\ThemesSettings::find()
            ->select(['setting_name','setting_value'])
            ->where([
                'theme_name' => $theme_name,
                'setting_group' => 'added_page',
            ])
            ->orderBy('setting_name')
            ->all();
        foreach ($addedPages as $page) {
            if (self::$types[$page['setting_value']]['mainGroup'] && self::$types[$page['setting_value']]['mainAction']) {
                self::$pages[design::pageName($page['setting_name'])] = [
                    'action' => self::$types[$page['setting_value']]['mainAction'],
                    'name' => $page['setting_name'],
                    'page_name' => design::pageName($page['setting_name']),
                    'title' => $page['setting_name'],
                    'type' => $page['setting_value'],
                    'group' => self::$types[$page['setting_value']]['mainGroup'],
                ];
            }
        }

        foreach (\common\helpers\Acl::getExtensionPages() as $page){
            self::$pages[$page['name']] = [
                'action' => $page['action'],
                'name' => $page['name'],
                'page_name' => $page['name'],
                'title' => $page['title'],
                'type' => $page['type'],
                'group' => $page['group'],
            ];
            $groupName = design::pageName($page['group']);
            if (!self::$groups[$groupName]) {
                self::$groups[$groupName] = [
                    'name' => $groupName,
                    'title' => $page['group'],
                    'types' => [$page['type']],
                ];
            } else {
                if (!in_array($page['type'], self::$groups[$groupName]['types'])){
                    self::$groups[$groupName]['types'] = array_merge(self::$groups[$groupName]['types'], [$page['type']]);
                }
            }
            if (!self::$types[$page['type']]) {
                self::$types[$page['type']] = [];
            }
        }

        self::$fielded = true;
        return false;
    }

    public static function getUnitedTypesGroup($type)
    {
        foreach (self::$unitedTypes as $united) {
            if (in_array($type, $united)) {
                return $united;
            }
        }
        return [$type];
    }

    public static function getPageGroups(){
        self::init();
        return self::$groups;
    }

    public static function getPageTypes(){
        self::init();
        return self::$types;
    }

    public static function getPages(){
        self::init();
        return self::$pages;
    }
}
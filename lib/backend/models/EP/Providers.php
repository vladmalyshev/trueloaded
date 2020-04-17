<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP;

use backend\models\EP\Provider\ProviderAbstract;
use Yii;
use backend\models\EP\Provider\ImportInterface;

class Providers
{

    protected $providers = [];

    public function __construct()
    {
        \common\helpers\Translation::init('admin/categories');

        $this->providers = [
            'product\catalog' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_DOWNLOAD_CATALOG,
                'class' => 'Provider\\CatalogArchive',
                'export' =>[
                    'allow_format' => ['ZIP'],
                    'filters' => ['category','with-images'],
                    'disableSelectFields' => true,
                ],
            ],
            'product\products' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_PRODUCT,
                'class' => 'Provider\\Products',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\categories' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_CATEGORIES,
                'class' => 'Provider\\Categories',
                'export' =>[
                    'filters' => ['category','with-images'],
                ],
            ],
            'product\products_to_categories' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_PRODUCTS_TO_CATEGORIES,
                'class' => 'Provider\\ProductsToCategories',
                'export' =>[
                    'filters' => ['category'],
                    'disableSelectFields' => true,
                ],
            ],
            'product\attributes' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_ATTRIBUTES,
                'class' => 'Provider\\Attributes',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\inventory' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_INVENTORY,
                'class' => 'Provider\\Inventory',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\brands' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_BRANDS,
                'class' => 'Provider\\Brands',
                'export' =>[
                    'filters' => [],
                ],
            ],
            'product\suppliers' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => BOX_CATALOG_SUPPIERS,
                'class' => 'Provider\\Suppliers',
                'export' =>[
                    'filters' => [],
                ],
            ],
            'product\suppliersproducts' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => BOX_CATALOG_SUPPIERS_PRODUCTS,
                'class' => 'Provider\\SuppliersProducts',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\stock' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_STOCK_FEED,
                'class' => 'Provider\\Stock',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
/**/
            'product\sales' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_SALES_FEED,
                'class' => 'Provider\\Sales',
                'export' =>[
                    'filters' => ['category'/*, 'warehouse', 'supplier'*/],
                ],
            ],
/**/
            'product\warehousestock' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => 'Warehouse '.TEXT_OPTION_STOCK_FEED,
                'class' => 'Provider\\WarehouseStock',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\bundles' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_PRODUCTS_BUNDLE,
                'class' => 'Provider\\Bundles',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\linked_products' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => defined('TEXT_LINKED_PRODUCTS')?TEXT_LINKED_PRODUCTS:'Linked products',
                'class' => 'LinkedProducts\\ImportExport',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'product\customer_products' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => defined('TEXT_CUSTOMER_PRODUCTS')?TEXT_CUSTOMER_PRODUCTS:'Customer products',
                'class' => 'CustomerProducts\\ImportExport',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'product\customer_prices' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => defined('TEXT_CUSTOMER_PRICES')?TEXT_CUSTOMER_PRICES:'Customer prices',
                'class' => 'CustomerProducts\\ImportPrices',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'product\xsell' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_CROSS_SELL_PRODUCTS,
                'class' => 'Provider\\XSell',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\images' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_IMAGES,
                'class' => 'Provider\\Images',
                'export' =>[
                    'filters' => ['category','with-images'],
                ],
            ],
            'product\properties' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_PROPERTIES,
                'class' => 'Provider\\Properties',
                'export' =>[
                    'filters' => ['category'],
                    'disableSelectFields' => true,
                ],
            ],
            'product\catalog_properties' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_OPTION_PROPERTIES_SETTINGS,
                'class' => 'Provider\\CatalogProperties',
                'export' =>[
                    'filters' => ['properties'],
                ],

            ],
            'product\assets' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TEXT_PRODUCT_ASSETS,
                'class' => 'Provider\\Assets',
                'export' =>[
                    'filters' => ['category'],
                    'disableSelectFields' => true,
                ],
            ],
            'product\reviews' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => BOX_CATALOG_REVIEWS,
                'class' => 'Provider\\ProductReviews',
                'export' =>[
                    'filters' => ['category'],
                ],
            ],
            'product\documents' => [
                'group' => TEXT_CATALOG_PRODUCTS,
                'name' => TAB_DOCUMENTS,
                'class' => 'Provider\\Documents',
                'export' =>[
                    'filters' => ['category','with-images'],
                ],
            ],
            'statistic\orders' => [
                'group' => TEXT_SITE_STATISTIC,
                'name' => 'Order Statistic',
                'class' => 'Provider\\OrderStatistic',
                'export' =>[
                    'filters' => ['orders-date-range'],
                    'disableSelectFields' => true,
                ],
            ],
            'seo\redirects' => [
                'group' => 'SEO Redirects',
                'name' => 'SEO Redirects',
                'class' => 'SeoRedirects\\SeoRedirectsExport',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'BrightPearl\\Stock' => [
                'group' => TEXT_BRIGHT_PEARL,
                'name' => 'Stock',
                'class' => 'Provider\\BrightPearl\\Stock',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'BrightPearl\\ExportPrice' => [
                'group' => TEXT_BRIGHT_PEARL,
                'name' => 'Export Price',
                'class' => 'Provider\\BrightPearl\\ExportPrice',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'BrightPearl\\ExportOrder' => [
                'group' => TEXT_BRIGHT_PEARL,
                'name' => 'Export Order',
                'class' => 'Provider\\BrightPearl\\ExportOrder',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'HolbiLink\\Products' => [
                'group' => 'Holbi Link',
                'name' => 'Import products',
                'class' => 'Provider\\HolbiLink\\ImportProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'HPCap\\ImportProducts' => [
                'group' => 'HP Cap',
                'name' => 'Import products',
                'class' => 'Provider\\HPCap\\ImportProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Magento\\ImportGroups' => [
                'group' => 'Magento',
                'name' => 'Import groups',
                'class' => 'Provider\\Magento\\ImportGroups',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Magento\\ImportProducts' => [
                'group' => 'Magento',
                'name' => 'Import products',
                'class' => 'Provider\\Magento\\ImportProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Magento\\ImportCustomers' => [
                'group' => 'Magento',
                'name' => 'Import customers',
                'class' => 'Provider\\Magento\\ImportCustomers',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Magento\\ImportOrders' => [
                'group' => 'Magento',
                'name' => 'Import orders',
                'class' => 'Provider\\Magento\\ImportOrders',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'orders\customers' => [
                'group' => TEXT_SITE_ORDER_EXPORT_IMPORT,
                'name' => 'Customers',
                'class' => 'Provider\\Customers',
                'export' =>[
                    'allow_format' => ['CSV'/*,'XML'*/],
                    //'filters' => ['orders-date-range'],
                    //'disableSelectFields' => true,
                ],
//                'import' =>[
//                    'format' => 'XML',
//                ],
            ],
            /*
            'orders\orders' => [
                'group' => TEXT_SITE_ORDER_EXPORT_IMPORT,
                'name' => 'Order Export/Import',
                'class' => 'Provider\\OrderExport',
                'export' =>[
                    'allow_format' => ['XML_orders_new'],
                    'filters' => ['orders-date-range'],
                    'disableSelectFields' => true,
                ],
                'import' =>[
                    'format' => 'XML_orders_new'
                ],
            ],
            */
            'orders\order' => [
                'group' => TEXT_SITE_ORDER_EXPORT_IMPORT,
                'name' => 'Order',
                'class' => 'Provider\\Order',
                'export' =>[
                    'allow_format' => ['CSV','XML'],
                    'filters' => ['orders-date-range'],
                    //'disableSelectFields' => true,
                ],
                'import' =>[
                    'format' => 'XML',
                ],
            ],
            'XTrader\\ImportProducts' => [
                'group' => 'XTrader',
                'name' => 'Import products',
                'class' => 'Provider\\XTrader\\ImportProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'XTrader\\ImportStock' => [
                'group' => 'XTrader',
                'name' => 'Import stock',
                'class' => 'Provider\\XTrader\\ImportStock',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Competitor\\CompetitorBot' => [
                'group' => 'Competitor',
                'name' => 'Competitor Bot',
                'class' => 'Provider\\Competitor\\CompetitorBot',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'PdfCatalogues\\PdfCatalogues' => [
                'group' => 'PdfCatalogues',
                'name' => 'PDF Catalogues Generator',
                'class' => 'Provider\\PdfCatalogues\\PdfCatalogGen',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'report\\customers' => [
                'group' => 'Report',
                'name' => 'Customers',
                'class' => 'Provider\\CustomersReport',
                'export' =>[
                    'allow_format' => ['CSV'],
                    'filters' => ['platform'],
                    //'disableSelectFields' => true,
                ],
            ],
            'PaymentBots\\PaypalCollector' => [
                'group' => 'PaymentBots',
                'name' => 'Paypal Transactions Collector',
                'class' => 'Provider\\PaymentBots\\PaypalCollector',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Google\\SyncEcommerce' => [
                'group' => 'Google',
                'name' => 'Sync e-commerce',
                'class' => 'Provider\\Google\\SyncEcommerce',
            ],
        ];

        if ( !defined('PRODUCTS_INVENTORY') || PRODUCTS_INVENTORY=='False' ){
            unset($this->providers['product\inventory']);
        }

        foreach ( DataSources::getAvailableList() as $dataSourceInfo){
            if ( method_exists($dataSourceInfo['className'],'getProviderList') ) {
                $dataSourceProviderList = call_user_func([$dataSourceInfo['className'],'getProviderList']);
                if ( is_array($dataSourceProviderList) && count($dataSourceProviderList)>0 ) {
                    $this->providers = array_merge($this->providers, $dataSourceProviderList);
                }
            }
        }

        $get_custom_r = tep_db_query(
            "SELECT custom_provider_id, name, parent_provider, provider_configure ".
            "FROM " . TABLE_EP_CUSTOM_PROVIDERS . " ".
            "WHERE 1 ".
            "ORDER BY 1"
        );
        if ( tep_db_num_rows($get_custom_r)>0 ) {
            while( $custom = tep_db_fetch_array($get_custom_r) ){
                $parentProvider = $custom['parent_provider'];
                if ( !isset($this->providers[$parentProvider]) ) continue;
                $provider_info = $this->providers[$parentProvider];

                $provider_info['name'] = $custom['name'];
                $provider_key = 'custom\\'.$custom['custom_provider_id'];

                //$provider_info['provider_configure'];

                $this->providers[ $provider_key ] = $provider_info;

            }
        }

    }

    public function getAvailableProviders($type, $filterGroup='')
    {
        $providerList = array();
        foreach ( $this->providers as $provider_key=>$provider_info ) {
            if ( !empty($filterGroup) && is_callable($filterGroup) ) {
                if (!$filterGroup($provider_key, $provider_info)) continue;
            }else{
                if ( !empty($filterGroup) && strpos($provider_key,$filterGroup.'\\')!==0 ) continue;        
            }
            $providerClassName = $this->getProviderFullClassName($provider_key);
            if ( $type=='Import' && is_subclass_of($providerClassName,'backend\models\EP\Provider\ImportInterface',true) && call_user_func([$providerClassName,'isImportAvailable'] )){
                $provider_info['key'] = $provider_key;
                $providerList[] = $provider_info;
            }elseif ( $type=='Export' && is_subclass_of($providerClassName,'backend\models\EP\Provider\ExportInterface',true) && call_user_func([$providerClassName,'isExportAvailable'] )){
                $provider_info['key'] = $provider_key;
                $providerList[] = $provider_info;
            }elseif ( $type=='Datasource' && is_subclass_of($providerClassName,'backend\models\EP\Provider\DatasourceInterface',true)) {
                $provider_info['key'] = $provider_key;
                $providerList[] = $provider_info;
            }elseif($ext = \common\helpers\Acl::checkExtension($provider_info['class'], 'allowed')){
                $provider_info['key'] = $provider_key;
                $providerList[] = $provider_info;
            }
        }
        return $providerList;
    }

    public function pullDownVariants($for='Import', $pullDownData = [], $filterGroup='')
    {
        if ( !isset($pullDownData['items']) ) $pullDownData['items'] = [];
        if ( !isset($pullDownData['options']) ) $pullDownData['options'] = [];
        if ( !isset($pullDownData['options']['options']) ) $pullDownData['options']['options'] = [];

        $option_key = strtolower($for);
        foreach($this->getAvailableProviders($for, $filterGroup) as $providerInfo)
        {
            $group = $providerInfo['group'];
            if ( !isset($pullDownData['items'][$group]) ) $pullDownData['items'][$group] = [];
            $pullDownData['items'][$group][$providerInfo['key']] = $providerInfo['name'];

            if ( isset($providerInfo[$option_key]) ) {
                $providerOptions = $providerInfo[$option_key];
                $options_data = [];
                if (!isset($providerOptions['disableSelectFields']) || !$providerOptions['disableSelectFields']) {
                    $options_data['data-select-fields'] = 'true';
                }
                if (isset($providerOptions['filters']) && count($providerOptions['filters']) > 0) {
                    foreach ($providerOptions['filters'] as $filterCode) {
                        $options_data['data-allow-select-' . $filterCode] = 'true';
                    }
                }
                if (isset($providerOptions['allow_format']) && count($providerOptions['allow_format']) > 0) {
                    $options_data['data-allow-format'] = implode(',',$providerOptions['allow_format']);
                }else{
                    $options_data['data-allow-format'] = 'CSV,ZIP';
                }

                if (count($options_data) > 0) {
                    $pullDownData['options']['options'][$providerInfo['key']] = $options_data;
                }
            }
        }

        return $pullDownData;
    }

    public function getProviderName($provider)
    {
        if ( isset($this->providers[$provider]) ) {
            return $this->providers[$provider]['name'];
        }
        return 'Unknown';
    }

    public function getProviderFullClassName($key)
    {
        if ( !isset($this->providers[$key]) ) return false;
        if ($providerClassName = \common\helpers\Acl::checkExtension($this->providers[$key]['class'], 'allowed')){

        } else {
            $providerClassName = 'backend\\models\\EP\\' . $this->providers[$key]['class'];
        }
        return $providerClassName;
    }
    /**
     * @param $key
     * @param array $providerConfig
     * @return bool|ProviderAbstract
     */
    public function getProviderInstance($key, $providerConfig=[])
    {
        $providerClassName = $this->getProviderFullClassName($key);
        if ( $providerClassName ) {
            /**
             * @var $obj ProviderAbstract
             */
            $obj = Yii::createObject($providerClassName, [$providerConfig]);
            if ( method_exists($obj,'customConfig') ) $obj->customConfig($providerConfig);
            return $obj;
        }
        return false;
    }

    public function bestMatch(array $fileColumns)
    {
        $providersMatchRate = array();
        foreach( $this->getAvailableProviders('Import') as $providerInfo)
        {
            if ( isset($providerInfo['export']) && isset($providerInfo['export']['allow_format']) && count($providerInfo['export']['allow_format'])>0 ) {
                if ( !in_array('CSV',$providerInfo['export']['allow_format']) ) continue;
            }
            if ( strpos($providerInfo['key'],'BrightPearl')!==false ) continue;
            $provider = $this->getProviderInstance($providerInfo['key']);
            if ( !is_object($provider) ) continue;
            /**
             * @var $provider ProviderAbstract
             */

            $score = $provider->getColumnMatchScore($fileColumns);
            if ( $score>0 ) {
                $providersMatchRate[$providerInfo['key']] = $score;
            }
        }
        arsort($providersMatchRate, SORT_NUMERIC);

        return $providersMatchRate;
    }

}
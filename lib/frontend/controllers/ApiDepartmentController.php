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


class ApiDepartmentController extends Sceleton
{

    public $enableCsrfValidation = false;
    public $authenticated = false;

    public function __construct($id, $module = null)
    {
        if ( function_exists('tep_session_flush_state') ) {
            tep_session_flush_state(false);
        }
        parent::__construct($id, $module);
    }


    public function actionIndex()
    {
        $this->layout = false;
    }

    public function actions()
    {
        $soapServerClassMap = array(
            'ProductListResponse' => '\\common\\api\\models\\GetProductListResponse',
            'GetCurrenciesResponse' => '\\common\\api\\models\\GetCurrenciesResponse',
            'ProductRef' => '\\common\\api\\models\\Products\\ProductRef',
            'InventoryRef' => '\\common\\api\\models\\Products\\InventoryRef',
            'CustomerSearch' => '\\common\\api\\models\\Customer\\CustomerSearch',
            'Order'=> '\\common\\api\\models\\Order\\Order',
            'Customer' => '\\common\\api\\models\\Customer\\Customer',
            'ArrayOfSearchConditions' => '\\common\\api\\models\\ArrayOfSearchConditions',
            'SearchCondition' => '\\common\\api\\models\\SearchCondition',
            'Paging' => '\\common\\api\\models\\Paging',
            'Category' => '\\common\\api\\models\\Categories\\Category',
            'Product' => '\\common\\api\\models\\Products\\Product',
            'ArrayOfOrderStatus' => '\\common\\api\\models\\Store\\ArrayOfOrderStatus',
            'OrderStatus' => '\\common\\api\\models\\Store\\OrderStatus',
            'LanguageValue' => '\\common\\api\\models\\LanguageValue',
            'ArrayOfLanguageValueMap' => '\\common\\api\\models\\ArrayOfLanguageValueMap',
            'Supplier' => '\\common\\api\\models\\Supplier\\Supplier',
            'CatalogProductProperty' => '\\common\\api\\models\\Products\\CatalogProductProperty',
            'Manufacturer' => '\\common\\api\\models\\Products\\Manufacturer',
            'PriceAndStockInfo' => '\\common\\api\\models\\Products\\PriceAndStockInfo',
        );

        return [
            'service' => [
                'class' => 'common\api\SoapServer\SoapAction',
                'provider' => 'common\api\SoapServer\SoapProxyRpc',
                'serviceOptions' => array(
                    'wsdlProvider' => 'common\api\SoapServer\Department',
                    'wsdlCacheDuration' => 300,
                ),
                'wsdlOptions' => array(
                    'bindingStyle' => \subdee\soapserver\WsdlGenerator::STYLE_RPC,
                    'operationBodyStyle' => array(
                        'use' => \subdee\soapserver\WsdlGenerator::USE_ENCODED,
                        'encodingStyle' => 'http://schemas.xmlsoap.org/soap/encoding/',
                    ),
                ),
                'classMap' => $soapServerClassMap,
            ],
            'service-dl' => [
                'class' => 'common\api\SoapServer\SoapAction',
                'provider' => 'common\api\SoapServer\SoapProxyDocument',
                'serviceOptions' => array(
                    'wsdlProvider' => 'common\api\SoapServer\Department',
                    'wsdlCacheDuration' => 300,
                ),
                'wsdlOptions' => array(
                    'bindingStyle' => \subdee\soapserver\WsdlGenerator::STYLE_DOCUMENT,
                    'operationBodyStyle' => array(
                        'use' => \subdee\soapserver\WsdlGenerator::USE_LITERAL,
                    ),
                ),
                'classMap' => $soapServerClassMap,
            ],
        ];
    }

    protected function setMeta($id, $params)
    {

    }

    public function initActionTranslation($entity)
    {

    }


}

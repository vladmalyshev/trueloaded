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


class ApiPlatformController extends Sceleton
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

        $soapServerClassMap = \common\api\SoapServer\Platform::getClassMap();

        return [
            'service' => [
                'class' => 'common\api\SoapServer\SoapAction',
                'provider' => [
                    'class'=>'common\api\SoapServer\SoapProxyRpc',
                    'serverClass' => 'common\api\SoapServer\Platform',
                ],
                'serviceOptions' => array(
                    'wsdlProvider' => 'common\api\SoapServer\Platform',
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
                'provider' => [
                    'class'=>'common\api\SoapServer\SoapProxyDocument',
                    'serverClass' => 'common\api\SoapServer\Platform',
                 ],
                'serviceOptions' => array(
                    'wsdlProvider' => 'common\api\SoapServer\Platform',
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

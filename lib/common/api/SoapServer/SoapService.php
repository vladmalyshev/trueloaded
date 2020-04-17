<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\SoapServer;


use subdee\soapserver\WsdlGenerator;

class SoapService extends \subdee\soapserver\SoapService
{

    public $wsdlProvider;

    /**
     * Generates the WSDL as defined by the provider.
     * The cached version may be used if the WSDL is found valid in cache.
     *
     * @return string the generated WSDL
     * @see wsdlCacheDuration
     */
    public function generateWsdl()
    {
        $providerClass = get_class($this->provider);
        if ( !empty($this->wsdlProvider) ) {
            if ( is_object($this->wsdlProvider) ) {
                $providerClass = get_class($this->wsdlProvider);
            }else{
                $providerClass = strval($this->wsdlProvider);
            }
        }
        if ($this->wsdlCacheDuration > 0 && $this->cacheID !== false) {
            $key = 'SoapService.' . $providerClass . $this->serviceUrl . $this->encoding;
            if (($wsdl = \Yii::$app->cache->get($key)) !== false) {
                return $wsdl;
            }
        }

        $generator = new WsdlGenerator();
        foreach ($this->wsdlOptions as $option => $value) {
            $generator->$option = $value;
        }
        $wsdl = $generator->generateWsdl($providerClass, $this->serviceUrl, $this->encoding);
        if (isset($key)) {
            \Yii::$app->cache->set($key, $wsdl, $this->wsdlCacheDuration);
        }
        return $wsdl;
    }

    public function run()
    {
        header('Content-Type: text/xml;charset=' . $this->encoding);
        if ($this->wsdlCacheDuration<=0) {
            ini_set("soap.wsdl_cache_enabled", 0);
        }else{
            // use always cache -- CLIENT cache
            if ( !ini_get("soap.wsdl_cache_enabled" ) ){
                ini_set("soap.wsdl_cache_enabled", 1);
            }
            ini_set("soap.wsdl_cache_ttl", $this->wsdlCacheDuration);
        }

        list(, $hash) = explode(' ', \Yii::$app->getRequest()->getHeaders()->get('authorization') . ' ');
        $auth = $hash ? base64_decode($hash) . '@' : '';
        $server = new \SoapServer(str_replace('http://', 'http://' . $auth, $this->wsdlUrl), $this->getOptions());
        try {
            if ($this->persistence !== null) {
                $server->setPersistence($this->persistence);
            }
            if (is_string($this->provider)) {
                $provider = $this->provider;
                $provider = new $provider();
            } else {
                $provider = $this->provider;
            }
            $server->setObject($provider);
            ob_start();
            try {
                $server->handle();
            } catch (Exception $e) {
                var_dump($e);
                die();
            }
            $soapXml = ob_get_contents();
            ob_end_clean();
            return $soapXml;
        } catch (Exception $e) {
            if ($e->getCode() !== self::SOAP_ERROR) // non-PHP error
            {
                // only log for non-PHP-error case because application's error handler already logs it
                // php <5.2 doesn't support string conversion auto-magically
                \Yii::error($e->__toString());
            }
            $message = $e->getMessage();
            if (YII_DEBUG) {
                $message .= ' (' . $e->getFile() . ':' . $e->getLine() . ")\n" . $e->getTraceAsString();
            }

            // We need to end application explicitly because of
            // http://bugs.php.net/bug.php?id=49513
            $server->fault(get_class($e), $message);
            exit(1);
        }
    }


}
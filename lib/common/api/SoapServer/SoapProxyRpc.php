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


use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class SoapProxyRpc extends BaseObject
{
    public $serverClass;

    private $soapObject = false;

    public function init()
    {
        parent::init();

        if ( $this->serverClass ) {
            $this->soapObject = \Yii::createObject($this->serverClass);
            if ( isset($_SERVER) && !empty($_SERVER['HTTP_API_KEY']) && method_exists($this->soapObject,'auth') ) {
                $this->soapObject->auth($_SERVER['HTTP_API_KEY']);
            }
        }else{
            throw new InvalidConfigException('serverClass required');
        }
    }

    public function __call($methodName, $args) {
        try {
            $result = call_user_func_array(array($this->soapObject, $methodName), $args);
        }catch (\SoapFault $soap) {
            throw $soap;
        }catch (\yii\base\Exception $ex){
            throw new \SoapFault('ERROR'/*$ex->getCode()*/, $ex->getName());
        }catch (\Exception $ex){
            throw new \SoapFault('ERROR'/*$ex->getCode()*/, $ex->getMessage());
        }
        return $result;
    }

}
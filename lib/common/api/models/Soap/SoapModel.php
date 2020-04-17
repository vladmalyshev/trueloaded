<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;

use yii\base\BaseObject;

class SoapModel extends BaseObject
{

    protected $_castType = [];

    public function __construct(array $config = [])
    {
        $initConfig = [];
        $objectProperties = \Yii::getObjectVars($this);
        foreach( array_keys($config) as $field){
            if ( array_key_exists($field, $objectProperties) ) {
                $initConfig[$field] = $config[$field];
            }
        }

        parent::__construct($initConfig);

        $this->castTypeSoap();

    }

    protected function castTypeSoap()
    {
        if ( is_array($this->_castType) && count($this->_castType)>0 ) {
            foreach ($this->_castType as $property=>$propertyType)
            {
                $nullable = false;
                if ( is_array($propertyType) ) {
                    $nullable = isset($propertyType[1]) && $propertyType[1];
                    $propertyType = $propertyType[0];
                }

                if ( $propertyType=='datetime' ) {
                    $value = $this->{$property};
                    if ( !empty($value) && $value>1000 ) {
                        $this->{$property} = \common\api\SoapServer\SoapHelper::soapDateTimeOut($value);
                    }elseif ($nullable){
                        $this->{$property} = null;
                    }
                }elseif ($propertyType=='date'){
                    $value = $this->{$property};
                    if ( !empty($value) && preg_match('/^(\d{4}-\d{2}-\d{2})/',$value, $m) ) {
                        $this->{$property} = $m[1];
                    }else{
                        if ($nullable){
                            $this->{$property} = null;
                        }
                    }
                }
            }
        }
    }

    public function build(){}

    protected function addMessage($code, $text)
    {
        if ( !isset($this->messages) ) return;
        if ( !is_object($this->messages) ) {
            $this->messages = new ArrayOfMessages();
        }
        $this->messages->message[] = new Message(['code'=>$code,'text'=>$text]);
        if ( isset($this->status) ) {
            if ($code=='ERROR') $this->status = 'ERROR';
        }
    }

    public function warning($text)
    {
        $this->addMessage('WARNING', $text);
    }

    public function error($text, $customErrorCode='ERROR')
    {
        $this->addMessage($customErrorCode, $text);
        if ( isset($this->status) ) {
            $this->status = 'ERROR';
        }
    }
}
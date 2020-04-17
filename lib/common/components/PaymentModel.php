<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components;

use Yii;

class PaymentModel extends \yii\base\Model
{
    public $rules = [];
    public $data = [];
    
    public function __construct(){
        
    }
 
    public function rules(){
        return $this->rules;
    }
    
    public function __set($name, $value) {
        $this->data[$name] = $value;
    }
    
    public function __get($name) {
        if (isset($this->data[$name])){
            return $this->data[$name];
        }
        return null;
    }
    
    public function getAttributes($names = null, $except = []){
        return $this->data;
    }
    
    public function beforeValidate() {
        foreach($this->rules() as $rule){
            if (isset($rule['format'])){
                $method = 'as'.$rule[1];
                foreach($rule[0] as $field){
                    try {
                        if ( strlen($this->{$field})){
                            $value = \common\helpers\Date::prepareInputDate($this->{$field});
                            $this->{$field} = Yii::$app->formatter->{$method}($value, $rule['format']);
                        }
                    } catch (\Exception $ex) {
                        $this->addError($field, $ex->getMessage());
                    }
                }
            }
        }
        if ($this->hasErrors()){
            return false;
        }
        return true;
    }
    
}
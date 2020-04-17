<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\orders\payments;


use Yii;
use yii\base\Widget;

class PaymentFields extends Widget {
        
    public $manager;
    public $rules;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        $fields = [];
        if (is_array($this->rules)){
            foreach($this->rules as $rule){
                if (is_array($rule[0])){
                    foreach($rule[0] as $_field){
                        if (!array_key_exists($_field, $fields)){
                            $fields[$_field] = ['name' => $_field, 'validators' => [$rule[1]]];
                        } else {
                            $fields[$_field]['validators'][] = $rule[1];
                        }
                    }
                } else {
                    if (!array_key_exists($rule[0], $fields)){
                        $fields[$rule[0]] = ['name' => $rule[0], 'validators' => [$rule[1]]];
                    }else {
                        $fields[$rule[0]]['validators'][] = $rule[1];
                    }
                }
            }
        }
        
        return json_encode([
            'required' => $this->render('payment-fields', [
                'fields' => $fields,
                'manager' => $this->manager
            ])
        ]);
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace suppliersarea\forms;

class LoginForm extends \yii\base\Model{
    
    public $email_address;
    public $password;
    
    
    public function rules() {
        return[
            [['email_address', 'password'], 'required'],
            //['email_address', 'email'],
        ];
    }
    
    public function loginSupplier(){
        $_supplier = \suppliersarea\components\SupplierIdentity::findIdentityByData($this->email_address, $this->password);              
        if (!$_supplier){
            $this->addError('email_address', 'Invalid email/password');
            return false;
        } else {            
            \suppliersarea\SupplierModule::getInstance()->user->login($_supplier);            
            return true;
        }
    }
    
}
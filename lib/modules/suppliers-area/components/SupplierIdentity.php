<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace suppliersarea\components;

class SupplierIdentity extends \common\models\Suppliers implements \yii\web\IdentityInterface {
    
    public function getId()
    {
        return $this->suppliers_id;
    }
    
    public static function findIdentity($id)
    {   
        return static::findOne(['suppliers_id' => $id]);
    }
    
    public static function findIdentityByAccessToken($token, $type = null)
    {
       //return static::findOne(['access_token' => $token]);
    }
       
 
    public function getAuthKey()
    {
        //return $this->authKey;
    }
 
    public function validateAuthKey($authKey)
    {
        //return $this->authKey === $authKey;
    }
    
    public static function findIdentityByData($email_address, $password){
        $sAuth = \common\models\SuppliersAuthData::find()
                ->where(['email_address' => $email_address, 'password' => $password])
                ->one();
        if ($sAuth){
            $supplier = static::findIdentity($sAuth->suppliers_id);            
            if ($supplier){                
                return $supplier;
            }            
        }
        return false;
    }
    
}
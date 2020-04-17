<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\Xml\Classes;

class Customer
{
    public $customers_id;
    
    public $customers;
    public $customers_info;
    public $address_book;
    
    public function load($customers_id) {
        $this->customers_id = $customers_id;
        
        $this->customers = \common\models\Customers::find()->where(['customers_id' => $customers_id])->asArray()->one();
        if (isset($this->customers['customers_id'])) {
            unset($this->customers['customers_id']);
        }
        
        $this->customers_info = \common\models\CustomersInfo::find()->where(['customers_info_id' => $customers_id])->asArray()->one();
        if (isset($this->customers['customers_info_id'])) {
            unset($this->customers['customers_info_id']);
        }
        
        $this->address_book = \common\models\AddressBook::find()->where(['customers_id' => $customers_id])->orderBy('address_book_id')->asArray()->all();
        if(is_array($this->address_book)) {
            foreach ($this->address_book as $index => $value) {
                if (isset($this->address_book[$index]['customers_id'])) {
                    unset($this->address_book[$index]['customers_id']);
                }
            }
        }
        
    }
    
    public function create() {
        $this->customers_id = 0;
        $this->save();
    }
    
    public function save() {
        $customers_id = $this->customers_id;
        
        if ($customers_id > 0) {//update
            $customers = \common\models\Customers::find()->where(['customers_id' => $customers_id])->one();
        } else {
            $customers = new \common\models\Customers();
        }
        
        if (!is_object($customers)) {
            return false;
        }
        
        if (is_array($this->customers)) {
            foreach ($this->customers as $key => $value) {
                $customers->{$key} = $value;
            }
        }
        $customers->save();//$customers->customers_id
        
        if (is_array($this->customers_info)) {
            if ($customers_id > 0) {
                $customersInfo = \common\models\CustomersInfo::find()->where(['customers_info_id' => $customers_id])->one();
            } else {
                $customersInfo = new \common\models\CustomersInfo();
            }
            $customersInfo->customers_info_id = $customers->customers_id;
            foreach ($this->customers_info as $key => $value) {
                $customersInfo->{$key} = $value;
            }
            $customersInfo->save();
        }
        
        if (is_array($this->address_book)) {
            foreach ($this->address_book as $item) {
                if ($customers_id > 0) {
                    $addressBook = \common\models\AddressBook::find()->where(['address_book_id' => $item['address_book_id']])->all();
                } else {
                    if (isset($item['address_book_id'])) {
                        unset($item['address_book_id']);
                    }
                    $addressBook = new \common\models\AddressBook();
                    $addressBook->customers_id = $customers->customers_id;
                }
                foreach ($item as $key => $value) {
                    $addressBook->{$key} = $value;
                }
                $addressBook->save();
            }
        }
        
        $this->customers_id = $customers->customers_id;
    }
}

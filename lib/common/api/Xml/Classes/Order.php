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

class Order
{
    public $orders_id;
    
    public $orders;
    public $orders_products;
    public $orders_history;
    public $orders_status_history;
    public $orders_total;
    public $orders_transactions;
    public $orders_payment;
    
    public function load($orders_id) {
        $this->orders_id = $orders_id;
        
        $this->orders = \common\models\Orders::find()->where(['orders_id' => $orders_id])->asArray()->one();
        if (isset($this->orders['orders_id'])) {
            unset($this->orders['orders_id']);
        }
        
        $this->orders_history = \common\models\OrdersHistory::find()->where(['orders_id' => $orders_id])->orderBy('orders_history_id')->asArray()->all();
        if(is_array($this->orders_history)) {
            foreach ($this->orders_history as $index => $value) {
                /*if (isset($this->orders_history[$index]['orders_history_id'])) {
                    unset($this->orders_history[$index]['orders_history_id']);
                }*/
                if (isset($this->orders_history[$index]['orders_id'])) {
                    unset($this->orders_history[$index]['orders_id']);
                }
            }
        }
        
        $this->orders_status_history = \common\models\OrdersStatusHistory::find()->where(['orders_id' => $orders_id])->orderBy('orders_status_history_id')->asArray()->all();
        if(is_array($this->orders_status_history)) {
            foreach ($this->orders_status_history as $index => $value) {
                /*if (isset($this->orders_status_history[$index]['orders_status_history_id'])) {
                    unset($this->orders_status_history[$index]['orders_status_history_id']);
                }*/
                if (isset($this->orders_status_history[$index]['orders_id'])) {
                    unset($this->orders_status_history[$index]['orders_id']);
                }
            }
        }
        
        $this->orders_total = \common\models\OrdersTotal::find()->where(['orders_id' => $orders_id])->orderBy('sort_order')->asArray()->all();
        if(is_array($this->orders_total)) {
            foreach ($this->orders_total as $index => $value) {
                /*if (isset($this->orders_total[$index]['orders_total_id'])) {
                    unset($this->orders_total[$index]['orders_total_id']);
                }*/
                if (isset($this->orders_total[$index]['orders_id'])) {
                    unset($this->orders_total[$index]['orders_id']);
                }
            }
        }
        
        $this->orders_transactions = \common\models\OrdersTransactions::find()->where(['orders_id' => $orders_id])->orderBy('orders_transactions_id')->asArray()->all();
        if(is_array($this->orders_transactions)) {
            foreach ($this->orders_transactions as $index => $value) {
                if (isset($this->orders_transactions[$index]['orders_id'])) {
                    unset($this->orders_transactions[$index]['orders_id']);
                }
            }
        }
        
        $this->orders_payment = \common\models\OrdersPayment::find()->where(['orders_payment_order_id' => $orders_id])->orderBy('orders_payment_id')->asArray()->all();
        if(is_array($this->orders_payment)) {
            foreach ($this->orders_payment as $index => $value) {
                if (isset($this->orders_payment[$index]['orders_payment_order_id'])) {
                    unset($this->orders_payment[$index]['orders_payment_order_id']);
                }
            }
        }
        
        $orders_products = \common\models\OrdersProducts::find()->where(['orders_id' => $orders_id])->orderBy('sort_order')->asArray()->all();
        if(is_array($orders_products)) {
            foreach ($orders_products as $value) {
                $orders_products_id = $value['orders_products_id'];
                unset($value['orders_id']);
                //unset($value['orders_products_id']);
                $this->orders_products[$orders_products_id] = $value;
            }
        }
        
        $orders_products_allocate = \common\models\OrdersProductsAllocate::find()->where(['orders_id' => $orders_id])->orderBy('orders_products_id')->asArray()->all();
        if(is_array($orders_products_allocate)) {
            foreach ($orders_products_allocate as $value) {
                $orders_products_id = $value['orders_products_id'];
                unset($value['orders_id']);
                unset($value['orders_products_id']);
                $this->orders_products[$orders_products_id]['orders_products_allocate'][] = $value;
            }
        }
        
        $orders_products_attributes = \common\models\OrdersProductsAttributes::find()->where(['orders_id' => $orders_id])->orderBy('orders_products_id')->asArray()->all();
        if(is_array($orders_products_attributes)) {
            foreach ($orders_products_attributes as $value) {
                $orders_products_id = $value['orders_products_id'];
                unset($value['orders_id']);
                unset($value['orders_products_id']);
                $this->orders_products[$orders_products_id]['orders_products_attributes'][] = $value;
            }
        }
        
        $orders_products_download = \common\models\OrdersProductsDownload::find()->where(['orders_id' => $orders_id])->orderBy('orders_products_id')->asArray()->all();
        if(is_array($orders_products_download)) {
            foreach ($orders_products_download as $value) {
                $orders_products_id = $value['orders_products_id'];
                unset($value['orders_id']);
                unset($value['orders_products_id']);
                $this->orders_products[$orders_products_id]['orders_products_download'][] = $value;
            }
        }
        
        $orders_products_status_history = \common\models\OrdersProductsStatusHistory::find()->where(['orders_id' => $orders_id])->orderBy('orders_products_id')->asArray()->all();
        if(is_array($orders_products_status_history)) {
            foreach ($orders_products_status_history as $value) {
                $orders_products_id = $value['orders_products_id'];
                unset($value['orders_id']);
                unset($value['orders_products_id']);
                unset($value['orders_products_history_id']);
                $this->orders_products[$orders_products_id]['orders_products_status_history'][] = $value;
            }
        }
    }
    
    public function create() {
        $this->orders_id = 0;
        $this->save();
    }
    
    public function save() {
        /*echo "<pre>";
        print_r($this);
        echo "</pre>";
        die();*/
        $orders_id = $this->orders_id;
        
        if ($orders_id > 0) {//update
            $orders = \common\models\Orders::find()->where(['orders_id' => $orders_id])->one();
        } else {
            $orders = new \common\models\Orders();
        }
        
        if (!is_object($orders)) {
            return false;
        }
        
        if (is_array($this->orders)) {
            foreach ($this->orders as $key => $value) {
                $orders->{$key} = $value;
            }
        }
        $orders->save();//$orders->orders_id
        
        if (is_array($this->orders_history)) {
            foreach ($this->orders_history as $item) {
                if ($orders_id > 0) {
                    $orders_history = \common\models\OrdersHistory::find()->where(['orders_history_id' => $item['orders_history_id']])->all();
                } else {
                    if (isset($item['orders_history_id'])) {
                        unset($item['orders_history_id']);
                    }
                    $orders_history = new \common\models\OrdersHistory();
                    $orders_history->orders_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_history->{$key} = $value;
                }
                $orders_history->save();
            }
        }
        
        if (is_array($this->orders_status_history)) {
            foreach ($this->orders_status_history as $item) {
                if ($orders_id > 0) {
                    $orders_status_history = \common\models\OrdersStatusHistory::find()->where(['orders_status_history_id' => $item['orders_status_history_id']])->all();
                } else {
                    if (isset($item['orders_status_history_id'])) {
                        unset($item['orders_status_history_id']);
                    }
                    $orders_status_history = new \common\models\OrdersStatusHistory();
                    $orders_status_history->orders_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_status_history->{$key} = $value;
                }
                $orders_status_history->save();
            }
        }
        
        if (is_array($this->orders_total)) {
            foreach ($this->orders_total as $item) {
                if ($orders_id > 0) {
                    $orders_total = \common\models\OrdersTotal::find()->where(['orders_total_id' => $item['orders_total_id']])->all();
                } else {
                    if (isset($item['orders_total_id'])) {
                        unset($item['orders_total_id']);
                    }
                    $orders_total = new \common\models\OrdersTotal();
                    $orders_total->orders_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_total->{$key} = $value;
                }
                $orders_total->save();
            }
        }
        
        if (is_array($this->orders_transactions)) {
            foreach ($this->orders_transactions as $item) {
                if ($orders_id > 0) {
                    $orders_transactions = \common\models\OrdersTransactions::find()->where(['orders_transactions_id' => $item['orders_transactions_id']])->all();
                } else {
                    if (isset($item['orders_transactions_id'])) {
                        unset($item['orders_transactions_id']);
                    }
                    $orders_transactions = new \common\models\OrdersTransactions();
                    $orders_transactions->orders_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_transactions->{$key} = $value;
                }
                $orders_transactions->save();
            }
        }
        
        if (is_array($this->orders_payment)) {
            foreach ($this->orders_payment as $item) {
                if ($orders_id > 0) {
                    $orders_payment = \common\models\OrdersTransactions::find()->where(['orders_payment_id' => $item['orders_payment_id']])->all();
                } else {
                    if (isset($item['orders_payment_id'])) {
                        unset($item['orders_payment_id']);
                    }
                    $orders_payment = new \common\models\OrdersPayment();
                    $orders_payment->orders_payment_order_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_payment->{$key} = $value;
                }
                $orders_payment->save();
            }
        }
        
        if (is_array($this->orders_products)) {
            foreach ($this->orders_products as $item) {
                
                if (isset($item['orders_products_allocate'])) {
                    $orders_products_allocate_data = $item['orders_products_allocate'];
                    unset($item['orders_products_allocate']);
                } else {
                    $orders_products_allocate_data = false;
                }
                if (isset($item['orders_products_attributes'])) {
                    $orders_products_attributes_data = $item['orders_products_attributes'];
                    unset($item['orders_products_attributes']);
                } else {
                    $orders_products_attributes_data = false;
                }
                if (isset($item['orders_products_download'])) {
                    $orders_products_download_data = $item['orders_products_download'];
                    unset($item['orders_products_download']);
                } else {
                    $orders_products_download_data = false;
                }
                if (isset($item['orders_products_status_history'])) {
                    $orders_products_status_history_data = $item['orders_products_status_history'];
                    unset($item['orders_products_status_history']);
                } else {
                    $orders_products_status_history_data = false;
                }
                
                if ($orders_id > 0) {
                    $orders_products = \common\models\OrdersProducts::find()->where(['orders_products_id' => $item['orders_products_id']])->all();
                } else {
                    if (isset($item['orders_products_id'])) {
                        unset($item['orders_products_id']);
                    }
                    $orders_products = new \common\models\OrdersProducts();
                    $orders_products->orders_id = $orders->orders_id;
                }
                foreach ($item as $key => $value) {
                    $orders_products->{$key} = $value;
                }
                $orders_products->save();//$orders_products->orders_products_id
                
                if (is_array($orders_products_attributes_data)) {
                    foreach ($orders_products_attributes_data as $data) {
                        
                        if ($orders_id > 0) {
                            $orders_products_attributes = \common\models\OrdersProductsAttributes::find()->where(['orders_products_attributes_id' => $data['orders_products_attributes_id']])->all();
                        } else {
                            if (isset($data['orders_products_attributes_id'])) {
                                unset($data['orders_products_attributes_id']);
                            }
                            $orders_products_attributes = new \common\models\OrdersProductsAttributes();
                            $orders_products_attributes->orders_id = $orders->orders_id;
                            $orders_products_attributes->orders_products_id = $orders_products->orders_products_id;
                        }
                        foreach ($data as $key => $value) {
                            $orders_products_attributes->{$key} = $value;
                        }
                        $orders_products_attributes->save();

                    }
                }
                
                
                
            }
        }
        
        $this->orders_id = $orders->orders_id;
    }
}

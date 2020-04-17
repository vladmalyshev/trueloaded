<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Ga extends ActiveRecord {

    public static function tableName() {
        return 'ga';
    }
    
    public function rules(){
        return [
            ['customers_id', 'required'],
            [['orders_id', 'basket_id', ], 'safe', 'skipOnEmpty' => true, ],
            [['utmgclid', 'utmccn', 'utmcmd', 'utmctr', 'utmcsr', 'ip_address', 'last_page_url', 'http_referer', 'user_agent', 'resolution'], 'loadPrevious', 'isEmpty' => function($attribute){ }]
        ];
    }
    
    public function loadPrevious($attribute, $params){
        if (empty($this->$attribute) && !empty($this->oldAttributes[$attribute])){
            $this->$attribute = $this->oldAttributes[$attribute];
        }
    }
    /**
     * 
     * @param type $customer_id
     * @param type $order_id
     * @param type $basket_id
     * @param string $agent
     * @param string $last_page_url
     * @param string $resolution
     * @param string $http_referer
     * @param array $utm
     */
    public static function create($customer_id, $order_id = 0, $basket_id = 0, $agent, $last_page_url, $resolution, $http_referer, array $utm){
        $ga = new self();
        $ga->customers_id = (int)$customer_id;
        $ga->orders_id = (int)$order_id;
        $ga->basket_id = (int)$basket_id;
        $ga->user_agent = strval($agent);
        $ga->last_page_url = strval($last_page_url);
        $ga->resolution = strval($resolution);
        $ga->http_referer = strval($http_referer);
        $ga->ip_address = \common\helpers\System::get_ip_address();
        if (is_array($utm)){
            $ga->setAttributes($utm);
        }
        $ga->validate() && $ga->save();        
    }
}

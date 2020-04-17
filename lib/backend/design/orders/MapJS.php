<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\orders;


use Yii;
use yii\base\Widget;

class MapJS extends Widget {
    
    public $addresses = [];
    public $order;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        $adds = [];
        if (is_array($this->addresses)){
            $zoom = 8;
            
            $aWarehouse = null;

            [$class, $method] = explode('_', $this->order->info['shipping_class']);
            /** @var \common\classes\modules\ModuleShipping $shipping */
            $shipping = $this->order->manager->getShippingCollection()->get($class);
            if (is_object($shipping)) {
                $collect = $shipping->toCollect($method);
                if ($collect !== false) {
                    /** @var \common\classes\VO\CollectAddress $aWarehouse */
                    $aWarehouse = $collect;
                }
            }
            foreach($this->addresses as $_address){
                $address = $_address['address'];
                if(isset($address['country']['zoom'])){
                    $zoom = max((int) $address['country']['zoom'], 8);
                }
                $adds[] = [
                    'add1' => ($aWarehouse !== null
                        ? trim(sprintf(
                            '%s, %s, %s',
                            $aWarehouse->getStreetAddress(),
                            $aWarehouse->getCity(),
                            $aWarehouse->getCountryName()
                        ))
                        : $address['street_address'] . ' ' . $address['city'] . ' ' . ($address['country']['title'] ?? '')),
                    'add2' => $aWarehouse !== null ? $aWarehouse->getPostcode() :$address['postcode'],
                    'marker' => $_address['marker'],
                    'zoom' => $zoom
                ];
            }
            return $this->render('map-js', [
                'key' => \common\components\GoogleTools::instance()->getMapProvider()->getMapsKey(),
                'adds' => $adds,
            ]);
        }
    }
}

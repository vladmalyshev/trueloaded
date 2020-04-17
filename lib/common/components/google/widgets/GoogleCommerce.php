<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\widgets;

use common\components\GoogleTools;

class GoogleCommerce extends \yii\base\Widget
{
    public $order;
    public $_gaq = ['_addTrans' => [], '_addItem' => [], '_trackTrans' => [], 'userId' => []];
    public $ga = ['ec:addProduct' => [], 'ec:setAction' => [], 'userId' => [], /*, 'ecommerce:send' => []*/];
    public $gtm = ['actionField' => [], 'products' => [], 'userId' => []];
    public $used_module = 'analytics';
    
    public function init()
    {
        parent::init();
    }
    
    public function prepareData(){
        $provider = (new GoogleTools)->getModulesProvider();
        $installed_modules = $provider->getInstalledModules($this->order->info['platform_id']);

        if (array_key_exists('tagmanger', $installed_modules)){
            $this->used_module = 'tagmanger';
        }
        $_tax = $_total = $_shipping = $_coupon = 0;
        foreach($this->order->totals as $totals){
          if ($totals['class'] == 'ot_total')
          {
            $_total = number_format($totals['value_inc_tax'], 2, ".", "");
          }
          else if ($totals['class'] == 'ot_tax') 
          {
            $_tax = number_format($totals['value'], 2, ".", "");
          }
          else if ($totals['class'] == 'ot_shipping') 
          {
            $_shipping = number_format($totals['value_exc_vat'], 2, ".", "");
          }
          else if ($totals['class'] == 'ot_coupon') 
          {
              $ex = explode(":", $totals['text']);
              if (isset($ex[1])){
                  $_coupon = trim($ex[1]);
              }        
          }
        }
        if ($this->used_module == 'analytics'){
            $this->_gaq['_addTrans'] = [
                        $this->order->info['order_id'],
                        \common\classes\platform::name($this->order->info['platform_id']),
                        $_total,
                        $_tax,
                        $_shipping,
                        $this->order->customer['city'],
                        $this->order->customer['state'],
                        $this->order->customer['country']['iso_code_3'],
                      ];
            
            $this->ga['ec:setAction'] = [
                        'id' => $this->order->info['order_id'],
                        'affiliation' => \common\classes\platform::name($this->order->info['platform_id']),
                        'revenue' => $_total,
                        'shipping'  => $_shipping,
                        'tax' => $_tax,
                        'coupon' => ($_coupon ? $_coupon : ''),
                     ];
            $this->ga['userId'] = [$this->order->customer['id']];
        } else { //tagmanager
            $this->gtm['actionField'] = [
                    'id' => $this->order->info['order_id'],
                    'affiliation' => \common\classes\platform::name($this->order->info['platform_id']),
                    'revenue' => $_total,
                    'tax' => $_tax,
                    'shipping' => $_shipping,
                    'coupon' => ($_coupon ? $_coupon : ''),
            ];
            $this->gtm['userId'] = [$this->order->customer['id']];
        }


        if (is_array($this->order->products)  && sizeof($this->order->products)){
          foreach($this->order->products as $item){

            $p2cModel = \common\models\Products2Categories::findOne(['products_id' => (int)$item['id']]);
            $category_name = $p2cModel ? str_replace('"', '\"', \common\helpers\Categories::get_categories_name($p2cModel->categories_id)) : '';
            if ($this->used_module == 'analytics'){
                $this->_gaq['_addItem'][] = [
                                   $this->order->order_id,
                                   $item['model'],
                                   str_replace('"', '\"', $item['name']),
                                   $category_name,
                                   number_format($item['final_price'], 2, ".", ""),
                                   $item['qty']
                                      ];
               $this->ga['ec:addProduct'][] = [
                                   'id' => $this->order->info['order_id'],
                                   'name' => str_replace('"', '\"', $item['name']),
                                   'sku' => $item['model'],
                                   'category' => $category_name,
                                   'price' => number_format($item['final_price'], 2, ".", ""),
                                   'quantity' => $item['qty']
                                      ];
            } else { //tagmanager
                $manufacturers_id = \common\helpers\Product::get_products_info((int)$item['id'], 'manufacturers_id');
                $brand = \common\helpers\Manufacturers::get_manufacturer_info('manufacturers_name', $manufacturers_id);
                $attributes = "";
                if (is_array($item['attributes']) && count($item['attributes'])){
                    $map = [
                        'options' => \yii\helpers\ArrayHelper::getColumn($item['attributes'], 'option'),
                        'values' => \yii\helpers\ArrayHelper::getColumn($item['attributes'], 'value'),
                    ];
                    foreach($map['options'] as $key => $value){
                        $attributes .= $value . ": " . $map['values'][$key]. ", ";
                    }
                    if (strlen($attributes) > 0){
                        $attributes = substr($attributes, 0, -2);
                    }
                }
                $this->gtm['products'][] = [
                    'name' => str_replace('"', '\"', $item['name']),
                    'id' => intval($item['id']),//product identified, seo assert it should be like id in google feed
                    'price' => number_format($item['final_price'], 2, ".", ""),
                    'brand' => ($brand ? $brand : ''),
                    'category' => $category_name,
                    'variant' => $attributes,
                    'quantity' => $item['qty'],
                    'coupon' => '',
                ];
            }

          }

        }
    }

    public function run(){
        $this->prepareData();
   
        return $this->renderJs();
    }
  
  public function renderJs(){
    ob_start();
    //
    if ($this->used_module == 'analytics'){
    ?>
    <script>
    tl(function(){
      var type = '';
    
      window.onload = function(){
        if (typeof ga != 'undefined' && typeof ga.P == 'object'){
          //check id
          var _tracker = ga.getByName('t0');
          var _account = _tracker.b.get('trackingId');
          if ( _tracker.b.data.values.hasOwnProperty(':trackingId') && _account.length > 0 && _account.indexOf('UA') > -1){
            type = 'ga';
          } 
          
        } else if (typeof _gaq != 'undefined'){
          var _tracker = _gaq._getAsyncTracker();
          var _account = _tracker._getAccount();
          if ( _account.length > 0 && _account.indexOf('UA') > -1){
            type = '_gaq';
          }
        } else { //notifie admin to set up analytics
          $.post('checkout/notify-admin', {
            'type': 'need_analytics',
          }, function(data, status){
            
          });
        }

        if (type == 'ga'){
          ga('require', 'ec');
          
          <?php 
            foreach($this->ga as $key => $item){
              if ($key == 'userId') continue;
              if (!count(array_filter($item, 'is_array'))){
                echo 'ga(\'' . $key . '\', \'purchase\' , ' . json_encode($item) . ');'."\r\n";
              } else {
                foreach($item as $item1){
                  echo 'ga(\'' . $key . '\', ' . json_encode($item1) . ');'."\r\n";
                }
              }
            }
            if (!empty($this->ga['userId'])){ echo "ga('create', _account, { 'userId': '".$this->ga['userId'][0]."' });"; }
          ?>
          ga('send', 'event', 'UX', 'purchase', 'checkout success');
          localStorage.removeItem('ga_cookie');
        } else if (type == '_gaq'){
          <?php 
            foreach($this->_gaq as $key => $item){
              if (!count(array_filter($item, 'is_array'))){
                if (count($item) > 0){
                  echo '_gaq.push([\'' . $key . '\', "' . implode('", "', $item) . '"]);'."\r\n";
                } else {
                  echo '_gaq.push([\'' . $key . '\']);'."\r\n";
                }
              } else {
                foreach($item as $item1){
                   echo '_gaq.push([\'' . $key . '\', "' . implode('", "', $item1) . '"]);'."\r\n";
                }
              }
              
            }
          ?>
        }
      }
    });
    </script>
    <?php
    } else {        
        ?>    
    <script>
        /*tl(function(){*/
            if (typeof dataLayer == 'object'){
                dataLayer.push({
                    'ecommerce': {
                        'currencyCode': '<?=$this->order->info['currency']?>',
                        'purchase' : 
                            <?php
                            echo json_encode($this->gtm);
                            ?>
                    }
                });
                dataLayer.push({'event': 'gtm.dom'});
                dataLayer.push({'userId': '<?=$this->gtm['userId'][0]?>'});
            }
       /* });*/
      </script>
      <?php
    }
    $buf = ob_get_contents();
    ob_clean();
    return $buf;
  }

}

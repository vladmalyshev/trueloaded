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

class PaymentExtraInfo extends Widget {
    
    public $manager;
    public $order;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        $extra = '';
        $info = $this->order->info;
        
        if(isset($info['payment_class']) && $info['payment_class'] == 'cardpos' && !empty($info['card_reference_id'])){
            $extra .= '<br /><span>' . TEXT_ID_REFERENCE . '</span><pre>' . $info['card_reference_id'] . '</pre>';
        }
        if(isset($info['payment_class']) && $info['payment_class']=='cash' && ($info['cash_data_summ'] > 0 || $info['cash_data_change'] > 0 )){
            $extra .= '<br /><span>' . TEXT_CHANGE . ': ' . $info['cash_data_change'] . '</span>';
            $extra .= '<span>' . TEXT_OUT_OF . ': ' . $info['cash_data_summ'] . '</span>';
        }
                        
        return $extra;
    }
}

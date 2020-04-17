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
use common\helpers\Acl;

class SMS extends Widget {
    
    public $order;
    public $manager;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
       if (Acl::checkExtensionAllowed('SMS','showOnOrderPage') && $sms = Acl::checkExtension('SMS', 'viewOrder')){
            $smsBlock = $sms::viewOrder($this->order);
            if ($smsBlock){
                echo $smsBlock;
            }
        }
    }
}

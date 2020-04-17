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

class ExtraCustomData extends Widget {
    
    public $order;
    public $manager;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        
        $showExtra = false;
        
        if ($rf = Acl::checkExtension('ReferFriend', 'allowed')){
            $rfBlock = $rf::getAdminOrderView($order->order_id);
            if ($rfBlock){
                $showExtra = true;
            }
        }
        
        return $this->render('extra-custom-data', [
            'manager' => $this->manager,
            'order' => $this->order,
            'showExtra' => $showExtra,
            'rfBlock' => $rfBlock
        ]);
    }
}

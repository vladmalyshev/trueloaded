<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\editor;

use Yii;
use yii\base\Widget;

class DeleteOrder extends Widget {
    
    public $manager;    
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        if($this->manager->isInstance() && $this->manager->getCart()->order_id){            
            return $this->render('delete-order', [
                'url' => Yii::$app->urlManager->createAbsoluteUrl(array_merge(['editor/checkout', 'action'=>'show_delete'], Yii::$app->request->getQueryParams())),
                
            ]);
        }
    }

}

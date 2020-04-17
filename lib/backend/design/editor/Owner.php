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

class Owner extends Widget {
    
    public $manager;
    public $name;
    public $currentCurrent;
    public $cancel;
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        return $this->render('owner',[
            'manager' => $this->manager,
            'currentCurrent' => $this->currentCurrent,
            'name' => $this->name,
            'cancel' => $this->cancel
        ]);
    }
}

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

class Toolbar extends Widget {
    
    public $expanded = true;
    public $visible = true;
            
    public function init(){
        parent::init();        
    }
    
    public function run(){
        return $this->render('toolbar',[ 'expanded' => $this->expanded, 'visible' => $this->visible]);
    }
}

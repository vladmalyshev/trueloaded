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

class Address extends Widget {
    
    public $manager;
    public $model;
    public $holder;
        
    public function init(){
        parent::init();
    }
    
    public function run(){

        return $this->render('address-area',[
            'model' => $this->model,
            'holder' => $this->holder,
            'postcoder' => new \common\modules\postcode\PostcodeTool(),
        ]);
    }
}

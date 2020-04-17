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

class CustomerAssign extends Widget {
    
    public $manager;
    public $hide;
        
    public function init(){
        parent::init();
    }
    
    public function run(){
        return $this->render('customer-assign',[
            'hide' => $this->hide,
            'queryParams' => array_merge(['editor/create-account'], Yii::$app->request->getQueryParams()),
        ]);
    }
}

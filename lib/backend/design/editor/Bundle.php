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
use common\helpers\Acl;

class Bundle extends Widget {
    
    public $products;
    public $manager;
    
    public function init(){
        parent::init();
    }    
    
    public function run(){
        return $this->render('bundle', [
            'products' => $this->products,
            'manager' => $this->manager,
            'tax_address' => $this->manager->getOrderInstance()->tax_address,
            'tax_class_array' => \common\helpers\Tax::get_complex_classes_list(),
        ]);
    }
    
}

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

class ProductAssets extends Widget {
    
    public $product;
    public $manager;
            
    public function init(){
        parent::init();
    }
    
    public function run(){
        if ($ext = \common\helpers\Acl::checkExtensionAllowed('ProductAssets' ,'allowed')){
            if ($this->manager->isInstance()){
                return $ext::getOrderedProductsAssets($this->manager->getOrderInstance(), $this->product['id']);
            }
        }
    }
}

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

class ProductsListing extends Widget {
    
    public $manager;    
    
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        $response = \common\helpers\Gifts::getGiveAwaysQuery();
        $giveaway_count = $response['giveaway_query']->count();
        
        return $this->render('product-listing', [
            'manager' => $this->manager,
            'giftWrapExist' => $this->manager->getCart()->cart_allow_giftwrap(),
            'queryParams' => array_merge(['editor/show-basket'], Yii::$app->request->getQueryParams()),
            'giveaway_count' => $giveaway_count,
        ]);
    }
    
}

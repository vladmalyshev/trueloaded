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

class ExtraCharge extends Widget {
    
    public $manager;
    public $product;
    
    public function init(){
        parent::init();
        if (!$this->product) throw new \Exception('Product is not defined');
    }
    
    public function run(){
        
        $predefined = null;
        if (isset($this->product['overwritten']) && !empty($this->product['overwritten'])){
            $predefined = $this->product['overwritten']['final_price_formula_data'][1]['vars'];
        }
        
        if (!$predefined){
            $predefined = [
                'percent_action' => '-',
                'percent_value' => 0,
                'fixed_action' => '-',
                'fixed_value' => 0,
            ];
        }
        
        return $this->render('extra-charge', [
            'product' => $this->product,
            'manager' => $this->manager,
            'predefined' => $predefined,
        ]);
    }
    
}

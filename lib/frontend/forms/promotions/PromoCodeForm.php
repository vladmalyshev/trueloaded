<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\forms\promotions;
 
use Yii;
use yii\base\Model;

class PromoCodeForm extends Model {
    
    public $promo_code;
    public $promo_action;
    
    public function rules() {
        return [
            [['promo_action', 'promo_code'], 'required'],
            ['promo_action', 'validPromoAction']
        ];
    }
    
    public function validPromoAction($attribute, $params, $validator){
        if ($this->promo_action != 'apply-promo-code') {
            $this->addError($attribute, 'error');
        }
    }
    
}
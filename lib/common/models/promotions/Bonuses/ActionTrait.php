<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions\Bonuses;
use Yii;

trait ActionTrait {
    
    public function getDefaultActionTitle(){ 
        return static::TITLE;
    }
    
    public function getDefaultActionAward(){
        return static::BONUS_POINTS_AWARD;
    }
    
    public function getDefaultActionLimit(){
        return static::BONUS_POINTS_LIMIT;
    }
    
}
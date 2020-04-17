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

class Comment extends \common\models\promotions\PromotionsBonusPoints {
    
    use ActionTrait;
    
    CONST TITLE = 'Commenting on A Blog Post/ Product Review';
    
    CONST BONUS_POINTS_AWARD = 10;
    
    CONST BONUS_POINTS_LIMIT = 5;
}
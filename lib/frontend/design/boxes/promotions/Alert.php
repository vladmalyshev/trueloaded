<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\promotions;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\models\promotions\PromotionsBonusNotify;

class Alert extends Widget {
    
    public $file;
    public $params;
    public $settings;
    public $isAjax;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if(defined('BONUS_ACTION_PROGRAM_STATUS') && BONUS_ACTION_PROGRAM_STATUS == 'true'){
            
            $message = PromotionsBonusNotify::getNotification();

            if ($message){
                $content = IncludeTpl::widget(['file' => 'boxes/promotions/alert.tpl', 'params' => [
                        'message' => $message,
                        'popup' => true
                    ]]);
                if ($this->isAjax){
                    echo $content;
                    exit();
                } else {
                    return $content;
                }
            }
        }
    }
    
}
<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\models\promotions\PromotionsBonusService;

class BonusProgramme extends Widget
{

  public $file;
  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  { 
    if (defined('BONUS_ACTION_PROGRAM_STATUS') && BONUS_ACTION_PROGRAM_STATUS == 'true'){
        $bonusesService = new PromotionsBonusService(true);
        $groups = $bonusesService->getAllGroups();

        return IncludeTpl::widget(['file' => 'boxes/bonus-programme.tpl', 'params' => [
          'groups' => $groups,
        ]]);
    }
    
  }
}
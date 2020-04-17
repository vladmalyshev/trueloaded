<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\email;

use Yii;
use yii\base\Widget;

class Date extends Widget
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
      \common\helpers\Translation::init('js');

      $monthNames = [ DATEPICKER_MONTH_JAN, DATEPICKER_MONTH_FEB, DATEPICKER_MONTH_MAR, DATEPICKER_MONTH_APR, DATEPICKER_MONTH_MAY, DATEPICKER_MONTH_JUN, DATEPICKER_MONTH_JUL, DATEPICKER_MONTH_AUG, DATEPICKER_MONTH_SEP, DATEPICKER_MONTH_OCT, DATEPICKER_MONTH_NOV, DATEPICKER_MONTH_DEC ];
      //$monthNames = [TEXT_JAN, TEXT_FAB, TEXT_MAR, TEXT_APR, TEXT_MAY, TEXT_JUN, TEXT_JUL, TEXT_AUG, TEXT_SEP, TEXT_OCT, TEXT_NOV, TEXT_DEC];
          
      return strftime("%e") . ' ' . $monthNames[date("n")-1] . ' ' . strftime("%Y");
  }
}
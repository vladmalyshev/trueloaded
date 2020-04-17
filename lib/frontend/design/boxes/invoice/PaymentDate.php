<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\invoice;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class PaymentDate extends Widget
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
    if (!empty($this->settings[0]['custom_format'])) {
      $ret = \common\helpers\Date::date_long($this->params["order"]->info['date_purchased'], $this->settings[0]['custom_format']);
    } else {
      $ret = \common\helpers\Date::date_long($this->params["order"]->info['date_purchased']);
    }
    return $ret;
  }
}
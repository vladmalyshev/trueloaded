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
use frontend\design\Info;

class StorePhone extends Widget
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
    if ($this->settings[0]['pdf']){
      $data = Info::platformData($this->params['platform_id']);
    } else {
      $data = Info::platformData();
    }
    return $data['telephone'];
  }
}
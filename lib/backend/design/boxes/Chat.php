<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes;

use Yii;
use yii\base\Widget;

class Chat extends Widget
{

  public $id;
  public $params;
  public $settings;
  public $visibility;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    
    return $this->render('chat.tpl', [
      'id' => $this->id,
      'params' => $this->params,
      'settings' => $this->settings,
      'languages_id' => $languages_id,
      'visibility' => $this->visibility,
    ]);
  }
}
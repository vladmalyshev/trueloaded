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
use frontend\design\IncludeTpl;

class Logo extends Widget
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
    $languages_id = \Yii::$app->settings->get('languages_id');

    $image = \frontend\design\Info::themeImage($this->settings[$languages_id]['logo'],
      [$this->settings[\common\classes\language::defaultId()]['logo'], $this->settings[0]['params']]);

    if ($this->settings[0]['pdf']){

      return '<img src="' . tep_catalog_href_link('images/' . $image) . '">';

    } else {

      return IncludeTpl::widget(['file' => 'boxes/email/image.tpl', 'params' => [
        'image' => BASE_URL . $image,
        'url' => tep_href_link('/'),
      ]]);

    }

  }
}
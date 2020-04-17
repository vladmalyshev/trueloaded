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

class Currencies extends Widget
{

  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    global $request_type;
    $currencies = \Yii::$container->get('currencies');
    $currencies_array = array();
    if (is_array($currencies->currencies)) foreach ($currencies->currencies as $key => $value) {
      $value['key'] = $key;
      if (!in_array($key, $currencies->platform_currencies)) continue;
      if (Yii::$app->controller->id . '/' . Yii::$app->controller->action->id == 'index/index') {
        $value['link'] = tep_href_link('/', \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'currency=' . $key, $request_type);
      } else {
        $value['link'] = tep_href_link(Yii::$app->controller->id . (Yii::$app->controller->action->id != 'index' ? '/' . Yii::$app->controller->action->id : ''), \common\helpers\Output::get_all_get_params(array('language', 'currency')) . 'currency=' . $key, $request_type);
      }
      $currencies_array[] = $value;
    }

    return IncludeTpl::widget(['file' => 'boxes/currencies.tpl', 'params' => ['currencies' => $currencies_array, 'currency_id' => (int)\Yii::$app->settings->get('currency_id')]]);
  }
}
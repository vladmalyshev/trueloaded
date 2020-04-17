<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\info;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Content extends Widget
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

    if(!$_GET['info_id']) return '';

    $info_id = (int)$_GET['info_id'];

    $sql = tep_db_query("select if(length(i1.description), i1.description, i.description) as description from " . TABLE_INFORMATION . " i LEFT JOIN " . TABLE_INFORMATION . " i1 on i.information_id = i1.information_id  and i1.languages_id = '" . (int)$languages_id . "' AND i1.platform_id='".\common\classes\platform::currentId()."' and i1.affiliate_id = '" . (int)$_SESSION['affiliate_ref'] . "'  where i.information_id = '" . (int)$info_id . "' and i.languages_id = '" . (int)$languages_id . "' and i.visible = 1 and i.affiliate_id = 0 AND i.platform_id='".\common\classes\platform::currentId()."' ");
    $row=tep_db_fetch_array($sql);

/*
in PHP:
        use frontend\design\boxes\Menu;
        Menu::widget([settings => [['params' => 'categories']]])

in content:
        <widget name="Menu" settings="{'params':'Categories'}"></widget>
*/

    $arr = explode('<widget ', $row['description']);
    $html = '';

    foreach ($arr as $item){
      if(stripos($item, '</widget>') !== false){

        $split = explode('</widget>', $item);

        preg_match("/name=\"([^\"]+)\"/", $split[0], $matches);
        $name = $matches[1];

        $settings = array();
        preg_match("/settings=\"([^\"]+)\"/", $split[0], $matches);
        $settings[0] = (array)json_decode( str_replace('\'', '"', $matches[1]));

        $widget_name = 'frontend\design\boxes\\' . $name;
        $html .= $widget_name::widget(['settings' => $settings]);

        $html .= $split[1];

      } else {
        $html .= $item;
      }
    }

      $html = \common\classes\TlUrl::replaceUrl($html);
      $html = \common\classes\PageComponents::addComponents($html);
      $html =  \frontend\design\EditData::addEditDataTeg(stripslashes($html), 'info', 'description', $info_id);

    return IncludeTpl::widget(['file' => 'boxes/info/content.tpl', 'params' => ['content' => $html ]]);
  }
}
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
use frontend\design\Info;

class Title extends Widget
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
      $full_action = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;

    if(!$_GET['info_id'] && Yii::$app->controller->id != 'contact' && $full_action!='catalog/gift-card') return '';

    $info_id = (int)$_GET['info_id'];

    $sql = tep_db_query("select if(length(i1.info_title), i1.info_title, i.info_title) as info_title, i.information_h1_tag from " . TABLE_INFORMATION . " i LEFT JOIN " . TABLE_INFORMATION . " i1 on i.information_id = i1.information_id  and i1.languages_id = '" . (int)$languages_id . "' and i1.affiliate_id = '" . (int)$_SESSION['affiliate_ref'] . "' AND i1.platform_id='".\common\classes\platform::currentId()."' where i.information_id = '" . (int)$info_id . "' and i.languages_id = '" . (int)$languages_id . "' and i.visible = 1 and i.affiliate_id = 0 AND i.platform_id='".\common\classes\platform::currentId()."' ");
    $row=tep_db_fetch_array($sql);

    if ($row['page_title'] == ''){
      $title = \frontend\design\EditData::addEditDataTeg(stripslashes($row['info_title']), 'info', 'info_title', $info_id);
    }else{
      $title =  \frontend\design\EditData::addEditDataTeg(stripslashes($row['page_title']), 'info', 'page_title', $info_id);
    }

    if (Yii::$app->controller->id == 'contact') {
        $h1 = defined('HEAD_H1_TAG_CONTACT_US') && tep_not_null(HEAD_H1_TAG_CONTACT_US) ? HEAD_H1_TAG_CONTACT_US : '';
        $title = TEXT_HEADER_CONTACT_US;
    } elseif ($full_action=='catalog/gift-card') {
        $h1 = defined('HEAD_H1_TAG_GIFT_CARD') && tep_not_null(HEAD_H1_TAG_GIFT_CARD) ? HEAD_H1_TAG_GIFT_CARD : '';
        $title = TEXT_GIFT_CARD;
    } else {
        $h1 = $row['information_h1_tag'];
        $h1 =  \frontend\design\EditData::addEditDataTeg($h1, 'info', 'information_h1_tag', $info_id);
    }
    
    return IncludeTpl::widget(['file' => 'boxes/info/title.tpl', 'params' => [
        'title' => $title,
        'h1' => $h1,
        'settings' => $this->settings,
    ]]);
  }
}
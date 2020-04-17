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
use frontend\design\Block;
use frontend\design\Info;

class BlockBox extends Widget
{

  public $settings;
  public $params;
  public $id;

  public function init()
  {
    parent::init();
  }

  public function blockWidthMultiplier($id){
    $query = tep_db_fetch_array(tep_db_query("select block_name from " . TABLE_DESIGN_BOXES . " where id='" . $id . "'"));

    if (substr($query['block_name'], 0, 5) != 'block'){
      return false;
    }

    $id_arr = explode('-', substr($query['block_name'], 6));
    if ($id_arr[1]){
      $col = $id_arr[1];
    } else {
      $col = 1;
    }
    $parent_id = $id_arr[0];
    $query = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id='" . $parent_id . "' and setting_name='block_type'"));
    $type = $query['setting_value'];

    $multiplier = 1;
    if ($type == 2 || $type == 8 && $col == 2){
      $multiplier = 0.5;
    } elseif ($type == 9 && $col == 1 || $type == 10 && $col == 2 || $type == 13 && ($col == 1 || $col == 3) || $type ==15){
      $multiplier = 0.2;
    } elseif ($type == 6 && $col == 1 || $type == 7 && $col == 2 || $type == 8 && ($col == 1 || $col == 3) || $type == 14){
      $multiplier = 0.25;
    } elseif ($type == 3 || $type == 4 && $col == 2 || $type == 5 && $col == 1){
      $multiplier = 0.3333;
    } elseif ($type == 11 && $col == 1 || $type == 12 && $col == 2){
      $multiplier = 0.4;
    } elseif ($type == 11 && $col == 2 || $type == 12 && $col == 1 || $type == 13 && $col == 2){
      $multiplier = 0.6;
    } elseif ($type == 4 && $col == 1 || $type == 5 && $col == 2){
      $multiplier = 0.6666;
    } elseif ($type == 6 && $col == 2 || $type == 7 && $col == 1){
      $multiplier = 0.75;
    } elseif ($type == 9 && $col == 2 || $type == 10 && $col == 1){
      $multiplier = 0.8;
    }

    $query = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id='" . $parent_id . "' and setting_name='padding_left'"));
    $padding = $query['setting_value'];
    $query = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id='" . $parent_id . "' and setting_name='padding_right'"));
    $padding = $padding + $query['setting_value'];

    $arr = $this->blockWidthMultiplier($parent_id);
    if (!$arr){
      $arr = array();
    }
    $arr[] = array('multiplier' => $multiplier, 'padding' => $padding);

    return $arr;
  }

  public function run()
  {
    $type = $this->settings[0]['block_type'];
    $block_id = 'block-' . $this->id;
    
    if ($_GET['to_pdf']){

      $block_1 = '';
      $block_2 = '';
      $block_3 = '';
      $block_4 = '';
      $block_5 = '';
      if ($type == 2 || $type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 9 || $type == 10 || $type == 11 || $type == 12){
        $block_1 = Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
        $block_2 = Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
      } elseif ($type == 3 || $type == 8 || $type == 13){
        $block_1 = Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
        $block_2 = Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
        $block_3 = Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
      } elseif ($type == 14){
        $block_1 = Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
        $block_2 = Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
        $block_3 = Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
        $block_4 = Block::widget(['name' => $block_id . '-4', 'params' => ['params' => $this->params, 'cols' => 4]]);
      } elseif ($type == 15){
        $block_1 = Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
        $block_2 = Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
        $block_3 = Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
        $block_4 = Block::widget(['name' => $block_id . '-4', 'params' => ['params' => $this->params, 'cols' => 4]]);
        $block_5 = Block::widget(['name' => $block_id . '-5', 'params' => ['params' => $this->params, 'cols' => 5]]);
      } elseif ($type == 1){
        $block_1 = Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
      }

      return IncludeTpl::widget([
        'file' => 'boxes/invoice/block.tpl',
        'params' => [
          'block_1' => $block_1,
          'block_2' => $block_2,
          'block_3' => $block_3,
          'block_4' => $block_4,
          'block_5' => $block_5,
          'type' => $type,
          'p_width' => Info::blockWidth($this->id)
        ]
      ]);
    }
    
    $var = '';

    if ($type == 2 || $type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 9 || $type == 10 || $type == 11 || $type == 12){
      $var .= Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
      $var .= Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
    } elseif ($type == 3 || $type == 8 || $type == 13){
      $var .= Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
      $var .= Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
      $var .= Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
    } elseif ($type == 14){
      $var .= Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
      $var .= Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
      $var .= Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
      $var .= Block::widget(['name' => $block_id . '-4', 'params' => ['params' => $this->params, 'cols' => 4]]);
    } elseif ($type == 15){
      $var .= Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
      $var .= Block::widget(['name' => $block_id . '-2', 'params' => ['params' => $this->params, 'cols' => 2]]);
      $var .= Block::widget(['name' => $block_id . '-3', 'params' => ['params' => $this->params, 'cols' => 3]]);
      $var .= Block::widget(['name' => $block_id . '-4', 'params' => ['params' => $this->params, 'cols' => 4]]);
      $var .= Block::widget(['name' => $block_id . '-5', 'params' => ['params' => $this->params, 'cols' => 5]]);
    } elseif ($type == 1){
      $var .= Block::widget(['name' => $block_id, 'params' => ['params' => $this->params]]);
    } else {
      $var .= '<div class="no-block-settings"></div>
  <script type="text/javascript">
  tl(function(){ setTimeout(function(){
    if (!window.isOpenedBlockPopup){
      window.isOpenedBlockPopup = true;
      $(\'#box-' . $this->id . '\').find(\'.edit-box\').trigger(\'click\')
    }
  }, 2000) })
  </script>';
    }

    return $var;

    /*return IncludeTpl::widget([
      'file' => 'boxes/block.tpl',
      'params' => [
        'block_id' => 'block-' . $this->id,
        'params' => $this->params,
        'settings' => $this->settings,
      ]
    ]);*/
  }
}
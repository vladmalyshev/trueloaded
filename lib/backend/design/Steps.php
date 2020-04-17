<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design;


use backend\controllers\DesignController;

class Steps
{

  public static $elementsEvent = ['stepSave', 'boxAdd', 'blocksMove',
    'boxSave', 'boxDelete', 'importBlock', 'elementsSave', 'elementsCancel'];

  public static $stylesEvent = ['styleSave', 'themeSave', 'themeCancel'];

  public static function stepSave($event, $data, $theme_name, $change_active = true) {
    $before = tep_db_fetch_array(tep_db_query("select steps_id from " . TABLE_THEMES_STEPS . " where active='1' and theme_name='" . tep_db_input($theme_name) . "'"));

    if ($change_active) tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");

    $sql_data_array = array(
      'parent_id' => $before['steps_id'],
      'event' => $event,
      'data' => json_encode($data),
      'theme_name' => $theme_name,
      'date_added' => 'now()',
      'active' => $change_active ? '1' : '',
      'admin_id' => $_SESSION['login_id'],
    );
    tep_db_perform(TABLE_THEMES_STEPS, $sql_data_array);
  }


  public static function undo($theme_name) {
    $step = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_STEPS . " where active='1' and theme_name='" . tep_db_input($theme_name) . "'"));

    $action = $step['event'] . 'Undo';
    self::$action($step);

    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "steps_id = '" . (int)$step['parent_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");
  }

  public static function redo($theme_name, $steps_id) {
    $step = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_STEPS . " where steps_id='" . (int)$steps_id . "' and theme_name='" . tep_db_input($theme_name) . "'"));

    $action = $step['event'] . 'Redo';
    self::$action($step);

    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "steps_id = '" . (int)$steps_id . "' and theme_name='" . tep_db_input($theme_name) . "'");

  }


  public static function boxAdd($data) {

    $data_s = array(
      'block_id' => $data['block_id'],
      'block_name' => $data['block_name'],
      'widget_name' => $data['widget_name'],
      'sort_order' => $data['sort_order'],
    );
    if ($data['sort_arr']){
      $data_s = array_merge(
        $data_s, [
          'sort_arr' => $data['sort_arr'],
          'sort_arr_old' => $data['sort_arr_old']
      ]);
    }

    self::stepSave('boxAdd', $data_s, $data['theme_name']);
  }

  public static function boxAddUndo($step) {

    $data = json_decode($step['data'], true);
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$data['block_id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['block_id'] . "'");

    DesignController::deleteBlock($data['block_id']);

    if ($data['sort_arr_old']){
      foreach ($data['sort_arr_old'] as $key => $order){
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, ['sort_order' => $order], 'update', "id = '" . (int)$key . "'");
      }
    }
  }

  public static function boxAddRedo($step) {

    $data = json_decode( $step['data'], true);
    $sql_data_array = array(
      'id' => $data['block_id'],
      'theme_name' => $step['theme_name'],
      'block_name' => $data['block_name'],
      'widget_name' => $data['widget_name'],
      'sort_order' => $data['sort_order'],
    );
    tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);
    
    if ($data['sort_arr']){
      foreach ($data['sort_arr'] as $key => $order){
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, ['sort_order' => $order], 'update', "id = '" . (int)$key . "'");
      }
    }
  }


  public static function blocksMove($data){

    $data_s = [
      'positions' => $data['positions'],
      'positions_old' => $data['positions_old'],
    ];

    self::stepSave('blocksMove', $data_s, $data['theme_name']);
  }

  public static function blocksMoveUndo($step){
    $data = json_decode($step['data'], true);
    if ($data['positions_old']){
      foreach ($data['positions_old'] as $item){
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, [
          'sort_order' => $item['sort_order'],
          'block_name' => $item['block_name']
        ], 'update', "id = '" . (int)$item['id'] . "'");
      }
    }
  }

  public static function blocksMoveRedo($step){
    $data = json_decode($step['data'], true);
    if ($data['positions']){
      foreach ($data['positions'] as $item){
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, [
          'sort_order' => $item['sort_order'],
          'block_name' => $item['block_name']
        ], 'update', "id = '" . (int)$item['id'] . "'");
      }
    }
  }


  public static function boxSave($data){

    $data_s = [
      'box_id' => $data['box_id'],
      'box_settings' => $data['box_settings'],
      'box_settings_old' => $data['box_settings_old'],
    ];

    self::stepSave('boxSave', $data_s, $data['theme_name']);
  }

  public static function boxSaveUndo($step){
    $data = json_decode($step['data'], true);

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['box_id'] . "'");
    if ($data['box_settings_old']){
      foreach ($data['box_settings_old'] as $item){
        tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, [
          'box_id' => $data['box_id'],
          'setting_name' => $item['setting_name'],
          'setting_value' => $item['setting_value'],
          'language_id' => $item['language_id'],
          'visibility' => $item['visibility']
        ]);
      }
    }
  }

  public static function boxSaveRedo($step){
    $data = json_decode($step['data'], true);

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['box_id'] . "'");
    if ($data['box_settings']){
      foreach ($data['box_settings'] as $item){
        tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, [
          'box_id' => $data['box_id'],
          'setting_name' => $item['setting_name'],
          'setting_value' => $item['setting_value'],
          'language_id' => $item['language_id'],
          'visibility' => $item['visibility']
        ]);
      }
    }
  }


  public static $boxDataArray = [];

  public static function boxData($id){


    $box = tep_db_fetch_array(tep_db_query("select * from " . TABLE_DESIGN_BOXES_TMP . " where id='" . (int)$id . "'"));
    self::$boxDataArray['design_boxes'][] = $box;

    $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where 	box_id='" . (int)$id . "'");
    while ($item = tep_db_fetch_array($query)){
      self::$boxDataArray['design_boxes_settings'][] = $item;
    }

    if ($box['widget_name'] == 'BlockBox' || $box['widget_name'] == 'email\BlockBox' || $box['widget_name'] == 'invoice\Container' || $box['widget_name'] == 'cart\CartTabs'){

      $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "'");
      if (tep_db_num_rows($query) > 0){
        while ($item = tep_db_fetch_array($query)){
          self::boxData($item['id']);
        }
      }
      $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "-2'");
      if (tep_db_num_rows($query) > 0){
        while ($item = tep_db_fetch_array($query)){
          self::boxData($item['id']);
        }
      }
      $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "-3'");
      if (tep_db_num_rows($query) > 0){
        while ($item = tep_db_fetch_array($query)){
          self::boxData($item['id']);
        }
      }
      $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "-4'");
      if (tep_db_num_rows($query) > 0){
        while ($item = tep_db_fetch_array($query)){
          self::boxData($item['id']);
        }
      }
      $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "-5'");
      if (tep_db_num_rows($query) > 0){
        while ($item = tep_db_fetch_array($query)){
          self::boxData($item['id']);
        }
      }
    } elseif ($box['widget_name'] == 'Tabs'){

      for($i = 1; $i < 11; $i++) {
        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . (int)$id . "-" . $i . "'");
        if (tep_db_num_rows($query) > 0) {
          while ($item = tep_db_fetch_array($query)) {
            self::boxData($item['id']);
          }
        }
      }
    }

  }

  public static function boxDelete($data){

    self::$boxDataArray = array();
    self::boxData($data['id']);
    $data_s = self::$boxDataArray;
    $data_s['box_id'] = $data['id'];

    self::stepSave('boxDelete', $data_s, $data['theme_name']);
  }

  public static function boxDeleteUndo($step){
    $data = json_decode($step['data'], true);

    foreach ($data['design_boxes'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $item);
    }
    foreach ($data['design_boxes_settings'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $item);
    }
  }

  public static function boxDeleteRedo($step){
    $data = json_decode($step['data'], true);

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$data['box_id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['box_id'] . "'");
    DesignController::deleteBlock($data['box_id']);
  }

    public static function removePageTemplate($data){

        self::$boxDataArray = array();

        $page_name = \common\classes\design::pageName($data['page_title']);

        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = '" . tep_db_input($page_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            self::boxData($item['id']);
        }

        $data_s = self::$boxDataArray;

        $themes_settings = tep_db_query("
                select * 
                from " . TABLE_THEMES_SETTINGS . " 
                where 
                    theme_name = '" . tep_db_input($data['theme_name']) . "' and 
                    ((setting_group = 'added_page' and setting_value = '" . tep_db_input($data['page_title']) . "') or 
                     (setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($data['page_title']) . "'))
        ");
        while ($setting = tep_db_fetch_array($themes_settings)) {
            $data_s['themes_settings'][] = $setting;
        }
        $data_s['page_title'] = $data['page_title'];

        self::stepSave('removePageTemplate', $data_s, $data['theme_name']);
    }

    public static function removePageTemplateUndo($step){
        $data = json_decode($step['data'], true);

        foreach ($data['themes_settings'] as $item){
            tep_db_perform(TABLE_THEMES_SETTINGS, $item);
        }
        foreach ($data['design_boxes'] as $item){
            tep_db_perform(TABLE_DESIGN_BOXES_TMP, $item);
        }
        foreach ($data['design_boxes_settings'] as $item){
            tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $item);
        }
    }

    public static function removePageTemplateRedo($step){
        $data = json_decode($step['data'], true);

        $page_title = $data['page_title'];
        $page_name = \common\classes\design::pageName($page_title);

        tep_db_fetch_array(tep_db_query("
                delete 
                from " . TABLE_THEMES_SETTINGS . " 
                where 
                    theme_name = '" . tep_db_input($data['theme_name']) . "' and 
                    ((setting_group = 'added_page' and setting_value = '" . tep_db_input($page_title) . "') or
                     (setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_title) . "'))
        "));

        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = '" . tep_db_input($page_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . $item['id'] . "'");
            self::deleteBlock($item['id']);
        }
    }


  public static function importBlock($data){

    self::$boxDataArray = array();
    self::boxData($data['box_id']);

    $data_s = [
      'box_id_old' => $data['box_id_old'],
      'box_id' => $data['box_id'],
      'blocksTree' => self::$boxDataArray,
    ];

    $theme_name = $data['theme_name'];
    self::stepSave('importBlock', $data_s, $theme_name);
  }

  public static function importBlockUndo($step){
    $data = json_decode($step['data'], true);

    $item = tep_db_fetch_array(tep_db_query("select theme_name, block_name, sort_order from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$data['box_id'] . "'"));

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$data['box_id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['box_id'] . "'");
    DesignController::deleteBlock($data['box_id']);

    $item['widget_name'] = 'Import';
    $item['id'] = $data['box_id_old'];
    tep_db_perform(TABLE_DESIGN_BOXES_TMP, $item);
  }

  public static function importBlockRedo($step){

    $data = json_decode($step['data'], true);

    foreach ($data['blocksTree']['design_boxes'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_TMP, $item);
    }
    foreach ($data['blocksTree']['design_boxes_settings'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $item);
    }

    tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$data['box_id_old'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$data['box_id_old'] . "'");

  }


  public static function elementsSave($theme_name){

    $data_s = [];
    self::stepSave('elementsSave', $data_s, $theme_name);

  }

  public static function elementsSaveUndo($step){
  }

  public static function elementsSaveRedo($step){
  }


  public static function elementsCancel($theme_name){

    $current = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_STEPS . " where active='1' and theme_name='" . tep_db_input($theme_name) . "'"));

    $query = tep_db_fetch_array(tep_db_query("
        select * 
        from " . TABLE_THEMES_STEPS . " 
        where 
          event='elementsSave' and 
          theme_name='" . tep_db_input($theme_name) . "' and
          date_added < '" . $current['date_added'] . "'
        order by	date_added desc limit 1"));

    $data_s = [];
    self::stepSave('elementsCancel', $data_s, $theme_name, false);

    $c = 1;
    $parent_id = $current['parent_id'];
    $chain = array();
    $chain[] = $current;
    while ($c){
      $chain_query = tep_db_query("select * from " . TABLE_THEMES_STEPS . " where steps_id='" . (int)$parent_id . "' and steps_id != '" . (int)$query['steps_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");
      $c = tep_db_num_rows($chain_query);
      if ($c) {
        $chain_arr = tep_db_fetch_array($chain_query);
        $parent_id = $chain_arr['parent_id'];
        $chain[] = $chain_arr;
      }
    }
    $chain[] = $query;
    $new_parent = $query['steps_id'];

    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "steps_id = '" . (int)$query['steps_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");

    for ($i = count($chain)-1; $i >= 0; $i--){
      if (!in_array($chain[$i]['event'], self::$elementsEvent)) {
        tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
        tep_db_perform(TABLE_THEMES_STEPS, array(
          'parent_id' => $new_parent,
          'event' => $chain[$i]['event'],
          'data' => $chain[$i]['data'],
          'theme_name' => $chain[$i]['theme_name'],
          'date_added' => $chain[$i]['date_added'],
          'active' => '1',
          'admin_id' => $chain[$i]['admin_id'],
        ));
        $new_parent = tep_db_insert_id();
      }
    }

  }

  public static function elementsCancelUndo($step){
  }

  public static function elementsCancelRedo($step){
  }


  public static function styleSave($data){

    $data_s = [
      'styles_old' => $data['styles_old'],
      'styles' => $data['styles'],
    ];
    
    self::stepSave('styleSave', $data_s, $data['theme_name']);

  }

  public static function styleSaveUndo($step){
    $data = json_decode($step['data'], true);
    foreach ($data['styles'] as $item){
      tep_db_query("delete from " . TABLE_THEMES_STYLES . " where id = '" . (int)$item['id'] . "'");
    }
    foreach ($data['styles_old'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, $item);
    }
  }

  public static function styleSaveRedo($step){
    $data = json_decode($step['data'], true);
    foreach ($data['styles_old'] as $item){
      tep_db_query("delete from " . TABLE_THEMES_STYLES . " where id = '" . (int)$item['id'] . "'");
    }
    foreach ($data['styles'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, $item);
    }
  }


  public static function settings($data){

    $data_s = [
      'them_settings_old' => $data['them_settings_old'],
      'them_settings' => $data['them_settings'],
    ];
    
    self::stepSave('settings', $data_s, $data['theme_name']);

  }

  public static function settingsUndo($step){
    $data = json_decode($step['data'], true);
    foreach ($data['them_settings'] as $item){
      tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$item['id'] . "'");
    }
    foreach ($data['them_settings_old'] as $item){
      tep_db_perform(TABLE_THEMES_SETTINGS, $item);
    }
  }

  public static function settingsRedo($step){
    $data = json_decode($step['data'], true);
    foreach ($data['them_settings_old'] as $item){
      tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$item['id'] . "'");
    }
    foreach ($data['them_settings'] as $item){
      tep_db_perform(TABLE_THEMES_SETTINGS, $item);
    }
  }


  public static function extendRemove($data){

    $themes_settings = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$data['id'] . "'"));

    $design_boxes_settings = [];
    $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where visibility = '" . (int)$data['id'] . "'");
    while ($item = tep_db_fetch_array($query)){
      $design_boxes_settings[] = $item;
    }

    $themes_styles = [];
    $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where visibility = '" . (int)$data['id'] . "'");
    while ($item = tep_db_fetch_array($query)){
      $themes_styles[] = $item;
    }

    $data_s = [
      'themes_settings' => $themes_settings,
      'design_boxes_settings' => $design_boxes_settings,
      'themes_styles' => $themes_styles,
    ];

    self::stepSave('extendRemove', $data_s, $data['theme_name']);

  }

  public static function extendRemoveUndo($step){
    $data = json_decode($step['data'], true);

    tep_db_perform(TABLE_THEMES_SETTINGS, $data['themes_settings']);

    foreach ($data['design_boxes_settings'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $item);
    }

    foreach ($data['themes_styles'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, $item);
    }
  }

  public static function extendRemoveRedo($step){
    $data = json_decode($step['data'], true);

    tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$data['themes_settings']['id'] . "'");
    tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where visibility = '" . tep_db_input($data['themes_settings']['id']) . "'");
    tep_db_query("delete from " . TABLE_THEMES_STYLES . " where visibility = '" . tep_db_input($data['themes_settings']['id']) . "'");

  }


  public static function extendAdd($data){

    self::stepSave('extendAdd', $data['data'], $data['theme_name']);

  }

  public static function extendAddUndo($step){
    $data = json_decode($step['data'], true);
    tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$data['id'] . "'");
  }

  public static function extendAddRedo($step){
    $data = json_decode($step['data'], true);
    tep_db_perform(TABLE_THEMES_SETTINGS, $data);
  }


  public static function cssSave($data){

      self::stepSave('cssSave', $data, $data['theme_name']);

  }

  public static function cssSaveUndo($step){
    $data = json_decode($step['data'], true);

    foreach ($data['attributes_changed'] as $item) {
        tep_db_perform(TABLE_THEMES_STYLES, [
            'value' => $item['value_old']
        ], 'update', "
                theme_name = '" . tep_db_input($data['theme_name']) . "' and
                selector = '" . tep_db_input($item['selector']) . "' and
                attribute = '" . tep_db_input($item['attribute']) . "' and
                visibility = '" . tep_db_input($item['visibility']) . "' and
                media = '" . tep_db_input($item['media']) . "' and
                accessibility = '" . tep_db_input($item['accessibility']) . "'
        ");
    }

    foreach ($data['attributes_delete'] as $item) {
        tep_db_perform(TABLE_THEMES_STYLES, [
                'theme_name' => $data['theme_name'],
                'selector' => $item['selector'],
                'attribute' => $item['attribute'],
                'value' => $item['value_old'],
                'visibility' => $item['visibility'],
                'media' => $item['media'],
                'accessibility' => $item['accessibility']
        ]);
    }

    foreach ($data['attributes_new'] as $item) {
        tep_db_query("delete from " . TABLE_THEMES_STYLES . " where
                theme_name = '" . tep_db_input($data['theme_name']) . "' and
                selector = '" . tep_db_input($item['selector']) . "' and
                attribute = '" . tep_db_input($item['attribute']) . "' and
                visibility = '" . tep_db_input($item['visibility']) . "' and
                media = '" . tep_db_input($item['media']) . "' and
                accessibility = '" . tep_db_input($item['accessibility']) . "'
        ");
    }

  }

  public static function cssSaveRedo($step){
    $data = json_decode($step['data'], true);

      foreach ($data['attributes_changed'] as $item) {
          tep_db_perform(TABLE_THEMES_STYLES, [
              'value' => $item['value']
          ], 'update', "
                theme_name = '" . tep_db_input($data['theme_name']) . "' and
                selector = '" . tep_db_input($item['selector']) . "' and
                attribute = '" . tep_db_input($item['attribute']) . "' and
                visibility = '" . tep_db_input($item['visibility']) . "' and
                media = '" . tep_db_input($item['media']) . "' and
                accessibility = '" . tep_db_input($item['accessibility']) . "'
        ");
      }

      foreach ($data['attributes_new'] as $item) {
          tep_db_perform(TABLE_THEMES_STYLES, [
              'theme_name' => $data['theme_name'],
              'selector' => $item['selector'],
              'attribute' => $item['attribute'],
              'value' => $item['value_old'],
              'visibility' => $item['visibility'],
              'media' => $item['media'],
              'accessibility' => $item['accessibility']
          ]);
      }

      foreach ($data['attributes_delete'] as $item) {
          tep_db_query("delete from " . TABLE_THEMES_STYLES . " where
                theme_name = '" . tep_db_input($data['theme_name']) . "' and
                selector = '" . tep_db_input($item['selector']) . "' and
                attribute = '" . tep_db_input($item['attribute']) . "' and
                visibility = '" . tep_db_input($item['visibility']) . "' and
                media = '" . tep_db_input($item['media']) . "' and
                accessibility = '" . tep_db_input($item['accessibility']) . "'
        ");
      }
  }


  public static function javascriptSave($data){

    $query = tep_db_fetch_array(tep_db_query("select steps_id, data, event, admin_id from " . TABLE_THEMES_STEPS . " where active='1' and theme_name='" . tep_db_input($data['theme_name']) . "'"));

    if ($query['event'] == 'javascriptSave' && $query['admin_id'] == $_SESSION['login_id']){

      $data_s = json_decode($query['data'], true);
      $data_s['javascript'] = $data['javascript'];

      $sql_data_array = array(
        'data' => json_encode($data_s),
        'date_added' => 'now()',
      );
      tep_db_perform(TABLE_THEMES_STEPS, $sql_data_array, 'update', "steps_id='" . (int)$query['steps_id'] . "'");

    } else {

      $data_s = [
        'javascript_old' => $data['javascript_old'],
        'javascript' => $data['javascript'],
      ];
      self::stepSave('javascriptSave', $data_s, $data['theme_name']);

    }

  }

  public static function javascriptSaveUndo($step){
    $data = json_decode($step['data'], true);

    tep_db_perform(TABLE_THEMES_SETTINGS, [
      'setting_value' => $data['javascript_old']
    ], 'update', " theme_name = '" . tep_db_input($data['theme_name']) . "' and setting_group = 'javascript' and setting_name = 'javascript'");
  }

  public static function javascriptSaveRedo($step){
    $data = json_decode($step['data'], true);

    tep_db_perform(TABLE_THEMES_SETTINGS, [
      'setting_value' => $data['javascript']
    ], 'update', " theme_name = '" . tep_db_input($data['theme_name']) . "' and setting_group = 'javascript' and setting_name = 'javascript'");
  }


  public static function backupSubmit($data){

    $data_s = [
      'backup_id' => (int)$data['backup_id']
    ];

    self::stepSave('backupSubmit', $data_s, $data['theme_name']);
  }

  public static function backupSubmitUndo($step){
  }

  public static function backupSubmitRedo($step){
  }


  public static function backupRestore($data){

    $data_s = [
      'backup_id' => $data['backup_id']
    ];

    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($data['theme_name']) . "'");
    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "data = '" . tep_db_input(json_encode(['backup_id' => (int)$data['backup_id']])) . "' and theme_name='" . tep_db_input($data['theme_name']) . "'");

    self::stepSave('backupRestore', $data_s, $data['theme_name'], false);
  }

  public static function backupRestoreUndo($step){
  }

  public static function backupRestoreRedo($step){
  }


  public static function themeSave($theme_name){

    $data_s = [];
    self::stepSave('themeSave', $data_s, $theme_name);

  }

  public static function themeSaveUndo($step){
  }

  public static function themeSaveRedo($step){
  }


  public static function themeCancel($theme_name){

    $current = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_STEPS . " where active='1' and theme_name='" . tep_db_input($theme_name) . "'"));

    $query = tep_db_fetch_array(tep_db_query("
        select * 
        from " . TABLE_THEMES_STEPS . " 
        where 
          event='themeSave' and 
          theme_name='" . tep_db_input($theme_name) . "' and
          date_added < '" . $current['date_added'] . "'
        order by	date_added desc limit 1"));

    $data_s = [];
    self::stepSave('themeCancel', $data_s, $theme_name, false);

    $c = 1;
    $parent_id = $current['parent_id'];
    $chain = array();
    $chain[] = $current;
    while ($c){
      $chain_query = tep_db_query("select * from " . TABLE_THEMES_STEPS . " where steps_id='" . (int)$parent_id . "' and steps_id != '" . (int)$query['steps_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");
      $c = tep_db_num_rows($chain_query);
      if ($c) {
        $chain_arr = tep_db_fetch_array($chain_query);
        $parent_id = $chain_arr['parent_id'];
        $chain[] = $chain_arr;
      }
    }
    $chain[] = $query;
    $new_parent = $query['steps_id'];

    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
    tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "steps_id = '" . (int)$query['steps_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");

    for ($i = count($chain)-1; $i >= 0; $i--){
      if (!in_array($chain[$i]['event'], self::$stylesEvent)) {
        tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
        tep_db_perform(TABLE_THEMES_STEPS, array(
          'parent_id' => $new_parent,
          'event' => $chain[$i]['event'],
          'data' => $chain[$i]['data'],
          'theme_name' => $chain[$i]['theme_name'],
          'date_added' => $chain[$i]['date_added'],
          'active' => '1',
          'admin_id' => $chain[$i]['admin_id'],
        ));
        $new_parent = tep_db_insert_id();
      }
    }

  }

  public static function themeCancelUndo($step){
  }

  public static function themeCancelRedo($step){
  }
  
  
  public static function addPage($data){

    $data_s = [
      'id' => $data['id'],
      'page_type' => $data['setting_name'],
      'page_name' => $data['setting_value']
    ];
    self::stepSave('addPage', $data_s, $data['theme_name']);

  }

  public static function addPageUndo($step){
    $data = json_decode($step['data'], true);
    tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where id = '" . (int)$data['id'] . "'");
  }

  public static function addPageRedo($step){
    $data = json_decode($step['data'], true);
    $sql_data_array = array(
      'id' => $data['id'],
      'theme_name' => $step['theme_name'],
      'setting_group' => 'added_page',
      'setting_name' => $data['page_type'],
      'setting_value' => $data['page_name']
    );
    tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
  }
  
  
  public static function addPageSettings($data){

    $data_s = [
      'page_name' => $data['page_name'],
      'settings_old' => $data['settings_old'],
      'settings' => $data['settings']
    ];
    self::stepSave('addPageSettings', $data_s, $data['theme_name']);

  }

  public static function addPageSettingsUndo($step){
    $data = json_decode($step['data'], true);
    
    $query_settings = tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($step['theme_name']) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($data['page_name']) . "'");
    
    foreach ($data['settings_old'] as $item){
      tep_db_perform(TABLE_THEMES_SETTINGS, $item);
    }
  }

  public static function addPageSettingsRedo($step){
    $data = json_decode($step['data'], true);

    $query_settings = tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($step['theme_name']) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($data['page_name']) . "'");

    foreach ($data['settings'] as $item){
      tep_db_perform(TABLE_THEMES_SETTINGS, $item);
    }
  }


    public static function log($theme_name, $output = [])
    {

        $active = tep_db_fetch_array(tep_db_query("select steps_id from " . TABLE_THEMES_STEPS . " where theme_name='" . tep_db_input($theme_name) . "' and active='1'"));
        $log = array();

        $filter = '';
        if (tep_not_null($output['from'])) {
            $from = tep_db_prepare_input($output['from']);
            $filter .= " and to_days(date_added) >= to_days('" . \common\helpers\Date::prepareInputDate($from) . "')";
        }
        if (tep_not_null($output['to'])) {
            $to = tep_db_prepare_input($output['to']);
            $filter .= " and to_days(date_added) <= to_days('" . \common\helpers\Date::prepareInputDate($to) . "')";
        }

        $limit = '';
        if (!$filter) {
            $limit = ' limit 500';
        }

        $query = tep_db_query("select steps_id, parent_id, event, date_added, admin_id from " . TABLE_THEMES_STEPS . " where theme_name='" . tep_db_input($theme_name) . "'" . $filter . " order by date_added desc " . $limit);

        $current = $active['steps_id'];
        $count = 0;
        while ($item = tep_db_fetch_array($query)){
            if (!$count && $output['to']){
                $current = $item['steps_id'];
                $count++;
            }
            $log[$item['steps_id']] = [
                'steps_id' => $item['steps_id'],
                'parent_id' => $item['parent_id'],
                'event' => $item['event'],
                'date_added' => $item['date_added'],
                'admin_id' => $item['admin_id'],
            ];
        }

        $trunk = array();
        $tree = array();
        while (is_array($log[$current])){
            $trunk[] = $current;
            $tree[$current] = $log[$current];
            $tree[$current]['branches'] = 1;
            $tree[$current]['branch_id'] = 0;
            $current = $log[$current]['parent_id'];
        }

        $branches = array();
        foreach ($log as $id => $item){
            if(!in_array($id, $trunk)){
                $branches[$item['steps_id']] = $item;
            }
        }

        $count_error = 0;

        while (count($branches) > 0) {
            foreach ($branches as $item) {
                if (is_array($tree[$item['parent_id']])) {
                    $tree[$item['parent_id']]['branches']++;

                    $tree[$item['steps_id']] = $item;
                    if ($tree[$item['parent_id']]['branches'] == 1){
                        $tree[$item['steps_id']]['branch_id'] = $tree[$item['parent_id']]['branch_id'];
                    } else {
                        $tree[$item['steps_id']]['branch_id'] = $item['parent_id'];
                    }

                    $tree[$item['steps_id']]['branches'] = 0;
                }
                unset($branches[$item['steps_id']]);
            }

            $count_error++;
            if ($count_error > 1000000) return 'Error, too many steps. 2';
        }

        foreach ($tree as $key => $item){
            $tree[$key]['text'] = self::logNames($item['event']);
            $tree[$key]['date_added'] = \common\helpers\Date::date_long($tree[$key]['date_added'], "%d %b %Y / %H:%M:%S");
        }

        return $tree;

    }

    public static function logDetails($id)
    {
        $details = tep_db_fetch_array(tep_db_query("select * from " . TABLE_THEMES_STEPS . " where steps_id = '" . (int)$id . "'"));

        $details['name'] = self::logNames($details['event']);
        $details['date_added'] = \common\helpers\Date::date_long($details['date_added'], "%d %b %Y / %H:%M:%S");

        $admin = tep_db_fetch_array(tep_db_query("
            select admin_id, admin_firstname, admin_lastname, admin_email_address 
            from " . TABLE_ADMIN . " 
            where admin_id = '" . (int)$details['admin_id'] . "'"));

        $data = json_decode($details['data'], true);

        $details['admin'] = $admin['admin_firstname'] . ' ' . $admin['admin_lastname'];

        if ($details['event'] == 'boxAdd') {
            $details['widget_name'] = $data['widget_name'];
        }
        if ($details['event'] == 'boxSave') {

            $widget = tep_db_fetch_array(tep_db_query("
                select widget_name 
                from " . TABLE_DESIGN_BOXES_TMP . " 
                where id = '" . (int)$data['box_id'] . "'"));

            $details['widget_name'] = $widget['widget_name'];
        }

        return $details;
    }


  public static function logNames($event){
    $text = '';
    switch ($event) {
      case 'boxAdd': $text = LOG_ADDED_NEW_BLOCK; break;
      case 'blocksMove': $text = LOG_CHANGED_BLOCK_POSITION; break;
      case 'boxSave': $text = LOG_CHANGED_BLOCK_SETTINGS; break;
      case 'boxDelete': $text = LOG_REMOVED_BLOCK; break;
      case 'importBlock': $text = LOG_IMPORTED_BLOCK; break;
      case 'elementsSave': $text = LOG_SAVED_EDIT_ELEMENTS_PAGE; break;
      case 'elementsCancel': $text = LOG_CANCELED_EDIT_ELEMENTS_PAGE; break;
      case 'styleSave': $text = LOG_CANCELED_THEME_STYLES; break;
      case 'settings': $text = LOG_CHANGED_THEME_SETTINGS; break;
      case 'extendRemove': $text = LOG_REMOVED_EXTEND_FIELD; break;
      case 'extendAdd': $text = LOG_ADDED_EXTEND_FIELD; break;
      case 'cssSave': $text = LOG_SAVED_CSS; break;
      case 'javascriptSave': $text = LOG_SAVED_JAVASCRIPT; break;
      case 'backupSubmit': $text = LOG_DID_BACKU; break;
      case 'backupRestore': $text = LOG_RESTORED_BACKUP; break;
      case 'themeSave': $text = LOG_SAVED_CUSTOMIZE_THEME_STYLES; break;
      case 'themeCancel': $text = LOG_CANCELED_CUSTOMIZE_THEME_STYLES; break;
      case 'addPage': $text = LOG_ADDED_NEW_PAGE; break;
      case 'addPageSettings': $text = LOG_CHANGED_ADDED_PAGE; break;
    }

    return $text;
  }
  
  
  public static function restore($id){

    $chain = array();
    $event = 1;
    $theme_name = 0;


    while (!$theme_name){

      while ($event && $event != 'backupSubmit' && $event != 'backupRestore') {
        $item = tep_db_fetch_array(tep_db_query("select steps_id, parent_id, event, data from " . TABLE_THEMES_STEPS . " where steps_id = '" . (int)$id . "'"));
        $event = $item['event'];
        $chain[] = $item['steps_id'];
        $id = $item['parent_id'];
      }

      if (!$event){
        return LOG_NO_BACKUPS;
      }

      $data = json_decode($item['data'], true);

      $query = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_DESIGN_BACKUPS . " where backup_id = '" . (int)$data['backup_id'] . "' limit 1"));
      $theme_name = $query['theme_name'];
      if ($theme_name && $data['backup_id']){
        \backend\design\Backups::backupRestore($data['backup_id'], $theme_name);
        tep_db_perform(TABLE_THEMES_STEPS, array('active' => '0'), 'update', "active = '1' and theme_name='" . tep_db_input($theme_name) . "'");
        tep_db_perform(TABLE_THEMES_STEPS, array('active' => '1'), 'update', "steps_id = '" . $item['steps_id'] . "' and theme_name='" . tep_db_input($theme_name) . "'");
      }

      $event = 1;
    }

    for ($i = count($chain)-1; $i >= 0; $i--){
      self::redo($theme_name, $chain[$i]);
    }




    return '';
  }


  public static function stylesChange($data)
  {
    $stylesId = array();
    if ($data['style'] == 'border_color') {
      $attribute = " and attribute in ('border_top_color', 'border_left_color', 'border_right_color', 'border_bottom_color')";
    } else {
      $attribute = " and attribute = '" . tep_db_input($data['style']) . "'";
    }
    $query = tep_db_query("select id from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($data['theme_name']) . "'" . $attribute . " and value = '" . tep_db_input($data['from']) . "'");
    while ($item = tep_db_fetch_array($query)) {
      $stylesId[] = $item['id'];
    }
    $settingsId = array();
    if ($data['style'] == 'border_color') {
      $setting_name = " and bs.setting_name in ('border_top_color', 'border_left_color', 'border_right_color', 'border_bottom_color')";
    } else {
      $setting_name = " and bs.setting_name = '" . tep_db_input($data['style']) . "'";
    }
    $query = tep_db_query("select bs.id from " . TABLE_DESIGN_BOXES_TMP . " b left join " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " bs on b.id = bs.box_id where b.theme_name = '" . tep_db_input($data['theme_name']) . "' " . $setting_name . " and bs.setting_value = '" . tep_db_input($data['from']) . "'");
    while ($item = tep_db_fetch_array($query)) {
      $settingsId[] = $item['id'];
    }

    $data_s = [
        'stylesId' => $stylesId,
        'settingsId' => $settingsId,
        'from' => $data['from'],
        'to' => $data['to'],
        'style' => $data['style'],
    ];

    self::stepSave('stylesChange', $data_s, $data['theme_name']);
  }

  public static function stylesChangeUndo($step)
  {
    $data = json_decode($step['data'], true);

    foreach ($data['stylesId'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, array('value' => $data['from']), 'update', " id = '" . $item . "'");
    }
    foreach ($data['settingsId'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, array('setting_value' => $data['from']), 'update', " id = '" . $item . "'");
    }
  }

  public static function stylesChangeRedo($step)
  {
    $data = json_decode($step['data'], true);

    foreach ($data['stylesId'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, array('value' => $data['to']), 'update', " id = '" . $item . "'");
    }
    foreach ($data['settingsId'] as $item){
      tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, array('setting_value' => $data['to']), 'update', " id = '" . $item . "'");
    }
  }


  public static function removeClass($data)
  {
    $styles = array();
    $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($data['theme_name']) . "' and selector = '" . tep_db_input($data['class']) . "'");
    while ($item = tep_db_fetch_array($query)) {
      $styles[] = $item;
    }

    $data_s = [
        'styles' => $styles,
        'class' => $data['class'],
    ];

    self::stepSave('removeClass', $data_s, $data['theme_name']);
  }

  public static function removeClassUndo($step)
  {
    $data = json_decode($step['data'], true);

    foreach ($data['styles'] as $item){
      tep_db_perform(TABLE_THEMES_STYLES, $item);
    }
  }

  public static function removeClassRedo($step)
  {
    $data = json_decode($step['data'], true);

    tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" .  tep_db_input($step['theme_name']) . "' and selector = '" . tep_db_input($data['class']) . "'");
  }


}

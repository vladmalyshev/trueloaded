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

use yii\helpers\FileHelper;

class Backups
{
    public static function create($theme_name, $backup_id)
    {
        $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'backup_id' => $backup_id,
                'theme_name' => $theme_name,
                'box_id' => $item['id'],
                'block_name' => $item['block_name'],
                'widget_name' => $item['widget_name'],
                'widget_params' => $item['widget_params'],
                'sort_order' => $item['sort_order'],
            );
            tep_db_perform(TABLE_DESIGN_BOXES_BACKUPS, $sql_data_array);

            $query2 = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . (int)$item['id'] . "'");
            while ($item2 = tep_db_fetch_array($query2)){
                $sql_data_array = array(
                    'backup_id' => $backup_id,
                    'box_id' => $item2['box_id'],
                    'setting_name' => $item2['setting_name'],
                    'setting_value' => $item2['setting_value'],
                    'language_id' => $item2['language_id'],
                    'visibility' => $item2['visibility'],
                    'theme_name' => $theme_name,
                );
                tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_BACKUPS, $sql_data_array);
            }
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'settings_id' => $item['id'],
                'backup_id' => $backup_id,
                'theme_name' => $theme_name,
                'setting_group' => $item['setting_group'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
            );
            tep_db_perform(TABLE_THEMES_SETTINGS_BACKUPS, $sql_data_array);
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'backup_id' => $backup_id,
                'theme_name' => $theme_name,
                'selector' => $item['selector'],
                'attribute' => $item['attribute'],
                'value' => $item['value'],
                'visibility' => $item['visibility'],
                'media' => $item['media'],
                'accessibility' => $item['accessibility'],
            );
            tep_db_perform(TABLE_THEMES_STYLES_BACKUPS, $sql_data_array);
        }
    }

    public static function backupRestore($backup_id, $theme_name)
    {

        $boxes_sql = tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($boxes_sql)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES . " where id = '" . $item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . $item['id'] . "'");
        }

        $boxes_sql1 = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($boxes_sql1)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . $item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . $item['id'] . "'");
        }

        tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where 	theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STYLES . " where 	theme_name = '" . tep_db_input($theme_name) . "'");

        $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_BACKUPS . " where backup_id = '" . (int)$backup_id . "' and theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'id' => $item['box_id'],
                'theme_name' => $theme_name,
                'block_name' => $item['block_name'],
                'widget_name' => $item['widget_name'],
                'widget_params' => $item['widget_params'],
                'sort_order' => $item['sort_order'],
            );
            tep_db_perform(TABLE_DESIGN_BOXES, $sql_data_array);
            tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);
        }

        $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS_BACKUPS . " where backup_id = '" . (int)$backup_id . "' and theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'box_id' => $item['box_id'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
                'language_id' => $item['language_id'],
                'visibility' => $item['visibility'],
            );
            tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, $sql_data_array);
            tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array);
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_SETTINGS_BACKUPS . " where backup_id = '" . (int)$backup_id . "' and theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'id' => $item['settings_id'],
                'theme_name' => $theme_name,
                'setting_group' => $item['setting_group'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
            );
            tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES_BACKUPS . " where backup_id = '" . (int)$backup_id . "' and theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $sql_data_array = array(
                'theme_name' => $theme_name,
                'selector' => $item['selector'],
                'attribute' => $item['attribute'],
                'value' => $item['value'],
                'visibility' => $item['visibility'],
                'media' => $item['media'],
                'accessibility' => $item['accessibility'],
            );
            tep_db_perform(TABLE_THEMES_STYLES, $sql_data_array);
        }

    }
}
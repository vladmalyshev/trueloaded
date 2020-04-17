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
use common\classes\Images;
use Yii;

class Theme
{
    public static function export($theme_name, $output='download')
    {
        $tmp_path = \Yii::getAlias('@site_root');
        $img_path = $tmp_path;
        $tmp_path = \Yii::getAlias('@runtime'.DIRECTORY_SEPARATOR);
        //$tmp_path .= DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

        $backup_file = $theme_name;

        $zip = new \ZipArchive();
        if ($zip->open($tmp_path . $backup_file . '.zip', \ZipArchive::CREATE) === TRUE) {

            $theme = $theme_name;
            $themeFolder = DIRECTORY_SEPARATOR . 'desktop';
            for ($i = 0; $i < 2 && $theme; $i++) {

                $json = self::getThemeJson($theme);
                $themes_arr = [];
                $parents = [];
                $parents_query = tep_db_query("select theme_name, parent_theme from " . TABLE_THEMES);
                while ($item = tep_db_fetch_array($parents_query)) {
                    $themes_arr[$item['theme_name']] = $item['parent_theme'];
                }
                $parent_theme = $theme;
                $parents[] = $theme;
                while ($themes_arr[$parent_theme]) {
                    $parents[] = $themes_arr[$parent_theme];
                    $parent_theme = $themes_arr[$parent_theme];
                }
                $parents = array_reverse($parents);
                foreach ($parents as $themeParent) {
                    $rootPath = \Yii::getAlias('@site_root'.DIRECTORY_SEPARATOR);

                    $path = $rootPath . 'themes' . DIRECTORY_SEPARATOR . $themeParent . DIRECTORY_SEPARATOR;
                    $files = self::themeFiles($themeParent);
                    foreach ($files as $item) {
                        $separator = DIRECTORY_SEPARATOR;
                        if (strpos($item, DIRECTORY_SEPARATOR) === 0) {
                            $separator = '';
                        }
                        $zip->addFile($path . $item, $themeFolder . $separator . $item);// add css, js, images, fonts
                    }

                    $tplPath = $rootPath . 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
                    $tplPath .= 'themes' . DIRECTORY_SEPARATOR . $themeParent . DIRECTORY_SEPARATOR;
                    $tplFiles = self::themeFiles($themeParent, '', true);
                    foreach ($tplFiles as $item) {
                        $zip->addFile($tplPath . $item, $themeFolder . DIRECTORY_SEPARATOR . 'tpl' . $item);// add tpl files
                    }
                }

                $zip->addFromString ($themeFolder . DIRECTORY_SEPARATOR . 'theme-tree.json', $json);

                foreach (Uploads::$archiveImages as $item){// add images from different places, by records in db
                    if (is_file($img_path . $item['old'])){
                        if (!in_array('img' . DIRECTORY_SEPARATOR . $item['new'], $files)) {
                            $zip->addFile($img_path . $item['old'], $themeFolder . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $item['new']);
                        }
                    }
                }

                $themeFolder = '/mobile';
                $theme = $theme_name . '-mobile';
            }

            $zip->close();
            $backup_file .= '.zip';

            if ( $output=='filename' ) {
                return $tmp_path . $backup_file;
            }else {
                header('Cache-Control: none');
                header('Pragma: none');
                header('Content-type: application/x-octet-stream');
                header('Content-disposition: attachment; filename=' . $backup_file);

                readfile($tmp_path . $backup_file);
                unlink($tmp_path . $backup_file);
            }
        }

        return '';
    }

    public static function import($themeName, $archiveFilename)
    {

        $path = \Yii::getAlias('@site_root'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR);
        $pathDesktop = $path . 'desktop' . DIRECTORY_SEPARATOR;
        $pathMobile = $path . 'mobile' . DIRECTORY_SEPARATOR;
        $arrMobile = '';

            $zip = new \ZipArchive();

            if ($zip->open($archiveFilename, \ZipArchive::CREATE) === TRUE) {
                if (!file_exists($path)) {
                    try {
                        FileHelper::createDirectory($path, 0777);
                    } catch (\Exception $ex) {
                    }
                    //mkdir($path);
                }
                if ($zip->extractTo($path)) {
                    clearstatcache();
                    $extractedFiles = FileHelper::findFiles($path, ['recursive' => true]);
                    if (!is_array($extractedFiles)) $extractedFiles = [];
                    foreach ($extractedFiles as $extractedFile) {
                        if (is_file($extractedFile)) {
                            @chmod($extractedFile, 0666);
                        } elseif (is_dir($extractedFile)) {
                            @chmod($extractedFile, 0777);
                        }

                    }
                }


                if (!is_dir($pathDesktop)) {
                    $pathDesktop = $path;
                }
                $arrDesktop = json_decode(file_get_contents($pathDesktop . 'theme-tree.json'), true);
                if (is_file($pathMobile . 'theme-tree.json')) {
                    $arrMobile = json_decode(file_get_contents($pathMobile . 'theme-tree.json'), true);
                }
                Theme::copyFiles($themeName);
            }else{
                return false;
            }

        if (is_array($arrDesktop) && $themeName){

            Theme::importTheme($arrDesktop, $themeName);
            if ($arrMobile) {
                Theme::importTheme($arrMobile, $themeName . '-mobile');
            }

            Style::createCache($themeName);
            Style::createCache($themeName . '-mobile');

            return true;
        }

        return false;
    }

    public static function getThemeJson($theme_name)
    {
        $theme = [];

        $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "' and block_name not like 'block-%'");
        while ($item = tep_db_fetch_array($query)){
            $theme['blocks'][] = self::blocksTree($item['id'], true);
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            if ($item['setting_group'] == 'css' && $item['setting_name'] == 'css'){

                preg_match_all("/url\([\'\"]{0,1}([^\)\'\"]+)/", $item['setting_value'], $out, PREG_PATTERN_ORDER);

                $css_img_arr = [];
                foreach ($out[1] as $img){
                    if (substr($img, 0, 2) != '//' && substr($img, 0, 4) != 'http'){
                        if (!$css_img_arr[$img]){
                            $css_img_arr[$img] = Uploads::addArchiveImages('background_image', $img);
                        }
                    }
                }
                foreach ($css_img_arr as $path => $img){
                    $item['setting_value'] = str_replace($path, $img, $item['setting_value']);
                }
            }
            $item['setting_value'] = str_replace($theme_name, '<theme_name>', $item['setting_value']);
            $theme['settings'][] = [
                'setting_group' => $item['setting_group'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
            ];
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            $item['value'] = Uploads::addArchiveImages($item['attribute'], $item['value']);

            $vArr = Style::vArr($item['visibility']);
            foreach ($vArr as $vKey => $vItem) {
                if ($vItem > 10) {
                    $vMedia = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where id = '" . $vItem . "'"));
                    $vArr[$vKey] = $vMedia['setting_value'];
                }
            }
            $item['visibility'] = Style::vStr($vArr, true);

            $theme['styles'][] = [
                'selector' => $item['selector'],
                'attribute' => $item['attribute'],
                'value' => $item['value'],
                'visibility' => $item['visibility'],
                'media' => $item['media'],
                'accessibility' => $item['accessibility'],
            ];
        }

        return json_encode($theme);
    }

    public static function themeFiles($theme_name, $path = '', $tpl = false)
    {
        $theme_path = \Yii::getAlias('@site_root'.DIRECTORY_SEPARATOR);
        if ($tpl) {
            $theme_path .= 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
            $theme_path .= 'themes' . DIRECTORY_SEPARATOR . $theme_name . DIRECTORY_SEPARATOR;
        } else {
            $theme_path .= 'themes' . DIRECTORY_SEPARATOR . $theme_name . DIRECTORY_SEPARATOR;
        }

        $files_arr = array();
        $arr = file_exists($theme_path . $path) ? scandir($theme_path . $path) : array();
        foreach ($arr as $item) {
            if ($item != '.' && $item != '..') {
                if (is_dir($theme_path . $path . DIRECTORY_SEPARATOR . $item)) {
                    $files_arr = array_merge($files_arr, self::themeFiles($theme_name, $path . DIRECTORY_SEPARATOR . $item, $tpl));
                } else {
                    $files_arr[] = $path . ($path ? DIRECTORY_SEPARATOR : '') . $item;
                }
            }
        }
        return $files_arr;
    }

    public static function blocksTree($id, $images = false) {
        $arr = array();

        $query = tep_db_fetch_array(tep_db_query("select widget_name, widget_params, sort_order, block_name, theme_name from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$id . "'"));

        $arr['block_name'] = $query['block_name'];
        $arr['widget_name'] = $query['widget_name'];
        $arr['widget_params'] = $query['widget_params'];
        $arr['sort_order'] = $query['sort_order'];

        $query2 = tep_db_query("
select dbs.setting_name, dbs.setting_value, dbs.visibility, l.code
from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " dbs left join " . TABLE_LANGUAGES . " l on dbs.language_id = l.languages_id
where dbs.box_id = '" . (int)$id . "'
");
        while ($item2 = tep_db_fetch_array($query2)) {
            if ($images){
                $item2['setting_value'] = Uploads::addArchiveImages($item2['setting_name'], $item2['setting_value']);
            }
            $vArr = Style::vArr($item2['visibility']);
            foreach ($vArr as $vKey => $vItem) {
                if ($vItem > 10) {
                    $vMedia = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where id = '" . $vItem . "'"));
                    $vArr[$vKey] = $vMedia['setting_value'];
                }
            }
            $item2['visibility'] = Style::vStr($vArr, true);

            $item2['setting_value'] = str_replace($query['theme_name'], '<theme_name>', $item2['setting_value']);
            $arr['settings'][] = array(
                'setting_name' => $item2['setting_name'],
                'setting_value' => $item2['setting_value'],
                'language_id' => ($item2['code'] ? $item2['code'] : 0),
                'visibility' => $item2['visibility']
            );
        }

        if ($query['widget_name'] == 'BlockBox' || $query['widget_name'] == 'email\BlockBox' || $query['widget_name'] == 'invoice\Container' || $query['widget_name'] == 'cart\CartTabs'){

            $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "'");
            if (tep_db_num_rows($query) > 0){
                while ($item = tep_db_fetch_array($query)){
                    $arr['sub_1'][] = self::blocksTree($item['id'], $images);
                }
            }
            $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "-2'");
            if (tep_db_num_rows($query) > 0){
                while ($item = tep_db_fetch_array($query)){
                    $arr['sub_2'][] = self::blocksTree($item['id'], $images);
                }
            }
            $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "-3'");
            if (tep_db_num_rows($query) > 0){
                while ($item = tep_db_fetch_array($query)){
                    $arr['sub_3'][] = self::blocksTree($item['id'], $images);
                }
            }
            $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "-4'");
            if (tep_db_num_rows($query) > 0){
                while ($item = tep_db_fetch_array($query)){
                    $arr['sub_4'][] = self::blocksTree($item['id'], $images);
                }
            }
            $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "-5'");
            if (tep_db_num_rows($query) > 0){
                while ($item = tep_db_fetch_array($query)){
                    $arr['sub_5'][] = self::blocksTree($item['id'], $images);
                }
            }
        } elseif ($query['widget_name'] == 'Tabs'){

            for($i = 1; $i < 11; $i++) {
                $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where block_name = 'block-" . tep_db_input($id) . "-" . $i . "'");
                if (tep_db_num_rows($query) > 0) {
                    while ($item = tep_db_fetch_array($query)) {
                        $arr['sub_' . $i][] = self::blocksTree($item['id'], $images);
                    }
                }
            }
        }

        return $arr;
    }

    public static function importTheme($arr, $theme_name)
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
        //tep_db_query("delete from " . TABLE_THEMES_STYLES_TMP . " where 	theme_name = '" . tep_db_input($theme_name) . "'");

        if (is_array($arr['settings'])) foreach ($arr['settings'] as $item) {
            if ($item['setting_group'] == 'css' && $item['setting_name'] == 'css'){
                $item['setting_value'] = str_replace("$$", 'themes/' . $theme_name . '/img/', $item['setting_value']);
            }
            $item['setting_value'] = str_replace('<theme_name>', $theme_name, $item['setting_value']);
            $sql_data_array = array(
                'theme_name' => $theme_name,
                'setting_group' => $item['setting_group'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
            );
            tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
        }

        if (is_array($arr['blocks'])) foreach ($arr['blocks'] as $item){
            self::blocksTreeImport($item, $theme_name);
        }


        if (is_array($arr['styles'])) foreach ($arr['styles'] as $item) {
            if (substr($item['value'], 0, 2) == '$$'){
                $item['value'] = 'themes/' . $theme_name . '/img/' . substr_replace( $item['value'], '', 0, 2);
            }
            if (strlen($item['visibility']) > 1){

                $vArr = Style::vArr($item['visibility'], true);
                foreach ($vArr as $vKey => $vItem) {
                    if (strlen($vItem) > 1) {
                        $vis_query = tep_db_fetch_array(tep_db_query("select id from " . TABLE_THEMES_SETTINGS . " where setting_value = '" . tep_db_input($vItem) . "' and setting_name = 'media_query' and theme_name = '" . tep_db_input($theme_name) . "'"));
                        $vArr[$vKey] = $vis_query['id'];
                    } else {
                        $vArr[$vKey] = $vItem;
                    }
                }
                $item['visibility'] = Style::vStr($vArr);
            }
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
            //tep_db_perform(TABLE_THEMES_STYLES_TMP, $sql_data_array);
        }

        self::elementsSave($theme_name);
    }

    public static function blocksTreeImport($arr, $theme_name, $block_name = '', $sort_order = '', $save = false)
    {

        $sql_data_array = array(
            'theme_name' => $theme_name,
            'block_name' => ($block_name ? $block_name : $arr['block_name']),
            'widget_name' => $arr['widget_name'],
            'widget_params' => $arr['widget_params'],
            'sort_order' => ($sort_order ? $sort_order : $arr['sort_order']),
        );
        tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);
        $box_id = tep_db_insert_id();
        if ($save) {
            $sql_data_array = array_merge($sql_data_array, ['id' => $box_id]);
            tep_db_perform(TABLE_DESIGN_BOXES, $sql_data_array);
        }

        if (is_array($arr['settings']) && count($arr['settings']))
            foreach ($arr['settings'] as $item){
                $language_id = 0;
                $key = true;
                if ($item['language_id']){
                    $lan_query = tep_db_fetch_array(tep_db_query("select languages_id from " . TABLE_LANGUAGES . " where code = '" . tep_db_input($item['language_id']) . "'"));
                    if ($lan_query['languages_id']) {
                        $language_id = $lan_query['languages_id'];
                    } else {
                        $key = false;
                    }
                }
                $visibility = '';
                if (strlen($item['visibility']) > 1){

                    $vArr = Style::vArr($item['visibility'], true);
                    foreach ($vArr as $vKey => $vItem) {
                        if (strlen($vItem) > 1) {
                            $vis_query = tep_db_fetch_array(tep_db_query("select id from " . TABLE_THEMES_SETTINGS . " where setting_value = '" . tep_db_input($vItem) . "' and setting_name = 'media_query' and theme_name = '" . tep_db_input($theme_name) . "'"));
                            $vArr[$vKey] = $vis_query['id'];
                        }
                    }
                    $visibility = Style::vStr($vArr);

                } else {
                    $visibility = $item['visibility'];
                }
                if ($key) {
                    if (substr($item['setting_value'], 0, 2) == '$$'){
                        $item['setting_value'] = 'themes/' . $theme_name . '/img/' . substr_replace( $item['setting_value'], '', 0, 2);
                    }
                    $item['setting_value'] = str_replace('<theme_name>', $theme_name, $item['setting_value']);
                    $sql_data_array = array(
                        'box_id' => $box_id,
                        'setting_name' => $item['setting_name'],
                        'setting_value' => $item['setting_value'],
                        'language_id' => $language_id,
                        'visibility' => $visibility,
                    );
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array);
                    $set_id = tep_db_insert_id();
                    if ($save) {
                        $sql_data_array = array_merge($sql_data_array, ['id' => $set_id]);
                        tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, $sql_data_array);
                    }
                }
            }

        if ($arr['widget_name'] == 'BlockBox' || $arr['widget_name'] == 'email\BlockBox' || $arr['widget_name'] == 'invoice\Container' || $arr['widget_name'] == 'cart\CartTabs'){

            if (is_array($arr['sub_1']) && count($arr['sub_1']) > 0){
                foreach ($arr['sub_1'] as $item){
                    self::blocksTreeImport($item, $theme_name, 'block-' . $box_id, '', $save);
                }
            }
            if (is_array($arr['sub_2']) && count($arr['sub_2']) > 0){
                foreach ($arr['sub_2'] as $item){
                    self::blocksTreeImport($item, $theme_name, 'block-' . $box_id . '-2', '', $save);
                }
            }
            if (is_array($arr['sub_3']) && count($arr['sub_3']) > 0){
                foreach ($arr['sub_3'] as $item){
                    self::blocksTreeImport($item, $theme_name, 'block-' . $box_id . '-3', '', $save);
                }
            }
            if (is_array($arr['sub_4']) && count($arr['sub_4']) > 0){
                foreach ($arr['sub_4'] as $item){
                    self::blocksTreeImport($item, $theme_name, 'block-' . $box_id . '-4', '', $save);
                }
            }
            if (is_array($arr['sub_5']) && count($arr['sub_5']) > 0){
                foreach ($arr['sub_5'] as $item){
                    self::blocksTreeImport($item, $theme_name, 'block-' . $box_id . '-5', '', $save);
                }
            }
        } elseif ($arr['widget_name'] == 'Tabs'){

            for($i = 1; $i < 11; $i++) {
                if (is_array($arr['sub_' . $i]) && count($arr['sub_1']) > 0){
                    foreach ($arr['sub_' . $i] as $item){
                        self::blocksTreeImport($item, $theme_name, 'block-' . $box_id . '-' . $i, '', $save);
                    }
                }
            }
        }

        return $box_id;
    }

    public static function elementsSave($theme_name)
    {

        /*
        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES . " where id = '" . (int)$item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . (int)$item['id'] . "'");
        }
        */

      tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id in (select id from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "')");
      //the order is important (empty settings first)
      tep_db_query("delete from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");

      tep_db_query("INSERT INTO " . TABLE_DESIGN_BOXES . " SELECT * FROM " . TABLE_DESIGN_BOXES_TMP . " WHERE theme_name = '" . tep_db_input($theme_name) . "'");
      tep_db_query("INSERT INTO " . TABLE_DESIGN_BOXES_SETTINGS . " SELECT dbs.* FROM " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " dbs, " . TABLE_DESIGN_BOXES . " db WHERE db.theme_name = '" . tep_db_input($theme_name) . "' and dbs.box_id = db.id");
      
/*
        $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){

            tep_db_perform(TABLE_DESIGN_BOXES, $item);

            tep_db_query("INSERT INTO " . TABLE_DESIGN_BOXES_SETTINGS . " SELECT * FROM " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " WHERE box_id = '" . (int)$item['id'] . "'");
        }
 */
    }

    public static function copyFiles($theme_name)
    {
        $path = \Yii::getAlias('@site_root'.DIRECTORY_SEPARATOR);

        $pathTpl = $path . 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
        $pathTpl .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;

        $pathFiles = $path . 'themes' . DIRECTORY_SEPARATOR . $theme_name;
        $pathDesktopTmp = $pathFiles . DIRECTORY_SEPARATOR . 'desktop';
        $pathMobileTmp = $pathFiles . DIRECTORY_SEPARATOR . 'mobile';

        if (is_dir($pathDesktopTmp . DIRECTORY_SEPARATOR . 'tpl')) {

            FileHelper::copyDirectory($pathDesktopTmp . DIRECTORY_SEPARATOR . 'tpl', $pathTpl);
            FileHelper::removeDirectory($pathDesktopTmp . DIRECTORY_SEPARATOR . 'tpl');

        } elseif (is_dir($pathFiles . DIRECTORY_SEPARATOR . 'tpl')) {// if old exported theme

            FileHelper::copyDirectory($pathFiles . DIRECTORY_SEPARATOR . 'tpl', $pathTpl);
            FileHelper::removeDirectory($pathFiles . DIRECTORY_SEPARATOR . 'tpl');

        }

        if (is_dir($pathMobileTmp . DIRECTORY_SEPARATOR . 'tpl')) {

            FileHelper::copyDirectory($pathMobileTmp . DIRECTORY_SEPARATOR . 'tpl', $pathTpl . '-mobile');
            FileHelper::removeDirectory($pathMobileTmp . DIRECTORY_SEPARATOR . 'tpl');

        }

        if (is_dir($pathDesktopTmp)) {
            FileHelper::copyDirectory($pathDesktopTmp, $pathFiles);
            FileHelper::removeDirectory($pathDesktopTmp);
        }

        if (is_dir($pathMobileTmp)) {
            FileHelper::copyDirectory($pathMobileTmp, $pathFiles . '-mobile');
            FileHelper::removeDirectory($pathMobileTmp);
        }


    }


    public static function copyTheme ($theme_name, $parent_theme, $parent_theme_files = '')
    {
        $id_array = array();
        $visibility_array = array();
        $visibilityStyleArray = array();

        $themes_arr = array();
        $parents = array();
        $parents_query = tep_db_query("select theme_name, parent_theme from " . TABLE_THEMES);
        while ($item = tep_db_fetch_array($parents_query)) {
            $themes_arr[$item['theme_name']] = $item['parent_theme'];
        }
        $parent_theme = $parent_theme;
        $parents[] = $parent_theme;
        while ($themes_arr[$parent_theme]) {
            $parents[] = $themes_arr[$parent_theme];
            $parent_theme = $themes_arr[$parent_theme];
        }
        $parents = array_reverse($parents);

        if ($parent_theme_files == 'copy') {
            $query = tep_db_query("select * from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($parent_theme) . "'");
            while ($item = tep_db_fetch_array($query)) {
                $sql_data_array = array(
                    'theme_name' => $theme_name,
                    'block_name' => $item['block_name'],
                    'widget_name' => $item['widget_name'],
                    'widget_params' => $item['widget_params'],
                    'sort_order' => $item['sort_order'],
                );
                tep_db_perform(TABLE_DESIGN_BOXES, $sql_data_array);
                $new_row_id = tep_db_insert_id();
                $sql_data_array['id'] = $new_row_id;
                tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array);

                $query2 = tep_db_query("select * from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . (int)$item['id'] . "'");
                while ($item2 = tep_db_fetch_array($query2)) {
                    if ($parent_theme_files == 'copy' && ($item2['setting_name'] == 'background_image' || $item2['setting_name'] == 'logo')) {
                        foreach ($parents as $parentItem) {
                            $item2['setting_value'] = str_replace($parentItem, $theme_name, $item2['setting_value']);
                        }
                    }
                    $sql_data_array = array(
                        'box_id' => $new_row_id,
                        'setting_name' => $item2['setting_name'],
                        'setting_value' => $item2['setting_value'],
                        'language_id' => $item2['language_id'],
                        'visibility' => $item2['visibility'],
                    );
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, $sql_data_array);
                    $new_row_id_2 = tep_db_insert_id();
                    $sql_data_array['id'] = $new_row_id_2;
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array);

                    foreach (Style::vArr($item2['visibility']) as $vItem) {
                        if ($vItem > 10) {
                            $visibility_array[$new_row_id_2] = $item2['visibility'];
                            break;
                        }
                    }
                }

                $id_array[$item['id']] = $new_row_id;
            }

            $query = tep_db_query("select id, block_name from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");
            while ($item = tep_db_fetch_array($query)) {
                preg_match('/[a-z]-([0-9]+)/', $item['block_name'], $matches);
                if ($matches[1]) {
                    $new_block_name = str_replace($matches[1], $id_array[$matches[1]], $item['block_name']);
                    $sql_data_array = array(
                        'block_name' => $new_block_name,
                    );
                    tep_db_perform(TABLE_DESIGN_BOXES, $sql_data_array, 'update', " id = '" . $item['id'] . "'");
                    tep_db_perform(TABLE_DESIGN_BOXES_TMP, $sql_data_array, 'update', " id = '" . $item['id'] . "'");
                }
            }
        }

        $query = tep_db_query("select * from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($parent_theme) . "'");
        while ($item = tep_db_fetch_array($query)){
            if ($parent_theme_files == 'copy' && (
                    $item['setting_name'] == 'css' ||
                    $item['setting_name'] == 'javascript' ||
                    $item['setting_name'] == 'font_added'
            )) {

                foreach ($parents as $parentItem) {
                    $item['setting_value'] = str_replace($parentItem, $theme_name, $item['setting_value']);
                }
            }
            $sql_data_array = array(
                'theme_name' => $theme_name,
                'setting_group' => $item['setting_group'],
                'setting_name' => $item['setting_name'],
                'setting_value' => $item['setting_value'],
            );
            tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
            $newMediaId = tep_db_insert_id();

            if ($item['setting_name'] == 'media_query'){
                $visibilityStyleArray[$item['id']] = $newMediaId;
                foreach ($visibility_array as $settingsId => $oldMediaId) {
                    $vArr = Style::vArr($oldMediaId);

                    foreach ($vArr as $vKey => $omi) {
                        if ($omi > 10) {
                            if ($omi == $item['id']) {
                                $vArr[$vKey] = $newMediaId;
                                $sql_data_array = array();
                                $sql_data_array['visibility'] = Style::vStr($vArr);
                                tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, $sql_data_array, 'update', " id = '" . $settingsId . "'");
                                tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS_TMP, $sql_data_array, 'update', " id = '" . $settingsId . "'");
                            }
                        }
                    }
                }
            }
        }

        if ($parent_theme_files == 'copy') {
            $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($parent_theme) . "'");
        } else {
            $query = tep_db_query("select * from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($parent_theme) . "' and accessibility = '.b-bottom'");
        }
        while ($item = tep_db_fetch_array($query)) {
            $visibilityArray = explode(',', $item['visibility']);
            $newVisibilityArray = [];
            foreach ($visibilityArray as $v) {
                if ($visibilityStyleArray[$v]) {
                    $newVisibilityArray[] = $visibilityStyleArray[$v];
                } else {
                    $newVisibilityArray[] = $v;
                }
            }
            $item['visibility'] = implode(',', $newVisibilityArray);
            if ($parent_theme_files == 'copy') {
                $item['value'] = str_replace($parent_theme, $theme_name, $item['value']);
            }
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

        if ($parent_theme_files == 'copy') {

            foreach ($parents as $parentItem) {
                $path = \Yii::getAlias('@webroot');
                $path .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
                $parent_path = $path . 'themes' . DIRECTORY_SEPARATOR . $parentItem;
                $tpl = $path . 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
                $tpl_parent = $tpl . 'themes' . DIRECTORY_SEPARATOR . $parentItem;
                $tpl .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;
                $path .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;

                FileHelper::copyDirectory($parent_path, $path);
                FileHelper::copyDirectory($tpl_parent, $tpl);
            }
        } else {
            $path = \Yii::getAlias('@webroot');
            $path .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
            $screenshot = $path . 'themes' . DIRECTORY_SEPARATOR . $parent_theme . DIRECTORY_SEPARATOR . 'screenshot.png';
            $path .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;

            if (file_exists($screenshot)) {
                mkdir($path);
                copy($screenshot, $path . DIRECTORY_SEPARATOR . 'screenshot.png');
            }

        }
    }

    public static function themeRemove ($theme_name, $removeFiles = true)
    {
        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES . " where id = '" . (int)$item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS . " where box_id = '" . (int)$item['id'] . "'");
        }
        $query = tep_db_query("select id from " . TABLE_DESIGN_BOXES_TMP . " where theme_name = '" . tep_db_input($theme_name) . "'");
        while ($item = tep_db_fetch_array($query)){
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_TMP . " where id = '" . (int)$item['id'] . "'");
            tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_TMP . " where box_id = '" . (int)$item['id'] . "'");
        }

        tep_db_query("delete from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STYLES . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STYLES_CACHE . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STEPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_DESIGN_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_DESIGN_BOXES_SETTINGS_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_SETTINGS_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STYLES_BACKUPS . " where theme_name = '" . tep_db_input($theme_name) . "'");
        tep_db_query("delete from " . TABLE_THEMES_STYLES_CACHE . " where theme_name = '" . tep_db_input($theme_name) . "'");


        if ($removeFiles) {
            $count = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES . " where parent_theme = '" . tep_db_input($theme_name) . "'"));
            if ($count['total'] == 0) {
                $path = \Yii::getAlias('@site_root'.DIRECTORY_SEPARATOR);
                $pathLib = $path . 'lib' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
                $pathLib .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;
                $path .= 'themes' . DIRECTORY_SEPARATOR . $theme_name;
                FileHelper::removeDirectory($pathLib);
                FileHelper::removeDirectory($path);
            }
        }
    }

    public static function getThemeTitle ($theme_name)
    {
        static $themeTitle = [];

        if ( isset($themeTitle[$theme_name]) ) {
            return $themeTitle[$theme_name];
        }

        $mobile = false;
        if (strpos($theme_name, '-mobile')) {
            $theme_name = str_replace('-mobile', '', $theme_name);
            $mobile = true;
        }

        $query = tep_db_fetch_array(tep_db_query("select title from " . TABLE_THEMES . " where theme_name = '" . tep_db_input($theme_name) . "'"));

        $themeTitle[$theme_name] = $query['title'];

        if ($mobile && defined('TEXT_MOBILE')) {
            $themeTitle[$theme_name] .= ' (' . TEXT_MOBILE . ')';
        }

        return $themeTitle[$theme_name];
    }

    public static function useMobileTheme ($theme_name)
    {

        $theme = tep_db_fetch_array(tep_db_query("select setting_value from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_name = 'use_mobile_theme'"));
        if ($theme['setting_value']) {
            return true;
        }

        return false;
    }

    public static function getThemeName ($platform_id)
    {
        static $_cache = [];

        if ($_cache[$platform_id]) {
            return $_cache[$platform_id];
        }

        if ($platform_id) {
            $platform_config = new \common\classes\platform_config($platform_id);
            $platform_config->constant_up();
            if ($platform_config->isVirtual() || $platform_config->isMarketplace()) {
                $theme = tep_db_fetch_array(tep_db_query("select t.theme_name from platforms_to_themes AS p2t INNER JOIN themes as t ON (p2t.theme_id=t.id) where p2t.is_default = 1 and p2t.platform_id = " . (int)\common\classes\platform::defaultId()));
            } else {
                $theme = tep_db_fetch_array(tep_db_query("select t.theme_name from " . TABLE_THEMES . " t, " . TABLE_PLATFORMS_TO_THEMES . " p2t where p2t.is_default = 1 and t.id = p2t.theme_id and p2t.platform_id='" . $platform_id . "'"));
            }
        } else {
            $theme = tep_db_fetch_array(tep_db_query("select theme_name from " . TABLE_THEMES));
        }

        $_cache[$platform_id] = $theme['theme_name'];

        return $theme['theme_name'];
    }


    /**
     * copy image in theme image dir and save name in db, use only with save design/settings, it use POST data
     * @param string   $name image name in db, theme_settings.setting_name
     * @return string  path and filename saved in db, 'themes/themename/img/imagename.jpg'
     */
    public static function saveThemeImage ($name)
    {
        $post = Yii::$app->request->post();

        $imgPath = 'themes' . DIRECTORY_SEPARATOR . $post['theme_name'] . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;

        if ($post[$name . '_upload']) {

            $uploadedFile = Uploads::move($post[$name . '_upload'], $imgPath, true);

        } else {

            $oldFavicon = \common\models\ThemesSettings::find()->where([
                'theme_name' => $post['theme_name'],
                'setting_group' => 'hide',
                'setting_name' => $name,
            ])->one();

            if ($oldFavicon && $oldFavicon->setting_value == $post[$name]) {
                return $oldFavicon->setting_value;
            } elseif ($oldFavicon && $post[$name] == '') {
                $oldFavicon->delete();
            }

            $pos = strripos($post[$name], DIRECTORY_SEPARATOR);
            $fileName = strtolower(substr($post[$name], $pos+1));

            $uploadedFile = $imgPath . $fileName;

            @copy(DIR_FS_CATALOG . $post[$name], DIR_FS_CATALOG . $uploadedFile);
            @chmod(DIR_FS_CATALOG . $uploadedFile, 0777);

        }

        $imageMod = \common\models\ThemesSettings::find()->where([
            'theme_name' => $post['theme_name'],
            'setting_group' => 'hide',
            'setting_name' => $name,
        ])->one();

        if ($imageMod) {
            $imageMod->setting_value = $uploadedFile;
        } else {
            $imageMod = new \common\models\ThemesSettings();

            $imageMod->attributes = [
                'theme_name' => $post['theme_name'],
                'setting_group' => 'hide',
                'setting_name' => $name,
                'setting_value' => $uploadedFile,
            ];
        }
        $imageMod->save();

        return $uploadedFile;
    }


    public static function saveFavicon ()
    {
        $uploadedFile = self::saveThemeImage('favicon');

        if (!$uploadedFile) {
            return false;
        }
        $path = \Yii::getAlias('@webroot');
        $path .= DIRECTORY_SEPARATOR;
        $path .= '..';
        $path .= DIRECTORY_SEPARATOR;
        $path .= 'themes';
        $path .= DIRECTORY_SEPARATOR;
        $path .= $_GET['theme_name'];
        $path .= DIRECTORY_SEPARATOR;
        $theme = $path;
        $path .= 'icons';
        $path .= DIRECTORY_SEPARATOR;

        if (!file_exists($path)) {
            if (!file_exists($theme)) {
                mkdir($theme);
            }
            mkdir($path);
        }

        if (!is_file(DIR_FS_CATALOG . $uploadedFile)) {
            return false;
        }

        $info = getimagesize(DIR_FS_CATALOG . $uploadedFile);
        $mime = $info['mime'];

        if ($mime == 'image/jpeg'){
            $im = imagecreatefromjpeg(DIR_FS_CATALOG . $uploadedFile);
        } elseif ($mime == 'image/png'){
            $im = imagecreatefrompng(DIR_FS_CATALOG . $uploadedFile);
        } elseif ($mime == 'image/gif'){
            $im = imagecreatefromgif(DIR_FS_CATALOG . $uploadedFile);
        }
        if (!$im) {
            return false;
        }
        $w = imagesx($im);
        $h = imagesy($im);

        $icons = [
            ['size' => 57, 'name' => 'apple-icon-57x57.png'],
            ['size' => 60, 'name' => 'apple-icon-60x60.png'],
            ['size' => 72, 'name' => 'apple-icon-72x72.png'],
            ['size' => 76, 'name' => 'apple-icon-76x76.png'],
            ['size' => 114, 'name' => 'apple-icon-114x114.png'],
            ['size' => 120, 'name' => 'apple-icon-120x120.png'],
            ['size' => 144, 'name' => 'apple-icon-144x144.png'],
            ['size' => 152, 'name' => 'apple-icon-152x152.png'],
            ['size' => 180, 'name' => 'apple-icon-180x180.png'],
            ['size' => 192, 'name' => 'android-icon-192x192.png'],
            ['size' => 32, 'name' => 'favicon-32x32.png'],
            ['size' => 96, 'name' => 'favicon-96x96.png'],
            ['size' => 16, 'name' => 'favicon-16x16.png'],
            ['size' => 16, 'name' => 'favicon.ico'],
            ['size' => 144, 'name' => 'ms-icon-144x144.png'],
            ['size' => 36, 'name' => 'android-icon-36x36.png'],
            ['size' => 48, 'name' => 'android-icon-48x48.png'],
            ['size' => 72, 'name' => 'android-icon-72x72.png'],
            ['size' => 96, 'name' => 'android-icon-96x96.png'],
            ['size' => 144, 'name' => 'android-icon-144x144.png'],
            ['size' => 192, 'name' => 'android-icon-192x192.png'],
        ];

        foreach ($icons as $icon){
            $l = $icon['size'];
            if ($w > $h){
                $left = 0 - (($l * ($w/$h)) - $l) / 2;
                $top = 0;
                $width = $l * ($w/$h);
                $height = $l;
            } else {
                $left = 0;
                $top = 0 - (($l * ($h/$w)) - $l) / 2;
                $width = $l;
                $height = $l * ($h/$w);
            }
            $im1 = imagecreatetruecolor($l, $l);
            imagealphablending($im1, false);
            imagesavealpha($im1, true);
            imagecopyresampled($im1, $im, $left, $top, 0, 0, $width, $height, $w, $h);
            imagepng($im1, $path . $icon['name']);
            imagedestroy($im1);
        }

        imagedestroy($im);

        return true;
    }

    public static function savePageSettings($post)
    {
        $theme_name = tep_db_prepare_input($post['theme_name']);
        $page_name = tep_db_prepare_input($post['page_name']);

        if (is_array($post['added_page_settings']))
        foreach ($post['added_page_settings'] as $setting => $value){

            $count = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_name) . "' and (setting_value = '" . tep_db_input($setting) . "' or setting_value like '" . tep_db_input($setting) . ":%')"));
            if ($value) {
                $sql_data_array = array(
                    'theme_name' => $theme_name,
                    'setting_group' => 'added_page_settings',
                    'setting_name' => $page_name,
                    'setting_value' => $value == 'on' ? $setting : $setting . ':' . $value
                );
                if ($count['total']){
                    tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array, 'update', "theme_name = '" . $theme_name . "' and 	setting_group = 'added_page_settings' and	setting_name='" . $page_name . "'");
                } else {
                    tep_db_perform(TABLE_THEMES_SETTINGS, $sql_data_array);
                }
            } elseif ($count['total'] > 0) {
                tep_db_query("delete from " . TABLE_THEMES_SETTINGS . " where theme_name = '" . tep_db_input($theme_name) . "' and setting_group = 'added_page_settings' and setting_name = '" . tep_db_input($page_name) . "' and (setting_value = '" . tep_db_input($setting) . "' or setting_value like '" . tep_db_input($setting) . ":%')");
            }

        }
    }

    public static function getThemePages($page_name, $theme_name)
    {
        $settings = \common\models\ThemesSettings::find()->where([
            'theme_name' => $theme_name,
            'setting_group' => 'added_page',
            'setting_name' => $page_name,
        ])->asArray()->all();

        $pages = [];
        foreach ($settings as $setting) {
            $pages[] = $setting['setting_value'];
        }

        return $pages;
    }
}

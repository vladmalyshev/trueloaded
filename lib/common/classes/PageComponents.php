<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes;

use Yii;

class PageComponents {

    public static function addComponents($text)
    {
        if ( !empty($text) && strpos($text,'##COMPONENT%')!==false ) {
            $text = preg_replace_callback("/\#\#COMPONENT\%([^\#^\%]+)[\%]{0,1}([^\#]{0,})##/", "self::addComponent", $text);
        }

        return $text;
    }

    private static function addComponent($matches)
    {
        $findPage = \common\models\ThemesSettings::findOne([
            'theme_name' => THEME_NAME,
            'setting_group' => 'added_page',
            'setting_value' => $matches[1],
        ]);

        if (!$findPage) {
            return '';
        }

        $params = [];
        if ($matches[2]) {
            $arr = explode('=', $matches[2]);
            $params = [
                $arr[0] => $arr[1]
            ];
        }

        return \frontend\design\Block::widget([
            'name' => \common\classes\design::pageName($matches[1]),
            'params' => [
                'params' => $params
            ],
        ]);
    }

    public static function componentTemplates()
    {
        $platform_id = Yii::$app->request->get('platform_id');

        $platform = \common\models\PlatformsToThemes::findOne($platform_id);
        $theme = \common\models\Themes::findOne($platform['theme_id']);

        $templatesQuery = \common\models\ThemesSettings::find()
            ->where([
                'theme_name' => $theme['theme_name'],
                'setting_group' => 'added_page',
            ])
            ->asArray()
            ->all();

        $templates = [];

        foreach ($templatesQuery as $template) {
            if ($template['setting_name'] == 'components') {
                $templates[$template['theme_name']][$template['setting_name']][\common\classes\design::pageName($template['setting_value'])] = $template['setting_value'];
            }
        }
        foreach ($templatesQuery as $template) {
            if ($template['setting_name'] != 'components') {
                $templates[$template['theme_name']][$template['setting_name']][\common\classes\design::pageName($template['setting_value'])] = $template['setting_value'];
            }
        }

        return $templates;
    }

}
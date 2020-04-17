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

use Yii;
use common\models\ThemesStyles;

class Data
{
    public static $jsGlobalData = [];

    public static function mainData(){
        self::addJsData(['tr' => [
            'TEXT_ENTERED_CHARACTERS' => TEXT_ENTERED_CHARACTERS,
            'TEXT_LEFT_CHARACTERS' => TEXT_LEFT_CHARACTERS,
            'TEXT_OVERFLOW_CHARACTERS' => TEXT_OVERFLOW_CHARACTERS,
        ], 'config' => [
            'META_TITLE_MAX_TAG_LENGTH' => META_TITLE_MAX_TAG_LENGTH,
            'META_DESCRIPTION_TAG_LENGTH' => META_DESCRIPTION_TAG_LENGTH
        ]]);
    }

    public static function addJsData($arr = []){
        self::$jsGlobalData = \yii\helpers\ArrayHelper::merge(self::$jsGlobalData, $arr);
    }
}
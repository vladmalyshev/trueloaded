<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

use Yii;

class PropertiesTypes {

    public static function getTypes($mode = 'all') {

        \common\helpers\Translation::init('admin/properties');

        if ($mode == 'search') {
            return [
                'text' => TEXT_TEXT,
                'number' => TEXT_NUMBER,
                'interval' => TEXT_NUMBER_INTERVAL,
                'flag' => TEXT_PR_FLAG,
            ];
        } else if ($mode == 'filter') {
            return [
                'text' => TEXT_TEXT,
                'number' => TEXT_NUMBER,
                'interval' => TEXT_NUMBER_INTERVAL,
                'flag' => TEXT_PR_FLAG,
                'file' => TEXT_PR_FILE
            ];
        } else {//all
            return [
                'text' => TEXT_TEXT,
                'number' => TEXT_NUMBER,
                'interval' => TEXT_NUMBER_INTERVAL,
                'flag' => TEXT_PR_FLAG,
                'file' => TEXT_PR_FILE
            ];
        }
    }

}

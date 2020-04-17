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

class AdminBox
{

    public static function buildNavigation($lastElement = '')
    {
        $path = [];
        $queryResponse = \common\models\AdminBoxes::findOne(['title' => $lastElement]); 
        if (is_object($queryResponse)) {
            $box_id = $queryResponse->box_id;
            do {
                $queryResponse = \common\models\AdminBoxes::findOne(['box_id' => $box_id]); 
                if (is_object($queryResponse)) {
                    $path[] = $queryResponse->title;
                    $box_id = $queryResponse->parent_id;
                } else {
                    $box_id = 0;
                }
            } while ($box_id > 0);
            
            
        }
        $path = array_reverse($path);
        return $path;
    }
}

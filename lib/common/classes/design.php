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

class design {

    public static function pageName($title){

        $page_name = strtolower($title);
        $page_name = str_replace(' ', '_', $page_name);
        return preg_replace('/[^a-z0-9_-]/', '', $page_name);
    }
}
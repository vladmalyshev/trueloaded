<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

$vendorDir = dirname(__FILE__);
spl_autoload_register(function($class) use ($vendorDir) {    
    if (strpos($class, 'subdee') !== false){
        $ex = explode("\\", $class);
        unset($ex[0]);
        unset($ex[1]);
        if (count($ex)){
            $file = implode(DIRECTORY_SEPARATOR , $ex);
            if (file_exists($vendorDir . DIRECTORY_SEPARATOR . $file . '.php')){
                require_once($vendorDir . DIRECTORY_SEPARATOR .$file . '.php');
            }
        }
    }
});
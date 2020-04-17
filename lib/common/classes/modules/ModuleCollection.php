<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

abstract class ModuleCollection {
   
    protected function getAllModules(){
        return $this->include_modules;
    }
    
    public function getModule($class){
        $modules = $this->getAllModules();
        return is_object($modules[$class]) ? $modules[$class] : false;
    }
   
}

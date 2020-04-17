<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google;

use Yii;

class Providers {

    public $hasConfigFile = false;
    
    public function getClassName(){
        return (new \ReflectionClass($this))->getShortName();
    }
}

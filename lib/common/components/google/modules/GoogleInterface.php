<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\modules;

interface GoogleInterface {

    public function getParams(); //config

    public function render(); // render in admin

    public function loaded(array $params); //loaded post config before save

    public function renderWidget(); // render at frontend
}

<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models;

use Yii;
/*
* NotificationInterface used for admin messages different types
*/

interface NotificationInterface {
 
    public function prepareAdminMessage($message = null);
    
    public function getAdminMessage($message = null);
    
}
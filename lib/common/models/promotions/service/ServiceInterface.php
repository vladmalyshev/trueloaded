<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions\service;

interface ServiceInterface {
        
    public function getDescription();
   
    public function getSettingsTemplate();
       
    public function load($vars);
    
    public function savePromotions();
    
    public function hasConditions();    
    
    public function getPromotionInfo($promo_id);
}

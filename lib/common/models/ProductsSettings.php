<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class ProductsSettings extends ActiveRecord
{
    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_settings';
    }
    
    public function saveSettings($products_id, array $settings){
        $isNew = false;
        if ($this->isNewRecord){
            $isNew = true;
            $this->setOldAttributes($this->getAttributes());
        }
        
        foreach($settings as $field => $setting){
            if ($this->hasAttribute($field)){
                $this->setAttribute($field, $setting);
            }
        }
        
        if ($this->getDirtyAttributes()){
            $this->setAttribute('products_id', intval($products_id));
            if ($isNew){
                $this->insert(false);
            } else {
                $this->update();
            }
        }
    }
}
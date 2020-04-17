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
use common\models\GoogleKeywordProducts;

class GoogleKeywords extends ActiveRecord {
    
    public static function tableName() {
        return 'gapi_search';
    }
    
    public static function primaryKey() {
        return ['gapi_id'];
    }
    
    public function getProducts() {
        return $this->hasMany(GoogleKeywordProducts::className(), ['gapi_id' => 'gapi_id'])->orderBy('sort');
    }
    
    public function rules() {
        return [
          ['gapi_keyword', 'trim'],
          ['gapi_keyword', 'unique'],
          ['gapi_keyword', 'string', 'length' => [2, 50]],          
        ];
    }
    
    public function afterDelete() {        
        GoogleKeywordProducts::deleteAll(['gapi_id' => $this->getAttribute('gapi_id')]);
        parent::afterDelete();        
    }
}
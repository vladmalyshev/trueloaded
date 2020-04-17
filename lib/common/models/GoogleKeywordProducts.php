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

class GoogleKeywordProducts extends ActiveRecord {

    private $data = [];

    /* private $gapi_id;
      private $products_id;
      private $sort; */

    public static function tableName() {
        return TABLE_GAPI_SEARCH_TO_PRODUCTS;
    }

    public static function primaryKey() {
        return ['gapi_id', 'products_id'];
    }

    public function rules() {
        return [
            [['gapi_id', 'products_id', 'sort'], 'default'],
        ];
    }

    public function setAttribute($name, $value) {
        if (!array_key_exists($name, $this->attributes)) {
            $this->data[$name] = $value;
        } else {
            parent::setAttribute($name, $value);
        }
    }

    public function getAttribute($name) {

        if (!array_key_exists($name, $this->attributes)) {
            return $this->data[$name];
        } else {
            return parent::getAttribute($name);
        }
    }
   
}

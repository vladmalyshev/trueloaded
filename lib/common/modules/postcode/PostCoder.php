<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\postcode;

class PostCoder extends \yii\base\Widget {
    
    public $model;
    public $callback;
    public $key;
    public $maxAllowed;
    public $searchType;
    
    public function init() {
        parent::init();
    }
    
    public function run() {
        return $this->render('postcoder', [
            'model' => $this->model,
            'callback' => $this->callback,
            'key' => $this->key,
            'maxAllowed' => $this->maxAllowed,
            'pc_cookie' => PostcodeTool::$cookieName,
            'searchType' => $this->searchType,
        ]);
    }
}

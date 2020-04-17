<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\widgets;

class SupplierProductEdit extends \yii\base\Widget {

    public $service;
    public $baseUrl;
    public $objName;
    
    public function init() {
        parent::init();
        if (empty($this->objName)){
            throw new Exception('object name in service is not defined');
        }
    }

    public function run() {
        
        $product = $this->service->get($this->objName);       
        
        return $this->render('supplier-product-edit',[
            'service' => $this->service,
            'currencies' => $this->service->get('currencies'),
            'sProduct' => $product,
            'path' => $this->getPath() . \Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id,
            
        ]);
    }
    
    public function getPath(){
        return (!is_null($this->baseUrl)?$this->baseUrl. '/':'');
    }

}

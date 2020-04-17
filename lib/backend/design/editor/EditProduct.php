<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\editor;

use Yii;
use yii\base\Widget;
use common\models\Products;

class EditProduct extends Widget {

    public $manager;
    public $uprid;

    public function init() {
        parent::init();
    }
    
    public function run() {

        $insulator = new \backend\services\ProductInsulatorService($this->uprid, $this->manager);
        $insulator->edit = true;
        $productDetails = $insulator->getProductMainDetails();
        return $this->render('edit-product',[
            'manager' => $this->manager,
            'product' => $productDetails,
            'rates' => \common\helpers\Tax::getOrderTaxRates(),
            'queryParams' => array_merge(['editor/show-basket'], Yii::$app->request->getQueryParams()),
        ]);
    }

}

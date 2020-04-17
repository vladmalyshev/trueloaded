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

class Account extends Widget {
    
    public $manager;
    public $contactForm;
    public $shippingForm;
    public $billingForm;

    public function init(){
        parent::init();
    }
    
    public function run(){
        
        
        return $this->render('account', [
            'manager' => $this->manager,
            'contact' => $this->contactForm,
            'shipping' => $this->shippingForm,
            'billing' => $this->billingForm,
            'showGroup' => (CUSTOMERS_GROUPS_ENABLE == 'True'),
            'platforms' => \yii\helpers\ArrayHelper::map(\common\classes\platform::getList(false), 'id', 'text'),
            'groups' => \yii\helpers\ArrayHelper::map(\common\models\Groups::find()->all(), 'groups_id', 'groups_name'),
            'url' => \yii\helpers\Url::to(array_merge(['editor/create-account'], Yii::$app->request->getQueryParams())),
        ]);
    }
}

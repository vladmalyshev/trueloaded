<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use common\models\Customers;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\forms\registration\CustomerRegistration;

class Account extends Widget
{
    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        //global $customer_id;

        $customer = null;
        if (!Yii::$app->user->isGuest) {
            $customer = Yii::$app->user->getIdentity();
        }
        $authContainer = new \frontend\forms\registration\AuthContainer();
        
        $isReseller = false;
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        if (\common\helpers\Customer::check_customer_groups($customer_groups_id, 'groups_is_reseller')) {
            $isReseller = true;
        }
        return IncludeTpl::widget(['file' => 'boxes/account.tpl', 'params' => [
            'customerData' => $customer,
            'customerLogged' => !Yii::$app->user->isGuest,
            'isReseller' => $isReseller,
            'settings' => $this->settings,
            'params' =>  ['enterModels' => $authContainer->getForms('account/login-box'), 'action' => tep_href_link('account/login', 'action=process', 'SSL'),]
        ]]);
    }
}

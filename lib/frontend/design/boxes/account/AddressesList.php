<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\CustomersAdditionalFields;

class AddressesList extends Widget
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
        if (Yii::$app->user->isGuest && !$this->params['customers_id']) {
            return '';
        }
        if ($this->params['customers_id']) {
            $customersId = $this->params['customers_id'];
        } else {
            $customersId = Yii::$app->user->id;
        }

        $addressArray = [];

        $addresses = \common\helpers\Customer::get_address_book_data($customersId);

        foreach ($addresses as $key => $item){
            $addresses[$key]['address'] = \common\helpers\Address::address_format(\common\helpers\Address::get_address_format_id($item['country_id']), $item, 1, ' ', ',');

        }

        $customer = \common\models\Customers::find()
            ->select(['customers_firstname', 'customers_lastname', 'platform_id', 'customers_email_address', 'customers_telephone', 'customers_company', 'customers_default_address_id'])
            ->andWhere(['customers_id' => (int)$customersId])
            ->one();

        return IncludeTpl::widget(['file' => 'boxes/account/addresses-list.tpl', 'params' => [
            'settings' => $this->settings,
            'addressArray' => $addresses,
            'id' => $this->id,
            'customer' => $customer,
        ]]);
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\DeliveryLocation;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class LocationProducts extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $use_product_set_id = 0;

    public function init()
    {
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            $this->use_product_set_id = Yii::$app->controller->deliveryLocationData['use_product_set_id'];
        }


        parent::init();
    }

    public function run()
    {
        if ( empty($this->use_product_set_id ) ) return '';

        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        $languages_id = \Yii::$app->settings->get('languages_id');

        if ($this->settings[0]['params']) {
            $max = $this->settings[0]['params'];
        } else {
            $max = 100;
        }

        $products2c_join = '';
        if ( \common\classes\platform::activeId() ) {
            $products2c_join .=
                " inner join " . TABLE_PLATFORMS_PRODUCTS . " plp on p.products_id = plp.products_id  and plp.platform_id = '" . \common\classes\platform::currentId() . "' ".
                " inner join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id ".
                " inner join " . TABLE_PLATFORMS_CATEGORIES . " plc ON plc.categories_id=p2c.categories_id AND plc.platform_id = '" . \common\classes\platform::currentId() . "' ";
        }
        $products2c_join .= " INNER JOIN ".TABLE_SEO_DELIVERY_LOCATION_PRODUCTS." dps ON dps.products_id=p.products_id AND dps.delivery_product_group_id='".(int)$this->use_product_set_id."' ";

        $common_columns = 'p.order_quantity_minimal, p.order_quantity_step, p.stock_indication_id, ';

        if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
            $products_query = tep_db_query("select distinct p.products_id, {$common_columns} p.is_virtual, p.products_image, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description_short), pd1.products_description_short, pd.products_description_short) as products_description_short, p.products_tax_class_id, p.products_price, p.products_quantity, p.products_model from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p {$products2c_join} " . " left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id='" . (int)$languages_id . "' and pd1.platform_id = '" . (int)Yii::$app->get('platform')->config()->getPlatformToDescription() . "' left join " . TABLE_PRODUCTS_PRICES . " pp on p.products_id = pp.products_id and pp.groups_id = '" . (int)$customer_groups_id . "' and pp.currencies_id = '" . (USE_MARKET_PRICES == 'True' ? (int)\Yii::$app->settings->get('currency_id') : '0') . "' where p.products_status = 1 " . \common\helpers\Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . " and if(pp.products_group_price is null, 1, pp.products_group_price != -1 )  " . "  and pd.language_id = '" . (int)$languages_id . "' and pd.products_id = p.products_id and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' order by dps.sort_order, pd.products_name limit " . $max);
        } else {
            $products_query = tep_db_query("select distinct p.products_id, {$common_columns} p.is_virtual, p.products_image, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, if(length(pd1.products_description_short), pd1.products_description_short, pd.products_description_short) as products_description_short, p.products_tax_class_id, p.products_price, p.products_quantity, p.products_model from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p {$products2c_join} " . " left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id='" . (int)$languages_id . "' and pd1.platform_id = '" . (int)Yii::$app->get('platform')->config()->getPlatformToDescription() . "' where p.products_status = 1 " . \common\helpers\Product::get_sql_product_restrictions(array('p', 'pd', 's', 'sp', 'pp')) . "  " . "  and pd.language_id = '" . (int)$languages_id . "' and pd.products_id = p.products_id and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' order by dps.sort_order, pd.products_name limit " . $max);
        }

        if (tep_db_num_rows($products_query) > 0){

            return IncludeTpl::widget([
                'file' => 'boxes/delivery-location/location-products.tpl',
                'params' => [
                    'products' => Info::getProducts($products_query, $this->settings),
                    'settings' => $this->settings,
                    'languages_id' => $languages_id
                ]
            ]);

        }
        return '';
    }
}
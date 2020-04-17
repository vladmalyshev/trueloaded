<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;


use backend\models\EP\Tools;
use common\api\models\Soap\SoapModel;
use common\api\models\Soap\Products\SalePriceInfo;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;
use yii\db\Expression;

/**
 * Class PriceInfo
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="price" type="xsd:float"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="discount_table" type="tns:ArrayOfQuantityDiscountPrice"/>
 * @soap-wsdl <xsd:element name="products_price_full" type="xsd:boolean"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="attributes_prices" type="tns:ArrayOfAttributesPrices"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="inventory_prices" type="tns:ArrayOfInventoryPrices"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="pack" type="tns:PackPriceInfo"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="pallet" type="tns:PalletPriceInfo"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="true" name="sale_price" type="tns:SalePriceInfo"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="customer_groups_prices" type="tns:ArrayOfCustomerGroupProductPrices"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="currency" type="xsd:string" use="required"/>
 */
class PriceInfo extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $currency;

    /**
     * @var float
     * @soap
     */
    public $price;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfQuantityDiscountPrice Array of QuantityDiscountPrice {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $discount_table;

    /**
     * @var bool
     * @soap
     */
    public $products_price_full = false;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfAttributesPrices Array of ArrayOfAttributesPrices {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $attributes_prices;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfInventoryPrices Array of ArrayOfInventoryPrices {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $inventory_prices;

    /**
     * @var \common\api\models\Soap\Products\PackPriceInfo {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $pack;

    /**
     * @var \common\api\models\Soap\Products\PalletPriceInfo {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $pallet;

    /**
     * @var \common\api\models\Soap\Products\SalePriceInfo {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $sale_price;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfCustomerGroupProductPrices Array of CustomerGroupProductPrices {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $customer_groups_prices;

    public function __construct(array $config = [])
    {
        $this->currency = \common\helpers\Currencies::systemCurrencyCode();
        if ( isset($config['currencies_id']) && !empty($config['currencies_id']) ) {
            //$this->currency = $config['currencies_id'];
        }
        if ( !isset($config['groups_id']) ) {
            $config['groups_id'] = 0;
        }

        if ($config['products_price_full'] && ($config['have_attributes'] && (defined('PRODUCTS_INVENTORY') && PRODUCTS_INVENTORY == 'True') && strpos($config['products_id'], '{') === false)) {
            $this->inventory_prices = new ArrayOfInventoryPrices();
            if ((defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') || $config['groups_id']) {
                $get_inventory_prices_r = tep_db_query(
                    "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                    (
                    $config['products_price_full'] ?
                        " ip.inventory_full_price AS price, " .
                        " ip.inventory_discount_full_price AS discount_table, "
                        :
                        " ip.inventory_group_price AS price, " .
                        " ip.inventory_group_discount_price AS discount_table, "
                    ) .
                    " " . (int)$config['products_price_full'] . " AS products_price_full, " .
                    " 0 AS have_attributes " .
                    "FROM " . TABLE_INVENTORY . " i " .
                    " INNER JOIN ".TABLE_INVENTORY_PRICES." ip ON ip.inventory_id=i.inventory_id AND ip.groups_id='".(int)$config['groups_id']."' AND ip.currencies_id='".(int)$config['currencies_id']."' ".
                    "WHERE i.prid='" . (int)$config['prid'] . "'"
                );
            }else{
                $get_inventory_prices_r = tep_db_query(
                    "SELECT DISTINCT i.products_id AS products_id, i.prid AS prid, " .
                    (
                    $config['products_price_full'] ?
                        " i.inventory_full_price AS price, " .
                        " i.inventory_discount_full_price AS discount_table, "
                        :
                        " i.inventory_price AS price, " .
                        " i.inventory_discount_price AS discount_table, "
                    ) .
                    " " . (int)$config['products_price_full'] . " AS products_price_full, " .
                    " 0 AS have_attributes " .
                    "FROM " . TABLE_INVENTORY . " i " .
                    "WHERE i.prid='" . (int)$config['prid'] . "'"
                );
            }
            if (tep_db_num_rows($get_inventory_prices_r)) {
                while ($_inventory_price = tep_db_fetch_array($get_inventory_prices_r)) {
                    $_inventory_price['isProductOwner'] = (isset($config['isProductOwner']) && $config['isProductOwner']);
                    $this->inventory_prices->inventory_price[] = new InventoryPrice($_inventory_price);
                }
            }
        }

        if (isset($config['discount_table'])) {
            if (!empty($config['discount_table'])) {
                $this->discount_table = ArrayOfQuantityDiscountPrice::createFromString($config['discount_table'], (isset($config['isProductOwner']) && $config['isProductOwner']));
            }
            unset($config['discount_table']);
        }

        if (!$config['products_price_full'] && ($config['have_attributes'] || strpos($config['products_id'], '{') !== false)) {
            $lang_id = \common\classes\language::defaultId();
            $this->attributes_prices = new ArrayOfAttributesPrices();
            $sql_where = '';
            if (strpos($config['products_id'], '{') !== false) {
                $where_attribute_pair = [];
                preg_match_all('/{(\d+)}(\d+)/', $config['products_id'], $matches);
                foreach ($matches[1] as $idx => $optId) {
                    $where_attribute_pair[$optId] = $optId . '-' . $matches[2][$idx];
                }
                $sql_where .=
                    "AND pa.options_id IN('" . implode("','", array_keys($where_attribute_pair)) . "') " .
                    "AND CONCAT(pa.options_id,'-',pa.options_values_id) IN ('" . implode("','", array_values($where_attribute_pair)) . "') ";
            }

            if ((defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') || $config['groups_id']) {
                $get_attributes_r = tep_db_query(
                    "SELECT pa.products_attributes_id, " .
                    " pa.options_id AS option_id, pa.options_values_id AS option_value_id, " .
                    " pa.price_prefix, ".
                    " IFNULL(pap.attributes_group_price,0) AS options_values_price, " .
                    " IFNULL(pap.attributes_group_discount_price,'') AS discount_table " .
                    "FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa " .
                    " LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES_PRICES." pap ON pap.products_attributes_id=pa.products_attributes_id AND pap.groups_id='".(int)$config['groups_id']."' AND pap.currencies_id='".(int)$config['currencies_id']."' ".
                    "WHERE pa.products_id='" . (int)$config['prid'] . "' " .
                    " {$sql_where}" .
                    "ORDER BY pa.options_id, pa.products_options_sort_order, pa.options_values_id"
                );
            }else{
                $get_attributes_r = tep_db_query(
                    "SELECT pa.products_attributes_id, " .
                    " pa.options_id AS option_id, pa.options_values_id AS option_value_id, " .
                    " pa.price_prefix, ".
                    " pa.options_values_price, " .
                    " pa.products_attributes_discount_price AS discount_table " .
                    "FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa " .
                    "WHERE pa.products_id='" . (int)$config['prid'] . "' " .
                    " {$sql_where}" .
                    "ORDER BY pa.options_id, pa.products_options_sort_order, pa.options_values_id"
                );
            }

            while ($_attribute = tep_db_fetch_array($get_attributes_r)) {
                $_attribute['option_name'] = Tools::getInstance()->get_option_name($_attribute['option_id'], $lang_id);
                $_attribute['option_value_name'] = Tools::getInstance()->get_option_value_name($_attribute['option_value_id'], $lang_id);
                $_attribute['price_prefix'] = ($_attribute['price_prefix'] == '-' ? $_attribute['price_prefix'] : '+');
                $_attribute['price'] = $_attribute['options_values_price'];
                $_attribute['isProductOwner'] = (isset($config['isProductOwner']) && $config['isProductOwner'])?true:false;
                $this->attributes_prices->attribute_price[] = new AttributePrice($_attribute);
            }
        }
        if ( !empty($config['pack_unit']) || ($config['products_price_pack_unit']>0 || !empty($config['products_price_discount_pack_unit'])) ) {
            $this->pack = new PackPriceInfo([
                'products_qty' => $config['pack_unit'],
                'price' => $config['products_price_pack_unit'],
                'discount_table' => $config['products_price_discount_pack_unit'],
                'isProductOwner' => (isset($config['isProductOwner']) && $config['isProductOwner'])?true:false,
            ]);
        }
        if ( !empty($config['packaging']) || ($config['products_price_packaging']>0 || !empty($config['products_price_discount_packaging'])) ) {
            $this->pallet = new PalletPriceInfo([
                'pack_qty' => $config['packaging'],
                'price' => $config['products_price_packaging'],
                'discount_table' => $config['products_price_discount_packaging'],
                'isProductOwner' => (isset($config['isProductOwner']) && $config['isProductOwner'])?true:false,
            ]);
        }

        if ( !$config['sale_price'] ){
            $saleDataQuery = \common\models\Specials::find()
                ->alias('s')
                ->where(['s.products_id'=>(int)$config['prid']]);
            if ((defined('USE_MARKET_PRICES') && USE_MARKET_PRICES=='True') || $config['groups_id']) {
                $saleDataQuery->join(
                    'left join',
                    \common\models\SpecialsPrices::tableName().' sp',
                    "sp.specials_id=s.specials_id AND sp.groups_id='".(int)$config['groups_id']."' AND sp.currencies_id='".(int)$config['currencies_id']."'"
                );
                $saleDataQuery->select([
                    'specials_new_products_price' => new Expression('IFNULL(sp.specials_new_products_price, s.specials_new_products_price)'),
                    //'expires_date' => ,
                    'status' => new Expression('IF(sp.specials_new_products_price=-1,0,1)'),
                ]);
            }else{
                $saleDataQuery->select(['s.specials_new_products_price', 's.expires_date', 's.start_date', 's.status']);
            }
            $saleDataQuery->andWhere(['OR',['s.status'=>1], ['>=', 's.start_date', new Expression('NOW()')]]);
            $saleDataQuery->orderBy(['s.status'=>SORT_DESC, 's.start_date'=>SORT_ASC]);
            $sale_data = $saleDataQuery->asArray()->one();

            if ( $sale_data ){
                $this->sale_price = new SalePriceInfo($sale_data);
            }
        }

        if ( $config['price']==-2 ) $config['price'] = null;
        if ( isset($config['price']) && $config['price']>0 && (!isset($config['isProductOwner']) || !$config['isProductOwner']) ) {
            $config['price'] = SoapHelper::applyOutgoingPriceFormula($config['price']);
        }

        if (ServerSession::get()->acl()->siteAccessPermission()){
            $this->customer_groups_prices = $this->getCustomerGroupsPrices((int)$config['prid'], $config['currencies_id'], isset($config['isProductOwner']) && $config['isProductOwner'] );
        }

        parent::__construct($config);

    }

    protected function getCustomerGroupsPrices($prid, $currencies_id, $isProductOwner )
    {
        return ArrayOfCustomerGroupProductPrices::forProduct((int)$prid, $currencies_id, $isProductOwner);
    }

}
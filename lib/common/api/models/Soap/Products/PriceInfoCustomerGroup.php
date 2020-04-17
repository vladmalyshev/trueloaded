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

/**
 * Class PriceInfo
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="price" nillable="true" type="xsd:float"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="discount_table" type="tns:ArrayOfQuantityDiscountPrice"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="attributes_prices" type="tns:ArrayOfAttributesPrices"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="inventory_prices" type="tns:ArrayOfInventoryPrices"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="pack" type="tns:PackPriceInfo"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="pallet" type="tns:PalletPriceInfo"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="true" name="sale_price" type="tns:SalePriceInfo"/>
 * @soap-wsdl </xsd:sequence>
 * @--soap-wsdl <xsd:attribute name="currency" type="xsd:string" use="required"/>
 * @soap-wsdl <xsd:attribute name="groups_id" type="xsd:integer" />
 * @soap-wsdl <xsd:attribute name="groups_name" type="xsd:string" />
 */

class PriceInfoCustomerGroup extends PriceInfo
{

    /**
     * @var string
     * @--soap
     */
    public $currency;

    /**
     * @var integer
     * @soap
     */
    public $groups_id;

    /**
     * @var string
     * @soap
     */
    public $groups_name;

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


    protected function getCustomerGroupsPrices($prid, $currencies_id, $isProductOwner)
    {
        return null;
    }
}
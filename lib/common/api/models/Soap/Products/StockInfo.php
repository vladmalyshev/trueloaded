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


use common\api\models\Soap\SoapModel;
use common\models\Suppliers;
use common\models\SuppliersProducts;
use common\models\WarehousesProducts;

/**
 * Class StockInfo
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="products_model" type="xsd:string" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="quantity" type="xsd:integer"/>
 * @soap-wsdl <xsd:element name="allocated_quantity" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="stock_indication_id" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="stock_indication_text" type="xsd:string" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="stock_delivery_terms_id" type="xsd:integer" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="stock_delivery_terms_text" type="xsd:string" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl <xsd:element name="warehouses_stock" type="tns:ArrayOfWarehouseStock" minOccurs="0" maxOccurs="1"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="products_id" type="xsd:string" use="required"/>
 * </xsd:sequence>
 */
class StockInfo extends SoapModel
{

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_id;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_model;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $quantity = 0;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $allocated_quantity = 0;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $stock_indication_id = 0;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $stock_indication_text = '';

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $stock_delivery_terms_id = 0;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $stock_delivery_terms_text = '';

    /**
     * @var \common\api\models\Soap\Products\ArrayOfWarehouseStock  {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $warehouses_stock;

    public function __construct(array $config = [])
    {
        if ( array_key_exists('products_quantity',$config) ) {
            $config['quantity'] = $config['products_quantity'];
        }
        if ( array_key_exists('stock_indication_id',$config) && empty($config['stock_indication_id']) ) {
            foreach( \common\classes\StockIndication::get_variants(false) as $variant ) {
                if ($variant['is_default']) {
                    $config['stock_indication_id'] = $variant['id'];
                    $config['stock_indication_text'] = $variant['text'];
                    break;
                }
            }
        }
        if ( array_key_exists('stock_delivery_terms_id',$config) && empty($config['stock_delivery_terms_id']) ) {
            foreach( \common\classes\StockIndication::get_delivery_terms(false) as $variant ) {
                if ($variant['is_default']) {
                    $config['stock_delivery_terms_id'] = $variant['id'];
                    $config['stock_delivery_terms_text'] = $variant['text'];
                    break;
                }
            }
        }
        $this->allocated_quantity = intval(\common\helpers\Product::get_allocated_stock_quantity($config['products_id']));

        $warehouses_stock = WarehousesProducts::find()
            ->where(['prid'=>isset($config['prid'])?$config['prid']:intval($config['products_id']), 'products_id'=>strval($config['products_id'])])
            ->asArray()
            ->all();
        if ( count($warehouses_stock)>0 ) {
            $this->warehouses_stock = new ArrayOfWarehouseStock($warehouses_stock);
        }else {
            $variant_suppliers = [];
            foreach (\common\helpers\Suppliers::getSuppliersToUprid($config['products_id']) as $supplier_product) {
                $variant_suppliers[] = (int)$supplier_product['suppliers_id'];
            };
            if (count($variant_suppliers) == 0) $variant_suppliers[] = \common\helpers\Suppliers::getDefaultSupplierId();
            $warehouses_stock = [];
            foreach ($variant_suppliers as $variant_supplier_id) {
                $whs = new WarehousesProducts([
                    'prid' => (int)$config['products_id'],
                    'products_id' => $config['products_id'],
                    'suppliers_id' => $variant_supplier_id,
                    'warehouse_id' => \common\helpers\Warehouses::get_default_warehouse(),
                    'location_id' => 0,
                ]);
                $whs->loadDefaultValues();
                $warehouses_stock[] = $whs->toArray([]);
            }
            $this->warehouses_stock = new ArrayOfWarehouseStock($warehouses_stock);
        }
        parent::__construct($config);
    }

    public function inputValidate()
    {
        $validate = [];
        if ( $this->warehouses_stock ) {
            $validate = array_merge($validate, $this->warehouses_stock->inputValidate());
        }
        return $validate;
    }

}
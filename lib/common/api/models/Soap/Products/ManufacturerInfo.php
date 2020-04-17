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

/**
 * Class ManufacturerInfo
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl  <xsd:element name="manufacturers_description" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_url" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_meta_description" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_meta_key" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_meta_title" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_seo_name" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_h1_tag" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_h2_tag" type="xsd:string"/>
 * @soap-wsdl  <xsd:element nillable="true" name="manufacturers_h3_tag" type="xsd:string"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */
class ManufacturerInfo extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    var $language;

    /**
     * @var string
     * @soap
     */
    var $manufacturers_description;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_url;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_meta_description;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_meta_key;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_meta_title;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_seo_name;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_h1_tag;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_h2_tag;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $manufacturers_h3_tag;
}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Categories;


use common\api\models\Soap\SoapModel;

/**
 * Class ProductDescription
 * @package common\api\models\Soap\Categories
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl  <xsd:element name="categories_name" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_description" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_heading_title" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_head_title_tag" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_head_desc_tag" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_head_keywords_tag" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="categories_seo_page_name" type="xsd:string"/>
 * @soap-wsdl  <xsd:element name="google_product_category" type="xsd:string"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */


class CategoryDescription extends SoapModel
{


    /**
     * @var string
     * @soap
     */
    public $categories_name;

    /**
     * @var string
     * @soap
     */

    public $categories_description;


    /**
     * @var string
     * @soap
     */

    public $categories_heading_title;

    /**
     * @var string
     * @soap
     */

    public $categories_head_title_tag;

    /**
     * @var string
     * @soap
     */
    public $categories_head_desc_tag;

    /**
     * @var string
     * @soap
     */
    public $categories_head_keywords_tag;


    /**
     * @var string
     * @soap
     */
    public $categories_seo_page_name;

    /**
     * @var string
     * @soap
     */
    public $google_product_category;

    /**
     * @var string
     * @-soap-wsdl <xs:attribute name="lang" type="xs:string"/>
     */
    public $language;

}
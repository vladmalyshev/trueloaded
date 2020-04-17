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
 * Class ProductDescription
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="properties_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element nillable="true" name="properties_description" type="xsd:string"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */

class CatalogProductPropertyDescription extends SoapModel
{
    /**
     * @var string
     * @-soap-wsdl <xs:attribute name="lang" type="xs:string"/>
     */
    public $language;

    /**
     * @var int
     * @-soap
     */
    var $language_id;

    /**
     * @var string
     * @soap
     */
    var $properties_name;

    /**
     * @var string {nillable = 1}
     * @soap
     */
    var $properties_description;

}
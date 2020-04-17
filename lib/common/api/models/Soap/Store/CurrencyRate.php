<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;

use common\api\models\Soap\SoapModel;


/**
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="title" type="xsd:string" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="is_default" type="xsd:boolean" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="status" type="xsd:boolean" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="symbol_left" type="xsd:string" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="symbol_right" type="xsd:string" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="decimal_point" type="xsd:string" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="thousands_point" type="xsd:string" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="decimal_places" type="xsd:int" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl <xsd:element name="value" type="xsd:double" minOccurs="0" maxOccurs="1" nillable="false" />
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="id" type="xsd:int"/>
 * @soap-wsdl <xsd:attribute name="code" type="xsd:string" use="required"/>
 */

class CurrencyRate extends SoapModel
{

    public $id;
    public $code;
    public $is_default;
    public $status;
    public $title;
    public $symbol_left;
    public $symbol_right;
    public $decimal_point;
    public $thousands_point;
    public $decimal_places;
    public $value;

}
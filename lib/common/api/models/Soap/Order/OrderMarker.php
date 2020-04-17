<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;

use common\api\models\Soap\SoapModel;

/**
 * Class PriceInfo
 * @package common\api\models\Soap\Order\Marker
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="text" type="xsd:string" minOccurs="0" maxOccurs="1" />
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="id" type="xsd:integer" use="optional"/>
 */

class OrderMarker extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $id;

    /**
     * @var string
     * @soap
     */
    public $text;

}
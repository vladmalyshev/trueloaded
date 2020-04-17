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

///**
// * Class ImageDescription
// * @package common\api\models\Soap\Products
// *
// * @soap-wsdl <xsd:sequence>
// * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" name="price" type="xsd:float"/>
// * @soap-wsdl </xsd:sequence>
// * @soap-wsdl <xsd:attribute name="currency" type="xsd:string" use="required"/>
// */

class PriceType extends SoapModel
{

    /**
     * @var string {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $currency;

    /**
     * @var float {minOccurs=0, maxOccurs=1}
     * @soap
     */
    public $price;

    public function __construct(array $config = [])
    {
        $config['currency'] = \common\helpers\Currencies::systemCurrencyCode();
        if ( isset($config['currencies_id']) && !empty($config['currencies_id']) ) {
            $config['currency'] = \common\helpers\Currencies::getCurrencyCode($config['currencies_id']);
        }

        parent::__construct($config);
    }

}
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
 * Class CouponDescription
 * @package common\api\models\Soap\Store
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="coupon_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="coupon_description" type="xsd:string"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */

class CouponDescription extends SoapModel
{

    /**
     * @var integer
     */
    public $language_id;

    /**
     * @var string
     * @soap
     */
    public $language;

    /**
     * @var string
     * @soap
     */
    public $coupon_name;

    /**
     * @var string
     * @soap
     */
    public $coupon_description;

    public function __construct(array $config = [])
    {
        if ( isset($config['language_id']) ) {
            $config['language'] = \common\classes\language::get_code($config['language_id']);
        }
        parent::__construct($config);
    }


}
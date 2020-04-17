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
 * @soap-wsdl <xsd:element name="products_name" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_description" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_url" type="xsd:string" nillable="true" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_head_title_tag" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_head_desc_tag" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_head_keywords_tag" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_description_short" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_seo_page_name" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="google_product_category" type="xsd:string" minOccurs="0" />
 * @soap-wsdl <xsd:element name="google_product_type" type="xsd:int" minOccurs="0" />
 * @soap-wsdl <xsd:element name="products_self_service" type="xsd:string" minOccurs="0" />
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */
class ProductDescription extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    public $products_name; //                | varchar(255) | NO     | MUL   |    <null> |                |

    /**
     * @var string
     * @soap
     */

    public $products_description; //         | text         | YES    | MUL   |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $products_url;
    /**
     * @var string
     * @soap
     */

    public $products_head_title_tag; //      | varchar(80)  | YES    |       |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $products_head_desc_tag; //       | longtext     | NO     |       |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $products_head_keywords_tag; //   | longtext     | NO     |       |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $products_description_short;

    /**
     * @var string
     * @soap
     */
    public $products_seo_page_name; //       | varchar(255) | NO     |       |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $google_product_category; //      | varchar(255) | NO     |       |    <null> |                |

    /**
     * @var integer
     * @soap
     */
    public $google_product_type; //          | int(11)      | NO     |       |    <null> |                |

    /**
     * @var string
     * @soap
     */
    public $products_self_service;


    /**
     * @var string
     * @-soap-wsdl <xs:attribute name="lang" type="xs:string"/>
     */
    public $language;


}
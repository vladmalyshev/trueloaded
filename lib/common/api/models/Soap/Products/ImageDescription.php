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
 * Class ImageDescription
 * @package common\api\models\Soap\Products
 * @soap-wsdl <xsd:sequence>
 * @soap-wsdl <xsd:element name="image_title" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="image_alt" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="orig_file_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="use_origin_image_name" type="xsd:integer"/>
 * @soap-wsdl <xsd:element name="hash_file_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="file_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="alt_file_name" type="xsd:string"/>
 * @soap-wsdl <xsd:element name="no_watermark" type="xsd:integer"/>
 * @soap-wsdl <xsd:element name="image_source_url" type="xsd:string"/>
 * @soap-wsdl <xsd:element minOccurs="0" maxOccurs="1" nillable="false" name="image_sources" type="tns:ArrayOfImageSources"/>
 * @soap-wsdl </xsd:sequence>
 * @soap-wsdl <xsd:attribute name="language" type="xsd:string" use="required"/>
 */
class ImageDescription extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $language;

    /**
     * @var string
     * @soap
     */
    public $image_title;

    /**
     * @var string
     * @soap
     */
    public $image_alt;

    /**
     * @var string
     * @soap
     */
    public $orig_file_name;

    /**
     * @var integer
     * @soap
     */
    public $use_origin_image_name;
    
    /**
     * @var string
     * @soap
     */
    public $hash_file_name;

    /**
     * @var string
     * @soap
     */
    public $file_name;

    /**
     * @var string
     * @soap
     */
    public $alt_file_name;

    /**
     * @var integer
     * @soap
     */
    public $no_watermark;

    /**
     * @var string
     * @soap
     */
    public $image_source_url;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfImageSources ArrayOfImageSources {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $image_sources;

    public function __construct(array $config = [])
    {
        if ( isset($config['image_sources']) && is_array($config['image_sources']) ) {
            $this->image_sources = new ArrayOfImageSources($config['image_sources']);
            unset($config['image_sources']);
        }
        parent::__construct($config);
    }

}
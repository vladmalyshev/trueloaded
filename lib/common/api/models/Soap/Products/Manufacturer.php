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

class Manufacturer extends SoapModel
{

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    var $manufacturers_id;

    /**
     * @var string
     * @soap
     */
    var $manufacturers_name;

    /**
     * @var string
     * @soap
     */
    var $manufacturers_image;

    /**
     * @var string {nillable = 1, minOccurs=0, maxOccurs=1}
     * @soap
     */
    var $manufacturers_image_source_url;

    /**
     * @var datetime
     * @soap
     */
    var $date_added;

    /**
     * @var datetime {nillable = 1, minOccurs=0}
     * @soap
     */
    var $last_modified;

    /**
     * @var integer
     * @soap
     */
    var $sort_order;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfManufacturerInfos
     * @soap
     */
    var $info_array;


    public function __construct(array $config = [])
    {
        if ( isset($config['infos']) & is_array($config['infos']) ) {
            $config['info_array'] = new ArrayOfManufacturerInfos($config['infos']);
        }else{
            $config['info_array'] = new ArrayOfManufacturerInfos([]);
        }
        parent::__construct($config);

        if (!empty($this->date_added)) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }
        if (!empty($this->last_modified)) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }
    }
}
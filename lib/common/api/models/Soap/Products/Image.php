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

class Image extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $default_image;

    /**
     * @var integer
     * @soap
     */
    public $image_status;

    /**
     * @var integer
     * @soap
     */
    public $sort_order;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfImagesDescriptions Array of ArrayOfImagesDescriptions {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $image_descriptions;

    public function __construct(array $config = [])
    {
        if ( isset($config['image_description']) && is_array($config['image_description']) ) {
            $this->image_descriptions = new ArrayOfImagesDescriptions($config['image_description']);
            unset($config['image_description']);
        }else{
            $this->image_descriptions = new ArrayOfImagesDescriptions([]);
        }

        parent::__construct($config);
    }


}
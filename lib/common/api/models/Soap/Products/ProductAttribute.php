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

class ProductAttribute extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $options_id;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $options_values_id;

    /**
     * @var string
     * @soap
     */
    public $options_name;

    /**
     * @var string
     * @soap
     */
    public $options_values_name;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_options_sort_order;

}
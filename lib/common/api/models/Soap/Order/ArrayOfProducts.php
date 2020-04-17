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

class ArrayOfProducts extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Order\OrderedProduct  OrderedProduct {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $product = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $product_config) {
            $this->product[] = new OrderedProduct($product_config);
        }
        parent::__construct($config);
    }


}
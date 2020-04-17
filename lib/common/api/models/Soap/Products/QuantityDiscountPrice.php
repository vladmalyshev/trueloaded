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
use common\api\SoapServer\SoapHelper;

class QuantityDiscountPrice extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $quantity;

    /**
     * @var float
     * @soap
     */
    public $discount_price;

    public function __construct(array $config = [])
    {
        if ( isset($config['discount_price']) && $config['discount_price']>0 && (!isset($config['isProductOwner']) || !$config['isProductOwner']) ) {
            $config['discount_price'] = SoapHelper::applyOutgoingPriceFormula($config['discount_price']);
        }
        parent::__construct($config);
    }


}
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

class PackPriceInfo extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_qty;

    /**
     * @var float {nillable = 1}
     * @soap
     */
    public $price;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfQuantityDiscountPrice Array of QuantityDiscountPrice {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $discount_table;

    public function __construct(array $config = [])
    {
        if (isset($config['discount_table'])) {
            if (!empty($config['discount_table'])) {
                $this->discount_table = ArrayOfQuantityDiscountPrice::createFromString($config['discount_table'], (isset($config['isProductOwner']) && $config['isProductOwner']));
            }
            unset($config['discount_table']);
        }
        if ( isset($config['price']) ){
            if ( $config['price']==-2 ) {
                $config['price'] = null;
            }elseif ( $config['price']>0 && (!isset($config['isProductOwner']) || !$config['isProductOwner']) ) {
                $config['price'] = SoapHelper::applyOutgoingPriceFormula($config['price']);
            }
        }

        parent::__construct($config);
    }


}
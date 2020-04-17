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

class PriceAndStockInfo extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $products_id;

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $status;
    /**
     * @var \common\api\models\Soap\Products\ArrayOfPriceInfo ArrayOfPriceInfo {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $prices;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfStockInfo Array of StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfSupplierProductData Array of SupplierProductData {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $supplier_product_data;

}
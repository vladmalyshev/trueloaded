<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


class UpdateProductSuppliersRequest extends SoapModel
{
    /**
     * @var integer
     * @soap
     */
    public $product_id;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfSupplierProductData ArrayOfSupplierProductData
     * @soap
     */
    public $supplier_products;


}
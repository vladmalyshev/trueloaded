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


use common\api\models\AR\Products\SuppliersData;
use common\api\models\Soap\Products\ArrayOfSupplierProductData;

class GetProductSuppliersResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Products\ArrayOfSupplierProductData ArrayOfSupplierProductData
     * @soap
     */
    public $supplier_products;

    protected $productId = 0;

    public function setProductId($productId)
    {
        $this->productId = $productId;
        $check = \common\models\Products::find()
            ->where(['products_id'=>(int)$this->productId])
            ->one();
        if ( !$check ){
            $this->error('Product not found');
        }
    }

    public function build()
    {
        $product_array = [];
        if ( $this->status!='ERROR' ) {
            $supplier_products = SuppliersData::find()
                ->where(['products_id' => $this->productId])
                ->orderBy(['uprid' => SORT_ASC])
                ->all();

            foreach ($supplier_products as $supplier_product) {
                $product_array[] = $supplier_product->exportArray([]);
            }
        }
        $this->supplier_products = new ArrayOfSupplierProductData($product_array);

        parent::build();
    }

}
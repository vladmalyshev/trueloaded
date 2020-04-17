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


use common\api\models\AR\Products\SupplierProduct;
use common\api\models\Soap\Products\Product;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;

class GetProductResponse extends SoapModel
{
    /**
     * @var \common\api\models\Soap\Products\Product Product
     * @soap
     */
    public $product;

    protected $productId = 0;

    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    public function build()
    {
        $productData = [];
        $product = \common\api\models\AR\Products::findOne(['products_id' => $this->productId]);
        if ($product) {
            $productData = $product->exportArray([]);
        }

        if ( ServerSession::get()->getDepartmentId() && SoapHelper::hasProduct($this->productId) ) {

            //TODO: make new solution for price formula
            \common\classes\ApiDepartment::get()->setCurrentResponseProductId($this->productId);

            $this->product = new Product($productData);

            \common\classes\ApiDepartment::get()->setCurrentResponseProductId(0);
        }
        if ( ServerSession::get()->getPlatformId() && SoapHelper::hasProduct($this->productId) ) {
            $this->product = new Product($productData);
        }

        parent::build();
    }

}
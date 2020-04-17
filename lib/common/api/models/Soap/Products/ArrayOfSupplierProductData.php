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
use yii\helpers\ArrayHelper;

class ArrayOfSupplierProductData extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\SupplierProductData SupplierProductData {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $supplier_product = [];

    public function __construct(array $config = [])
    {
        if ( is_array($config) && ArrayHelper::isIndexed($config) ) {
            foreach ($config as $supplier_product){
                $this->supplier_product[] = new SupplierProductData($supplier_product);
            }
        }

        parent::__construct($config);
    }


}
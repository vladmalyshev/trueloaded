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

class ArrayOfQuantityDiscountPrice extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\QuantityDiscountPrice QuantityDiscountPrice {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $price = [];

    static public function createFromString($discount_string, $isProductOwner=false)
    {
        $that = new self();
        $that->price = [];

        $ar = preg_split("/[:;]/", rtrim($discount_string, ' ;'));

        for ($i = 0, $n = sizeof($ar); $i < $n; $i = $i + 2) {
            $that->price[] = new QuantityDiscountPrice([
                'quantity' => (int)$ar[$i],
                'discount_price' => (float)$ar[$i + 1],
                'isProductOwner' => $isProductOwner,
            ]);
        }
        return $that;
    }

}
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


class UpdateStockRequest extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\ArrayOfStockInfo Array of StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock;

}
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

class ArrayOfInventories extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\Inventory Inventory {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $inventory = [];

    public function __construct(array $config = [])
    {
        foreach( $config as $key=>$data ) {
            $this->inventory[] = new Inventory($data);
        }
        parent::__construct([]);
    }

    public function inputValidate()
    {
        $validate = [];
        foreach($this->inventory as $inventory ) {
            $validate = array_merge($validate, $inventory->inputValidate());
        }
        return $validate;
    }

}
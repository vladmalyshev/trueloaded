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

class ArrayOfWarehouseStock extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\WarehouseStock WarehouseStock {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $warehouse_stock = [];

    public function __construct(array $config = [])
    {
        if ( is_array($config) && ArrayHelper::isIndexed($config) ) {
            foreach ($config as $warehouse_product){
                $this->warehouse_stock[] = new WarehouseStock($warehouse_product);
            }
        }

        parent::__construct([]);
    }

    public function inputValidate()
    {
        $validate = [];
        if ( is_array($this->warehouse_stock) ) {
            if ( !ArrayHelper::isIndexed($this->warehouse_stock) ) $this->warehouse_stock = [$this->warehouse_stock];
        }else
        if ( is_object($this->warehouse_stock) ){
            $this->warehouse_stock = [$this->warehouse_stock];
        }

        foreach ($this->warehouse_stock as $warehouse_stock) {
            $validate = array_merge($validate, $warehouse_stock->inputValidate());
        }

        return $validate;
    }
}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;


use common\api\models\Soap\Products\ArrayOfAttributes;
use common\api\models\Soap\SoapModel;

class OrderedProduct extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    public $id;

    /**
     * @var string
     * @soap
     */
    public $name;

    /**
     * @var string
     * @soap
     */
    public $model;

    /**
     * @var integer
     * @soap
     */
    public $qty;

    /**
     * @var float
     * @soap
     */
    public $tax;

    /**
     * @var double
     * @soap
     */
    public $price;

    /**
     * @var double
     * @soap
     */
    public $final_price;

    /**
     * @var boolean
     * @soap
     */
    public $ga;

    /**
     * @var boolean
     * @soap
     */
    public $is_virtual;

    /**
     * @var string
     * @soap
     */
    public $gv_state;

    /**
     * @var double
     * @soap
     */
    public $gift_wrap_price;

    /**
     * @var boolean
     * @soap
     */
    public $gift_wrapped;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfOrderedAttributes Array of ArrayOfOrderedAttributes {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $attributes;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $packs;
    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $packs_price;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $units;

    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $units_price;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $packagings;
    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $packagings_price;

    /**
     * @var double {minOccurs=0}
     * @soap
     */
    public $weight;
    /**
     * @var \common\api\models\Soap\Order\OrderedProductRelation {minOccurs=0}
     * @soap
     * @doc Readonly
     */
    public $relation;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfStockAllocate Array of StockAllocate {minOccurs=0, maxOccurs = 1}
     * @soap
     * @doc Readonly
     */
    public $stock_allocate_data;

    /**
     * @var \common\api\models\Soap\Order\StockAllocateSummary {minOccurs=0, maxOccurs = 1}
     * @soap
     * @doc Readonly
     */
    public $stock_summary;

    public function __construct(array $config = [])
    {
        if ( $config['orders_products_id'] ){
            foreach (\common\helpers\OrderProduct::getAllocatedArray($config['orders_products_id'],true) as $orderProductAllocateRecord) {
                if ( !is_object($this->stock_allocate_data) ) $this->stock_allocate_data = new ArrayOfStockAllocate();
                $this->stock_allocate_data->stock_allocate[] = new StockAllocate($orderProductAllocateRecord);
            }
        }
        $this->stock_summary = new StockAllocateSummary($config);

        if ( isset($config['attributes']) ) {
            $this->attributes = new ArrayOfAttributes();
            if (is_array($config['attributes'])) foreach ($config['attributes'] as $attribute) {
                $this->attributes->attribute[] = new OrderedAttribute(                [
                    'option_id' => (int)$attribute['option_id'],
                    'value_id' => (int)$attribute['value_id'],
                    'option_name' => (string)$attribute['option'],
                    'option_value_name' => (string)$attribute['value'],
                ]);
            }
            unset($config['attributes']);
        }
        if ( $config['parent_product'] ){
            $config['relation'] = [
                'id' => $config['template_uprid'],
                'parent_id' => $config['parent_product'],
            ];
        }
        if ( $config['sub_products'] ) {
            $config['relation'] = [
                'id' => $config['template_uprid'],
                'children' => $config['sub_products'],
            ];
        }
        parent::__construct($config);
    }


}
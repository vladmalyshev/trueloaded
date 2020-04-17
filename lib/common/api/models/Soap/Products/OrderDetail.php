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

class OrderDetail extends SoapModel
{

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $order_quantity_minimal;

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $order_quantity_max;

    /**
     * @var integer {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $order_quantity_step;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $add_to_cart_button;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $allow_ask_sample;


    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $allow_request_for_quote;

    /**
     * @var boolean {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $allow_request_for_quote_out_of_stock;


    public function __construct(array $config = [])
    {

        if (!\common\helpers\Acl::checkExtension('MinimumOrderQty', 'productBlock')){
            unset($config['order_quantity_minimal']);
        }

        if (!\common\helpers\Acl::checkExtension('MaxOrderQty', 'productBlock')){
            unset($config['order_quantity_max']);
        }

        if (!\common\helpers\Acl::checkExtension('OrderQuantityStep', 'productBlock')){
            unset($config['order_quantity_step']);
        }

        if ( isset($config['cart_button']) ){
            $this->add_to_cart_button = !!$config['cart_button'];
        }
        if ( isset($config['ask_sample']) ) {
            $this->allow_ask_sample = $config['ask_sample'];
        }
        if ( isset($config['request_quote']) ) {
            $this->allow_request_for_quote = $config['request_quote'];
        }
        if ( isset($config['request_quote_out_stock']) ) {
            $this->allow_request_for_quote_out_of_stock = $config['request_quote_out_stock'];
        }

        parent::__construct($config);
    }

    public static function makeAR($val)
    {
        if ( !is_array($val) ) return [];
        $returnData = [];
        if (isset($val['order_quantity_minimal']) && \common\helpers\Acl::checkExtension('MinimumOrderQty', 'productBlock')){
            $returnData['order_quantity_minimal'] = $val['order_quantity_minimal'];
        }
        if (isset($val['order_quantity_max']) && \common\helpers\Acl::checkExtension('MaxOrderQty', 'productBlock')){
            $returnData['order_quantity_max'] = $val['order_quantity_max'];
        }
        if (isset($val['order_quantity_step']) && \common\helpers\Acl::checkExtension('OrderQuantityStep', 'productBlock')){
            $returnData['order_quantity_step'] = $val['order_quantity_step'];
        }
        if ( isset($val['add_to_cart_button']) ){
            $returnData['cart_button'] = $val['add_to_cart_button']?1:0;
        }
        if ( isset($val['allow_ask_sample']) ) {
            $returnData['ask_sample'] = $val['allow_ask_sample']?1:0;
        }
        if ( isset($val['allow_request_for_quote']) ) {
            $returnData['request_quote'] = $val['allow_request_for_quote']?1:0;
        }
        if ( isset($val['allow_request_for_quote_out_of_stock']) ) {
            $returnData['request_quote_out_stock'] = $val['allow_request_for_quote_out_of_stock']?1:0;
        }

        return $returnData;
    }

}
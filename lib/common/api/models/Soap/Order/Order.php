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

use common\api\models\Soap\SoapModel;

class Order extends SoapModel
{

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $order_id;

    /**
     * @var integer {minOccurs=1}
     * @soap
     */
    public $client_order_id;

    /**
     * @var \common\api\models\Soap\Order\CustomerAddress
     * @soap
     */
    public $customer;

    /**
     * @var \common\api\models\Soap\Order\DeliveryAddress
     * @soap
     */
    public $delivery;

    /**
     * @var \common\api\models\Soap\Order\BillingAddress
     * @soap
     */
    public $billing;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfProducts Array of ArrayOfProducts {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $products;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfTotals Array of ArrayOfTotals {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */

    public $totals;
    
    /**
     * @var \common\api\models\Soap\Order\ArrayOfTransactions Array of ArrayOfTransactions
     * @soap
     */
    public $transactions;

    /**
     * @var \common\api\models\Soap\Order\Info
     * @soap
     */
    public $info;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfStatusHistory Array of StatusHistory {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $status_history_array;

    /**
     * @var \common\api\models\Soap\Order\OrderLegend OrderLegend {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $legend_data;


    public function __construct(array $config = [])
    {
        $this->customer = new CustomerAddress(isset($config['customer'])?$config['customer']:[]);
        $this->billing = new BillingAddress(isset($config['billing'])?$config['billing']:[]);
        $this->delivery = new DeliveryAddress((isset($config['delivery']) && is_array($config['delivery']))?$config['delivery']:[]);
        $this->products = new ArrayOfProducts(isset($config['products'])?$config['products']:[]);
        $this->totals = new ArrayOfTotals(isset($config['totals'])?$config['totals']:[]);
        $this->transactions = new ArrayOfTransactions(isset($config['info'])?$config['info']:[]);
        $this->info = new Info(isset($config['info'])?$config['info']:[]);
        if ( isset($config['status_history']) && is_array($config['status_history']) ) {
            $this->status_history_array = new ArrayOfStatusHistory($config['status_history']);
        }
        $this->order_id = isset($config['order_id'])?$config['order_id']:null;
        if ( isset($config['info']) && !empty($config['info']['api_client_order_id']) ) {
            $this->client_order_id = $config['info']['api_client_order_id'];
        }
        if ( $this->order_id ) {
            $this->legend_data = OrderLegend::makeForOrder($this->order_id);
        }
        //parent::__construct($config);
    }

    public function setCustomer($param)
    {
    }


}
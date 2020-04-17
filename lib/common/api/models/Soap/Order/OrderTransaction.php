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
use common\extensions\ProductDesigner as PD;

class OrderTransaction extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $transaction_id;
    
    /**
     * @var string
     * @soap
     */
    public $transaction_status;

    /**
     * @var float
     * @soap
     */
    public $transaction_amount;
    
    /**
     * @var string
     * @soap
     */
    public $transaction_currency;
    
    /**
     * @var string
     * @soap
     */
    public $payments_class;
    
    /**
     * @var datetime
     * @soap
     */
    public $date_created;
    
    public function __construct(array $config = []){
        if (!empty($config['payment_class'])){
            $this->payments_class = $config['payment_class'];
        }
        parent::__construct($config);
        if ( !empty($this->date_created) ) {
            $this->date_created = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_created);
        }
        if (isset($config['orders_transactions_child_id'])){
            $this->transaction_amount = 0 - $this->transaction_amount;
        }
    }

}
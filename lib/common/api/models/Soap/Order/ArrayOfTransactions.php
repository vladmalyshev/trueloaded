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

class ArrayOfTransactions extends SoapModel
{
    /**
     * @var \common\api\models\Soap\Order\OrderTransaction OrderTransaction {nillable = 1, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $transaction = [];

    public function __construct(array $config = [])
    {
        if (isset($config['orders_id'])){
            foreach(\common\models\OrdersTransactions::find()->where(['orders_id' => $config['orders_id']])->all() as $transaction){
                $this->transaction[] = new OrderTransaction($transaction->toArray());
                if ($transaction->transactionChildren){
                    foreach($transaction->transactionChildren as $child){
                        $this->transaction[] = new OrderTransaction($child->toArray());
                    }
                }
            }
        }
        parent::__construct($config);
    }
}
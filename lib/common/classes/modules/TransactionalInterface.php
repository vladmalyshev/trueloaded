<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

use common\services\PaymentTransactionManager;

interface TransactionalInterface {
    
    /*transaction id from payment system*/
    public function getTransactionDetails($transaction_id, PaymentTransactionManager $tManager = null);

    public function canRefund($transaction_id);
    
    public function refund($transaction_id, $amount = 0);
    
    public function canVoid($transaction_id);
    
    public function void($transaction_id);
}

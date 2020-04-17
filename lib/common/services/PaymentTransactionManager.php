<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\services;

class PaymentTransactionManager {

    private $payment;
    private $manager;
    private $enabled = false;

    public function __construct(\common\services\OrderManager $manager, $payment = null) {
        $this->enabled = true;
        $this->payment = $payment;
        $this->manager = $manager;
        if (!($this->manager->getOrderInstance() instanceof \common\classes\extended\TransactionsInterface)) {
            $this->enabled = false;
        }
    }
    /**
     * set Payment module to work with transactions
     * @param \common\classes\modules\ModulePayment $payment
     */
    public function usePayment(\common\classes\modules\ModulePayment $payment) {
        $this->payment = $payment;
    }

    public function isReady() {
        return $this->enabled && $this->getOrder()->order_id;
    }

    private function getOrder() {
        return $this->manager->getOrderInstance();
    }

    private function getAdminId() {
        return $_SESSION['login_id'] ?? 0;
    }
    
    /**
     * Update status & amount on transaction from payment module
     * @param string $tranasction_id
     * @param string $status
     * @param float $amount
     */
    public function updateTransactionFromPayment($tranasction_id, $status, $amount, $dateCreated = null){
        if ($this->isReady() && $this->isTransactional()){
            //first find child
            $child = $this->_getTCModelQuery()->andWhere(['transaction_id' => $tranasction_id])->one();
            if ($child){
                $child->transaction_status = $status;
                $child->transaction_amount = $amount;
                if (!is_null($dateCreated)){
                    $child->date_created = date("Y-m-d H:i:s", strtotime($dateCreated));
                }
                $child->save(false);
            } else {
                //may be parent transaction
                $parent = $this->_getTModelQuery()->andWhere(['transaction_id' => $tranasction_id])->one();
                if ($parent){
                    $parent->transaction_status = $status;
                    $parent->transaction_amount = $amount;
                    if (!is_null($dateCreated)){
                        $parent->date_created = date("Y-m-d H:i:s", strtotime($dateCreated));
                    }
                    $parent->save(false);
                }
            }
        }
    }

    /**
     * Search transaction details by payment transaction id && preused Payment
     * @param string $transaction_id
     * @return mix OrdersTransaction|null
     */
    public function getTransaction($transaction_id) {
        if ($this->isReady()) {
            $transaction = $this->_getTModelQuery()->andWhere(['payment_class' => $this->payment->code, 'transaction_id' => $transaction_id])
                    ->one();
            return $transaction;
        }
        return null;
    }
    
    public function addTransaction($transaction_id, $status, $amount, $suborder_id = null, $comments = '') {
        if ($this->isReady() && $this->isTransactional()) {
            $transaction = \common\models\OrdersTransactions::create($this->getOrder()->order_id, $this->payment->code, $transaction_id, $status, $amount, $suborder_id, $this->getOrder()->info['currency'], $comments, $this->getAdminId());
            if ($transaction){
                $this->linkLocalTransaction($transaction_id);
            }
            return $transaction;
        }
        return false;
    }
    /**
     * Update or add new OrdersTransaction.
     * @param string $transaction_id - payment transaction value
     * @param string $status - payment transaction status
     * @param float $amount - transaction amount
     * @param int $suborder_id - (invoice|creditnote id)|null
     * @param text $comments
     * @return boolean
     */
    public function updateTransaction($transaction_id, $status, $amount, $suborder_id = null, $comments = '') {
        if ($this->isReady() && $this->isTransactional()) {
            $transaction = $this->getTransaction($transaction_id);
            if ($transaction) {
                $transaction->transaction_status = $status;
                $transaction->save();
                return $transaction;
            } else {
                $transaction = $this->addTransaction($transaction_id, $status, $amount, $suborder_id, $comments);
                return $transaction;
            }
        }
        return false;
    }
    
    /**
     * Update child transaction or add new, child transaction makes for refund|void
     * @param string $transaction_id - payment transaction id
     * @param string $child_transaction_id - payment child transaction id, if payment does not return child use transaction id
     * @param string $status - payment status
     * @param float $amount - payment transaction value
     * @param text $comments
     * @return boolean result
     */
    public function updateTransactionChild($transaction_id, $child_transaction_id, $status, $amount, $comments = '') {
        if ($this->isReady() && $this->isTransactional()) {
            $parent = $this->getTransaction($transaction_id);
            if ($parent) {
                $child = $parent->updateTransactionChild($child_transaction_id, $status, $amount, $comments, $this->getAdminId());//instead updateTransactionChild
                /*if ($child){
                    if ($this->propagination){
                        if (!$child->splinters_suborder_id){
                            $splitter = $this->manager->getOrderSplitter();
                            $cnId = $splitter->createCreditNote($parent->splinters_suborder_id, $amount, $this->payment);//if splitter has prepared
                            if ($cnId){ //has been created new document
                                $child->splinters_suborder_id = $cnId;
                                $child->save(false);
                            }
                            if (!$this->manager->hasCart()){
                                $this->manager->loadCart(new \common\classes\shopping_cart);
                            }
                            $this->manager->getOrderInstance()->return_paid($amount, $amount);
                            $this->manager->getOrderInstance()->save_details();
                        }
                    }
                }*/
            }
        }
        return false;
    }
    
    public function addTransactionChild($transaction_id, $child_transaction_id, $status, $amount, $comments = '') {
        if ($this->isReady() && $this->isTransactional()) {
            $parent = $this->getTransaction($transaction_id);
            if ($parent) {
                $child = $parent->addTransactionChild($child_transaction_id, $status, $amount, $comments, $this->getAdminId());
                if ($child){
                    if ($this->propagination){
                        if (!$child->splinters_suborder_id){ //
                            $splitter = $this->manager->getOrderSplitter();
                            $cnId = $splitter->createCreditNote($parent->splinters_suborder_id, $amount, $this->payment);//if splitter has prepared
                            if ($cnId){ //has been created new document
                                $child->splinters_suborder_id = $cnId;
                                $child->save(false);
                            }
                            if (!$this->manager->hasCart()){
                                $this->manager->loadCart(new \common\classes\shopping_cart);
                            }
                            $currencies = \Yii::$container->get('currencies');
                            if (defined('DEFAULT_CURRENCY') && !empty($this->manager->getOrderInstance()->info['currency'])){
                                $amount *= $currencies->get_market_price_rate($this->manager->getOrderInstance()->info['currency'], DEFAULT_CURRENCY);
                            }
                            $this->manager->getOrderInstance()->return_paid($amount, $amount);
                            $this->manager->getOrderInstance()->save_details();
                        }
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Use for creating documents after got transaction child data from payment
     * @var boolean, by default true
     */
    private $propagination = true;
    
    public function stopPropagination(){
        $this->propagination = false;
    }
    
    public function continuePropagination(){
        $this->propagination = true;
    }
    
    public function finalizeRefunding($ownerSplinterId, array $childTransactions, $amount){
        if ($childTransactions){
            $splitter = $this->manager->getOrderSplitter();
            $docId = $splitter->createCreditNote($ownerSplinterId, $amount);
            \Yii::info('creditnote form several transactions '.$docId);
            if ($docId){
                \Yii::info('creditnote transactions '.print_r($childTransactions,1));
                foreach($childTransactions as $child){
                    $trChild = $this->_getTCModelQuery()->andWhere(['orders_transactions_child_id' => $child])->one();
                    \Yii::info('creditnote transactions child before '. print_r($trChild, 1));
                    if ($trChild){
                        $trChild->splinters_suborder_id = $docId;
                        $trChild->save();
                        \Yii::info('creditnote transactions child after '. print_r($trChild, 1));
                    }
                }
            }
        }
        $this->continuePropagination();
        return $docId;
    }

    private function _getTModelQuery() {
        return \common\models\OrdersTransactions::find()
                        ->where(['orders_id' => $this->getOrder()->order_id]);
    }
    
    private function _getTCModelQuery() {
        return \common\models\OrdersTransactionsChildren::find()
                ->where(['orders_id' => $this->getOrder()->order_id]);
    }

    public function getTransactionById($id) {
        if ($this->isReady()) {
            return $this->_getTModelQuery()
                            ->andWhere(['orders_transactions_id' => $id])
                            ->one();
        }
        return false;
    }

    public function getTransactionsCount() {
        if ($this->isReady()) {
            return $this->_getTModelQuery()->count();
        }
        return false;
    }

    public function getTransactions($withChildren = false) {
        if ($this->isReady()) {
            $tmQ = $this->_getTModelQuery();
            if ($withChildren){
                $tmQ->with('transactionChildren');
            }
            return $tmQ->all();
        }
        return false;
    }

    public function isTransactional() {
        if ($this->payment && $this->payment instanceof \common\classes\modules\TransactionalInterface) {
            return true;
        }
        return false;
    }
    
    public function isTransactionSearch() {
        if ($this->payment && $this->payment instanceof \common\classes\modules\TransactionSearchInterface) {
            return true;
        }
        return false;
    }
    
    /**
     * get current transactions status and check availbiality to do refun|void
     * @param array $orders_transactions
     * @return array $response
     */
    public function getTransactionsStatus(array $orders_transactions){
        $response = [];
        if ($this->isReady()){
            foreach($this->_getTModelQuery()->where(['in', 'orders_transactions_id', $orders_transactions])->orderBy('date_created asc')->all() as $transaction){
                $data = ['parent' => $transaction->orders_transactions_id];
                $payment = $this->manager->getPaymentCollection()->get($transaction->payment_class, true);
                if ($payment){
                    $this->usePayment($payment);
                    $data['can_refund'] = $this->canPaymentRefund($transaction->transaction_id);
                    $data['can_void'] = !$data['can_refund'] ? $this->canPaymentVoid($transaction->transaction_id) : false;
                }
                $children = $transaction->getTransactionChildren()->asArray()->all();
                if ($children){
                    $currencies = \Yii::$container->get('currencies');
                    foreach($children as &$child){
                        $child['date_created'] = \common\helpers\Date::formatDateTime($child['date_created']);
                        $child['transaction_amount'] = "-".$currencies->format($child['transaction_amount'], false, $child['transaction_currency']);
                        $child['transaction_amount_clear'] = $currencies->format_clear($child['transaction_amount'], false, $child['transaction_currency']);
                        $child['transaction_status'] = \yii\helpers\Inflector::humanize($child['transaction_status']);
                    }
                }
                $data['children'] = $children;
                $data['status'] = \yii\helpers\Inflector::humanize($transaction->transaction_status);
                $data['date_created'] = \common\helpers\Date::formatDateTime($transaction->date_created);
                $data['amount'] = $transaction->transaction_amount;
                $response[] = $data;
            }
        }
        
        return $response;
    }

    public function checkPaymentTransaction($transaction_id) {
        if ($this->isReady() && $this->isTransactional()) {
            try {
                $this->payment->getTransactionDetails($transaction_id, $this);
            } catch (\Exception $ex) {
                \Yii::info($ex->getMessage(), 'PAYMENTTRANSACTION'); 
            }
        }
        return false;
    }

    public function canPaymentRefund($transaction_id) {
        if ($this->isReady() && $this->isTransactional()) {
            $result = $this->payment->canRefund($transaction_id, $this);
            return $result;
        }
        return false;
    }

    public function paymentRefund($transaction_id, $amount = 0) {
        if ($this->isReady() && $this->isTransactional()) {
            $result = $this->payment->refund($transaction_id, $amount, $this);
            return $result;
        }
        return false;
    }

    public function canPaymentVoid($transaction_id) {
        if ($this->isReady() && $this->isTransactional()) {
            $result = $this->payment->canVoid($transaction_id, $this);
            return $result;
        }
        return false;
    }
    
    public function paymentVoid($transaction_id) {
        if ($this->isReady() && $this->isTransactional()) {
            $result = $this->payment->void($transaction_id);
            return $result;
        }
        return false;
    }
    
    public function getRequeredFields(){
        return [];
    }
    
    public function getFields(){
        if ($this->isReady() && $this->isTransactionSearch()) {
            return $this->payment->getFields();
        }
        return [];
    }

    private $_requered = [];
    private $_errors = [];
    public function prepareQuery(array $searchData){//fieldname => value
        if ($this->isReady() && $this->isTransactionSearch()) {
            $_fields = $this->payment->getFields();
            if (is_array($_fields)){
                $model = \Yii::createObject('common\components\PaymentModel');
                \Yii::configure($model, ['rules' => $_fields]);
                if ($model->load($searchData, '') && $model->validate()){
                    $this->_requered = $model->getAttributes();
                    return true;
                } else {
                    $this->_errors = $model->getErrors();
                }
            }
        }
        return false;
    }
    
    public function getErrors(){
        return $this->_errors;
    }
    
    /**
     * @return array of transactions [id => transaction description] 
     */
    public function executeQuery(){
        $found = [];
        if ($this->isReady() && $this->isTransactionSearch()) {
            try{
                if (!$this->_errors){
                    $found = $this->payment->search($this->_requered);
                }
            } catch (\Exception $ex) {
                \Yii::info($ex->getMessage(), 'PAYMENTTRANSACTION'); 
            }
        }
        return $found;
    }
    
    public function linkLocalTransaction($transaction_id){
        if ($this->isReady()){
            if (method_exists($this->payment, 'linkTransaction')){
                $this->payment->linkTransaction($this->getOrder()->order_id, $transaction_id);
            }
        }
    }
    
    public function unLinkLocalTransaction($transaction_id, $payment_class = null){
        if ($this->isReady()){
            if (!is_object($this->payment) && !is_null($payment_class)){
                $payment = $this->manager->getPaymentCollection()->get($payment_class, true);
                if ($payment){
                    $this->usePayment($payment);
                }
            }
            if (method_exists($this->payment, 'unLinkTransaction')){
                $this->payment->unLinkTransaction($transaction_id);
            }
        }
    }
    
    /**
     * Unlink transaction from order
     * @param type $transaction_orders_id
     */
    public function unlinkTransactionById($transaction_orders_id){
        $transaction = $this->getTransactionById($transaction_orders_id);
        if ($transaction){
            $transaction->orders_id = 0;
            //$transaction->splinters_suborder_id = 0;//??need to be unlinked
            if ($transaction->save(false)){
                if ($transaction->getTransactionChildren()->exists()){
                    
                }
                $this->unLinkLocalTransaction($transaction->transaction_id, $transaction->payment_class);
                return true;
            }
        }
        return false;
    }
    
    public function isLinkedTransaction($transaction_id){
        if ($this->isReady() && $this->isTransactionSearch()){
            return \common\models\OrdersTransactions::hasLinked($this->payment->code, $transaction_id);
        }
        return false;
    }

}

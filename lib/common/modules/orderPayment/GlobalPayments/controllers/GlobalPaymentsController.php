<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\orderPayment\GlobalPayments\controllers;

use common\classes\Order;
use common\modules\orderPayment\GlobalPayments\Helpers\Country;
use common\modules\orderPayment\GlobalPayments\services\GlobalPaymentsService;
use common\modules\orderPayment\globalpaymentshpp;
use common\services\CustomersService;
use common\services\storages\StorageInterface;
use frontend\controllers\Sceleton;
use common\helpers\Translation;
use Yii;

class GlobalPaymentsController extends Sceleton
{
    public $enableCsrfValidation = false;

    /** @var GlobalPaymentsService */
    private $globalPaymentsService;
    /** @var CustomersService */
    private $customersService;
    /** @var \common\services\OrderManager */
    private $orderManager;
    /** @var StorageInterface */
    private $storage;

    public function __construct(
        $id,
        $module = null,
        GlobalPaymentsService $globalPaymentsService,
        CustomersService $customersService,
        array $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->initTranslations();
        $this->globalPaymentsService = $globalPaymentsService;
        $this->customersService = $customersService;
        $this->storage = \Yii::$app->get('storage');
        $this->orderManager = new \common\services\OrderManager($this->storage);
        if (!Yii::$app->user->isGuest && !$this->orderManager->isCustomerAssigned()) {
            $this->orderManager->assignCustomer(\Yii::$app->user->getId());
        }
    }

    public function init()
    {
        parent::init();
    }

    public function actionHash(): string
    {
        $request = json_decode(file_get_contents('php://input'));
        $globalPaymentsSharedSecret = 'secret';
        $toHash = [
            $request->TIMESTAMP ?? '',
            $request->MERCHANT_ID ?? '',
            $request->ORDER_ID ?? '',
            '',
            $request->CURRENCY ?? '',
            $request->PAYER_REF  ?? '',
            $request->PMT_REF  ?? '',
        ];
        $request->SHA1HASH = sha1(sha1(implode('.', $toHash)) . '.' . $globalPaymentsSharedSecret);
        echo json_encode($request);
    }

    public function actionAuthorizationRequest()
    {
        global $cart;
        try {
            /** @var \common\classes\currencies $currencies */
            $currencies = \Yii::$container->get('currencies');
            $customer_id = $this->orderManager->getCustomerAssigned();
            $this->orderManager->loadCart($cart);
            $order_id = (int)\Yii::$app->request->get('id', 0);
            if ($order_id) {
                $order = $this->orderManager->getOrderInstanceWithId(Order::class, $order_id);
            } elseif ($this->orderManager->isInstance()) {
                $order = $this->orderManager->getOrderInstance();
            } else {
                $order = $this->orderManager->createOrderInstance(Order::class);
                $order->cart();
                $this->orderManager->checkoutOrder();
                $this->orderManager->totalPreConfirmationCheck();
                $this->orderManager->getTotalOutput(true, 'TEXT_CHECKOUT');
            }
            $order->update_piad_information();

            $orderAmount = $this->globalPaymentsService->getChargeFromOrder($order, $currencies);
            if($orderAmount->getAmount() < 1){
                throw new \DomainException('Amount in empty');
            }
            $date = new \DateTimeImmutable();
            $customer = $this->customersService->findById($customer_id, true);

            $hostedService = $this->getConfig();
            $gpOrderId = STORE_NAME . ($order_id ? '(' . $order_id . ')' : '');
            $gpOrderId = $this->globalPaymentsService->generateGPOrderId($gpOrderId, $date);
            $this->storage->set('gpOrderId', $gpOrderId);
            //$hash = $this->globalPaymentsService->getHash($merchantId, $secret, $gpOrderId, $orderAmount, $date);
            return $this->globalPaymentsService->authorizationRequest(
                $hostedService,
                $gpOrderId,
                $orderAmount,
                $customer ? $customer['payerreference'] : '',
                $order,
                new Country()
            );

        } catch (\Exception $e) {
            return $this->asJson($e->getMessage());
        }
    }

    private function initTranslations()
    {
        Translation::init('ordertotal');
        Translation::init('admin/orders');
        Translation::init('admin/orders/create');
        Translation::init('admin/orders/order-edit');
    }

    public function getViewPath()
    {
        return \Yii::getAlias('@global-payments/views');
    }

    /**
     * @return \GlobalPayments\Api\Services\HostedService
     */
    public function getConfig(): \GlobalPayments\Api\Services\HostedService
    {
        $merchantId = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_MERCHANT_ID;
        $secret = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_SHARED_SECRET;
        $account = MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_ACCOUNT;

        $hostedService = $this->globalPaymentsService->getGPService($merchantId, $account, $secret, globalpaymentshpp::getRequestUrl());
        return $hostedService;
    }
}

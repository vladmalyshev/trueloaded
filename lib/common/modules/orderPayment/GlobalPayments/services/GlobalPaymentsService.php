<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace common\modules\orderPayment\GlobalPayments\services;


use common\classes\currencies;
use common\classes\Order;
use common\modules\orderPayment\GlobalPayments\Helpers\Country;
use common\modules\orderPayment\GlobalPayments\VO\Price;
use common\models\Customers;
use common\models\GlobalPaymentsTransactions;
use common\services\GlobalPaymentsTransactionsService;
use common\services\PlatformsConfigurationService;
use GlobalPayments\Api\ServicesConfig;
use GlobalPayments\Api\HostedPaymentConfig;
use GlobalPayments\Api\Entities\HostedPaymentData;
use GlobalPayments\Api\Entities\Address;
use GlobalPayments\Api\Services\HostedService;
use GlobalPayments\Api\Entities\Enums\AddressType;
use GlobalPayments\Api\Entities\Exceptions\ApiException;
use GlobalPayments\Api\Entities\Enums\HppVersion;

class GlobalPaymentsService
{
    const PAYMENT_SUCCESS_CODE = '00';

    /** @var GlobalPaymentsTransactionsService */
    private $globalPaymentsTransactionsService;

    public function __construct(GlobalPaymentsTransactionsService $globalPaymentsTransactionsService)
    {
        $this->globalPaymentsTransactionsService = $globalPaymentsTransactionsService;
    }

    public function authorizationRequest(
        HostedService $service,
        string $orderId,
        Price $amount,
        string $payerRef,
        Order $order,
        Country $country
    )
    {
        $hostedPaymentData = $this->getHostedData($order, $payerRef);
        $billingAddress = $this->getAddress($order->billing, $country);
        $shippingAddress = $this->getAddress($order->delivery, $country);
        try {
            $hppJson = $service->charge($amount->getAmountRound())
                ->withCurrency($amount->getCurrency())
                ->withOrderId($orderId)
                ->withHostedPaymentData($hostedPaymentData)
                ->withAddress($billingAddress, AddressType::BILLING)
                ->withAddress($shippingAddress, AddressType::SHIPPING)
                ->serialize();

            return $hppJson;

        } catch (ApiException $e) {
            return json_encode($e->getMessage());
        }
    }

    public function getHash(string $merchantId, string $secret, string $gpOrderId, Price $amount, \DateTimeImmutable $date): string
    {
        $sha1hash = sha1("{$date->format('YmdHis')}.$merchantId.$gpOrderId.{$amount->getAmountRoundInt()}.{$amount->getCurrency()}.FALSE");
        return sha1("$sha1hash.$secret");
    }

    public function generateGPOrderId(string $orderId, \DateTimeImmutable $date): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/im', '-', $orderId).'-'.$date->format('YmdHis') . '-' . random_int(1, 999);
    }

    public function getGPService(
        string $merchantId,
        string $account,
        string $secret,
        string $requestUrl
    ): HostedService
    {
        $config = $this->getConfig($merchantId, $account, $secret, $requestUrl);
        $config->hostedPaymentConfig = new HostedPaymentConfig();
        $config->hostedPaymentConfig->displaySavedCards = true;
        $config->hostedPaymentConfig->version = HppVersion::VERSION_2;
        return new HostedService($config);
    }

    public function getChargeFromOrder(Order $order, currencies $currencies): Price
    {
        $orderAmount = 0;
        if (is_array($order->totals) && $order->info['orders_id'] > 0) {
                foreach ($order->totals as $key => $total) {
                    if ($total['class'] === 'ot_due') {
                        $orderAmount = (float)$order->totals[$key]['value_inc_tax'];
                        break;
                    }
                }
        } else {
            $orderAmount = (float)$order->info['total_inc_tax'];
        }

        $currency = $this->getPaymentCurrency($order->info['currency']);
        if ($currency !== $order->info['currency']) {
            // $orderAmount = number_format($orderAmount * $currencies->get_value($currency), $currencies->get_decimal_places($currency), '.', '');
            $orderAmount *= $currencies->get_value($currency);
        }
        // $orderAmount = (int)number_format($orderAmount *100, 0, '', '');
        return Price::create($orderAmount, $currency, 2);
    }

    /**
     * @param Order $order
     * @param null|string $payRef
     * @return HostedPaymentData
     */
    private function getHostedData(Order $order, string $payRef): HostedPaymentData
    {
        $hostedPaymentData = new HostedPaymentData();
        if ($payRef) {
            $hostedPaymentData->customerEmail = $order->customer['email_address'];
            $hostedPaymentData->customerPhoneMobile = $order->customer['telephone'];
            $hostedPaymentData->addressesMatch = false;
            $hostedPaymentData->offerToSaveCard = true;
            $hostedPaymentData->customerKey = $payRef;
            //$hostedPaymentData->paymentKey = $payRef;
            $hostedPaymentData->customerExists = true;
        } else {
            $hostedPaymentData->customerEmail = $order->customer['email_address'];
            $hostedPaymentData->customerPhoneMobile = $order->customer['telephone'];
            $hostedPaymentData->addressesMatch = false;
            $hostedPaymentData->offerToSaveCard = true;
            $hostedPaymentData->customerExists = false;
        }
        return $hostedPaymentData;
    }


    public function addGlobalPaymentsTransaction(string $transactionId, string $gpOrderId, string $storeName, Customers $customer, array $responseValues): GlobalPaymentsTransactions
    {
        return $this->globalPaymentsTransactionsService->addGlobalPaymentsTransaction($transactionId, $gpOrderId, $storeName, $customer, $responseValues);
    }

    /**
     * @param string $transaction_id
     * @param bool $asArray
     * @return array|GlobalPaymentsTransactions|null
     */
    public function findTransaction(string $transaction_id, bool $asArray = false) {
        return $this->globalPaymentsTransactionsService->findTransaction($transaction_id, $asArray);
    }
    /**
     * @param array $address
     * @param Country $country
     * @return Address
     */
    private function getAddress(array $address, Country $country): Address
    {
        $gpAddress = new Address();
        $gpAddress->streetAddress1 = $address['street_address'] ?? null;
        $gpAddress->streetAddress2 = $address['suburb'] ?? null;
        //$gpAddress->streetAddress3 = '';
        $gpAddress->city = $address['city'] ?? null;
        $gpAddress->state = $address['state'] ?? null;
        $gpAddress->postalCode = $address['postcode'] ?? null;
        $gpAddress->country = isset($address['country']) ? $country->alpha3($address['country']['iso_code_3'])['numeric']: null;
        return $gpAddress;
    }

    /**
     * @param string $merchantId
     * @param string $account
     * @param string $secret
     * @param string $requestUrl
     * @param string $rebatePassword
     * @param string $refundPassword
     * @return ServicesConfig
     */
    public function getConfig(string $merchantId, string $account, string $secret, string $requestUrl, string $rebatePassword = '', string $refundPassword = ''): ServicesConfig
    {
        $config = new ServicesConfig();
        $config->merchantId = $merchantId;
        $config->accountId = $account;
        $config->sharedSecret = $secret;
        $config->serviceUrl = $requestUrl;
        $config->rebatePassword = $rebatePassword;
        $config->refundPassword = $refundPassword;
        return $config;
    }

    /**
     * @param string $currentCurrency
     * @return string
     */
    public function getPaymentCurrency(string $currentCurrency = 'EUR'): string
    {
        if (!in_array($currentCurrency, ['EUR', 'GBP'])) {
            $currentCurrency = defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_CURRENCY') ? MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_CURRENCY : 'EUR';
        }
        return $currentCurrency;
    }

    public static function allowed(): bool
    {
        try {
            /** @var PlatformsConfigurationService $platformsConfigurationService */
            $platformsConfigurationService = \Yii::createObject(PlatformsConfigurationService::class);
            if (defined('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS')) {
                return true;
            }
            return $platformsConfigurationService->existByKey('MODULE_PAYMENT_GLOBAL_PAYMENTS_HPP_STATUS');
        } catch (\Exception $e) {
            return false;
        }
    }
}

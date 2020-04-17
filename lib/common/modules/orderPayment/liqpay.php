<?php

namespace common\modules\orderPayment;

use common\classes\Currencies;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\modules\orderPayment\LiqPay\services\LiqPayService;

class liqpay extends ModulePayment
{
    /** @var string */
    public $title;
    /** @var string */
    public $description;
    /** @var string */
    public $public_key;
    /** @var string */
    public $private_key;
    /** @var string */
    public $action;
    /** @var string */
    public $sandbox;
    /** @var bool  */
    public $enabled;
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_LIQPAY_TEXT_TITLE' => 'Privat Bank',
        'MODULE_PAYMENT_LIQPAY_TEXT_DESCRIPTION' => 'LiqPay'
    ];
    /** @var LiqPayService */
    private $liqPayService;
    /** @var Currencies */
    private $currencies;
    /** @var string */
    private $language;
    function __construct()
    {
        parent::__construct();
        $this->countries = ['UKR'];
        $this->code = 'liqpay';
        $this->title = defined('MODULE_PAYMENT_LIQPAY_TEXT_TITLE') ? MODULE_PAYMENT_LIQPAY_TEXT_TITLE : 'LiqPay title';
        $this->description = defined('MODULE_PAYMENT_LIQPAY_TEXT_DESCRIPTION') ? MODULE_PAYMENT_LIQPAY_TEXT_DESCRIPTION : 'LiqPay description';
        $this->action = 'pay';

        if (!defined('MODULE_PAYMENT_LIQPAY_STATUS')) {
            $this->enabled = false;
            return;
        }
        $this->liqPayService = \Yii::createObject(LiqPayService::class);
        $this->currencies = \Yii::$container->get('currencies');
        $this->public_key = MODULE_PAYMENT_LIQPAY_PUBLIC_KEY;
        $this->private_key = MODULE_PAYMENT_LIQPAY_PRIVATE_KEY;
        $this->sandbox = MODULE_PAYMENT_LIQPAY_SANDBOX;

        $this->sort_order = MODULE_PAYMENT_LIQPAY_SORT_ORDER;
        $this->enabled = MODULE_PAYMENT_LIQPAY_STATUS === 'True';

        $languageId = (int)\Yii::$app->settings->get('languages_id');
        $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
        /** @var \common\services\LanguagesService $languagesService */
        $languagesService = \Yii::createObject(\common\services\LanguagesService::class);
        $this->language = mb_strtolower($languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code']);
        if ($this->language === 'ua') {
            $this->language = 'uk';
        }
        // $this->update_status();
    }

    public function update_status()
    {
        $publicKey = MODULE_PAYMENT_LIQPAY_PUBLIC_KEY;
        $privateKey = MODULE_PAYMENT_LIQPAY_PRIVATE_KEY;
        if (empty($publicKey) || empty($privateKey) /*|| !in_array($platform_country->countries_iso_code_3, $this->getCountries())*/) {
            $this->enabled = false;
        }
    }

    function selection()
    {
        return array(
            'id' => $this->code,
            'module' => $this->title
        );
    }


    function before_process()
    {
        $this->manager->getOrderInstance()->info['order_status'] = MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID;
    }

    function after_process()
    {
        $this->manager->clearAfterProcess();
        return $this->redirectForm();
    }

    public function redirectForm()
    {
        $order = $this->manager->getOrderInstance();
        $recalculate = defined('USE_MARKET_PRICES') && USE_MARKET_PRICES === 'True';
        $total = $this->currencies->format_clear($this->currencies->calculate_price_in_order($order->info, $order->info['total']), $recalculate, $order->info['currency']);
        $params = [
            'action' => $this->action,
            'amount' => $total,
            'currency' => $order->info['currency'],
            'description' => STORE_NAME . '-' . ORDER_PAY . $order->order_id,
            'order_id' => $order->order_id . '_' . uniqid('', false),
            'version' => '3',
            'language' => $this->language,
            'sandbox' => MODULE_PAYMENT_LIQPAY_SANDBOX === 'True' ? 1 : 0,
            'result_url' => \Yii::$app->urlManager->createAbsoluteUrl([
                'checkout/success',
                'orders_id' => $order->order_id
            ]),
            'server_url' => \Yii::$app->urlManager->createAbsoluteUrl([
                'liq-pay/handle-transaction',
                'order_id' => $order->order_id
            ]),
        ];
        echo '<html><body onload="document.forms[0].submit()"><div style="display: none;">' . $this->liqPayService->getCnbForm($this->public_key, $this->private_key, $params) . '</div></body></html>';
        exit;
    }

    /**
     * @param string $data
     * @param string $signature
     * @return bool
     */
    public function validResponse(string $data, string $signature): bool
    {
        $local_signature = base64_encode(sha1($this->private_key . $data . $this->private_key, 1));
        return $local_signature === $signature;
    }


    /**
     * @return array
     */

    public function configure_keys()
    {
        $status_id = defined('MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID') ? MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID : $this->getDefaultOrderStatusId();
        $status_id_paid = defined('MODULE_PAYMENT_LIQPAY_ORDER_PAID_STATUS_ID') ? MODULE_PAYMENT_LIQPAY_ORDER_PAID_STATUS_ID : $this->getDefaultOrderStatusId();
        return array(
            'MODULE_PAYMENT_LIQPAY_STATUS' => array(
                'title' => 'LIQPAY Enable Module',
                'value' => 'True',
                'description' => 'Do you want to accept LIQPAY payments?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),

            'MODULE_PAYMENT_LIQPAY_PUBLIC_KEY' => array(
                'title' => 'LIQPAY public key',
                'value' => '',
                'description' => '',
                'sort_order' => '2',
            ),
            'MODULE_PAYMENT_LIQPAY_PRIVATE_KEY' => array(
                'title' => 'LIQPAY private key',
                'value' => '',
                'description' => '',
                'sort_order' => '3',
            ),

            'MODULE_PAYMENT_LIQPAY_SORT_ORDER' => array(
                'title' => 'Sort order of display.',
                'value' => '0',
                'description' => 'Sort order of display. Lowest is displayed first.',
                'sort_order' => '5',
            ),
            'MODULE_PAYMENT_LIQPAY_SANDBOX' => array(
                'title' => 'LIQPAY Test mode',
                'value' => 'True',
                'description' => 'Test mode',
                'sort_order' => '6',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_LIQPAY_ORDER_PROCESS_STATUS_ID' => array(
                'title' => 'LIQPAY Set Order Processing Status',
                'value' => $status_id,
                'description' => 'Set the process status of orders made with this payment module to this value',
                'sort_order' => '14',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_LIQPAY_ORDER_PAID_STATUS_ID' => array(
                'title' => 'LIQPAY Set Order Paid Status',
                'value' => $status_id_paid,
                'description' => 'Set the paid status of orders made with this payment module to this value',
                'sort_order' => '15',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
        );
    }

    /**
     * @return ModuleStatus
     */
    public function describe_status_key(): ModuleStatus
    {
        return new ModuleStatus('MODULE_PAYMENT_LIQPAY_STATUS', 'True', 'False');
    }

    /**
     * @return ModuleSortOrder
     */
    public function describe_sort_key(): ModuleSortOrder
    {
        return new ModuleSortOrder('MODULE_PAYMENT_LIQPAY_SORT_ORDER');
    }

    function isOnline()
    {
        return true;
    }
}

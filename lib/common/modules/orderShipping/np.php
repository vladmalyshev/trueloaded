<?php

namespace common\modules\orderShipping;

use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;
use common\classes\Order;
use common\classes\VO\CollectAddress;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use common\services\OrderManager;
use common\services\storages\StorageInterface;

/**
 * Class np
 * @property OrderManager $manager
 */
class np extends ModuleShipping
{
    public $apiKey = '';
    public $tracking;
    public $enabled;
    public $cost;
    public $code;
    public $title;
    public $comment;
    public $quotes;
    public $description;
    private $language;
    /** @var NovaPoshtaServicenewInternetDocument */
    private $novaPoshtaService;
    /** @var StorageInterface */
    private $storage;

    protected $defaultTranslationArray = [
        'MODULE_SHIPPING_NP_TEXT_TITLE' => 'Nova Poshta',
        'MODULE_SHIPPING_NP_TEXT_DESCRIPTION' => 'Nova Poshta',
        'MODULE_SHIPPING_NP_TEXT_WAY' => 'Nova Poshta'
    ];

    public function __construct()
    {
        parent::__construct();
        try {
            $this->countries = ['UKR'];
            $this->code = 'np';
            $this->title = MODULE_SHIPPING_NP_TEXT_TITLE;
            $this->description = MODULE_SHIPPING_NP_TEXT_DESCRIPTION;
            if (!defined('MODULE_SHIPPING_NP_STATUS') || MODULE_SHIPPING_NP_STATUS !== 'True') {
                $this->enabled = false;
                return;
            }
            $languageId = (int)\Yii::$app->settings->get('languages_id');
            $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
            /** @var \common\services\LanguagesService $languagesService */
            $languagesService = \Yii::createObject(\common\services\LanguagesService::class);
            $this->language = $languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code'];
            $this->novaPoshtaService = \Yii::createObject(NovaPoshtaService::class);
            $this->storage = \Yii::$app->get('storage');
            $this->tracking = defined('MODULE_SHIPPING_NP_TRACKING_STATUS') && MODULE_SHIPPING_NP_TRACKING_STATUS === 'True';
            $this->apiKey = defined('MODULE_SHIPPING_NP_API_KEY') ? MODULE_SHIPPING_NP_API_KEY : '';
            $this->enabled = defined('MODULE_SHIPPING_NP_API_KEY') && MODULE_SHIPPING_NP_STATUS === 'True' && $this->apiKey !== '';
            $this->cost = defined('MODULE_SHIPPING_NP_COST') ? MODULE_SHIPPING_NP_COST : 0;
        } catch (\Exception $e) {
            $this->enabled = false;
            \Yii::error($e->getMessage());
        } catch (\Error $e) {
            restore_error_handler();
            $this->enabled = false;
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        }
    }
    public function costCaption()
    {
        $currencies = \Yii::$container->get('currencies');
        if ($this->cost > 0) {
            return $currencies->format(\common\helpers\Tax::add_tax($this->cost, $this->tax ?? 0));
        }
        return DELIVERY_SERVICE_COST_TEXT;
    }

    /**
     * @return bool|string
     */
    public function costUserCaption()
    {
        try{
            $order = $this->manager->getOrderInstance();
        } catch (\Exception $e) {
            $order = null;
        }
        if (
            $this->cost > 0 ||
            (
                is_object($order) &&
                isset($order->info['shipping_cost_exc_tax']) &&
                $order->info['shipping_cost_exc_tax'] > 0
            )
        ) {
            return false;
        }
        return DELIVERY_SERVICE_COST_TEXT;
    }


    public function configure_keys()
    {
        return [
            'MODULE_SHIPPING_NP_STATUS' =>
                [
                    'title' => 'Enable Item Shipping',
                    'value' => 'True',
                    'description' => 'Do you want to offer NovaPoshta shipping?',
                    'sort_order' => '0',
                    'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                ],
            'MODULE_SHIPPING_NP_API_KEY' =>
                [
                    'title' => 'NovaPoshta ApiKey',
                    'value' => '',
                    'description' => '',
                    'sort_order' => '0',
                ],
            'MODULE_SHIPPING_NP_COST' =>
                [
                    'title' => 'NovaPoshta Shipping Shipping Cost',
                    'value' => '40',
                    'description' => '',
                    'sort_order' => '0',
                ],
            'MODULE_SHIPPING_NP_SORT_ORDER' =>
                [
                    'title' => 'NP Shipping Sort Order',
                    'value' => '0',
                    'description' => 'Sort order of display.',
                    'sort_order' => '0',
                ],
            'MODULE_SHIPPING_NP_PREFERRED_METHOD' =>
                [
                    'title' => 'Preferred Shipping Method',
                    'value' => 'WarehouseToWarehouse',
                    'description' => 'Sort order of display.',
                    'sort_order' => '0',
                    'set_function' => 'tep_cfg_select_option(array(\'WarehouseToWarehouse\', \'WarehouseToDoors\'), ',
                ],
            'MODULE_SHIPPING_NP_TRACKING_NUMBER_URL' =>
                [
                    'title' => 'Tracking Url',
                    'value' => 'https://novaposhta.ua/tracking/?newtracking=1&cargo_number=',
                    'description' => 'Url for tracking cargo',
                    'sort_order' => '0',
                ],
            'MODULE_SHIPPING_NP_TRACKING_STATUS' => [
                'title' => 'Enable NP Tracking',
                'value' => 'True',
                'description' => 'Do you want to enable NP tracking?',
                'sort_order' => '0',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],


        ];
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_SHIPPING_NP_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_SHIPPING_NP_SORT_ORDER');
    }

    public function getAdditionalOrderParams(array $shippingParams = [], $orderId = null, string $type = ''): string
    {
        if (isset($shippingParams[$this->code])) {
            $shippingParams = $shippingParams[$this->code];
        } elseif ($orderId > 0) {
            $shippingParams = $this->novaPoshtaService->findShippingData($orderId, $type, true);
            if (!$shippingParams) {
                return '';
            }
            return $this->novaPoshtaService->getWidgetInfo($this->novaPoshtaService->shippingParamsArrayToView($shippingParams));
        } else {
            return '';
        }
        return $this->novaPoshtaService->getWidgetInfo($this->novaPoshtaService->shippingParamsSourceArrayToView($shippingParams));
    }

    public function useDelivery()
    {
        return true;
        if ($this->manager->getShippingChoice()) {
            return true;
        }
        return false;
    }

    /**
     * @param string $method
     * @return array|void
     * @throws \Exception
     */
    public function quote($method = '')
    {
        $orderId = $this->getOrderId();
        $isFreeShipping = $this->manager->checkFreeShipping();
        $this->quotes = [
            'id' => $this->code,
            'module' => $this->title,
            'comment' => $this->comment,
            'methods' => [],
        ];
        if ($method !== '') {
            $this->quotes['methods'] = $this->getQuoteByMethod($method, $isFreeShipping, $orderId);
        }else {
            $this->quotes['methods'] = $this->quoteCombine($isFreeShipping, $orderId);
        }
        /*
        if ($method !== '') {
            $this->quotes['methods'] = $this->getQuoteByMethod($method, $isFreeShipping, $orderId);
        }elseif ($this->manager->combineShippings) {
            $this->quotes['methods'] = $this->quoteCombine($isFreeShipping, $orderId);
        } elseif($this->manager->getShippingChoice()) {
            $this->quotes['methods'][] = $this->quoteAddress($isFreeShipping);
        } else{
            $this->quotes['methods'][] = $this->quoteWarehouse($isFreeShipping, $orderId);
        }
        /**/
        return $this->quotes;
    }

    /**
     * @param bool $isFreeShipping
     * @param null $orderId
     * @return array
     * @throws \Exception
     */
    private function quoteCombine(bool $isFreeShipping = false, $orderId = null): array
    {
        $methods = [
            $this->quoteWarehouse($isFreeShipping, $orderId),
            $this->quoteAddress($isFreeShipping),
        ];
        if (defined('MODULE_SHIPPING_NP_PREFERRED_METHOD') && MODULE_SHIPPING_NP_PREFERRED_METHOD === 'WarehouseToDoors') {
            $methods = [
                $this->quoteAddress($isFreeShipping),
                $this->quoteWarehouse($isFreeShipping, $orderId),
            ];
        }
        return $methods;
    }

    private function getQuoteByMethod(string $methodName, bool $isFreeShipping = false, $orderId = null): array
    {
        $method = $this->{'quote'.ucfirst($methodName)}($isFreeShipping, $orderId);
        return [$method];
    }
    /**
     * @param bool $isFreeShipping
     * @return array
     */
    private function quoteAddress(bool $isFreeShipping = false, $orderId = null): array
    {
        return [
            'id' => 'address',
            'title' => MODULE_SHIPPING_NP_ADDRESS_WAY,
            'cost' => $isFreeShipping || (!$isFreeShipping && !defined('MODULE_SHIPPING_NP_COST')) ? 0 : (float)MODULE_SHIPPING_NP_COST,
            'cost_f' => $isFreeShipping ? FREE_SHIPPING_TITLE : $this->costCaption(),
            'cost_caption' => $isFreeShipping ? FREE_SHIPPING_TITLE : $this->costCaption(),
            'hideaddress' => false,
        ];
    }

    /**
     * @param bool $isFreeShipping
     * @param bool $orderId
     * @return array
     * @throws \Exception
     */
    private function quoteWarehouse(bool $isFreeShipping = false, $orderId = false): array
    {
        return [
            'id' => 'warehouse',
            'title' => MODULE_SHIPPING_NP_WAREHOUSE_WAY,
            'cost' => $isFreeShipping || (!$isFreeShipping && !defined('MODULE_SHIPPING_NP_COST')) ? 0 : (float)MODULE_SHIPPING_NP_COST,
            'cost_f' => $isFreeShipping ? FREE_SHIPPING_TITLE : $this->costCaption(),
            'cost_caption' => $isFreeShipping ? FREE_SHIPPING_TITLE : $this->costCaption(),
            'hideaddress' => true,
            'widget' => $this->novaPoshtaService->getWidget(
                $this->code,
                $this->apiKey,
                $orderId,
                $this->language,
                ''
            ),
        ];
    }


    public function needDeliveryDate()
    {
        return false;
    }

    /**
     * @param string $method
     * @param null|array|object $data
     * @return bool|array
     */
    public function validate(string $method = '', $data = null)
    {
        if ($data === null) {
            $data = \Yii::$app->request->post('shippingparam') ?? $this->storage->get('shippingparam');
        }
        if (method_exists($this,'validateUser'.ucfirst($method))) {
            return $this->{'validateUser'.ucfirst($method)}($data);
        }
        return true;
    }

    /**
     * @param array $data
     * @return bool|array
     */
    public function validateUserWarehouse(array $data)
    {
        if (isset($data['shippingparam'])) {
            $data = $data['shippingparam'];
        }
        if (isset($data['np'])) {
            $data = $data['np'];
        }
        $this->manager->setSoftShippingValidation(true);
        return $this->novaPoshtaService->validateShipingParams($data);
    }

    /**
     * @return bool|int
     */
    private function getOrderId()
    {
        $orderId = \Yii::$app->request->get('orders_id', false);
        try {
            /** @var Order $order */
            $order = $this->manager->getOrderInstance();
        } catch (\Exception $e) {
            $order = null;
        }

        if (is_object($order)) {
            $orderId = $order->getOrderId();
        }
        if (is_numeric($orderId)) {
            $orderId = (int)$orderId;
        }
        return $orderId;
    }

    /**
     * @param string $method
     * @return bool|\common\classes\VO\CollectAddress
     */
    public function toCollect(string $method = '')
    {
        if ($method !== 'warehouse') {
            return false;
        }
        try{
            /** @var Order $order */
            $order = $this->manager->getOrderInstance();
            if (is_numeric($order->getOrderId())) {
                $orderId = (int)$order->getOrderId();
                $shippingParams = $this->novaPoshtaService->findShippingData($orderId, $order->table_prefix, true);
                $shippingParams = $shippingParams['valueData'];
            } else {
                $shippingParams = $this->storage->get('shippingparam');
                if (!is_array($shippingParams) || !array_key_exists('np', $shippingParams)) {
                    throw new \DomainException('Shipping param "storage" is empty');
                }
                $shippingParams = $shippingParams['np'];
            }
            $streetAddress = explode(':', $shippingParams['warehouseText']);
            $warehouse = isset($streetAddress[1]) ? trim(trim($streetAddress[0], ',')) : '';
            $streetAddress = isset($streetAddress[1]) ? trim(trim($streetAddress[1], ',')) : trim(trim($streetAddress[0], ','));
            return (CollectAddress::create(
                $streetAddress,
                $shippingParams['cityText'],
                $shippingParams['areaText'],
                '',
                $this->delivery['country']['countries_name'] ?? $this->delivery['country']['title'],
                $this->delivery['country']['iso_code_2'],
                $this->delivery['country']['iso_code_3'],
                $warehouse
            ));
        } catch (\Exception $e) {
            \Yii::error(__CLASS__ . '(' . __FUNCTION__ . ')' . ' cannot get order class! ' . $e->getMessage());
            return false;
        } catch (\Error $e) {
            restore_error_handler();
            \Yii::error(__CLASS__ . '(' . __FUNCTION__ . ')' . ' cannot get order class! ' . $e->getMessage());
            return false;
        }
    }

    public function reorderShippingData(int $orderId, int $referenceOrderId, string $type =''): void
    {
        $shippingData = $this->novaPoshtaService->findShippingData($referenceOrderId, $type);
        $this->novaPoshtaService->createNewFromExist($orderId, $shippingData, $type);
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare (strict_types=1);

namespace common\modules\label;

use backend\services\OrdersLabelService;
use common\classes\modules\ModuleLabel;
use common\classes\modules\ModuleSortOrder;
use common\classes\modules\ModuleStatus;
use common\classes\Order;
use common\models\OrdersLabel;
use common\modules\orderShipping\NovaPoshta\API\DTO\Area;
use common\modules\orderShipping\NovaPoshta\API\DTO\CargoDescription;
use common\modules\orderShipping\NovaPoshta\API\DTO\City;
use common\modules\orderShipping\NovaPoshta\API\DTO\Warehouse;
use common\modules\orderShipping\NovaPoshta\API\NPApiClient;
use common\modules\label\np\forms\NovaPoshtaCreateDocumentCollectForm;
use common\modules\label\np\widgets\NovaPoshtaDocument;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use Yii;

class np extends ModuleLabel
{
    const DIRECT_DELIVERY = 'np_address';
    const COLLECTION_POINT = 'np_warehouse';

    const TYPE_DELIVERY_WAREHOUSE_WAREHOUSE = 'WarehouseWarehouse';
    const TYPE_DELIVERY_WAREHOUSE_DOORS = 'WarehouseDoors';

    const COUNTERPARTY_SENDER = 'Sender';
    const COUNTERPARTY_RECIPIENT = 'Recipient';

    const PAYMENT_METHOD_CASH = 'Cash';
    const PAYMENT_METHOD_NON_CASH = 'NonCash';

    const TYPE_DELIVERIES = [
        self::TYPE_DELIVERY_WAREHOUSE_WAREHOUSE => WAREHOUSE_WAREHOUSE_TEXT,
        self::TYPE_DELIVERY_WAREHOUSE_DOORS => WAREHOUSE_WAREHOUSE_DOORS,
    ];
    const METHODS = [
        self::DIRECT_DELIVERY => MODULE_SHIPPING_NP_ADDRESS_WAY,
        self::COLLECTION_POINT => MODULE_SHIPPING_NP_WAREHOUSE_WAY,
    ];
    public $title;
    public $description;
    public $code = 'np';
    private $client;

    public $can_update_shipment = false;
    public $can_cancel_shipment = true;

    /** @var \common\services\OrderManager */
    private $orderManager;
    /** @var OrdersLabelService */
    private $ordersLabelService;
    /** @var NovaPoshtaService|object */
    private $novaPoshtaService;
    /** @var \common\classes\platform */
    private $platform;
    /** @var int */
    private $adminId;

    public function __construct()
    {
        global $login_id;

        $this->title = 'Нова Пошта';
        $this->description = 'Нова Пошта';
        $this->countries = ['UKR'];
        $languageId = (int)\Yii::$app->settings->get('languages_id');
        $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
        /** @var \common\services\LanguagesService $languagesService */
        $languagesService = \Yii::createObject(\common\services\LanguagesService::class);
        $language = $languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code'];
        $this->novaPoshtaService = \Yii::createObject(NovaPoshtaService::class);
        $this->client = \Yii::createObject(NPApiClient::class)->withLanguage((string)$language);
        if(defined('MODULE_LABEL_NP_API_KEY') && MODULE_LABEL_NP_API_KEY) {
            $this->client->withApiKey(MODULE_LABEL_NP_API_KEY);
        } else {
            return;
        }

        $this->ordersLabelService = \Yii::createObject(OrdersLabelService::class);
        $this->orderManager = \common\services\OrderManager::loadManager();
        $this->platform = \Yii::$app->get('platform');
        $this->adminId = (int)$login_id;
    }

    public function get_methods($country_iso_code_2, $method = '', $shipping_weight = 0, $num_of_sheets = 0, $orderLabelId = 0)
    {
        $orderId = \Yii::$app->request->post('orders_id') ?? \Yii::$app->request->get('orders_id');
        $orderId = (int)$orderId;
        $orderLabelId = (int)$orderLabelId;
        if ($orderLabelId === 0) {
            $orderLabelId = \Yii::$app->request->post('orders_label_id') ?? \Yii::$app->request->get('orders_label_id');
            $orderLabelId = (int)$orderLabelId;
        }

        if (!$orderLabelId) {
            $products = array_keys(\Yii::$app->request->get('selected_products', []));
            $orderLabel = $this->ordersLabelService->findLabelByProducts($orderId, $products);
        } else {
            $orderLabel = $this->ordersLabelService->findLabelByOrder($orderId, $orderLabelId);
        }
        if ($orderLabel === null && $orderId) {
            $this->ordersLabelService->removeOrderProductLabelsByOrder($orderId);
            return $this->invalidLabel($orderId);
        }
        $methods = [
            $this->code . '_' . $this->code => [
                'name' => $this->title,
                'widget' => $this->getForm($orderId, $orderLabel),
            ],
        ];
        return $methods;
    }

    function create_shipment(int $orderId, int $orderLabelId, string $method = '')
    {
        $orderLabel = $this->ordersLabelService->findLabelByOrder($orderId, $orderLabelId);
        /** @var Order $order */
        $order = $this->orderManager->getOrderInstanceWithId(Order::class, $orderId);
        if ($orderLabel && $orderLabel->label_status === OrdersLabel::LABEL_STATUS_DONE) {
            return array_replace(json_decode(json_encode($orderLabel), true), [
                'parcel_label' => base64_decode($orderLabel['parcel_label_pdf']),
            ]);
        }
        $form = new NovaPoshtaCreateDocumentCollectForm();
        if (!$form->load(\Yii::$app->request->get()) || !$form->validate()) {
            if ($orderLabel) {
                $this->ordersLabelService->remove($orderLabel);
            }
            $this->ordersLabelService->removeOrderProductLabelsByOrder($orderId);
            return $this->invalidLabel($orderId);
        }
        try {
            $this->novaPoshtaService->saveSenderWarehouse(
                $form->areaSender,
                $form->citySender,
                $form->warehouseSender,
                $form->description,
                (int)$order->info['platform_id']
            );
            $counterpartyApi = $this->client->api('counterparty');
            $recipient = $counterpartyApi->save(
                $form->firstname,
                $form->middlename,
                $form->lastname,
                $form->telephone,
                (string)$form->email
            );
        } catch (\Exception $e) {
            $this->ordersLabelService->edit($orderLabel, [
                'label_status' => OrdersLabel::LABEL_STATUS_ERROR,
                'label_module_error' => $e->getMessage(),
                'admin_id' => $this->adminId,
                'date_created' => (new \DateTimeImmutable())->format('Y-m-d'),
            ]);
            return array_replace(json_decode(json_encode($orderLabel), true), [
                'tracking_number' => 'Error',
                'errors' => [$e->getMessage()]
            ]);
        }
        if ($form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS) {
            $addressApi = $this->client->api('address');
            $cities = $addressApi->getCities('', $form->cityNameRecipient);
            $streets = null;
            $cityRecipient = null;
            $addressRecipient = null;
            foreach ($cities as $city) {
                try {
                    $streets = $addressApi->getStreet($city->getRef(), $form->addressNameRecipient);
                } catch (\Exception $e) {
                    $streets = null;
                }
                if ($streets) {
                    $addressRecipient = $addressApi->save(
                        $recipient->getRef(),
                        $streets[0]->getRef(),
                        $form->houseRecipient,
                        $form->flatRecipient
                    );
                    $cityRecipient = $city;
                    break;
                }
            }
            if (!$addressRecipient) {
                throw new \RuntimeException('Nova Poshta Street Not Found');
            }
        }
        try {
            $documentApi = $this->client->api('document');
            $document = $documentApi->save(
                $form->description,
                (float)$form->cost,
                (float)$form->weight,
                (string)$form->type,
                $form->senderRef,
                $form->citySender,
                $form->warehouseSender,
                $form->senderContactRef,
                $form->telephoneSender,
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $cityRecipient->getRef() : $form->cityRecipient,
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $cityRecipient->getAreaRef() : $form->areaRecipient,
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $addressRecipient->getRef() : $form->warehouseRecipient,
                $recipient->getRef(),
                $recipient->getContactPerson()->getRef(),
                $form->telephone,
                $form->getDeliveryDate()->format('d.m.Y'),
                'PrivatePerson',
                $form->payerType,
                'Cash',
                $form->cargo,
                (float)$form->volumeGeneral,
                (int)$form->seatsAmount,
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $form->houseRecipient : '',
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $form->flatRecipient : '',
                $form->type === self::TYPE_DELIVERY_WAREHOUSE_DOORS ? $recipient->getContactPerson()->getDescription() : '',
                (int)$form->backwardDelivery,
                (float)$form->backwardCost
            );
        } catch (\Exception $e) {
            $this->ordersLabelService->edit($orderLabel, [
                'label_status' => OrdersLabel::LABEL_STATUS_ERROR,
                'label_module_error' => $e->getMessage(),
                'admin_id' => $this->adminId,
                'date_created' => (new \DateTimeImmutable())->format('Y-m-d'),
            ]);
            return array_replace(json_decode(json_encode($orderLabel), true), [
                'tracking_number' => 'Error',
                'errors' => [$e->getMessage()]
            ]);
        }
        $pdf = $documentApi->printDocument($document->getRef());

        $addTracking = \common\classes\OrderTrackingNumber::instanceFromString(MODULE_SHIPPING_NP_TRACKING_NUMBER_URL . $document->getIntDocNumber(), (int)$form->orderId);
        $addTracking->tracking_url = MODULE_SHIPPING_NP_TRACKING_NUMBER_URL . $document->getIntDocNumber();
        $addTracking->setOrderProducts($orderLabel->getOrdersLabelProducts());
        $order->info['tracking_number'][] = $addTracking;
        $order->saveTrackingNumbers();

        $this->ordersLabelService->edit($orderLabel, [
            'tracking_number' => $document->getRef(),
            'tracking_numbers_id' => $addTracking->tracking_numbers_id,
            'label_status' => OrdersLabel::LABEL_STATUS_DONE,
            'admin_id' => $this->adminId,
            'parcel_label_pdf' => base64_encode($pdf),
            'date_created' => (new \DateTimeImmutable())->format('Y-m-d'),
        ]);

        return array_replace(json_decode(json_encode($orderLabel), true), [
            'parcel_label' => $pdf,
        ]);


    }


    public function getForm(int $orderId, OrdersLabel $ordersLabel)
    {
        $shipmentWeight = round($this->shipment_weight($orderId, $ordersLabel->orders_label_id), 2);
        $shipmentVolume = round($this->shipment_volume_weight($orderId, $ordersLabel->orders_label_id), 2);
        $commonApi = $this->client->api('common');
        $cargoTypes = $commonApi->getCargoTypes();
        $cargoDescriptions = $commonApi->getCargoDescription();
        $cargoDescriptions = array_reduce($cargoDescriptions, static function (array $carry, CargoDescription $item) {
            $carry[] = $item->getDescription();
            return $carry;
        }, []);
        unset($commonApi);
        $addressApi = $this->client->api('address');
        $areas = $addressApi->getAreas();
        $cities = $addressApi->getCities();

        /** @var Order $order */
        $order = $this->orderManager->getOrderInstanceWithId(Order::class, $orderId);
        $this->platform->config($order->info['platform_id'])->constant_up();
        if (!$order) {
            throw new \RuntimeException('Order Not Found');
        }
        $counterpartyApi = $this->client->api('counterparty');
        $sender = $counterpartyApi->getCounterparties('Sender');
        $senderInfo = $counterpartyApi->getCounterpartyContactPersons($sender[0]->getRef());
        $form = (new NovaPoshtaCreateDocumentCollectForm())
            ->withPrepareInfo(
                $cargoTypes,
                json_encode($cargoDescriptions),
                defined('MODULE_NP_CARGO_DESCRIPTION') ? MODULE_NP_CARGO_DESCRIPTION : '',
                $areas,
                $cities,
                [],
                (float)$order->info['total_exc_tax'],
                (float)$shipmentWeight,
                (float)$shipmentVolume,
                date('Y-m-d'), // (string)$order->info['delivery_date'],
                $ordersLabel->orders_label_id,
                $orderId,
                $order->info['payment_class'] === 'cod' ? 1 : 0
            )
            ->withSenderInfo(
                Area::createSimply(MODULE_NP_SENDER_AREA_REF),
                City::createSimply(MODULE_NP_SENDER_CITY_REF),
                Warehouse::createSimply(MODULE_NP_SENDER_WAREHOUSE_REF),
                $senderInfo[0]->getPhones(),
                $sender[0]->getRef(),
                $senderInfo[0]->getRef()
            )->withType(self::TYPE_DELIVERY_WAREHOUSE_DOORS);
        if (defined('MODULE_NP_SENDER_CITY_REF') && MODULE_NP_SENDER_CITY_REF) {
            $form->setSenderWarehouses($addressApi->getWarehouses(MODULE_NP_SENDER_CITY_REF));
        }

        $shippingParam = $this->novaPoshtaService->findShippingData($order->order_id, $order->table_prefix, true);
        if ($shippingParam) {
            $form->withRecipientCollectInfo(
                Area::createSimply($shippingParam['valueData']['area'], $shippingParam['valueData']['areaText']),
                City::createSimply($shippingParam['valueData']['city'], $shippingParam['valueData']['cityText']),
                Warehouse::createSimply($shippingParam['valueData']['warehouse'], $shippingParam['valueData']['warehouseText']),
                $shippingParam['valueData']['firstname'],
                $shippingParam['valueData']['lastname'],
                '',
                $shippingParam['valueData']['telephone'],
                $order->delivery['email_address'] ?? $order->customer['email_address']
            )->setRecipientWarehouses($addressApi->getWarehouses($shippingParam['valueData']['city']))
                ->withType(self::TYPE_DELIVERY_WAREHOUSE_WAREHOUSE);
        } else {
            $form->withRecipientAddressInfo(
                $order->delivery['state'],
                '',
                $order->delivery['city'],
                $order->delivery['street_address'],
                '',
                '',
                $order->delivery['firstname'],
                $order->delivery['lastname'],
                '',
                $order->delivery['telephone'],
                $order->delivery['email_address'] ?? $order->customer['email_address']
            );
        }
        unset($addressApi);
        return NovaPoshtaDocument::widget([
            'form' => $form
        ]);
    }

    public function configure_keys()
    {
        return [
            'MODULE_LABEL_NP_STATUS' =>
                [
                    'title' => 'Enable NP Labels',
                    'value' => 'True',
                    'description' => 'Do you want to offer NP labels?',
                    'sort_order' => '0',
                    'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
                ],
            'MODULE_LABEL_NP_API_KEY' =>
                [
                    'title' => 'ApiKey',
                    'value' => '',
                    'description' => 'ApiKey',
                    'sort_order' => '1',
                ],
            'MODULE_LABEL_NP_SORT_ORDER' =>
                [
                    'title' => 'APC Sort Order',
                    'value' => '0',
                    'description' => 'Sort order of display.',
                    'sort_order' => '10',
                ],
            'MODULE_NP_CARGO_DESCRIPTION' =>
                [
                    'title' => 'Cargo Description',
                    'value' => '',
                    'description' => 'Cargo Description (Saved automatically)',
                    'sort_order' => '10',
                    'set_function' => 'getAutoCompleteField([\'' . NovaPoshtaService::class . '\',\'getCargoDescriptions\'],  ',
                ],
            'MODULE_NP_SENDER_AREA_REF' =>
                [
                    'title' => 'Sender Area',
                    'value' => '',
                    'description' => 'Ref Sender Area (Saved automatically)',
                    'sort_order' => '10',
                    'set_function' => 'getDropDownField([\'' . NovaPoshtaService::class . '\',\'getAreas\'],  ',
                ],
            'MODULE_NP_SENDER_CITY_REF' =>
                [
                    'title' => 'Sender City',
                    'value' => '',
                    'description' => 'Ref Sender City (Saved automatically)',
                    'sort_order' => '10',
                    'set_function' => 'getDropDownDependField([\'' . NovaPoshtaService::class . '\',\'getCities\'], \'MODULE_NP_SENDER_AREA_REF\', ',
                ],
            'MODULE_NP_SENDER_WAREHOUSE_REF' =>
                [
                    'title' => 'Sender Warehouse',
                    'value' => '',
                    'description' => 'Ref Sender Warehouse (Saved automatically)',
                    'sort_order' => '10',
                    'set_function' => 'getAutoCompleteAjaxDependField([\'' . NovaPoshtaService::class . '\',\'getWarehouseByCityAjaxLink\'], \'MODULE_NP_SENDER_CITY_REF\', \'GET\' , \'cityRef\' , ',
                ],
        ];
    }

    public function cancel_shipment($order_id, $orders_label_id) {
        $return = [];
        $orderId = (int)$order_id;
        $orderLabelId = (int)$orders_label_id;
        \common\helpers\Translation::init('admin/orders');
        $order = $this->orderManager->getOrderInstanceWithId(Order::class, $orderId);
        Yii::$app->get('platform')->config($order->info['platform_id'])->constant_up();
        $this->orderManager->set('platform_id', $order->info['platform_id']);
        $orderLabel = $this->ordersLabelService->findLabelByOrder($orderId, $orderLabelId);
        $trackingNumbersId = (int)$orderLabel->tracking_numbers_id;
        $trackingNumberRef = $orderLabel->tracking_number;
        $return['success'] = 'Failed Chancel';
        $documentApi = $this->client->api('document');
        if ($trackingNumberRef && $documentApi->delete($trackingNumberRef)) {
            $return['success'] = 'Successfully Chancel';
            if ($orderLabel) {
                $this->ordersLabelService->remove($orderLabel);
            }
            if ($trackingNumbersId) {
                $order->removeTrackingNumber($trackingNumbersId);
            }
        }
        return $return;
    }

    public function describe_status_key()
    {
        return new ModuleStatus('MODULE_LABEL_NP_STATUS', 'True', 'False');
    }

    public function describe_sort_key()
    {
        return new ModuleSortOrder('MODULE_LABEL_NP_SORT_ORDER');
    }

    public function needDeliveryDate()
    {
        return false;
    }

    private function invalidLabel(int $orderId)
    {
       /* try {
            [$c, $aId] = \Yii::$app->createController('orders/print-label');
            $a = $c->createAction($aId);
            return $a->runWithParams(['orders_id' => $orderId]);
        } catch (\Exception $e) {
            return \Yii::$app->response->redirect(Yii::$app->urlManager->createUrl(['orders/process-order','orders_id' => $orderId]));
            \Yii::$app->end();
        }/**/
    }

    public function withoutSettings(OrdersLabel $ordersLabel): bool
    {
        return $ordersLabel->label_status !== OrdersLabel::LABEL_STATUS_DONE;
    }
}

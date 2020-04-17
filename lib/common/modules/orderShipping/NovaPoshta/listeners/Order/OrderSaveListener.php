<?php
declare (strict_types=1);

namespace common\modules\orderShipping\NovaPoshta\listeners\Order;

use common\classes\events\common\order\OrderSaveEvent;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use common\services\storages\StorageInterface;

class OrderSaveListener
{
    /** @var NovaPoshtaService */
    private $novaPoshtaService;
    /** @var StorageInterface */
    private $storage;

    public function __construct(NovaPoshtaService $novaPoshtaService)
    {
        $this->novaPoshtaService = $novaPoshtaService;
        $this->storage = \Yii::$app->get('storage');
    }

    public function process(OrderSaveEvent $event)
    {
        try {
            $order = $event->getOrder();
            if ($order->info['shipping_class'] !== 'np_warehouse') {
                return;
            }
            $npShippingData = \Yii::$app->request->post('shippingparam');
            if ($npShippingData !== null && isset($npShippingData['np'])) {
                $npShippingData = $npShippingData['np'];
            }
            $this->novaPoshtaService->findShippingDataAndDelete((int)$order->order_id, $order->table_prefix);
            if ($this->storage->has('shippingparam')) {
                $value = $this->storage->get('shippingparam');
                if (isset($value['np'])) {
                    $npShippingData = $value['np'];
                    unset($value['np']);
                    $this->storage->set('shippingparam', $value);
                }
            }
            if ($npShippingData) {
                $this->novaPoshtaService->saveShippingData($order, $npShippingData);
            }

        } catch (\Exception $e) {
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        } catch (\Throwable $e) {
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        } catch (\Error $e) {
            restore_error_handler();
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        }
    }
}

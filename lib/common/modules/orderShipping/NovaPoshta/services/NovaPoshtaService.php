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


namespace common\modules\orderShipping\NovaPoshta\services;


use backend\services\ConfigurationService;
use common\classes\extended\OrderAbstract;
use common\models\repositories\ShippingNpOrderParamsRepository;
use common\models\ShippingNpOrderParams;
use common\modules\orderShipping\NovaPoshta\API\DTO\Area;
use common\modules\orderShipping\NovaPoshta\API\DTO\CargoDescription;
use common\modules\orderShipping\NovaPoshta\API\DTO\City;
use common\modules\orderShipping\NovaPoshta\API\NPApiClient;
use common\modules\orderShipping\NovaPoshta\VO\ViewShippingInfo;
use common\modules\orderShipping\NovaPoshta\widgets\NovaPoshta;
use common\modules\orderShipping\NovaPoshta\widgets\NovaPoshtaViewInfo;
use common\services\PlatformsConfigurationService;
use common\services\storages\StorageInterface;
use yii\base\DynamicModel;

class NovaPoshtaService
{
    /** @var ShippingNpOrderParamsRepository */
    private $npOrderParamsRepository;
    /** @var PlatformsConfigurationService */
    private $platformsConfiguration;

    public function __construct(
        ShippingNpOrderParamsRepository $npOrderParamsRepository,
        PlatformsConfigurationService $platformsConfiguration
    )
    {
        $this->npOrderParamsRepository = $npOrderParamsRepository;
        $this->platformsConfiguration = $platformsConfiguration;
    }

    /**
     * @param string $code
     * @param string $apiKey
     * @param bool|int $orderId
     * @param string $languageId
     * @param string $type
     * @return string
     * @throws \Exception
     */
    public function getWidget(string $code, string $apiKey, $orderId = false,  string $languageId, string $type = '')
    {
        $params = [];
        if (is_int($orderId)) {
            $params = $this->findShippingData((int)$orderId, $type, true);
        }
        return NovaPoshta::widget([
            'apiKey' => $apiKey,
            'shippingCode' => $code,
            'params' => $params,
            'language' => $languageId,
        ]);
    }
    public function getWidgetInfo(ViewShippingInfo $info)
    {
        return NovaPoshtaViewInfo::widget([
            'view' => 'params',
            'info' => $info,
        ]);
    }

    /**
     * @param int $orderId
     * @param string $type
     * @param bool $asArray
     * @return array|ShippingNpOrderParams|null
     */
    public function findShippingData(int $orderId, string $type = '', bool $asArray = false)
    {
        return $this->npOrderParamsRepository->findShippingData($orderId, $type, $asArray);
    }

    public function createNewFromExist(int $orderId, ?ShippingNpOrderParams $originalShippingParams = null, string $type ='')
    {
        $shippingParam = $this->shippingParamsDuplicate($orderId, $originalShippingParams, $type);
        $result = $this->npOrderParamsRepository->save($shippingParam);
        if ($result) {
            return $shippingParam;
        }
        return false;
    }

    public function shippingParamsDuplicate(int $orderId, ?ShippingNpOrderParams $originalShippingParams = null, string $type = '')
    {
        $shippingParam = new ShippingNpOrderParams();
        if ($originalShippingParams === null) {
            $shippingParam->orders_id = $orderId;
            $shippingParam->type = $type;
            return $shippingParam;
        }
        $shippingParam->attributes = $originalShippingParams->attributes;
        $shippingParam->orders_id = $orderId;
        $shippingParam->type = $type;
        return $shippingParam;
    }

    public static function allowed(): bool
    {
        try {
            /** @var PlatformsConfigurationService $platformsConfigurationService */
            $platformsConfigurationService = \Yii::createObject(PlatformsConfigurationService::class);
            if (defined('MODULE_SHIPPING_NP_STATUS')) {
                return true;
            }
            return $platformsConfigurationService->existByKey('MODULE_SHIPPING_NP_STATUS');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveShippingData(OrderAbstract $order, array $params)
    {
        $this->shippingParamsCreateAndSave((int)$order->order_id, $params, $order->table_prefix);
    }

    public function shippingParamsCreate(int $orderId, array $value, string $type = '', string $name = '')
    {
        return new ShippingNpOrderParams([
            'orders_id' => $orderId,
            'type' => $type,
            'name' => $name,
            'value' => json_encode($value),
            'valueData' => $value
        ]);
    }

    public function shippingParamsCreateAndSave(int $orderId, array $value, string $type = '', string $name = '')
    {
        $data = $this->shippingParamsCreate($orderId, $value, $type, $name);
        $this->save($data);
    }

    public function save(ShippingNpOrderParams $npOrderParams)
    {
        $this->npOrderParamsRepository->save($npOrderParams);
    }

    public function shippingParamsArrayToView(array $shippingParams)
    {
        return $this->shippingParamsSourceArrayToView($shippingParams['valueData']);
    }
    public function shippingParamsSourceArrayToView(array $shippingParams)
    {
        return ViewShippingInfo::create(
            $shippingParams['areaText'],
            $shippingParams['cityText'],
            $shippingParams['warehouseText'],
            $shippingParams['firstname'],
            $shippingParams['lastname'],
            $shippingParams['telephone']
        );
    }

    public function saveTemporaryShippingDataInStorage(array $post, StorageInterface $storage)
    {
        $params = $storage->get('shippingparam');
        if ($params === null) {
            $params = [];
        }
        $storage->set('shippingparam', array_replace($params, $post));
    }

    /**
     * @param ShippingNpOrderParams $shippingParams
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function remove(ShippingNpOrderParams $shippingParams)
    {
        return $this->npOrderParamsRepository->remove($shippingParams);
    }

    /**
     * @param int $orderId
     * @param string $type
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function findShippingDataAndDelete(int $orderId, string $type): bool
    {
        $shippingParams = $this->findShippingData($orderId, $type);
        if ($shippingParams) {
            $this->remove($shippingParams);
        }
        return true;
    }

    public function saveSenderWarehouse(string $areaRef, string $cityRef, string $warehouseRef, string $cargoDescription, int $platformId = 0)
    {
        $this->platformsConfiguration->updateByKey('MODULE_NP_SENDER_AREA_REF', $areaRef, $platformId);
        $this->platformsConfiguration->updateByKey('MODULE_NP_SENDER_CITY_REF', $cityRef, $platformId);
        $this->platformsConfiguration->updateByKey('MODULE_NP_SENDER_WAREHOUSE_REF', $warehouseRef, $platformId);
        $this->platformsConfiguration->updateByKey('MODULE_NP_CARGO_DESCRIPTION', $cargoDescription, $platformId);
    }

    /**
     * @param array $data
     * @return array|bool
     */
    public function validateShipingParams(array $data)
    {
        try {
            $model = DynamicModel::validateData([
                'area' => $data['area'],
                'city' => $data['city'],
                'warehouse' => $data['warehouse'],
            ],
                [
                    ['area', 'string' , 'min' => 6, 'message' => ENTRY_NP_AREA_SELECT, 'tooShort' => ENTRY_NP_AREA_SELECT],
                    ['area', 'required', 'message' => ENTRY_NP_AREA_SELECT],
                    ['city', 'string' , 'min' => 6, 'message' => ENTRY_NP_CITY_SELECT, 'tooShort' => ENTRY_NP_CITY_SELECT],
                    ['city', 'required', 'message' => ENTRY_NP_CITY_SELECT],
                    ['warehouse', 'string' , 'min' => 6, 'message' => ENTRY_NP_WAREHOUSE_SELECT, 'tooShort' => ENTRY_NP_WAREHOUSE_SELECT],
                    ['warehouse', 'required', 'message' => ENTRY_NP_WAREHOUSE_SELECT],
                ]);
            if ($model->hasErrors()) {
                return $model->getErrors();
            }
            return true;
        } catch (\Exception $ex) {
            return [$ex->getMessage()];
        }
    }

    /**
     * @return string
     */
    public static function getCargodescriptions()
    {
        $NPClient = self::getNPClient();
        $commonApi = $NPClient->api('common');
        $cargoDescriptions = $commonApi->getCargoDescription();
        $cargoDescriptions = array_reduce($cargoDescriptions, static function (array $carry, CargoDescription $item) {
            $carry[] = $item->getDescription();
            return $carry;
        }, []);
        return (string)json_encode($cargoDescriptions);
    }

    /**
     * @return array
     */
    public static function getAreas()
    {
        $NPClient = self::getNPClient();
        $addressApi = $NPClient->api('address');
        $areas = $addressApi->getAreas();
        $areasDescriptions = array_reduce($areas, static function (array $carry, Area $item) {
            $carry[$item->getRef()] = $item->getDescription();
            return $carry;
        }, []);
        return $areasDescriptions;
    }

    /**
     * @return string
     */
    public static function getCities()
    {
        $NPClient = self::getNPClient();
        $addressApi =$NPClient->api('address');
        $cities = $addressApi->getCities();
        $citiesDescriptions = array_reduce($cities, static function (array $carry, City $item) {
            $carry[$item->getRef()] = [
                'text' => $item->getDescription(),
                'id' => $item->getRef(),
                'depend' => $item->getAreaRef()
                ];
            return $carry;
        }, []);
        return (string)json_encode($citiesDescriptions);
    }

    /**
     * @return string
     */
    public static function getWarehouseByCityAjaxLink()
    {
        return \Yii::$app->urlManager->createUrl(['nova-poshta/get-warehouses-module']);
    }

    /**
     * @return NPApiClient
     */
    private static function getNPClient()
    {
        try{
            $languageId = (int)\Yii::$app->settings->get('languages_id');
            $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
            /** @var \common\services\LanguagesService $languagesService */
            $languagesService = \Yii::createObject(\common\services\LanguagesService::class);
            $language = $languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code'];
            $NPClient = \Yii::createObject(NPApiClient::class)->withLanguage((string)$language);
            if(defined('MODULE_LABEL_NP_API_KEY') && MODULE_LABEL_NP_API_KEY) {
                $NPClient->withApiKey(MODULE_LABEL_NP_API_KEY);
                return $NPClient;
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(__FUNCTION__ ." - error: {$e->getMessage()}");
        }
        throw new \RuntimeException('ApiKey not installed');
    }
}

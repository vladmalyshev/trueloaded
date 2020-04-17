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


namespace common\modules\orderShipping\NovaPoshta\controllers;

use backend\services\OrdersLabelService;
use common\modules\orderShipping\NovaPoshta\API\NPApiClient;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use common\services\LanguagesService;
use common\services\storages\StorageInterface;
use backend\controllers\Sceleton;

class NovaPoshtaAdminController extends Sceleton
{
    use NovaPoshtaControllerTrait;

    public $enableCsrfValidation = false;
    /** @var StorageInterface */
    protected $storage;
    /** @var NovaPoshtaService */
    protected $novaPoshtaService;
    /** @var NPApiClient */
    protected $client;
    /** @var \common\classes\platform */
    protected $platform;
    /** @var \common\modules\orderShipping\NovaPoshta\API\EndPoints\Address */
    private $addressApi;
    /** @var OrdersLabelService */
    private $ordersLabelService;
    /** @var \common\services\OrderManager */
    private $orderManager;
    /** @var int */
    private $adminId;
    public function __construct(
        $id,
        $module = null,
        NovaPoshtaService $novaPoshtaService,
        LanguagesService $languagesService,
        NPApiClient $client,
        OrdersLabelService $ordersLabelService,
        array $config = []
    )
    {
        global $login_id;

        parent::__construct($id, $module, $config);
        $this->storage = \Yii::$app->get('storage');
        $this->novaPoshtaService = $novaPoshtaService;
        $languageId = (int)\Yii::$app->settings->get('languages_id');
        $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
        $this->platform = \Yii::$app->get('platform');
        $this->platform->config()->constant_up();
        $this->client = $client
            ->withApiKey(MODULE_SHIPPING_NP_API_KEY)
            ->withLanguage((string)$languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code']);
        $this->addressApi = $this->client->api('address');
        $this->ordersLabelService = $ordersLabelService;
        $this->orderManager = \common\services\OrderManager::loadManager();
        $this->adminId = (int)$login_id;
    }

    public function actionGetWarehousesModule(string $cityRef = '', string $cityName = '', int $page = 0, int $limit = 0)
    {
        $warehouses = $this->addressApi->getWarehouses($cityRef, $cityName, $page, $limit);
        $result = [];
        foreach ($warehouses as $warehouse) {
            $result[$warehouse->getRef()] = [
                'id' => $warehouse->getRef(),
                'text' => $warehouse->getDescription(),
            ];
        }
        return $this->asJson([
            'success' => true,
            'data' => $result,
        ]);
    }
}

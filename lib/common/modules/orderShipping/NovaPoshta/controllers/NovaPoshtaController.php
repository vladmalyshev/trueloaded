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

use common\modules\orderShipping\NovaPoshta\API\NPApiClient;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use common\services\LanguagesService;
use common\services\storages\StorageInterface;
use frontend\controllers\Sceleton;

class NovaPoshtaController extends Sceleton
{
    use NovaPoshtaControllerTrait;

    public $enableCsrfValidation = false;
    /** @var StorageInterface */
    protected $storage;
    /** @var NovaPoshtaService */
    protected $novaPoshtaService;
    /** @var NPApiClient */
    protected $client;
    /** @var \common\modules\orderShipping\NovaPoshta\API\EndPoints\Address */
    private $addressApi;

    public function __construct(
        $id,
        $module = null,
        NovaPoshtaService $novaPoshtaService,
        LanguagesService $languagesService,
        NPApiClient $client,
        array $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->storage = \Yii::$app->get('storage');
        $this->novaPoshtaService = $novaPoshtaService;
        $languageId = (int)\Yii::$app->settings->get('languages_id');
        $languageId = $languageId > 0 ? $languageId : (int)\common\classes\language::defaultId();
        $this->client = $client
            ->withApiKey(MODULE_SHIPPING_NP_API_KEY)
            ->withLanguage((string)$languagesService->getLanguageInfo($languageId, (int)\common\classes\language::defaultId(), true)['code']);
        $this->addressApi = $this->client->api('address');
    }
}

<?php

namespace common\modules\orderShipping\NovaPoshta\widgets;

use common\services\LanguagesService;
use common\services\storages\StorageInterface;

class NovaPoshta extends \yii\base\Widget
{
	public $apiKey;
	public $view = 'widget';
	public $params = [];
    public $language;
    public $manager;
	public $selectedArea;
	public $selectedCity;
	public $selectedWarehouse;
	public $selectedWarehouseText;
    public $selectedFirstName;
    public $selectedLastName;
    public $selectedTelephone;
    public $shippingCode;
    /** @var StorageInterface */
    private $storage;

    public function __construct(LanguagesService $languagesService, $config = [])
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $language = (int)$languages_id > 0 ? $languages_id : (int)\common\classes\language::defaultId();
        $this->language = $languagesService->getLanguageInfo($language, (int)\common\classes\language::defaultId(), true)['code'];
        $this->storage = \Yii::$app->get('storage');
        $this->manager = \common\services\OrderManager::loadManager();
        parent::__construct($config);
    }

	public function init()
	{
        parent::init();
        $storedParams = $this->storage->has('shippingparam') ? $this->storage->get('shippingparam') : false;
        $this->selectedArea = $storedParams[$this->shippingCode]['area'] ?? $this->params['valueData']['area'] ?? null;
        $this->selectedCity  = $storedParams[$this->shippingCode]['city'] ?? $this->params['valueData']['city'] ?? null;
        $this->selectedWarehouse = $storedParams[$this->shippingCode]['warehouse'] ?? $this->params['valueData']['warehouse'] ?? null;
        $this->selectedWarehouseText = $storedParams[$this->shippingCode]['warehouseText'] ?? $this->params['valueData']['warehouseText'] ?? null;
        $address = $this->manager->getDeliveryAddress();
        $this->selectedFirstName = $storedParams[$this->shippingCode]['firstname'] ?? $this->params['valueData']['firstname'] ??  $address['firstname'] ?? null;
        $this->selectedLastName  = $storedParams[$this->shippingCode]['lastname'] ?? $this->params['valueData']['lastname'] ?? $address['lastname'] ?? null;
        $this->selectedTelephone = $storedParams[$this->shippingCode]['telephone'] ?? $this->params['valueData']['telephone'] ?? $address['telephone'] ?? null;
    }

	public function run()
	{
        $select_shipping = $this->manager->getSelectedShipping();
        return $this->render('widget.tpl', [
            'url' => \Yii::$app->urlManager->createUrl('nova-poshta/set-session-shipping-params'),
            'apiKey' => $this->apiKey,
            'selectedArea' => $this->selectedArea,
            'selectedCity' => $this->selectedCity,
            'selectedWarehouse' => $this->selectedWarehouse,
            'selectedWarehouseText' =>  $this->selectedWarehouseText,
            'selectedFirstName' => $this->selectedFirstName,
            'selectedLastName' => $this->selectedLastName,
            'selectedTelephone' => $this->selectedTelephone,
            'select_shipping' => $select_shipping,
            'language' => $this->language,
        ]);
	}
}

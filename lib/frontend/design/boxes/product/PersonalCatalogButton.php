<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use common\extensions\PersonalCatalog\services\PersonalCatalogService;
use common\models\Customers;
use frontend\design\boxes\ButtonListingInterface;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class PersonalCatalogButton extends Widget implements ButtonListingInterface
{
    public $file;
    public $params;
    public $settings;
    /** @var PersonalCatalogService */
    private $personalCatalogService;
    /** @var bool|Customers */
    private $customer;
    /** @var string|null */
    private $productId;
    /** @var bool */
    private $getButton;
    /** @var string */
    private $saveButtonId;
    /** @var int */
    private $priority = 3;
    /**
     * PersonalCatalogButton constructor.
     * @param PersonalCatalogService $personalCatalogService
     * @param array $config
     */
    public function __construct(
        PersonalCatalogService $personalCatalogService,
        array $config = []
    )
    {
        parent::__construct($config);
        $this->personalCatalogService = $personalCatalogService;
        if (!is_array($this->params)) {
            $this->params = [$this->params];
        }
        $this->params = isset($this->params['products_id'])
            ? $this->params
            : array_merge($this->params, \Yii::$app->request->get());
        $this->productId = $this->params['products_id'] ?? false;
        $this->getButton = $this->params['get_button'] ?? false;
        $this->saveButtonId = $this->params['saveButtonId'] ?? '';
        try {
            $this->customer = \Yii::$app->user->isGuest ? false : \Yii::$app->user->getIdentity();
        } catch (\Throwable $t) {
            $this->customer = false;
        }
    }

    public function run(): string
    {
        if ((int)$this->productId < 0 || !$this->personalCatalogService->isAllowed($this->customer)) {
            return '';
        }
        $inCatalog = $this->personalCatalogService->isInPersonalCatalog($this->customer->customers_id, $this->productId);
        $id = uniqid('pc_', false);
        if ($this->saveButtonId !== '') {
            $id = $this->saveButtonId;
        }
        return IncludeTpl::widget(['file' => 'boxes/product/personal-catalog-button.tpl', 'params' => [
            'id' => $id,
            'product_in_personal_catalog' => $inCatalog,
            'get_button' => $this->getButton
        ]]);
    }

    /** @return bool */
    public function isAllowed(): bool
    {
        return $this->personalCatalogService->isAllowed($this->customer);
    }
    /** @return int */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return ButtonListingInterface
     */
    public function setPriority(int $priority): ButtonListingInterface
    {
        $this->priority = $priority;
        return $this;
    }
}

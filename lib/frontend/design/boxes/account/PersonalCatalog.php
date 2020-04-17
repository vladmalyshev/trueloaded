<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use common\extensions\PersonalCatalog\services\PersonalCatalogService;
use common\models\Customers;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class PersonalCatalog extends Widget
{

    public $file;
    public $params;
    public $settings;
    /** @var PersonalCatalogService */
    private $personalCatalogService;
    /** @var int */
    private $languageId;
    /** @var bool|Customers */
    private $customer;

    /**
     * PersonalCatalog constructor.
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
        try {
            $this->customer = \Yii::$app->user->isGuest ? false : \Yii::$app->user->getIdentity();
        } catch (\Throwable $t) {
            $this->customer = false;
        }
        $this->languageId = (int)\Yii::$app->settings->get('languages_id');
    }

    public function run(): string
    {
        $maxItems = $this->settings[0]['max_items'] ?? 3;
        if (
            !$this->personalCatalogService->isAllowed($this->customer) ||
            Info::isAdmin()
        ) {
            return '';
        }
        $productsPersonalCatalog = $this->personalCatalogService->setListingProductsToContainer(
            $this->customer->customers_id,
            $this->languageId,
            $maxItems,
            SORT_DESC
        );
        return IncludeTpl::widget(['file' => 'boxes/account/personal-catalog.tpl', 'params' => [
            'settings' => $this->settings,
            'id' => $this->id,
            'productsPersonalCatalog' => $productsPersonalCatalog,
        ]]);
    }
}

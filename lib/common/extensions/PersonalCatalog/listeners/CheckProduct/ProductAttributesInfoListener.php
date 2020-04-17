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

namespace common\extensions\PersonalCatalog\listeners\CheckProduct;

use common\classes\events\frontend\attributes\productAttributesInfo\ProductAttributesInfoEvent;
use common\extensions\PersonalCatalog\services\PersonalCatalogService;

class ProductAttributesInfoListener
{
    /** @var PersonalCatalogService */
    private $personalCatalogService;
    /** @var string */
    private $personalCatalogButtonWrapId;

    public function __construct(
        PersonalCatalogService $personalCatalogService
    )
    {
        $this->personalCatalogService = $personalCatalogService;
        $this->personalCatalogButtonWrapId = \Yii::$app->request->get('personalCatalogButtonWrapId') ?? \Yii::$app->request->post('personalCatalogButtonWrapId', '');
    }

    public function process(ProductAttributesInfoEvent $event)
    {
        if ($event->getCustomer()) {
            $checkInPersonalCatalog = $this->personalCatalogService->isInPersonalCatalog(
                $event->getCustomer(),
                $event->getProductAttributesProperty('current_uprid'));
            $button = $this->personalCatalogService->ajaxButton(
                $event->getProductAttributesProperty('current_uprid'),
                $checkInPersonalCatalog,
                $this->personalCatalogButtonWrapId
            );
            $event->setProductAttributesProperty('personalCatalogButton', $button);
            $event->setProductAttributesProperty('personalCatalogButtonWrapId', $this->personalCatalogButtonWrapId);
        }
    }
}

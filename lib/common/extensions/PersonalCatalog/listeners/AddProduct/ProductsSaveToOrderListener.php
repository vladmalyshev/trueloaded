<?php
declare (strict_types=1);

namespace common\extensions\PersonalCatalog\listeners\AddProduct;

use common\classes\events\common\order\ProductsSaveToOrderEvent;
use common\extensions\PersonalCatalog\services\PersonalCatalogService;

class ProductsSaveToOrderListener
{
    /** @var PersonalCatalogService */
    private $personalCatalogService;

    public function __construct(PersonalCatalogService $personalCatalogService)
    {
        $this->personalCatalogService = $personalCatalogService;
    }

    public function process(ProductsSaveToOrderEvent $event)
    {
        $this->personalCatalogService->saveFromOrder($event->getOrder());
    }
}

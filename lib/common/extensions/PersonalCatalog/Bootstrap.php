<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\PersonalCatalog;

use common\extensions\PersonalCatalog\controllers\PersonalCatalogController;
use common\extensions\PersonalCatalog\listeners\AddProduct\ProductsSaveToOrderListener;
use common\extensions\PersonalCatalog\listeners\CheckProduct\ProductAttributesInfoListener;
use common\extensions\PersonalCatalog\services\PersonalCatalogService;
use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /** @var PersonalCatalogService */
    private $personalCatalogService;

    public function __construct(
        PersonalCatalogService $personalCatalogService
    )
    {
        $this->personalCatalogService = $personalCatalogService;
    }

    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        try {
            if (!$this->personalCatalogService->isModuleActive()) {
                return;
            }
            \Yii::setAlias('@personal-catalog', __DIR__);
            \Yii::$container->get('eventProvider')->attach([\Yii::createObject(ProductAttributesInfoListener::class), 'process']);
            \Yii::$container->get('eventProvider')->attach([\Yii::createObject(ProductsSaveToOrderListener::class), 'process']);
            if ($app instanceof \yii\web\Application && strpos($app->id, 'frontend') !== false) {
                $app->controllerMap['personal-catalog'] = [
                    'class' => PersonalCatalogController::class,
                ];
            }
        } catch (\Exception $e) {
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        }
    }

}

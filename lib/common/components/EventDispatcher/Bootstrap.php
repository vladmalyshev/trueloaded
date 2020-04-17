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


namespace common\components\EventDispatcher;


use common\components\EventDispatcher\Provider\Provider;
use common\components\EventDispatcher\Provider\ProvidersAggregate;
use common\services\CategoriesService;
use common\services\ProductService;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $container = \Yii::$container;
        try {
            $container->setSingleton('eventProvider', static function () {
                return new Provider();
            });
            $container->setSingleton('eventDispatcher', static function () use ($container) {
                $providersAggregate = new ProvidersAggregate();
                $providersAggregate->attach($container->get('eventProvider'));
                return new EventDispatcher($providersAggregate);
            });
        } catch (\Exception $e) {
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        }
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\orderShipping\NovaPoshta;


use common\modules\orderShipping\NovaPoshta\controllers\NovaPoshtaAdminController;
use common\modules\orderShipping\NovaPoshta\controllers\NovaPoshtaController;
use common\modules\orderShipping\NovaPoshta\listeners\Order\OrderSaveListener;
use common\modules\orderShipping\NovaPoshta\services\NovaPoshtaService;
use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        try {
            if (!NovaPoshtaService::allowed()) {
                return;
            }
            \Yii::setAlias('@nova-poshta', __DIR__);
            \Yii::$container->get('eventProvider')->attach([\Yii::createObject(OrderSaveListener::class), 'process']);
            if ($app instanceof \yii\web\Application && strpos($app->id, 'frontend') !== false) {
                $app->controllerMap['nova-poshta'] = [
                    'class' => NovaPoshtaController::class,
                ];
            }
            if ($app instanceof \yii\web\Application && strpos($app->id, 'backend') !== false) {
                $app->controllerMap['nova-poshta'] = [
                    'class' => NovaPoshtaAdminController::class,
                ];
            }
        }catch (\Exception $e) {
            //throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        } catch (\Error $e) {
            restore_error_handler();
            // throw new \RuntimeException($e->getMessage(), 0, $e);
            \Yii::error($e->getMessage());
        }
    }
}

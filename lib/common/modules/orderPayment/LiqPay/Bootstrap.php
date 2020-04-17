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


namespace common\modules\orderPayment\LiqPay;

use common\modules\orderPayment\LiqPay\controllers\LiqPayController;
use common\modules\orderPayment\LiqPay\services\LiqPayService;
use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        if (!LiqPayService::allowed()) {
            return;
        }
        \Yii::setAlias('@liq-pay', __DIR__);
        if ($app instanceof \yii\web\Application && strpos($app->id, 'frontend') !== false) {
            $app->controllerMap['liq-pay'] = [
                'class' => LiqPayController::class,
            ];
        }

    }
}

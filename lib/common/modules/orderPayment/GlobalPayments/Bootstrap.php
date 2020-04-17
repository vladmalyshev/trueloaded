<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\modules\orderPayment\GlobalPayments;


use common\modules\orderPayment\GlobalPayments\services\GlobalPaymentsService;
use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        if (!GlobalPaymentsService::allowed()) {
            return;
        }
        \Yii::setAlias('@global-payments', __DIR__);
        if ( $app instanceof \yii\console\Application ){
            // $app->controllerMap['migrate']['migrationNamespaces'][] = 'common\\extensions\\GlobalPayments\\migrations';
        }
        $this->autoloadLib();
        if ($app instanceof \yii\web\Application && strpos($app->id, 'frontend') !== false) {
            //\Yii::$app->on(\yii\web\Application::EVENT_BEFORE_REQUEST, [$this->authorizationControl, 'checkAuthorization']);
            $app->controllerMap['global-payments'] = [
                'class' => \common\modules\orderPayment\GlobalPayments\controllers\GlobalPaymentsController::class,
            ];
        }

    }

    private function autoloadLib()
    {
        $vendorDir = __DIR__ . '/lib/src/';
        $namespacePrefix = 'GlobalPayments\Api';
        spl_autoload_register(function($class) use ($vendorDir, $namespacePrefix) {
            if (strpos($class, $namespacePrefix) !== false){
                $ex = explode("\\", $class);
                unset($ex[0], $ex[1]);
                $file = implode(DIRECTORY_SEPARATOR , $ex);
                if (file_exists($vendorDir . DIRECTORY_SEPARATOR . $file . '.php')){
                    require_once $vendorDir . DIRECTORY_SEPARATOR .$file . '.php';
                }
            }
        });
    }

}

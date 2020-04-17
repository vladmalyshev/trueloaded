<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

use common\classes\modules\ModuleLabel;
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleShipping;

class Modules {

    public static function loadVisibility($forCode)
    {
        static $modulesVisibility = false;
        if ( !is_array($modulesVisibility) ) {
            $modulesVisibility = [];
            foreach (\common\models\ModulesVisibility::find()->select(['code', 'area'])->asArray()->all() as $moduleVisibility) {
                $modulesVisibility[strtolower($moduleVisibility['code'])] = explode(',', $moduleVisibility['area']);
            }
        }
        return isset($modulesVisibility[strtolower($forCode)])?$modulesVisibility[strtolower($forCode)]:false;
    }

    public static function count_modules($modules = '') {
        $count = 0;

        if (empty($modules))
            return $count;

        $modules_array = explode(';', $modules);

        for ($i = 0, $n = sizeof($modules_array); $i < $n; $i++) {
            $class = substr($modules_array[$i], 0, strrpos($modules_array[$i], '.'));

            if (is_object($GLOBALS[$class])) {
                if ($GLOBALS[$class]->enabled) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function count_payment_modules() {
        return self::count_modules(MODULE_PAYMENT_INSTALLED);
    }

    public static function count_shipping_modules() {
        return self::count_modules(MODULE_SHIPPING_INSTALLED);
    }

/**
 * get shipping modules for [current] platform
 * @param int|null $platformId
 * @return ModuleShipping[] \common\helpers\namespaceModuleClass
 */
    public static function shippingModules( $platformId = null )
    {
        Translation::init('shipping');

        $modulesList = [];
        if (is_null($platformId)) {
          $platformId = \Yii::$app->get('platform')->config()->getId();
        }
        $MODULE_INSTALLED = \common\helpers\Configuration::get_platform_configuration_key_value($platformId,'MODULE_SHIPPING_INSTALLED');
        $modulesFiles = explode(';',$MODULE_INSTALLED);
        foreach ($modulesFiles as $modulesFile) {
            $moduleClass = substr($modulesFile,0, strrpos($modulesFile,'.'));
            $namespaceModuleClass = '\\common\\modules\\orderShipping\\'.$moduleClass;
            if ( is_file(DIR_FS_CATALOG . DIR_WS_MODULES . 'shipping/' . $modulesFile) ) {
                include_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'shipping/' . $modulesFile);
            }
            if (class_exists($namespaceModuleClass)){
                $modulesList[$moduleClass] = new $namespaceModuleClass;
            }
        }

        return $modulesList;
    }

/**
 * get payment modules for [current] platform
 * @param int|null $platformId
 * @return ModulePayment[] \common\helpers\namespaceModuleClass
 */
    public static function paymentModules( $platformId = null )
    {
        Translation::init('payment');

        $modulesList = [];
        if (is_null($platformId)) {
          $platformId = \Yii::$app->get('platform')->config()->getId();
        }
        $MODULE_INSTALLED = \common\helpers\Configuration::get_platform_configuration_key_value($platformId,'MODULE_PAYMENT_INSTALLED');
        $modulesFiles = explode(';',$MODULE_INSTALLED);
        foreach ($modulesFiles as $modulesFile) {
            $moduleClass = substr($modulesFile,0, strrpos($modulesFile,'.'));
            $namespaceModuleClass = '\\common\\modules\\orderPayment\\'.$moduleClass;
            if ( is_file(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/' . $modulesFile) ) {
                include_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/' . $modulesFile);
            }
            if( class_exists($namespaceModuleClass) ){
                $modulesList[$moduleClass] = new $namespaceModuleClass;
            }
        }

        return $modulesList;
    }
    
    public static function getLabelsList($platform_id) {
        //$path = \Yii::getAlias('@common');
        //$path .= DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'label';
        $labelsList = [];
        $installed_modules_str = '';
        $get_actual_value = tep_db_fetch_array(tep_db_query("SELECT configuration_value FROM ".TABLE_PLATFORMS_CONFIGURATION." WHERE configuration_key='MODULE_LABEL_INSTALLED' AND platform_id='".intval($platform_id)."'"));
        if ( is_array($get_actual_value) ) {
        $installed_modules_str = $get_actual_value['configuration_value'];
        }
        $installed_modules = explode(';',$installed_modules_str);
        if (is_array($installed_modules)) {
            foreach ($installed_modules as $file) {
                if (substr($file, strrpos($file, '.') + 1) == 'php') {
                    //$labelsList[substr($file, 0, -4)] = substr($file, 0, -4);
                    $file = substr($file, 0, strrpos($file, '.'));
                    $class = "common\\modules\\label\\" . $file;
                    if (class_exists($class) && is_subclass_of($class,"common\\classes\\modules\\ModuleLabel") ) {
                        $module = new $class;
                        $active = $module->is_module_enabled($platform_id);
                        if ($active) {
                            $labelsList[$file] = $file;
                        }
                    }
                    
                    
                }
            }
        }
        /*if ($dir = @dir($path)) {
            while ($file = $dir->read()) {
                if (!is_dir($path . $file)) {
                    if (substr($file, strrpos($file, '.') + 1) == 'php') {
                        $labelsList[substr($file, 0, -4)] = substr($file, 0, -4);
                    }
                }
            }
            ksort($labelsList);
            $dir->close();
        }*/
        return $labelsList;
    }

    /**
     * @return ModuleLabel[]
     */
    public static function labelModules()
    {
        $platformId = \Yii::$app->get('platform')->config()->getId();
        $collection = [];
        foreach (static::getLabelsList($platformId) as $class) {
            $namespaceModuleClass = "common\\modules\\label\\" . $class;
            if (class_exists($namespaceModuleClass) && is_subclass_of($namespaceModuleClass, "common\\classes\\modules\\ModuleLabel")) {
                $label = new $namespaceModuleClass;
                $collection[$class] = $label;
            }
        }
        return $collection;
    }

}

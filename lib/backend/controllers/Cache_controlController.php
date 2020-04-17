<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;

/**
 * default controller to handle user requests.
 */
class Cache_controlController extends Sceleton  {

    public $acl = ['TEXT_SETTINGS', 'BOX_HEADING_CACHE_CONTROL'];
    
    public function actionIndex() {
      global $language;
      
      $this->selectedMenu = array('settings', 'cache_control');
      $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('cache_control/index'), 'title' => HEADING_TITLE);
      
      $this->view->headingTitle = HEADING_TITLE;
      
        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);
        if (!is_array($messages)) $messages = [];
        return $this->render('index', array('messages' => $messages));
      
    }
    
    public function actionFlush() {
        global $language;
        \common\helpers\Translation::init('admin/cache_control');
        
        $runtimePath = Yii::getAlias('@runtime');
        $messageType = 'warning';//success warning

         /**
          * System
          */
        if (Yii::$app->request->post('system') == 1) {
            Yii::$app->getCache()->flush();


            $message = TEXT_SYSTEM_WARNING;
            ?>
            <div class="pop-mess-cont pop-mess-cont-<?= $messageType?>">
                <?= $message?>
            </div>

            <?php
        }

        /**
         * Smarty
         */
        if (Yii::$app->request->post('smarty') == 1) {
            $smartyPath = $runtimePath . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR . '*.*';
            array_map('unlink', glob($smartyPath));

            //remove css cache
            $themesPath = DIR_FS_CATALOG . 'themes' . DIRECTORY_SEPARATOR;
            $dir = scandir($themesPath);
            foreach ($dir as $theme) {
                if (file_exists($themesPath . $theme . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR)) {
                    \yii\helpers\FileHelper::removeDirectory($themesPath . $theme . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR);
                }
            }

            
        $message = TEXT_SMARTY_WARNING;
?>
        <div class="pop-mess-cont pop-mess-cont-<?= $messageType?>">
            <?= $message?>
        </div>  
        
<?php
        }
        
        /**
         * Debug
         */
        if (Yii::$app->request->post('debug') == 1) {
            $debugPath = $runtimePath . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . '*.*';
            array_map('unlink', glob($debugPath));
        $message = TEXT_DEBUG_WARNING;
?>
        <div class="pop-mess-cont pop-mess-cont-<?= $messageType?>">
            <?= $message?>
        </div>  
        
<?php
        }
        
        
        /**
         * Logs
         */
        if (Yii::$app->request->post('logs') == 1) {
            $logsPath = $runtimePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . '*.*';
            array_map('unlink', glob($logsPath));
        $message = TEXT_LOGS_WARNING;
?>
		<div class="pop-mess-cont pop-mess-cont-<?= $messageType?>">
            <?= $message?>
        </div>          
        
<?php
        }


      /**
       * Image cache
       */
      if (Yii::$app->request->post('image_cache') == 1) {
        \common\classes\Images::cacheFlush(true);
        \common\classes\Images::cleanImageReference();

        $message = TEXT_IMAGE_CACHE_CLEANED;
        ?>
        <div class="pop-mess-cont pop-mess-cont-<?= $messageType?>">
            <?= $message?>
        </div>

        <?php
      }

    }
    
}

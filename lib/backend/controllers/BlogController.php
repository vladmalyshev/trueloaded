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
 *
 */
class BlogController extends Sceleton {
    
  /**
   *
   */
  public function actionIndex()
  {

    \common\helpers\Translation::init('admin/design');

    $this->selectedMenu = array('design_controls', 'blog/index');
    $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('design/index'), 'title' => WP_BLOG);

    if (ENABLE_SSL_CATALOG == 'true') {
      $url = HTTPS_CATALOG_SERVER . DIR_WS_CATALOG . '_blog/wp-admin';
    } else {
      $url = HTTP_CATALOG_SERVER . DIR_WS_CATALOG . '_blog/wp-admin';
    }

    return $this->render('index.tpl', [
      'url' => $url,
    ]);
  }

}

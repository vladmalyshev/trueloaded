<?php

/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;
use Yii;

/**
 * Description of Session
 *
 * @author vlad
 */
class Session {

  public static function get($key) {
    return static::getSession()->get($key);
  }

  public static function getSession() {
    if (method_exists(Yii::$app, 'getSession')) {
      return Yii::$app->getSession();
    } else {
      //console workaround
      $storage = Yii::$app->get('storage');
      if (is_object($storage)) {
        return $storage;
      } else {
        return Yii::$app->session;
      }
    }
  }
}

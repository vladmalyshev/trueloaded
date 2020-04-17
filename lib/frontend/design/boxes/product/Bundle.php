<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class Bundle extends Widget
{

  public $file;
  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    $params = Yii::$app->request->get();

    if ($params['products_id'] && Yii::$app->controller instanceof \frontend\controllers\CatalogController ) {
      $action = Yii::$app->controller->createAction('product-bundle');
      return $action->runWithParams($params);
      //return Yii::$app->runAction('catalog/product-bundle', $params);
    } else {
      return '';
    }
  }
}
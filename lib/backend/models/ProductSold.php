<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models;

use Yii;
use backend\controller\Sceleton;
use yii\db\Expression;

class ProductSold
{
  public static function fromPeriodSold($controller, $view, $products_id, $from, $to, $header){
      
    $sold = (new \yii\db\Query)->select(["sum(op.products_quantity) AS products_sold", "{$from} AS `from_date`", "{$to} AS `to_date`"])
            ->from(TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o ")
            ->where("op.uprid = :prid and op.orders_id = o.orders_id and o.date_purchased > {$from} and o.date_purchased <= {$to}", [':prid' => $products_id])
            ->one();
    return $controller->renderPartial($view, [
        'period' => $header,
        'data' => $sold,
    ]);
    
  }
  
  
}

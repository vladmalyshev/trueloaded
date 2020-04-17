<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\invoice;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class InvoiceNote extends Widget
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
    if ($this->params["order"]->order_id){
        $order_id = $this->params["order"]->parent_id ? $this->params["order"]->parent_id : $this->params["order"]->order_id;
        $comment = \common\models\OrdersComments::find()->where(['orders_id' => $order_id, 'for_invoice' => 1])->one();
        if ($comment && !empty($comment->comments)){
            return $comment->comments;
        }
    }
  }
}
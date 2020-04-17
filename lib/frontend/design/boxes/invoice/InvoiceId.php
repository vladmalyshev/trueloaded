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

class InvoiceId extends Widget
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
    \common\helpers\Translation::init('admin/design');

    if ($this->params["order"] instanceof \common\classes\Splinter){
        return ($this->params["order"]->isInvoice() ? TEXT_INVOICE_PREFIX : TEXT_CREDIT_NOTE_PREFIX ) . $this->params["order"]->order_id;
    }
  }
}
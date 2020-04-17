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

class OrderBarcode extends Widget
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
    global $request_type;
    $type = ''; // default is order not required
    if (!empty($this->params['order']) && is_object($this->params['order'])) {
      $reflect = new \ReflectionClass($this->params['order']);
      if ($reflect->getShortName() === 'Quotation') {
        $type = '&type=QuoteOrders';
      } elseif ($reflect->getShortName() === 'Sample') {
        $type = '&type=SampleOrders';
      }
    }
    
    return '<img alt="' . $this->params["oID"] . '" src="' . ($request_type=='SSL'?HTTPS_SERVER:HTTP_SERVER) . DIR_WS_CATALOG . 'account/order-barcode?oID=' . $this->params["oID"] . '&cID=' . $this->params["order"]->customer['id'] . $type . '">';
    
  }
}
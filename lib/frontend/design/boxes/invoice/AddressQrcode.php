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

class AddressQrcode extends Widget
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
    return '<img alt="' . \common\helpers\Output::output_string(\common\helpers\Address::address_format($this->params["order"]->delivery['format_id'], $this->params["order"]->delivery, 0, '', "\n")) . '" src="' . ($request_type=='SSL'?HTTPS_SERVER:HTTP_SERVER) . DIR_WS_CATALOG . 'account/order-qrcode?oID=' . $this->params["oID"] . '&cID=' . $this->params["order"]->customer['id'] . '">';
  }
}
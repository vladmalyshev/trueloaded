<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\cart;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class DiscountCoupon extends Widget
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
      if (($this->settings[0]['hide'] || defined(HIDE_DISCOUNT_COUPON_WIDGET) && HIDE_DISCOUNT_COUPON_WIDGET == 'true') && !\frontend\design\Info::isAdmin()) {
          return '';
      }

      if ($ext = \common\helpers\Acl::checkExtension('CouponsAndVauchers', 'cartDiscountCoupon')) {
          return $ext::cartDiscountCoupon($this->params['manager']);
      }
  }
}
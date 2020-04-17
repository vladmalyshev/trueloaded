<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Taxable extends Widget
{

  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
      //hide on account and checkout pages
      if ( in_array(Yii::$app->controller->id, ['checkout', 'account']) ) return '';

    // if customer is logged in and his group is NOT taxable then tax is hever caclulated/added Switcher doesn't work (useless)
      $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
      /** @var \common\extensions\BusinessToBusiness\BusinessToBusiness $extb2b */
      if ($extb2b = \common\helpers\Acl::checkExtension('BusinessToBusiness', 'checkTaxRate')) {
          if ($extb2b::checkTaxRate($customer_groups_id)) {
              return '';
          }
      }

      $url = \yii\helpers\Url::current(['action' => 'taxabeling']);

      $def = 1;
      /** @var common\extensions\CustomerTaxable $ext */
      if ($ext = \common\helpers\Acl::checkExtension('CustomerTaxable', 'getDefaultStatus')) {
        $def = $ext::getDefaultStatus();
      }

      $taxable = Yii::$app->storage->has('taxable') ? Yii::$app->storage->get('taxable') : $def;
      $tList = [TEXT_EXC_VAT, TEXT_INC_VAT];
      return IncludeTpl::widget([
                'file' => 'boxes/taxable.tpl',
                'params' => [
                    'taxable' => $taxable,
                    'settings' => $this->settings,
                    'id' => $this->id,
                    'tList' => $tList,
                    'url' => $url,
                ]
            ]);
  }
}
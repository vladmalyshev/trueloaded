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
use common\classes\Images;

class Quote extends Widget
{

  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    if (GROUPS_DISABLE_CHECKOUT) return '';
    if (!\common\helpers\Acl::checkExtension('Quotations', 'allowed') || PRODUCTS_QUOTATIONS != 'True') {
        return '';
    }

    global $quote;

    $currencies = \Yii::$container->get('currencies');
    $products = $quote->get_products();

    foreach ($products as $key => $item) {
      $products[$key]['price'] = $currencies->display_price($item['final_price'], \common\helpers\Tax::get_tax_rate($item['tax_class_id']), $item['quantity']);
      $products[$key]['image'] = Images::getImageUrl($item['id'], 'Small');
      $products[$key]['link'] = tep_href_link('catalog/product', 'products_id='. $item['id']);
    }


    return IncludeTpl::widget(['file' => 'boxes/quote.tpl', 'params' => [
      'total' => $currencies->format($quote->show_total()),
      'count_contents' => $quote->count_contents(),
      'settings' => $this->settings,
      'products' => $products
    ]]);
  }
}
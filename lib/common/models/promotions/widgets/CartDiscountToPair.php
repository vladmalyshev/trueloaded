<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions\widgets;

use Yii;
use yii\base\Widget;
use common\models\Product\Price;

class CartDiscountToPair extends Widget {

    public $promo;
    public $master;
    public $slave;

    public function init() {
        parent::init();
    }

    public function run() {

        if (is_object($this->master) && is_object($this->slave)){
            $currencies = Yii::$container->get('currencies');
            $mPrice = ($this->master->special_price?$this->master->special_price:$this->master->price);
            $mPrice = $currencies->display_price_clear($mPrice, $this->master->tax_rate);
            
            $sPriceOld = $currencies->display_price_clear($this->slave->price, $this->slave->tax_rate);
            $sPriceNew = $currencies->display_price_clear($this->slave->special_price, $this->slave->tax_rate);
            
            $icon = false;
            $label = (is_object($this->promo->settings['promo'])?$this->promo->settings['promo']->promo_label:'');
            if (!empty($this->promo->settings['promo_icon'])){
                $icon = \common\classes\Images::getWSCatalogImagesPath() . 'promo_icons/' . $this->promo->settings['promo_icon'];
                if (!is_file($icon)){
                    $icon = false;
                } else {
                    $icon = \yii\helpers\Html::img($icon, ['alt' => $label]);
                }
            }
            
            return \frontend\design\IncludeTpl::widget(['file' => 'promotions/cart-discount-pair.tpl', 'params' => [
                    'master' => $this->master,
                    'slave' => $this->slave,
                    'is_master' => $this->promo->settings['is_master'],
                    'currencies' => $currencies,
                    'label' => $label,
                    'icon' => $icon,
                    'advantage' => $this->promo->getPriceAdvantages($mPrice, $sPriceOld, $sPriceNew),
            ]]);
        }
    }

}

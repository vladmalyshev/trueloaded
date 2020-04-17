<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\pdf;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class ProductElement extends Widget
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

        static $productsItem = 0;
        static $productsElements = [];
        static $productsItemSize = 0;
        static $productsElementsSize = [];
        if ($this->settings['item_clear']){
            $productsItem = 0;
            $productsElements = [];
            $productsItemSize = 0;
            $productsElementsSize = [];
            return '';
        }

        $element = $this->settings[0]['product_element'];

        $products = $this->params['products'];

        if (!$this->settings[0]['pdf'] && !is_array($products)) {
            return $element;
        }

        if (!is_array($products)) {
            return '';
        }

        if ($this->settings['out']){// main boxes

            if ($productsElements[$element]) {
                $productsElements = [];
                $productsElements[$element] = 1;
                $productsItem++;
            } else {
                $productsElements[$element] = 1;
            }

            if ($productsItem > count($products) - 2) {
                \frontend\design\Info::$pdfProductsEnd = true;
            }
            return $products[$productsItem][$element];

        } else {// boxes for counting size, it queried before show main boxes

            if ($productsElementsSize[$element]) {
                $productsElementsSize = [];
                $productsElementsSize[$element] = 1;
                $productsItemSize++;
            } else {
                $productsElementsSize[$element] = 1;
            }

            if (\frontend\design\Info::$pdfProductsEnd == true) {
                return '';
            }

            return $products[$productsItemSize][$element];
        }
    }
}
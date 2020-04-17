<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Description extends Widget
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
        $params = Yii::$app->request->get();

        if (!$params['products_id']) {
            return '';
        }
        
        $products = Yii::$container->get('products');
        $product = $products->getProduct($params['products_id']);

        if ($product['products_description_short']) {
            $ogDescription = $product['products_description_short'];
        } elseif ($product['products_description']) {
            $ogDescription = $product['products_description'];
        }

        if ($ogDescription) {
            $ogDescription = strip_tags($ogDescription);
            $ogDescription = str_replace("\n", '', $ogDescription);
            $ogDescription = str_replace("\t", ' ', $ogDescription);
            $ogDescription = trim($ogDescription);
            if (strlen($ogDescription) > 295) {
                $ogDescription = mb_substr($ogDescription, 0, 291) . '...';
            }
            Yii::$app->getView()->registerMetaTag([
                'property' => 'og:description',
                'content' => $ogDescription
            ],'og:description');


            \frontend\design\JsonLd::addData(['Product' => [
                'description' => $ogDescription
            ]]);
        }

        if (!$product['products_description']) {
            return '';
        }

        return IncludeTpl::widget(['file' => 'boxes/product/description.tpl', 'params' => ['description' => $product['products_description']]]);

    }
}
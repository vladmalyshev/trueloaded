<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\LinkedProducts;


use common\extensions\LinkedProducts\models\ProductsLinkedChildren;
use yii\base\Widget;

class ProductEdit extends Widget
{
    public $pInfo;
    public $products_linked_children = [];

    public function run()
    {
        $this->pInfo->products_linked_children = [];

        if ( $this->pInfo->products_id ) {
            $LinkedChildren = ProductsLinkedChildren::find()
                ->where(['parent_product_id' => $this->pInfo->products_id])
                ->orderBy(['sort_order'=>SORT_ASC])
                ->all();
            foreach ($LinkedChildren as $LinkedChild){
                $child_array = $LinkedChild->getAttributes();
                $child_array["products_name"] = \common\helpers\Product::get_backend_products_name($child_array['linked_product_id']);
                $child_array["img-src"] = \common\classes\Images::getImageUrl($child_array['linked_product_id']);

                $this->pInfo->products_linked_children[] = $child_array;
            }
        }

        $LinkedChildren = new ProductsLinkedChildren();
        $LinkedChildren->loadDefaultValues();

        return self::begin()->render('backend/product-edit.tpl', [
            'pInfo' => $this->pInfo,
            'LinkedChildrenDefaults' => $LinkedChildren->getAttributes(),
        ]);
    }

}
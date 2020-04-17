<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\v1;


use common\api\models\AR\Products;
use yii\web\NotFoundHttpException;

class Action
{
    const MAX_PRODUCTS_PER_PAGE = 5;

    public function productList()
    {
        $perPage = \Yii::$app->request->get('perPage',self::MAX_PRODUCTS_PER_PAGE);
        $perPage = min(abs((int)$perPage), self::MAX_PRODUCTS_PER_PAGE);

        $page = \Yii::$app->request->get('page',1);
        $page = max((int)$page,1);

        $products = [];
        $products_query = tep_db_query(
            "SELECT products_id, products_model, products_ean FROM ".TABLE_PRODUCTS."  ".
            "ORDER BY products_id ".
            "LIMIT ".(($page-1)*$perPage).", {$perPage} "
        );
        while( $product = tep_db_fetch_array($products_query) ) {
            $product = \common\api\models\AR\Products::findOne(['products_id'=>$product['products_id']]);
            $products[] = $product->exportArray([
/*                'products_model',
                'stock_indication_text',
                'stock_delivery_terms_text',
                'manufacturers_name',
                'descriptions' => [
                    'en_0'=>['products_name'],
                    //'de_0'=>['products_name'],
                ]*/
            ]);
        }
        return [
            'perPage' => $perPage,
            'page' => $page,
            'currency' => DEFAULT_CURRENCY,
            'list' => $products,
        ];
    }

    public function productInfo()
    {
        $id = \Yii::$app->request->get('id');
        $product = Products::findOne(['products_id'=>$id]);
        if ( is_object($product) ) {
            return [
                'product' => $product->exportArray(),
            ];
        }else{
            throw new NotFoundHttpException('Product not found');
        }
    }

}
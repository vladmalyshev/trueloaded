<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\ProductEdit;

use yii;
use common\models\Products;

class SaveProductVideos
{

    protected $product;

    public function __construct(Products $product)
    {
        $this->product = $product;
    }

    public function save()
    {
        $languages = \common\helpers\Language::get_languages();
        $products_id = $this->product->products_id;

        $video = Yii::$app->request->post('video');
        tep_db_query("delete from " . TABLE_PRODUCTS_VIDEOS . " where products_id  = '" . (int) $products_id . "'");
        if (is_array($video)) {

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                $language_id = $languages[$i]['id'];

                if (isset($video[$language_id]) && is_array($video[$language_id])) {
                    foreach ($video[$language_id] as $item) {

                        if ($item) {
                            $sql_data_array = array(
                                'products_id' => $products_id,
                                'video' => $item,
                                'language_id' => $language_id,
                            );
                            tep_db_perform(TABLE_PRODUCTS_VIDEOS, $sql_data_array);
                        }
                    }
                }
            }
        }
    }
}
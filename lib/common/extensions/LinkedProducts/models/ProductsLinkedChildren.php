<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\LinkedProducts\models;

use yii\db\ActiveRecord;

class ProductsLinkedChildren extends ActiveRecord
{


    /**
     * This is the model class for table "products_linked_parent".
     *
     * @property int $id
     * @property int $parent_product_id
     * @property int $linked_product_id
     * @property int $sort_order
     * @property int $linked_product_quantity
     * @property int $show_on_invoice
     * @property int $show_on_packing_slip
     *
     **/

    /**
     * set table name
     * @return string
     */
    public static function tableName()
    {
        return 'products_linked_children';
    }

}
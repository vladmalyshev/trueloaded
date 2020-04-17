<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;


/**
 * This is the model class for table "products_xsell_type".
 *
 * @property int $xsell_type_id
 * @property int $language_id
 * @property string $xsell_type_name
 * @property int $link_update_disable
 */
class ProductsXsellType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'products_xsell_type';
    }



}

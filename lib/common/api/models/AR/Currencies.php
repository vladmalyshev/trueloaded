<?php
/*
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR;

use yii\db\Expression;

class Currencies extends EPMap
{

    protected $hideFields = [
    ];

    protected $childCollections = [
    ];

    protected $indexedCollections = [
    ];

    public static function tableName()
    {
        return TABLE_CURRENCIES;
    }

    public static function primaryKey()
    {
        return ['currencies_id'];
    }

    public function rules() {
        return array_merge(
            parent::rules(),
            [
             ///   ['customers_company_vat', 'default', 'value' => '']
            ]
        );
    }

}
<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR\Products\Documents;

use common\api\models\AR\EPMap;

class Title extends EPMap
{

    protected $hideFields = [
        'products_documents_id',
        'language_id',
    ];

    public static function tableName()
    {
        return TABLE_PRODUCTS_DOCUMENTS_TITLES;
    }

    public static function primaryKey()
    {
        return ['products_documents_id', 'language_id',];
    }

    public static function getAllKeyCodes()
    {
        $keyCodes = [];
        foreach (\common\classes\language::get_all() as $lang){
            $keyCode = $lang['code'];
            $keyCodes[$keyCode] = [
                'products_documents_id' => null,
                'language_id' => $lang['id'],
            ];
        }
        return $keyCodes;
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->products_documents_id = $parentObject->products_documents_id;
        parent::parentEPMap($parentObject);
    }


}
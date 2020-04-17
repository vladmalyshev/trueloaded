<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR\CatalogProperty;

use common\api\models\AR\EPMap;

class PropertyDescription extends EPMap
{

    public static function tableName()
    {
        return TABLE_PROPERTIES_DESCRIPTION;
    }

    public static function primaryKey()
    {
        return ['properties_id', 'language_id'];
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->properties_id = $parentObject->properties_id;
        parent::parentEPMap($parentObject);
    }

    public static function getAllKeyCodes()
    {
        $keyCodes = [];
        foreach (\common\classes\language::get_all() as $lang){
            $keyCode = $lang['code'].'';
            $keyCodes[$keyCode] = [
                'properties_id' => null,
                'language_id' => $lang['id'],
            ];
        }
        return $keyCodes;
    }
}
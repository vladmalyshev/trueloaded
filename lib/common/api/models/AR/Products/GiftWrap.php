<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR\Products;


use common\api\models\AR\EPMap;

class GiftWrap extends EPMap
{

    protected $hideFields = [
        'gw_id',
        'products_id',
    ];

    /**
     * @var EPMap
     */
    protected $parentObject;

    public static function tableName()
    {
        return 'gift_wrap_products';
    }

    public static function primaryKey()
    {
        return ['gw_id'];
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->products_id = $parentObject->products_id;
        $this->parentObject = $parentObject;
    }

    public function matchIndexedValue(EPMap $importedObject)
    {
        if (isset($importedObject->gw_id) && intval($importedObject->gw_id) > 0) {
            if (intval($importedObject->gw_id) == intval($this->gw_id)) {
                $this->pendingRemoval = false;
                return true;
            }
            return false;
        }

        if (
            !is_null($importedObject->groups_id) && !is_null($this->groups_id) && $importedObject->groups_id==$this->groups_id
            &&
            !is_null($importedObject->currencies_id) && !is_null($this->currencies_id) && $importedObject->currencies_id==$this->currencies_id
        ){
            $this->pendingRemoval = false;
            return true;
        }
        return false;

    }

}
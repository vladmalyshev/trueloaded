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
use common\helpers\Seo;

class Description extends EPMap
{

    /**
     * @var EPMap
     */
    protected $parentObject;

    protected $hideFields = [
        'products_id',
        'language_id',
        'platform_id',
        'products_name_soundex',
        'products_description_soundex',
    ];

    public static function getAllKeyCodes()
    {
        $keyCodes = [];
        $platforms = \common\models\Platforms::getPlatformsByType("non-virtual")->all();
        foreach($platforms as $platform){
            foreach (\common\classes\language::get_all() as $lang){
                $keyCode = $lang['code'].'_'.$platform->platform_id;
                $keyCodes[$keyCode] = [
                    'products_id' => null,
                    'language_id' => $lang['id'],
                    'platform_id' => $platform->platform_id,
                ];
            }
        }
        
        return $keyCodes;
    }

    public static function tableName()
    {
        return TABLE_PRODUCTS_DESCRIPTION;
    }

    public static function primaryKey()
    {
        return ['products_id', 'language_id', 'platform_id'];
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->products_id = $parentObject->products_id;
        $this->parentObject = $parentObject;
    }

    public function beforeSave($insert)
    {

        $this->products_seo_page_name = \common\helpers\Seo::makeProductSlug($this, $this->parentObject);

        return parent::beforeSave($insert);
    }

}
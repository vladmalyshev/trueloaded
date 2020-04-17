<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\AR;


use yii\db\Expression;

class Supplier extends EPMap
{

    public static function primaryKey()
    {
        return ['suppliers_id'];
    }


    public static function tableName()
    {
        return 'suppliers';
    }

    public function beforeSave($insert)
    {
        if ( $insert ) {
            if ( empty($this->date_added) ) {
                $this->date_added = new Expression("NOW()");
            }
        }else{
            if ( $this->isModified() ) {
                $this->last_modified = new Expression("NOW()");
            }
        }
        return parent::beforeSave($insert);
    }

}
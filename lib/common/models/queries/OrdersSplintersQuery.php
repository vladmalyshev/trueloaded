<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\queries;

use yii\db\ActiveQuery;

class OrdersSplintersQuery extends ActiveQuery {

    public function status($status, $withSubId = false){
        $query = $this->andWhere(['splinters_status' => $status]);
        if ($withSubId){
            $query->andWhere(['is not', 'splinters_suborder_id', null]);
        }
        return $query;
    }
    
    public function type($type){
        return $this->andWhere(['splinters_type' => $type]);
    }
}
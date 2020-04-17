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

class ProductsQuery extends ActiveQuery{
    use \common\helpers\SqlTrait;

    public function withDescription( $language  = null) {

        if(!$language){
            return $this->joinWith( [ 'descriptions'] );
        }

        return $this->joinWith( [ 'descriptions' => function (ActiveQuery $query ) USE ($language) {
            $query->where(['products_description.language_id' => $language ]);
        }]);
    }

    public function wDescription(int $languageId = 1)
    {
        if(!$languageId){
            return $this->joinWith(['descriptions']);
        }
        return $this->joinWith( [ 'description' => function (ActiveQuery $query ) USE ($languageId) {
            $query->andOnCondition(['language_id' => $languageId ]);
        }]);
    }

    public function active()
    {
        return $this->andWhere(['products_status' => 1]);
    }
}

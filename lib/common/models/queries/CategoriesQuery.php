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
use paulzi\nestedsets\NestedSetsQueryTrait;

class CategoriesQuery extends ActiveQuery{

  use NestedSetsQueryTrait;

    public function withListDescription( ) {
      $languages_id = \Yii::$app->settings->get('languages_id');
      return $this->withDescription($languages_id);
    }



/**
 * link to products_to_categories
 * @return \yii\db\ActiveQuery
 */
    public function withProductIds() {
       return $this->joinWith('productIds');
    }

    public function withDescription( $language  = null) {

        if(!$language){
            return $this->joinWith( [ 'descriptions'] );
        }

        return $this->joinWith( [ 'descriptions' => function (ActiveQuery $query ) USE ($language) {
            $query->andWhere(['categories_description.language_id' => $language ]);
        }]);
    }
    public function active()
    {
        return $this->andWhere(['categories_status' => 1]);
    }
}

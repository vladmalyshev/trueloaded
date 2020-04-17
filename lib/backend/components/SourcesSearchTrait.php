<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\components;


use common\models\SuppliersProducts;

trait SourcesSearchTrait
{

    public function actionSources()
    {
        $this->layout = false;
        $term = \Yii::$app->request->get('term');

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query1 = (new \yii\db\Query())
            ->select("source as `text`, source AS label")
            ->from(\common\models\Products::tableName())
            ->where(['!=','source',''])
            ->andFilterWhere(['LIKE', 'source', $term]);

        $query2 = (new \yii\db\Query())
            ->select("source as `text`, source AS label")
            ->from(\common\models\SuppliersProducts::tableName())
            ->where(['!=','source',''])
            ->andFilterWhere(['LIKE', 'source', $term]);

        $unionQuery = (new \yii\db\Query())
            ->from(['dummy_name' => $query1->union($query2)])
            ->orderBy(['label' => SORT_ASC]);
                
        \Yii::$app->response->data = $unionQuery->all();
    }

}
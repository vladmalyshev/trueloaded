<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

trait StatusTrait {
    
    /**
     * for dropDownList statuses
     * @param bool $withAll def false
     * @param bool $wouAutomated def false
     * @param int|array $includeStatus def 0
     * @return array of names: $orders_statuses[$gStatus->orders_status_groups_name][$status->orders_status_id] = $status->orders_status_name;
     */
    public static function getStatusList($withAll = false, $wouAutomated = false, $includeStatus = 0) {
        $orders_statuses = [];
        if ($withAll) {
            $orders_statuses[''] = TEXT_ALL_ORDERS_STATUS;
        }

        foreach(self::getStatuses($wouAutomated, $includeStatus) as $gStatus){
            $orders_statuses[$gStatus->orders_status_groups_name] = [];
            foreach($gStatus->statuses as $status ){
                $orders_statuses[$gStatus->orders_status_groups_name][$status->orders_status_id] = $status->orders_status_name;
            }
        }
        return $orders_statuses;
    }

    /**
     * uses cache.
     * @staticvar array $cache
     * @param type $wouAutomated
     * @param type $includeStatus
     * @return array of objects [group->statuses]
     */
    public static function getStatuses($wouAutomated = false, $includeStatus = 0){
        static $cache = [];
        if (!isset($cache[$wouAutomated.$includeStatus])){
            $q = \common\models\OrdersStatusGroups::find()->alias('osg')->where([
                'osg.language_id' => \Yii::$app->settings->get('languages_id'),
                'osg.orders_status_type_id' => self::getStatusTypeId()
                ])
                //->addOrderBy('osg.orders_status_groups_id')
                ->joinWith([
                'statuses' => function(\yii\db\ActiveQuery $query) use ($wouAutomated, $includeStatus) {
                    $condition = [];
                    
                    if ($wouAutomated){
                        $condition[] =  ['automated' => 0];
                        if ($includeStatus){
                            $condition[] = ['orders_status_id' => $includeStatus];
                        }
                    }
                    if ($condition){
                        array_unshift($condition, 'or');
                        $query->orOnCondition($condition);
                    }
                    $query->andOnCondition(['hidden' => 0]);
                    $query->addOrderBy('orders_status_name');
                }
            ]);
            $table = \Yii::$app->db->schema->getTableSchema('orders_status_groups');
            if (isset($table->columns['sort_order'])) {
              $q->addOrderBy('sort_order');
            }
            $cache[$wouAutomated.$includeStatus] = $q->addOrderBy(['orders_status_groups_id' => SORT_ASC])->all();
        }
        
        return $cache[$wouAutomated.$includeStatus];
    }
}

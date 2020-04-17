<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Store\ArrayOfOrderStatus;
use common\api\models\Soap\Store\OrderStatus;
use common\api\SoapServer\SoapHelper;
use yii\helpers\ArrayHelper;

class PutOrderStatusesResponse  extends SoapModel
{
    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    protected $client_statuses = [];
    protected $client_status_mapping = [];

    public function setRequestStatuses(ArrayOfOrderStatus $statuses)
    {
        $order_statuses = [];
        if ( isset($statuses->order_status) ) {
            $order_statuses = ArrayHelper::isIndexed($statuses->order_status)?$statuses->order_status:[$statuses->order_status];
        }
        foreach ( $order_statuses as $order_status ) {
            $names = ArrayHelper::isIndexed($order_status->names->language_value)?$order_status->names->language_value:[$order_status->names->language_value];
            $group_names = ArrayHelper::isIndexed($order_status->group_names->language_value)?$order_status->group_names->language_value:[$order_status->group_names->language_value];
            $order_status = (array)$order_status;
            $status_name = [];
            $status_group_name = [];

            foreach ($names as $name)
                $status_name[$name->language] = $name->text;

            foreach ($group_names as $group_name)
                $status_group_name[$group_name->language] = $group_name->text;

            $order_status['names'] = $status_name;
            $order_status['group_names'] = $status_group_name;

            $this->client_statuses[] = $order_status;

            //$this->client_status_mapping[  ]
        }
    }

    public function build()
    {
        if ( $this->status!='ERROR' ) {
            foreach ( $this->client_statuses as $idx=>$statusIn ) {
                if ( isset($statusIn['createInGroup']) && is_numeric($statusIn['createInGroup']) ) {
                    $groupId = (int)$statusIn['createInGroup'];
                    $check_group = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS c FROM ".TABLE_ORDERS_STATUS_GROUPS." WHERE orders_status_groups_id='{$groupId}' "));
                    if ( $check_group['c']>0 ) {
                        $defaultName = current($statusIn['names']);
                        if ( isset($statusIn['names'][DEFAULT_LANGUAGE]) ) {
                            $defaultName = $statusIn['names'][DEFAULT_LANGUAGE];
                            $check_exists_r = tep_db_query(
                                "SELECT orders_status_id ".
                                "FROM ".TABLE_ORDERS_STATUS." ".
                                "WHERE orders_status_name='".tep_db_input($defaultName)."' ".
                                " AND language_id='".\common\classes\language::get_id(DEFAULT_LANGUAGE)."' ".
                                " AND orders_status_groups_id='{$groupId}' ".
                                "LIMIT 1"
                            );
                            if ( tep_db_num_rows($check_exists_r)>0 ) {
                                $check_exists = tep_db_fetch_array($check_exists_r);
                                $this->client_statuses[$idx]['external_status_id'] = $check_exists['orders_status_id'];
                                break;
                            }
                        }else{
                            $check_exists_r = tep_db_query(
                                "SELECT orders_status_id ".
                                "FROM ".TABLE_ORDERS_STATUS." ".
                                "WHERE orders_status_name='".tep_db_input($defaultName)."' ".
                                " AND orders_status_groups_id='{$groupId}' ".
                                "LIMIT 1"
                            );
                            if ( tep_db_num_rows($check_exists_r)>0 ) {
                                $check_exists = tep_db_fetch_array($check_exists_r);
                                $this->client_statuses[$idx]['external_status_id'] = $check_exists['orders_status_id'];
                                break;
                            }
                        }
                        $get_current_status_id = tep_db_fetch_array(tep_db_query(
                            "SELECT MAX(orders_status_id) AS current_max_id FROM ".TABLE_ORDERS_STATUS." "
                        ));
                        $new_status_id = intval($get_current_status_id['current_max_id'])+1;
                        tep_db_query(
                            "INSERT INTO ".TABLE_ORDERS_STATUS." (orders_status_id, orders_status_groups_id, language_id, orders_status_name) ".
                            " SELECT {$new_status_id}, {$groupId}, languages_id, '".tep_db_input($defaultName)."' FROM ".TABLE_LANGUAGES." "
                        );
                        $defLangId = \common\classes\language::get_id(DEFAULT_LANGUAGE);
                        foreach ( $statusIn['names'] as $langCode=>$langName ) {
                            $langId = \common\classes\language::get_id($langCode);
                            if ( $defLangId==$langId ) continue;
                            tep_db_query(
                                "UPDATE ".TABLE_ORDERS_STATUS." ".
                                "SET orders_status_name='".tep_db_input($defaultName)."' ".
                                "WHERE orders_status_id='{$new_status_id}' AND language_id='".$langId."'"
                            );
                        }
                        $this->client_statuses[$idx]['external_status_id'] = $new_status_id;
                    }
                }
            }
        }
        if ( $this->status!='ERROR' ) {
            $db_statuses_string = base64_encode(json_encode($this->client_statuses));
            SoapHelper::setServerKeyValue('clientOrder/Statuses', $db_statuses_string);
            $clientMappingCompare = [];
            foreach ($this->client_statuses as $client_status){
                if ( empty($client_status['external_status_id']) ) continue;
                $clientMappingCompare[$client_status['id']] = $client_status['external_status_id'];
            }
            $outStatusesMap = SoapHelper::getServerKeyValue('clientOrder/StatusesMapping');
            if ( !empty($outStatusesMap) ) {
                $outStatusesMap = json_decode($outStatusesMap,true);
                if ( is_array($outStatusesMap) ) $outStatusesMap = array_filter($outStatusesMap,function($value){ return !empty($value); });
            }
            $needCreate = SoapHelper::getServerKeyValue('clientOrder/StatusesNeedCreate');
            if ( !empty($needCreate) ) {
                $needCreate = json_decode($needCreate,true);
                if ( is_array($needCreate) ) {
                    foreach ($needCreate as $createStatusServerIds){
                        foreach ($createStatusServerIds as $createStatusServerId) {
                            $createdClientStatusId = array_search($createStatusServerId, $clientMappingCompare);
                            if ($createdClientStatusId) {
                                $outStatusesMap[$createdClientStatusId] = $createStatusServerId;
                            }
                        }
                    }
                }
            }

            if ( is_array($outStatusesMap) && $outStatusesMap==$clientMappingCompare ) {
                SoapHelper::setServerKeyValue('clientOrder/StatusesMapping', '');
                SoapHelper::setServerKeyValue('clientOrder/StatusesNeedCreate', '');
            }
        }
    }
}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;


use common\api\models\Soap\SoapModel;

class ArrayOfQuotationStatus extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\OrderStatus OrderStatus {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $quotation_status = [];

    public $mapping_array = [];
    public $create_request = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $list = [];
        $out_mapping = array_flip($this->mapping_array);

        $configured_languages = [0];
        foreach( \common\classes\language::get_all() as $_lang){
            $configured_languages[] = $_lang['id'];
        }

        $get_data_r = tep_db_query(
            "SELECT os.orders_status_id, os.language_id, os.orders_status_name, ".
            " osg.orders_status_groups_id, osg.orders_status_groups_name, ".
            " osg.orders_status_groups_color as color ".
            "FROM ".TABLE_ORDERS_STATUS." os ".
            " LEFT JOIN ".TABLE_ORDERS_STATUS_GROUPS." osg ON osg.orders_status_groups_id=os.orders_status_groups_id AND osg.language_id=os.language_id ".
            "WHERE os.language_id IN (".implode(",",$configured_languages).") ".
            " AND osg.orders_status_type_id = '".intval(\common\helpers\Quote::getStatusTypeId())."' ".
            "ORDER BY osg.orders_status_groups_id, os.orders_status_id, os.language_id"
        );
        if ( tep_db_num_rows($get_data_r)>0 ) {
            while( $data = tep_db_fetch_array($get_data_r) ) {
                $data['language'] = \common\classes\language::get_code($data['language_id']);
                if ( !isset($list[ $data['orders_status_id'] ]) ) {
                    $list[ $data['orders_status_id'] ] = [
                        'id' => $data['orders_status_id'],
                        'names' => [],
                        'group_id' => $data['orders_status_groups_id'],
                        'group_names' => [],
                        'color' => $data['color'],
                    ];
                    if ( isset($this->mapping_array[$data['orders_status_id']]) && !empty($this->mapping_array[$data['orders_status_id']]) ) {
                        $list[ $data['orders_status_id'] ]['external_order_id'] = $this->mapping_array[$data['orders_status_id']];
                    }
                }
                $list[ $data['orders_status_id'] ]['names'][ $data['language'] ] = $data['orders_status_name'];
                $list[ $data['orders_status_id'] ]['group_names'][ $data['language'] ] = $data['orders_status_groups_name'];

                if ( isset($out_mapping[$data['orders_status_id']]) ) {
                    $list[ $data['orders_status_id'] ]['external_status_id'] = $out_mapping[$data['orders_status_id']];
                }
                foreach ($this->create_request as $__grpId=>$createStatuses){
                    if ( in_array($data['orders_status_id'],$createStatuses) ) {
                        $list[ $data['orders_status_id'] ]['createInGroup'] = $__grpId;
                    }
                }
            }
        }

        foreach ($list as $listItem) {
            $this->quotation_status[] = new OrderStatus($listItem);
        }

    }


    public function build()
    {
        parent::build();
    }

}
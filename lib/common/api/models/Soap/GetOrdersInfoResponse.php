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


use backend\models\EP\Tools;
use common\api\models\Soap\Order\ArrayOfOrderShortInfo;
use common\api\models\Soap\Order\OrderShortInfo;
use common\api\SoapServer\ServerSession;

class GetOrdersInfoResponse extends SoapModel
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

    /**
     * @var \common\api\models\Soap\Paging {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $paging;

    /**
     * @var \common\api\models\Soap\Order\ArrayOfOrderShortInfo Array of OrderShortInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $ordersInfo;

    public $asPurchaseOrder = false;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    public function __construct(array $config = [])
    {
        $this->ordersInfo = new ArrayOfOrderShortInfo();
        if ( !is_object($this->paging) ) {
            $this->paging = new Paging([
                'maxPerPage' => 200,
            ]);
        }
        parent::__construct($config);
    }

    public function setSearchCondition(ArrayOfSearchConditions $searchCondition)
    {
        $this->searchCondition = $searchCondition;
    }

    public function build()
    {
        global $languages_id;

        $this->searchCondition->setAllowedOperators([
            '*' => ['=','IN'],
            'last_modified' => ['=', '>','>=','<','<='],
            'date_purchased' => ['=', '>','>=','<','<='],
        ]);
        $this->searchCondition->addDateTimeColumn('last_modified');
        $this->searchCondition->addDateTimeColumn('date_purchased');
        $filter_conditions = $this->searchCondition->buildRequestCondition([
            'orders_id' => 'o.orders_id',
            'orders_status' => 'o.orders_status',
            'last_modified' => 'IFNULL(o.last_modified, o.date_purchased) ?' /*'o.last_modified'*/,
            'date_purchased' => 'o.date_purchased' /*'o.last_modified'*/,
        ]);

        if ( $filter_conditions===false ) {
            $this->error($this->searchCondition->getLastError());
            return;
        }

        $join_tables = '';
        $filter_sql = '';
        if ( !empty($filter_conditions) ) {
            $filter_sql .= "AND {$filter_conditions} ";
        }

        if ( $this->asPurchaseOrder ) {
            $filter_sql .= "AND o.order_type='purchase' ";
            if ( ServerSession::get()->getDepartmentId()>0 ) {
                $filter_sql .= "AND o.purchase_order_department_id='".ServerSession::get()->getDepartmentId()."'";
            }
        }else{
            //$filter_sql .= "AND o.order_type='saleOrder' ";
            if ( ServerSession::get()->getDepartmentId()>0 ) {
                $filter_sql .= "AND o.department_id='".ServerSession::get()->getDepartmentId()."'";
            }elseif(!ServerSession::get()->acl()->siteAccessPermission()){
                $filter_sql .= "AND o.platform_id='".ServerSession::get()->getPlatformId()."'";
            }
        }

        $orderBy = "ORDER BY o.orders_id";
        if ( $this->searchCondition->isColumnPresent('last_modified') ) {
            $orderBy = "ORDER BY IFNULL(o.last_modified, o.date_purchased)";
        }

        $main_sql =
            "SELECT SQL_CALC_FOUND_ROWS DISTINCT o.orders_id AS order_id, " .
            " o.api_client_order_id AS client_order_id, ".
            " o.tracking_number, " .
            (class_exists('\common\classes\SapClient')?
            " o.sap_export, o.sap_export_mode, o.sap_order_id, o.sap_export_date, ":''
            ).
            " o.orders_status AS order_status, ".
            " o.platform_id, ".
            " o.date_purchased, o.last_modified, ".
            " o.currency, o.currency_value, ".
            " o.transaction_id, o.purchase_order, o.payment_method, o.payment_class, ".
            " ot_total.value AS total ".
            " " .
            "FROM " . TABLE_ORDERS . " o " .
            " LEFT JOIN ".TABLE_ORDERS_TOTAL." ot_total ON o.orders_id=ot_total.orders_id AND ot_total.class='ot_total' ".
            " {$join_tables} " .
            "WHERE 1 {$filter_sql} " .
            "{$orderBy} " .
            "";
        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
        //echo $main_sql; die;

        $get_data_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_data_r), (int)$getRows['rows_count']);

        $status_names = [];
        if ( tep_db_num_rows($get_data_r)>0 ) {
            while($_data = tep_db_fetch_array($get_data_r)){
                if ( !isset($status_names[$_data['order_status']]) ) {
                    $status_names[$_data['order_status']] = '';
                    $order_status_query = tep_db_query("select orders_status_name from " . TABLE_ORDERS_STATUS . " where orders_status_id = '" . (int)$_data['order_status'] . "' and language_id = '" . (int)$languages_id . "' LIMIT 1");
                    if ( tep_db_num_rows($order_status_query)>0 ) {
                        $order_status = tep_db_fetch_array($order_status_query);
                        $status_names[$_data['order_status']] = $order_status['orders_status_name'];
                    }
                }
                $_data['orders_status_name'] = $status_names[$_data['order_status']];
                if ( !empty($_data['tracking_number']) ) {
                    $order = new \common\classes\Order($_data['order_id']);
                    $_data['tracking_number'] = $order->info['tracking_number'];
                }

                if ($ext = \common\helpers\Acl::checkExtensionAllowed('OrderMarkers', 'allowed')) {
                    $orderMarker = $ext::getOrderMarkers($_data['order_id']);
                    if ( is_array($orderMarker) ) {
                        $_data['flags'] = $orderMarker['flags'];
                        $_data['markers'] = $orderMarker['markers'];
                    }
                }
                
                if ( $this->asPurchaseOrder ) {
                    unset($_data['client_order_id']);
                }
                $_data['platform_name'] = Tools::getInstance()->getPlatformName($_data['platform_id']);
                
                $this->ordersInfo->order_info[] = new OrderShortInfo($_data);
            }
        }

        $this->ordersInfo->build();

        parent::build();
    }
}
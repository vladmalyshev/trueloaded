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


use common\api\models\AR\Customer;
use common\api\models\Soap\Customer\ArrayOfCustomers;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;

class SearchCustomersResponse extends SoapModel
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
     * @var \common\api\models\Soap\Customer\ArrayOfCustomers Array of Customer {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $customers;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    public function __construct(array $config = [])
    {
        $this->customers = new ArrayOfCustomers();

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
        $join_tables = '';
        $filter_sql = '';

        $this->searchCondition->setDateTimeColumns(['time_modified']);
        $filter_conditions = $this->searchCondition->buildRequestCondition([
            'customers_email_address' => 'c.customers_email_address',
            'customers_status' => 'c.customers_status',
            'time_modified' => 'c._api_time_modified',
        ]);
        if ( $filter_conditions===false ) {
            $this->error($this->searchCondition->getLastError());
            return;
        }

        if ( !empty($filter_conditions) ) {
            $filter_sql .= "AND {$filter_conditions} ";
        }

        if ( ServerSession::get()->getDepartmentId() ) {
            $filter_sql .= "AND c.departments_id='".ServerSession::get()->getDepartmentId()."' ";
        }

        SoapHelper::updateCustomerModifyTime();

        $main_sql =
            "SELECT SQL_CALC_FOUND_ROWS c.customers_id ".
            "FROM ".TABLE_CUSTOMERS." c ".
            "  {$join_tables} ".
            "WHERE 1 {$filter_sql} ".
            "ORDER BY c.customers_id";
        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
        //echo $main_sql; die;
        $get_rows_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_rows_r), (int)$getRows['rows_count']);
        if ( tep_db_num_rows($get_rows_r)>0 ) {
            while($_data = tep_db_fetch_array($get_rows_r)){
                $customerAR = Customer::findOne(['customers_id'=>$_data['customers_id']]);
                if ( !is_object($customerAR) || empty($customerAR->customers_id)) {
                    \Yii::error('SearchCustomersResponse Customer #'.$_data['customers_id'].' Load error','soap_server');
                    continue;
                }
                try {
                    $this->customers->customer[] = new \common\api\models\Soap\Customer\Customer($customerAR->exportArray([]));
                }catch (\Exception $ex){
                    \Yii::error('Exception SearchCustomersResponse Customer #'.$_data['customers_id'].' Load '.$ex->getTraceAsString(),'soap_server');
                }
            }
        }

        $this->customers->build();

        parent::build();

    }
}
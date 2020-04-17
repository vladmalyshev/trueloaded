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


use common\api\models\Soap\Customer\ArrayOfCustomers;
use common\api\models\Soap\Customer\Customer;
use common\api\models\Soap\Customer\CustomerSearch;
use common\api\SoapServer\ServerSession;

class GetCustomerResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Customer\ArrayOfCustomers ArrayOfCustomers {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $customers;
    /**
     * @var CustomerSearch
     */
    protected $searchCondition;

    public function __construct(array $config = [])
    {
        $this->customers = new ArrayOfCustomers();
        parent::__construct($config);
    }


    public function setSearchCondition(CustomerSearch $search)
    {
        $this->searchCondition = $search;
    }

    public function build()
    {
        $where = '';
        if ( ServerSession::get()->getDepartmentId()>0 ) {
            $where .= "AND departments_id='".ServerSession::get()->getDepartmentId()."' ";
        }
        /*
        if ( ServerSession::get()->getPlatformId()>0 ) {
            $where .= "AND platform_id='".ServerSession::get()->getDepartmentId()."' ";
        }
        */

        if ( $this->searchCondition ) {
            $where .= "AND customers_email_address='".tep_db_input($this->searchCondition->customers_email_address)."' ";
        }else{
            $where .= "AND 1=0 ";
        }

        $get_customers_r = tep_db_query(
            "SELECT * ".
            "FROM ".TABLE_CUSTOMERS." ".
            "WHERE 1 {$where} "
        );
        if ( tep_db_num_rows($get_customers_r)>0 ) {
            while( $_customer = tep_db_fetch_array($get_customers_r) ){
                $this->customers->customer[] = new Customer($_customer);
            }
        }

    }


}
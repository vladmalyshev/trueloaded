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


use common\api\models\AR\Manufacturer;
use common\api\models\Soap\Products\ArrayOfManufacturers;
use common\api\models\Soap\Products\Manufacturer as SoapBrand;

class GetManufacturersResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\ArrayOfManufacturers Array of ArrayOfManufacturers {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $manufacturers_list;

    /**
     * @var ArrayOfSearchConditions
     */
    public $searchCondition = false;

    public function __construct(array $config = [])
    {
        if ( !is_object($this->paging) ) {
            $this->paging = new Paging();
        }

        parent::__construct($config);
    }

    public function setSearchCondition(ArrayOfSearchConditions $searchCondition)
    {
        $this->searchCondition = $searchCondition;
    }

    public function build()
    {
        $this->paging->setMaxPerPage(1000);

        $this->manufacturers_list = new ArrayOfManufacturers();

        $joins = '';
        $condition = '';

        $main_sql =
            "SELECT SQL_CALC_FOUND_ROWS DISTINCT m.manufacturers_id ".
            "FROM ".TABLE_MANUFACTURERS." m ".
            " {$joins} ".
            "WHERE 1 ".
            " {$condition} ".
            "ORDER BY m.manufacturers_id ";

        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();

        $get_list_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_list_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_list_r)>0 ) {
            while( $data = tep_db_fetch_array($get_list_r) ) {
                $propertyObj = Manufacturer::findOne(['manufacturers_id'=>$data['manufacturers_id']]);
                $data = $propertyObj->exportArray([]);
                $this->manufacturers_list->manufacturer[] = new SoapBrand($data);
            }
        }
    }

}
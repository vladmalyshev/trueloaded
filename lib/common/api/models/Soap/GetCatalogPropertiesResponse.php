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

use common\api\models\AR\CatalogProperty;
use common\api\models\Soap\ArrayOfSearchConditions;
use common\api\models\Soap\Products\ArrayOfProductCatalogProperties;
use common\api\models\Soap\Products\CatalogProductProperty;

class GetCatalogPropertiesResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\ArrayOfProductCatalogProperties Array of ArrayOfProductCatalogProperties {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $properties;

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

        $this->properties = new ArrayOfProductCatalogProperties();

        $joins = '';
        $condition = '';

        $main_sql =
            "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.properties_id ".
            "FROM ".TABLE_PROPERTIES." p ".
            " {$joins} ".
            "WHERE 1 ".
            " {$condition} ".
            "ORDER BY p.properties_id ";

        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();

        $get_list_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_list_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_list_r)>0 ) {
            while( $data = tep_db_fetch_array($get_list_r) ) {
                $propertyObj = CatalogProperty::findOne(['properties_id'=>$data['properties_id']]);
                $data = $propertyObj->exportArray([]);
                $this->properties->property[] = new CatalogProductProperty($data);
            }
        }
    }
}
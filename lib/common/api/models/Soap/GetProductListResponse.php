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

use common\api\models\Soap\Products\ArrayOfProductRef;
use common\api\models\Soap\Products\ProductRef;
use common\api\SoapServer\ServerSession;

class GetProductListResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Paging {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $paging;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfProductRef Array of ArrayOfProductRef {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $products;

    public function __construct(array $config = [])
    {
        if ( !is_object($this->paging) ) {
            $this->paging = new Paging();
        }

        parent::__construct($config);
    }


    public function build()
    {
        $this->paging->setMaxPerPage(1000);

        $this->products = new ArrayOfProductRef();

        $joins = '';
        $condition = '';
        if ( ServerSession::get()->getDepartmentId() ) {
            $condition = '';
            $joins .=
                "INNER JOIN ".TABLE_DEPARTMENTS_PRODUCTS." dp ON dp.products_id=p.products_id AND dp.departments_id='".ServerSession::get()->getDepartmentId()."' ".
                "INNER JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p2c.products_id=p.products_id ".
                "INNER JOIN ".TABLE_DEPARTMENTS_CATEGORIES." dc ON p2c.categories_id=dc.categories_id AND dc.departments_id='".ServerSession::get()->getDepartmentId()."' ";
        }
        if ( ServerSession::get()->getPlatformId() ) {
            $condition = '';
            if (\common\classes\platform::isMulti() && !ServerSession::get()->acl()->siteAccessPermission()) {
                $joins .=
                    "INNER JOIN " . TABLE_PLATFORMS_PRODUCTS . " plp ON plp.products_id=p.products_id AND plp.platform_id='" . ServerSession::get()->getPlatformId() . "' " .
                    "INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id=p.products_id " .
                    "INNER JOIN " . TABLE_PLATFORMS_CATEGORIES . " plc ON p2c.categories_id=plc.categories_id AND plc.platform_id='" . ServerSession::get()->getPlatformId() . "' ";
            }
        }

        $main_sql =
            "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.products_id, p.products_model, ".
            (ServerSession::get()->getDepartmentId()?
                " IF(p.created_by_department_id='".ServerSession::get()->getDepartmentId()."',1,0) AS is_own_product, ":
                (ServerSession::get()->getPlatformId()?
                    " IF(p.created_by_platform_id='".ServerSession::get()->getPlatformId()."',1,0) AS is_own_product, ":''
                )
            ).
            " p.products_date_added, p.products_last_modified ".
            "FROM ".TABLE_PRODUCTS." p ".
            " {$joins} ".
            "WHERE 1 ".
            " {$condition} ".
            "ORDER BY p.products_id ";

        $main_sql .= " LIMIT ".$this->paging->getPageOffset().", ".$this->paging->getPerPage();
//echo $main_sql."\n\n"; die;
        $get_list_r = tep_db_query($main_sql);
        $getRows = tep_db_fetch_array(tep_db_query("SELECT FOUND_ROWS() AS rows_count"));
        $this->paging->setFoundRows(tep_db_num_rows($get_list_r), (int)$getRows['rows_count']);

        if ( tep_db_num_rows($get_list_r)>0 ) {
            while( $data = tep_db_fetch_array($get_list_r) ) {
                if ( isset($data['is_own_product']) ) $data['is_own_product'] = !!$data['is_own_product'];
                $this->products->product[] = new ProductRef($data);
            }
        }
    }
}
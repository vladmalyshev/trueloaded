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


use common\api\models\AR\Categories;
use common\api\models\Soap\Categories\ArrayOfCategories;
use common\api\models\Soap\Categories\Category;
use common\api\SoapServer\ServerSession;

class GetCategoriesResponse extends SoapModel
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
     * @var \common\api\models\Soap\Categories\ArrayOfCategories Category {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $categories;

    public function __construct(array $config = [])
    {
        $this->categories = new ArrayOfCategories();

        parent::__construct($config);
    }

    public function build()
    {
        $join_tables = '';
        if ( ServerSession::get()->getDepartmentId() ) {
            $join_tables .=
                " INNER JOIN ".TABLE_DEPARTMENTS_CATEGORIES." dc ON c.categories_id=dc.categories_id AND dc.departments_id='".ServerSession::get()->getDepartmentId()."' ";
        }
        if (ServerSession::get()->getPlatformId() && !ServerSession::get()->acl()->siteAccessPermission()){
            $join_tables .=
                " INNER JOIN ".TABLE_PLATFORMS_CATEGORIES." plc ON c.categories_id=plc.categories_id AND plc.platform_id='".ServerSession::get()->getPlatformId()."' ";
        }

        $data_query_sql =
            "SELECT c.categories_id ".
            "FROM ".TABLE_CATEGORIES." c ".
            " {$join_tables} ".
            "WHERE 1 ".
            "ORDER BY c.categories_left ";

        $data_r = tep_db_query($data_query_sql);
        if ( tep_db_num_rows($data_r)>0 ) {
            while($data = tep_db_fetch_array($data_r)) {
                $categoryObj = Categories::findOne(['categories_id'=>$data['categories_id']]);
                if ( $categoryObj ) {
                    $exportData = $categoryObj->exportArray([]);
                    $this->categories->category[] = new Category($exportData);
                }
            }
        }

        parent::build();
    }
}
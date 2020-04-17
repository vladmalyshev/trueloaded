<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;

use common\api\models\Soap\SoapModel;
use common\api\SoapServer\ServerSession;

class ProductRef extends SoapModel
{
    /**
     * @var integer
     * @soap
     */
    public $products_id;

    /**
     * @var string
     * @soap
     */
    public $products_model;

    /**
     * @var datetime
     * @soap
     */
    public $products_date_added;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_last_modified;

    /**
     * @var boolean {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $is_own_product;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if ( !empty($this->products_date_added) ) {
            $this->products_date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->products_date_added);
        }
        if ( !empty($this->products_last_modified) ) {
            $this->products_last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->products_last_modified);
        }
    }

    static function fromId($id)
    {
        $data = [];
        $get_data_r = tep_db_query(
            "SELECT products_id, products_model, ".
            (ServerSession::get()->getDepartmentId()?
                " IF(created_by_department_id='".ServerSession::get()->getDepartmentId()."',1,0) AS is_own_product, ":
                (ServerSession::get()->getPlatformId()?
                    " IF(created_by_platform_id='".ServerSession::get()->getPlatformId()."',1,0) AS is_own_product, ":''
                )
            ).
            " products_date_added, products_last_modified ".
            "FROM ".TABLE_PRODUCTS." ".
            "WHERE products_id='".(int)$id."'"
        );
        if ( tep_db_num_rows($get_data_r)>0 ) {
            $data = tep_db_fetch_array($get_data_r);
            if ( isset($data['is_own_product']) ) $data['is_own_product'] = !!$data['is_own_product'];
        }

        return new self($data);
    }
}
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


use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;

class RemoveProductResponse extends SoapModel
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
     * @var int
     */
    protected $productId;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;

        if (ServerSession::get()->getDepartmentId() && !SoapHelper::hasProduct($this->productId)) {
            $this->error('The product does not belong to the department.');
            return;
        }elseif (ServerSession::get()->getPlatformId() && !SoapHelper::hasProduct($this->productId)) {
            $this->error('The product does not belong to the platform.');
            return;
        }

        if (!ServerSession::get()->acl()->allowRemoveProduct()){
            $this->error('Product removal is not allowed');
            return;
        }

        $get_owner = ['c'=>0];
        if ( ServerSession::get()->getDepartmentId() ) {
            $get_owner = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c " .
                "FROM " . TABLE_PRODUCTS . " " .
                "WHERE products_id='" . (int)$this->productId . "' AND created_by_department_id='" . ServerSession::get()->getDepartmentId() . "'"
            ));
        }elseif(ServerSession::get()->getPlatformId()){
            if (ServerSession::get()->acl()->siteAccessPermission()) {
                $get_owner = ['c' => 1]; // hasProduct - check existent
            }else {
                $get_owner = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS c " .
                    "FROM " . TABLE_PRODUCTS . " " .
                    "WHERE products_id='" . (int)$this->productId . "' AND created_by_platform_id='" . ServerSession::get()->getPlatformId() . "'"
                ));
            }
        }
        if ( $get_owner['c']>0 ) {

        }else{
            $this->error('Only product own can remove product');
            return;
        }
    }

    public function build()
    {
        if ($this->status == 'ERROR') return;

        \common\helpers\Product::remove_product($this->productId);
        \Yii::info('SoapServer: product removal #'.$this->productId, 'datasource');
    }
}
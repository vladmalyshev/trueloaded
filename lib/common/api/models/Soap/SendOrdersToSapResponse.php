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

class SendOrdersToSapResponse  extends SoapModel
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
     * @var string[]
     * @soap
     */
    public $export_messages = [];

    public $orderIds;

    public function build()
    {
        $ownCheck = '';
        if (!ServerSession::get()->acl()->siteAccessPermission()){
            $ownCheck =
                " ".(ServerSession::get()->getDepartmentId()?"AND department_id='".ServerSession::get()->getDepartmentId()."' ":'').
                " ".(!ServerSession::get()->getDepartmentId() && ServerSession::get()->getPlatformId()?"AND platform_id='".ServerSession::get()->getPlatformId()."' ":'');
        }
        foreach ($this->orderIds as $_idx=>$orderId){
            $check_order = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c ".
                "FROM ".TABLE_ORDERS." ".
                "WHERE orders_id='".(int)$orderId."' ".
                " {$ownCheck}"
            ));
            if ( $check_order['c'] ) {
                // ok
            }else{
                unset($this->orderIds[$_idx]);
                $this->warning('Order #'.$orderId.' not found');
            }
        }

        if ( $this->status!='ERROR' ) {
            if ( class_exists('\common\helpers\SapCommon') ) {
                if (count($this->orderIds) > 0) {
                    $result = \common\helpers\SapCommon::exportOrderManually($this->orderIds);
                    $this->export_messages = $result['messages'];
                }
            }else{
                $this->error('SAP support missing');
            }
        }

        parent::build();

    }

}
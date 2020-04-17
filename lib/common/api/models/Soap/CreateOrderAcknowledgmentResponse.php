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

class CreateOrderAcknowledgmentResponse extends SoapModel
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

    public $orderId = 0;
    public $clientOrderId = 0;

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function setClientOrderId($orderId)
    {
        $this->clientOrderId = $orderId;
    }

    public function build()
    {
        $ownCheck = '';
        if (!ServerSession::get()->acl()->siteAccessPermission()){
            $ownCheck =
                (ServerSession::get()->getDepartmentId()?"AND department_id='".ServerSession::get()->getDepartmentId()."' ":'').
                (!ServerSession::get()->getDepartmentId() && ServerSession::get()->getPlatformId()?"AND platform_id='".ServerSession::get()->getPlatformId()."' ":'');
        }
        $check_order_ref_r = tep_db_query(
            "SELECT orders_id, api_client_order_id " .
            "FROM " . TABLE_ORDERS . " " .
            "WHERE orders_id='".tep_db_input($this->orderId)."' ".
            " {$ownCheck}"
        );
        if ( tep_db_num_rows($check_order_ref_r)>0 ) {
            $check_order_ref = tep_db_fetch_array($check_order_ref_r);
            if ( $check_order_ref['api_client_order_id'] ) {
                $this->error('Order already has client order id');
            }else{

                $check_unique_ref = tep_db_fetch_array(tep_db_query(
                    "SELECT COUNT(*) AS assigned " .
                    "FROM " . TABLE_ORDERS . " " .
                    "WHERE api_client_order_id='".tep_db_input($this->clientOrderId)."' ".
                    " {$ownCheck}"
                ));
                if ( $check_unique_ref['assigned']==0 ) {
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS." ".
                        "SET ".
                        " api_client_order_id='".tep_db_input($this->clientOrderId)."', ".
                        " _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                        "WHERE orders_id = '".intval($this->orderId)."' ".
                        " {$ownCheck}"
                    );
                }else{
                    $this->error('Client order id "'.$this->clientOrderId.'" already used');
                }
            }
        }else{
            $this->error('Order not found');
        }

    }

}
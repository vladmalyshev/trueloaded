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

class UpdateOrderAcknowledgmentResponse extends SoapModel
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

    protected $update_order_ids = [];

    public function setOrderIds($ids)
    {
        $this->update_order_ids = array_map('intval',$ids);
    }

    public function build()
    {

        if ( count($this->update_order_ids)>0 ) {
            $ownCheck = '';
            if ( ServerSession::get()->acl()->siteAccessPermission() ) {
                $ownCheck =
                    " ".(ServerSession::get()->getDepartmentId()?"AND department_id='".ServerSession::get()->getDepartmentId()."' ":'').
                    " ".(!ServerSession::get()->getDepartmentId() && ServerSession::get()->getPlatformId()?"AND platform_id='".ServerSession::get()->getPlatformId()."' ":'');
            }
            tep_db_query(
                "UPDATE ".TABLE_ORDERS." ".
                "SET _api_order_time_modified=_api_order_time_modified, _api_order_time_processed=_api_order_time_modified ".
                "WHERE orders_id IN ('".implode("', '",$this->update_order_ids)."') ".
                " {$ownCheck} "
            );
        }

    }
}
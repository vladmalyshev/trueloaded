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


class ResetSapOrderErrorResponse extends SoapModel
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

    public $orderId;
    public $newState;

    public function build()
    {
        if ( is_numeric($this->newState) && in_array((int)$this->newState, [0,-1, 2]) ) {

        }else{
            $this->error("Invalid Order new state: possible values is 0,-1, 2");
        }

        if ( $this->status!='ERROR' ) {
            tep_db_query(
                "UPDATE " . TABLE_ORDERS . " ".
                "SET sap_export='".(int)$this->newState."', last_modified=NOW()  ".
                "WHERE orders_id='".(int)$this->orderId."' AND sap_export!=1 "
            );
            if ( (int)$this->newState==0 ) {
                tep_db_query(
                    "DELETE FROM ep_sap_order_issues WHERE orders_id='".(int)$this->orderId."'"
                );
            }
        }

        parent::build();
    }


}
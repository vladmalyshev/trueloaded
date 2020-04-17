<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

class Sample {
    
    use StatusTrait;
    
    public static function getStatusTypeId()
    {
        return 4;
    }

    public static function getStatusGroup() {
        $order_status_query = tep_db_query("SELECT orders_status_groups_id FROM " . TABLE_ORDERS_STATUS_GROUPS . " WHERE orders_status_groups_name = 'Samples'");
        $order_status = tep_db_fetch_array($order_status_query);
        return $order_status['orders_status_groups_id'];
    }
    
    public static function getStatus($name) {
        $order_status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = '" . $name . "' AND orders_status_groups_id = '" . self::getStatusGroup() . "'");
        $order_status = tep_db_fetch_array($order_status_query);
        return $order_status['orders_status_id'];
    }

}

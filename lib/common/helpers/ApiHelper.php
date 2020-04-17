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


class ApiHelper
{

    static public function generateApiKey()
    {
        $__server_part = tep_db_fetch_array(tep_db_query(
            "SELECT UUID() AS server_part"
        ));
        return strtolower(str_replace('-','',$__server_part['server_part']).\common\helpers\Password::create_random_value(16));
    }

}
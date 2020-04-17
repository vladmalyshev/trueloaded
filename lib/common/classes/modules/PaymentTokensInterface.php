<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\classes\modules;

interface PaymentTokensInterface {

/**
 * calls parent deleteToken to check/delete token locally and sends delete token request to gateway
 * @param int $customersId
 * @param string $token
 * @return int|bool [number of tokens deleted]
 */
    public function deleteToken($customersId, $token);

/**
 * checks whether the module supports token system and tokens allowed on the site.
 * @return bool
 */
    public function hasToken(): bool ;

/**
 * checks whether the module hasToken and its enabled in the module settings.
 * @return bool
 */
    public function useToken(): bool;

}

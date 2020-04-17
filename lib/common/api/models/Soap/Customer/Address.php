<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Customer;


use common\api\models\Soap\SoapModel;

class Address extends SoapModel
{

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $address_book_id;

    /**
     * @var bool
     * @soap
     */
    public $is_default;

    /**
     * @var string {nillable=1}
     * @soap
     */
    public $entry_gender;

    /**
     * @var string
     * @soap
     */
    public $entry_firstname;

    /**
     * @var string
     * @soap
     */
    public $entry_lastname;

    /**
     * @var string
     * @soap
     */
    public $entry_street_address;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $entry_suburb;

    /**
     * @var string
     * @soap
     */
    public $entry_postcode;

    /**
     * @var string
     * @soap
     */
    public $entry_city;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $entry_state;

    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $entry_country_id;

    /**
     * @var string
     * @soap
     */
    public $entry_country_iso2;


//| entry_zone_id        | int(11)      | NO     |       | 0         |                |
// entry_company        | varchar(32)  | YES    |       | <null>    |                |
//| entry_company_vat    | varchar(128) | NO     |       |           |                |


}
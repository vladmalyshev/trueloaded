<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;


use common\api\models\Soap\SoapModel;

class BillingAddress extends SoapModel
{

    /**
     * @var string  {nillable=1}
     * @soap
     */
    public $gender;

    /**
     * @var string
     * @soap
     */
    public $name;

    /**
     * @var string
     * @soap
     */
    public $firstname;

    /**
     * @var string
     * @soap
     */
    public $lastname;

    /**
     * @var string
     * @soap
     */
    public $company;

    /**
     * @var string
     * @soap
     */
    public $street_address;

    /**
     * @var string
     * @soap
     */
    public $suburb;

    /**
     * @var string
     * @soap
     */
    public $city;

    /**
     * @var string
     * @soap
     */
    public $postcode;

    /**
     * @var string
     * @soap
     */
    public $state;

    /**
     * @var string
     * @soap
     */
    public $country_iso2;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $telephone;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $email_address;

    /**
     * @var integer
     * @soap
     */
    public $address_book_id;

    public function __construct(array $config = [])
    {
        if ( isset($config['country']) && is_array($config['country']) && !empty($config['country']['iso_code_2']) ) {
            $config['country_iso2'] = $config['country']['iso_code_2'];
        }
        parent::__construct($config);
    }

}
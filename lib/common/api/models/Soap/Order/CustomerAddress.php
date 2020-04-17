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

class CustomerAddress extends SoapModel
{
    /**
     * @var integer
     * @soap
     */
    public $customer_id;

    /**
     * @var string
     * @soap
     */
    public $company_vat;

    /**
     * @var string
     * @soap
     */
    public $company_vat_status;

    /**
     * @var string
     * @soap
     */
    public $telephone;

    /**
     * @var string
     * @soap
     */
    public $landline;

    /**
     * @var string
     * @soap
     */
    public $email_address;


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


    public function __construct(array $config = [])
    {
        if ( isset($config['country']) && is_array($config['country']) && !empty($config['country']['iso_code_2']) ) {
            $config['country_iso2'] = $config['country']['iso_code_2'];
        }
        parent::__construct($config);
    }

}
<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;


use common\api\models\Soap\SoapModel;

class FeaturedInfo extends SoapModel
{

    /**
     * @var boolean
     * @soap
     */
    public $status;

    /**
     * @var date {nillable = 1, minOccurs=0}
     * @soap
     */
    public $expires_date;

    protected $_castType = [
        'expires_date' => ['date', true],
    ];

    public function __construct(array $config = [])
    {
        if (isset($config['expires_date']) && substr($config['expires_date'],0,10)=='0000-00-00') {
            $config['expires_date'] = null;
        }
        parent::__construct($config);
    }

    public static function makeAR($data)
    {
        if ( is_array($data) && array_key_exists('status',$data) ) {
            $result = [
                'status' => $data['status'] ? 1 : 0,
                'expires_date' => $data['expires_date'] > 2000 ? date('Y-m-d H:i:s', strtotime($data['expires_date'])) : null,
                'affiliate_id' => 0,
            ];
            return [$result];
        }
        return [];
    }

}
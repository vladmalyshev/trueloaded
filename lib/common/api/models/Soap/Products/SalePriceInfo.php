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

class SalePriceInfo extends SoapModel
{

    /**
     * @var boolean
     * @soap
     */
    public $status;

    /**
     * @var float {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $price;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $start_date;

    /**
     * @var datetime {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $expires_date;

    protected $_castType = [
        'start_date' => ['datetime',true],
        'expires_date' => ['datetime',true],
    ];

    public function __construct(array $config = [])
    {
        if ( array_key_exists('specials_new_products_price',$config) && !array_key_exists('price',$config) ){
            $config['price'] = $config['specials_new_products_price'];
        }
        if ( $config['price']<0 ) {
            $config['price'] = null;
        }

        parent::__construct($config);
    }

    public static function makeAR($data)
    {
        if ( is_array($data) && array_key_exists('price',$data) ) {
            $result = [
                'status' => (isset($data['status']) && $data['status']) ? 1 : 0,
                'specials_new_products_price' => $data['price'],
                'start_date' => (isset($data['start_date']) && $data['start_date'] > 2000) ? date('Y-m-d H:i:s', strtotime($data['start_date'])) : null,
                'expires_date' => (isset($data['expires_date']) && $data['expires_date'] > 2000) ? date('Y-m-d H:i:s', strtotime($data['expires_date'])) : null,
            ];
            return [$result];
        }
        return [];
    }

}
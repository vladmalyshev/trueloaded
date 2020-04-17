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

class XSell extends SoapModel
{

    /**
     * @var integer
     * @soap
     */
    public $sort_order;

    /**
     * @var integer
     * @soap
     */
    public $xsell_type_id;

    /**
     * @var string
     * @soap
     */
    public $xsell_type_name;

    /**
     * @var \common\api\models\Soap\Products\ProductRef ProductRef
     * @soap
     */
    public $product;

    public function __construct(array $config = [])
    {
        $this->product = ProductRef::fromId($config['xsell_id']);
        parent::__construct($config);
    }


}
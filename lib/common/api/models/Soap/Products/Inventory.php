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

class Inventory extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $products_id;

    /**
     * @var integer
     * @soap
     */
    public $prid;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_name;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_ean;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_asin;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_isbn;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_upc;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $non_existent;

    /**
     * @var \common\api\models\Soap\Products\StockInfo {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $stock_info;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfAttributeMap Array of ArrayOfAttributeMap {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $attribute_maps = [];

    public function __construct(array $config = [])
    {
        if ( isset($config['attribute_map']) ) {
            $this->attribute_maps = new ArrayOfAttributeMap($config['attribute_map']);
            unset($config['attribute_map']);
        }

        if ( array_key_exists('products_quantity', $config) ) {
            $this->stock_info = new StockInfo($config);
        }else{
            $this->stock_info = new StockInfo();
        }

        parent::__construct($config);
    }

    public function inputValidate()
    {
        $validate = [];
        if ( $this->stock_info ){
            $validate = array_merge($validate, $this->stock_info->inputValidate());
        }
        return $validate;
    }

}
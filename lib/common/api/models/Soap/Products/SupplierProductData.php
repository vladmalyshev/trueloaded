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

class SupplierProductData extends SoapModel
{

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    var $products_id;

    /**
     * @var int
     * @soap
     */
    var $suppliers_id;
    /**
     * @var string
     * @soap
     */
    var $suppliers_name;

    /**
     * @var string
     * @soap
     */
    var $suppliers_model;

    /**
     * @var string
     * @soap
     */
    var $suppliers_product_name;

    /**
     * @var string
     * @soap
     */
    var $suppliers_upc;
    /**
     * @var string
     * @soap
     */
    var $suppliers_ean;
    /**
     * @var string
     * @soap
     */
    var $suppliers_isbn;
    /**
     * @var string
     * @soap
     */
    var $suppliers_asin;

    /**
     * @var float
     * @soap
     */
    var $suppliers_price;

    /**
     * @var float {nillable = 1}
     * @soap
     */
    var $suppliers_discount;

    /**
     * @var float {nillable = 1}
     * @soap
     */
    var $suppliers_surcharge_amount;

    /**
     * @var float {nillable = 1}
     * @soap
     */
    var $suppliers_margin_percentage;

    /**
     * @var integer
     * @soap
     */
    var $suppliers_quantity;

    //supplier_discount           | decimal(8,2) | NO     |       | 0.00      |         |
    //suppliers_surcharge_amount  | decimal(8,2) | NO     |       | 0.00      |         |
    //suppliers_margin_percentage | decimal(8,2) | NO     |       | 0.00      |         |

    /**
     * @var datetime {nillable = 1, minOccurs=0}
     * @soap
     */
    var $date_added;

    /**
     * @var datetime {nillable = 1, minOccurs=0}
     * @soap
     */
    var $last_modified;

    /**
     * @var string
     * @soap
     */
    var $source;

    /**
     * @var string
     * @soap
     */
    var $notes;

    var $is_default;

    public function __construct(array $config = [])
    {
        if ( isset($config['uprid']) ) {
            $config['products_id'] = $config['uprid'];
        }

        parent::__construct($config);

        if ( !empty($this->date_added) ) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }

        if ( !empty($this->last_modified) ) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }
    }


}
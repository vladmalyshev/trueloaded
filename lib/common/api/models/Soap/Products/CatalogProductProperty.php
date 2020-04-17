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
use common\api\models\Soap\Products\ArrayOfProductCatalogPropertiesDescription;

class CatalogProductProperty extends SoapModel
{
    /**
     * @var int {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    var $properties_id;

    /**
     * @var int {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    var $parent_id;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfProductCatalogPropertiesDescription {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    var $descriptions;

    /**
     * @var int
     * @soap
     */
    var $sort_order;

    /**
     * @var string
     * @soap
     */
    var $properties_type;

    /**
     * @var bool
     * @soap
     */
    var $multi_choice;

    /**
     * @var bool
     * @soap
     */
    var $multi_line;

    /**
     * @var int
     * @soap
     */
    var $decimals;

    /**
     * @var bool
     * @soap
     */
    var $display_product;

    /**
     * @var bool
     * @soap
     */
    var $display_listing;

    /**
     * @var bool
     * @soap
     */
    var $display_filter;

    /**
     * @var bool
     * @soap
     */
    var $display_search;

    /**
     * @var bool
     * @soap
     */
    var $display_compare;

    /**
     * @var bool
     * @soap
     */
    var $products_groups;

    /**
     * @var datetime
     * @soap
     */
    var $date_added;

    /**
     * @var datetime {nillable = 1, minOccurs=0}
     * @soap
     */
    var $last_modified;

    public function __construct(array $config = [])
    {
        if ( isset($config['descriptions']) & is_array($config['descriptions']) ) {
            $config['descriptions'] = new ArrayOfProductCatalogPropertiesDescription($config['descriptions']);
        }else{
            $config['descriptions'] = new ArrayOfProductCatalogPropertiesDescription([]);
        }
        parent::__construct($config);

        if (!empty($this->date_added)) {
            $this->date_added = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->date_added);
        }
        if (!empty($this->last_modified)) {
            $this->last_modified = \common\api\SoapServer\SoapHelper::soapDateTimeOut($this->last_modified);
        }
    }
}
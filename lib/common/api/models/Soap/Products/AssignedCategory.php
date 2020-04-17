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

class AssignedCategory extends SoapModel
{

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $categories_id;
    /**
     * @var integer
     * @soap
     */
    public $sort_order;
    /**
     * @var string {minOccurs=0}
     * @soap
     */
    public $categories_path;

    /**
     * @var \common\api\models\Soap\Products\ArrayOfCategoryInfo Array of CategoryInfo {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $categories_path_array = [];

    public function __construct(array $config = [])
    {
        if ( isset($config['categories_path_array']) && is_array($config['categories_path_array']) ) {
            $this->categories_path_array = new ArrayOfCategoryInfo($config['categories_path_array']);
            unset($config['categories_path_array']);
        }
        parent::__construct($config);
    }


}
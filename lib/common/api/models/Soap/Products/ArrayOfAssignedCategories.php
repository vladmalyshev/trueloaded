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

class ArrayOfAssignedCategories extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Products\AssignedCategory AssignedCategory {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $assigned_category = [];

    public function __construct(array $config = [])
    {
        foreach( $config as $key=>$data ) {
            $this->assigned_category[] = new AssignedCategory($data);
        }
        parent::__construct([]);
    }


}
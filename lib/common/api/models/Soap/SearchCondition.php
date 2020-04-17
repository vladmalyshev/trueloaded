<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


class SearchCondition extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $column;

    /**
     * @var string
     * @soap
     */
    public $operator;

    /**
     * @-var \common\api\models\Soap\ArrayOfStringValues {nillable = 1, minOccurs=0, maxOccurs = 1}
     * @var string[] {nillable = 1, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $values = [];
    //public $values;

}
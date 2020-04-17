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


use common\api\models\Soap\Store\ArrayOfXsellType;

class GetXsellTypesResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\ArrayOfXsellType Array of XsellType {nillable = 0, minOccurs=1, maxOccurs = unbounded}
     * @soap
     */
    public $xsell_types;

    public function __construct(array $config = [])
    {
        $this->xsell_types = new ArrayOfXsellType();
        parent::__construct($config);
    }

    public function build()
    {
        $this->xsell_types->build();
        parent::build();
    }

}
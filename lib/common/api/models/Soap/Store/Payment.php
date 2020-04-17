<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Store;


use common\api\models\Soap\SoapModel;

class Payment extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $code;

    /**
     * @var string
     * @soap
     */
    public $title;

    /**
     * @var bool
     * @soap
     */
    public $enabled;

}
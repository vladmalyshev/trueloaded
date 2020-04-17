<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Order;


use common\api\models\Soap\SoapModel;

class Total extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $title;

    /**
     * @var double
     * @soap
     */
    public $value;

    /**
     * @var string
     * @soap
     */
    public $code;

    /**
     * @var string
     * @soap
     */
    public $text;

    /**
     * @var string
     * @soap
     */
    public $text_exc_tax;

    /**
     * @var string
     * @soap
     */
    public $text_inc_tax;

    //'tax_class_id' => $totals['tax_class_id'],

    /**
     * @var double
     * @soap
     */
    public $value_exc_vat;

    /**
     * @var double
     * @soap
     */
    public $value_inc_tax;

}
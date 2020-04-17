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

class ArrayOfCurrencies extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\CurrencyRate CurrencyRate {nillable = 0, minOccurs=0, maxOccurs = unbounded}
     * @soap
     */
    public $currency = [];

    public function __construct(array $config = [])
    {
        $currencies = \Yii::$container->get('currencies');

        foreach ($currencies->currencies as $currency) {
            $currency['status'] = in_array($currency['code'],$currencies->platform_currencies);
            $currency['is_default'] = $currencies->dp_currency==$currency['code'];
            $this->currency[] = new CurrencyRate($currency);
        }

        parent::__construct($config);
    }

}
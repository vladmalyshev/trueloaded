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


use common\api\models\Soap\Store\ArrayOfPayments;
use common\api\models\Soap\Store\Payment;
use common\classes\modules\ModulePayment;

class GetAvailablePaymentsResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\ArrayOfPayments Array of ArrayOfPayments {nillable = 0, minOccurs=1, maxOccurs = unbounded}
     * @soap
     */
    public $payments;

    public function __construct(array $config = [])
    {
        $this->payments = new ArrayOfPayments();

        $payment_modules = new \common\classes\payment();
        foreach( $payment_modules->modules as  $filename ) {
            $className = preg_replace('/\.php$/','',$filename);
            if ( is_object($GLOBALS[$className]) && $GLOBALS[$className] instanceof ModulePayment) {

                $this->payments->payment[$className] = new Payment([
                    'code' => $className,
                    'title' => $GLOBALS[$className]->title,
                    'enabled' => $GLOBALS[$className]->is_module_enabled( \Yii::$app->get('platform')->config()->getId() ),
                ]);
            }
        }
        $this->payments->payment = array_values($this->payments->payment);

        parent::__construct($config);
    }


}
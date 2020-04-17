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
use common\api\SoapServer\ServerSession;
use common\classes\platform;
use common\models\Currencies;
use common\models\Platforms;

class CurrencyUpdateResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Store\CurrencyRate {nillable = 0, minOccurs=0, maxOccurs = 1}
     * @-soap
     */
    public $currency;

    public function build()
    {
        $updateCurrency = $this->currency;
        $this->currency = null;
        $currencyModel = Currencies::findOne(['code'=>$updateCurrency->code]);
        $platformModel = Platforms::findOne(ServerSession::get()->getPlatformId());
        if ( !$currencyModel || !$currencyModel->currencies_id ) {
            $this->error('Currency "' . $updateCurrency->code . '" not found');
        }elseif ( !$platformModel ) {
            $this->error('Current platform not found');
        }else{
            $currencies = \Yii::$container->get('currencies');

            if ( !is_null($updateCurrency->status) ) {
                if ( $updateCurrency->status && strpos($platformModel->defined_currencies,$currencyModel->code)===false ) {
                    $platformModel->defined_currencies = trim($platformModel->defined_currencies.','.$currencyModel->code,',');
                }
                if ( !$updateCurrency->status && strpos($platformModel->defined_currencies,$currencyModel->code)!==false ) {
                    $platformModel->defined_currencies = trim(preg_replace('/,?'.$currencyModel->code.',?/i',',',$platformModel->defined_currencies),',');
                }
            }

            if ( !is_null($updateCurrency->is_default) && $updateCurrency->is_default ) {
                if ( $platformModel->default_currency!=$currencyModel->code ) {
                    $platformModel->default_currency = $currencyModel->code;
                    if (strpos($platformModel->defined_currencies,$currencyModel->code)===false){
                        $platformModel->defined_currencies = trim($platformModel->defined_currencies.','.$currencyModel->code,',');
                    }
                }
            }

            $platformModel->save(false);

            $updateData = (array)$updateCurrency;
            unset($updateData['id']);
            unset($updateData['is_default']);
            unset($updateData['code']);
            unset($updateData['status']);
            foreach ($updateData as $_k=>$_v) {
                if( is_null($_v) ) unset($updateData[$_k]);
            }
            $currencyModel->setAttributes($updateData);
            $currencyModel->save(false);
        }

        parent::build();
    }


}
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
use common\models\Coupons;
use common\models\CouponsDescription;
use yii\helpers\ArrayHelper;

class UpdateCouponResponse extends SoapModel
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
     * @var \common\api\models\Soap\Store\Coupon Coupon {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $coupon;

    protected $couponIn;

    public function setCoupon(Coupon $couponIn)
    {
        $this->couponIn = $couponIn;
    }

    public function build()
    {
        if ( is_object($this->couponIn) ) {
            $dbModel = Coupons::findOne(['coupon_id' => $this->couponIn->coupon_id]);
            if ( !$dbModel ) {
                $this->error('Record not found','RECORD_NOT_FOUND');
                parent::build();
                return;
            }
            $dbModel->setAttributes((array)$this->couponIn);

            if ( empty($dbModel->coupon_code) ) {
                $dbModel->coupon_code = \common\helpers\Coupon::create_coupon_code();
            }

            if ($dbModel->save()) {
                $dbModel->refresh();

                $languages = ArrayHelper::getColumn(\common\classes\language::get_all(),'id');
                $descIn = [];
                if ( isset($this->couponIn->descriptions) && isset($this->couponIn->descriptions->description) && is_array($this->couponIn->descriptions->description) ) {
                    foreach ($this->couponIn->descriptions->description as $_descModelIn){
                        if ( !isset($languages[$_descModelIn->language]) ) continue;
                        $_descModelIn->language_id = $languages[$_descModelIn->language];
                        $descIn[$_descModelIn->language] = $_descModelIn;
                    }
                }


                foreach( $languages as $code=>$_lang_id ) {
                    $dbDescription = new CouponsDescription();

                    if ( isset($descIn[$code]) ) {
                        $dbDescription->setAttributes((array)$descIn[$code]);
                    }

                    $dbDescription->coupon_id = $dbModel->coupon_id;
                    $dbDescription->language_id = $_lang_id;
                    $dbDescription->save();
                }

                $this->coupon = Coupon::makeFromAR($dbModel);

            }else{
                $err = $dbModel->getErrors();
                foreach ($err as $field=>$errorList){
                    foreach ($errorList as $errorMessage) {
                        $this->error($errorMessage);
                    }
                }
            }
        }else{
            $this->error('Input error');
        }

        parent::build();
    }

}
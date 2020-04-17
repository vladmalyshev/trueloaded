<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\promotions;
use Yii;


class PromotionsCustomerCodes extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'promotions_customer_codes';
    }
    
    public static function primaryKey() {
        return ['promotions_cc_id'];
    }
    
    public function rules() {
        return [
            [['promo_id', 'customer_id'], 'required' ],
        ];
    }
    
    public static function saveCode(\common\models\promotions\Promotions $promo, $customer_id){
        if ($customer_id){
            $model = self::find()
                        ->where('promo_id = :id and customer_id =:cid', [':id' => $promo->promo_id, ':cid' => (int)$customer_id])
                        ->one();
            if (!$model){
                $pcc = new self();
                $pcc->setAttributes([
                    'promo_id' => $promo->promo_id,
                    'customer_id' => (int)$customer_id
                ], false);
                $pcc->save(false);

                return true;
            }        
        }
        return false;
    }
    
    public static function getCustomerRegisteredPromo($customer_id){
        $t1= self::tableName();
        $t2 = Promotions::tableName();
        return  self::find()
                    ->where('customer_id =:cid', [':cid' => (int)$customer_id])
                    ->join("inner join", $t2, "{$t2}.promo_id = {$t1}.promo_id and promo_status = 1")
                    ->all();
    }
    
}
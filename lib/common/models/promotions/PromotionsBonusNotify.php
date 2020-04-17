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
use common\components\Customer;

class PromotionsBonusNotify {
    
    private static $ingore = false;

    public static function setNotification($award, $type = ''){
        if (self::$ingore) return;
        $notif = ['award' => $award,];
        if (!empty($type)){
            $notif['type'] = $type;
        }
        $info = base64_encode(\yii\helpers\Json::encode($notif));
        Yii::$app->session->setFlash('promotion-action', $info);
    }
    
    public static function getNotification(){
        self::$ingore = false;
        $message = Yii::$app->session->getFlash('promotion-action');
        if ($message){
            $message = base64_decode($message);
            try{
                $message = \yii\helpers\Json::decode($message);
                $message['total'] = \common\helpers\Customer::get_customer_points(Yii::$app->user->getId());
            } catch (\Exception $ex) {
                $message = ['message' => $message];
            }
            return $message;
        }
        return false;
    }
    
    public static function setNotificationToAuthorize(){
        Yii::$app->session->setFlash('promotion-action', base64_encode('please authorize to save points'), true);
    }
    
    public static function setNotificationMessage(string $message){
        self::$ingore = true;
        Yii::$app->session->setFlash('promotion-action', base64_encode($message), true);
    }
    
}
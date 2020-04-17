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
use frontend\design\boxes\promotions\Alert;

class PromotionsBonusObserver {

    private static $instance = [];

    private function __construct() {
        
    }

    private function __clone() {
        
    }

    public static function createInstance() {

        if (is_object($_SESSION['bpObserver']) && $_SESSION['bpObserver'] instanceof self) {
            $bpObserver = &$_SESSION['bpObserver'];
            if ($bpObserver->workingDate != date("d-m-Y")) {
                $bpObserver = new self();
                $bpObserver->clearAttributes();
            }
        } else {
            $bpObserver = new self();
            $bpObserver->clearAttributes();
            $_SESSION['bpObserver'] = $bpObserver;
        }
        //echo'<pre>';print_r($bpObserver);
        return $bpObserver;
    }

    public static function getInstance() {
        return self::createInstance();
    }

    private function clearAttributes() {
        $this->workingDate = date("d-m-Y");        
        $this->actions = [];
        return $this;
    }
    
    private function prepare($oAction, $history){
        return [
                'occasion' => (is_object($history) ? $history->bonus_points_occasion:0),
                'limit' => $oAction->bonus_points_limit,
                'saved' => (is_object($history) ? $history->bonus_points_occasion :0 ),
            ];
    }
    
    /*
    * increment ocassions of actions for events
    * set notifcation
    * return true if positive result
    */
    public function triggerAction($action, $cid = 0) {
        PromotionService::triggerPriceActions($action);
        if(defined('BONUS_ACTION_PROGRAM_STATUS') && BONUS_ACTION_PROGRAM_STATUS == 'true'){
            if (!$cid) {
                $cid = Yii::$app->user->getId();
            }
            $oAction = (new PromotionsBonusService)->getAction($action);
            if (is_object($oAction)) {
                if (!isset($this->actions[$action])) {
                    if ($cid){
                        $history = PromotionsBonusHistory::getTodayHistory($oAction, $cid);
                        $this->actions[$action] = $this->prepare($oAction, $history);                        
                    } else {
                        $this->actions[$action] = $this->prepare($oAction, 0);  
                    }
                }
                if ($this->actions[$action]['occasion'] < $this->actions[$action]['limit'] ) {
                    $this->actions[$action]['occasion']++;
                }

                if ($cid) {
                    //save points
                    $lastOcassion = $oAction->description->points_title;
                    $bonusAmount = $this->saveActions($cid, $lastOcassion);
                    if ($bonusAmount){
                        PromotionsBonusNotify::setNotification($bonusAmount, $lastOcassion);
                    } else {
                        return false;
                    }
                } else {
                    //notify customer to auth to save points
                    PromotionsBonusNotify::setNotificationToAuthorize();
                }
                return true;
            } else {
                 if ($cid) {                    
                    $this->saveActions($cid);                    
                }
            }
        }
        return false;
    }

    /*
    * save customer bonuses
    * return $totalBalanceAmount updated balance
    */
    private function saveActions($customer_id = 0, &$lastOcassion = '') {        
        $totalBalanceAmount = 0;
        if ($customer_id) {
            if (is_array($this->actions) && count($this->actions)) {
                $customer = new \common\components\Customer();
                
                foreach ($this->actions as $action_code => $info) {
                    if ($info['saved'] < $info['occasion']) {
                        $oAction = (new PromotionsBonusService)->getAction($action_code);
                        if (is_object($oAction)){
                            //update bonus history
                            $amountForOcassion = PromotionsBonusHistory::updateHistory($oAction, $customer_id, $info);
                            $totalBalanceAmount += $amountForOcassion;
                            if ($amountForOcassion){
                                $lastOcassion = $oAction->description->points_title;
                            }
                        }
                        $this->actions[$action_code]['saved'] = $info['occasion']; //update amount of saved ocassions
                    }
                }
                if ($totalBalanceAmount) {
                    $customer->updateBonusPoints($customer_id, $totalBalanceAmount); //update customer balance
                }
            }
        }
        return $totalBalanceAmount;
    }
    
    public function checkRequestPromoAction(){
        if (Yii::$app->request->isAjax){
            $action = null;
            if (isset($_REQUEST['promo-action'])) $action = $_REQUEST['promo-action'];
            if (!is_null($action) && !empty($action)){
                if($this->validPromoAction($action)){                    
                    if ($this->triggerAction($action)){
                        return Alert::widget(['isAjax' => true]);
                    }
                }
                exit();
            }
        }
    }
    
    public static function checkAlertPromoAction(){
        return Alert::widget(['isAjax' => false]);
    }

    public function validPromoAction($action_code){
        if(defined('BONUS_ACTION_PROGRAM_STATUS') && BONUS_ACTION_PROGRAM_STATUS == 'true'){
            $oAction = (new PromotionsBonusService)->getAction($action_code);
            if (is_object($oAction)){
                return true;
            }
        }
        return false;
    }
    
    public function renderValidPromoAction($action_code){        
        if ($this->validPromoAction($action_code)){
            return \frontend\design\boxes\promotions\Action::widget(['action' => $action_code]);
        }
    }

}

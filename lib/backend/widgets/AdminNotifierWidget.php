<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class AdminNotifierWidget extends Widget {
    
    public $notifier;
    
    public function init() {
        parent::init();
    }

    public function run() {
                
        $notifications = $this->notifier->getUnreadNotifications();
        $response = '';
        if ($notifications){
            $notificationsByType = ArrayHelper::index($notifications, null, 'type');
            foreach($notificationsByType as $type => $notifications){
                $items = [];
                foreach($notifications as $notification){
                    $items[] =  $notification->date_added . "  ". TEXT_MESSAGE . " " . $notification->message;
                }
                $response .= Html::ul($items, ['class' => "admin-notifications-list alert-{$type}"]);
            }
            return $response;
        }
        return;
    }

}
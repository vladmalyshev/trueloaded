<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;

use Yii;
use common\models\Subscribers;
use common\helpers\Mail;
/**
 * Site controller
 */
class SubscribersController extends Sceleton
{
    
    public function actionIndex()
    {
        return $this->render('index.tpl', []);
    }
    
    public function actionSubscribe()
    {
        $subscribersMd5hash = Yii::$app->request->get('subscriber');
        $subscriber = Subscribers::findOne(['subscribers_md5hash' => $subscribersMd5hash]);
        if (!$subscriber) {
            tep_redirect(Yii::$app->urlManager->createAbsoluteUrl(['subscribers/subscribed']));
        }
        $subscriber->subscribers_status = 1;
        $subscriber->save();


        $unsubscribe_link = Yii::$app->urlManager->createAbsoluteUrl(['subscribers/unsubscribe','subscriber'=> $subscribersMd5hash]);

        $emailParams = [];
        $emailParams['STORE_NAME'] = STORE_NAME;
        $emailParams['FIRST_NAME'] = $subscriber->subscribers_firstname;
        $emailParams['LAST_NAME'] = $subscriber->subscribers_lastname;
        $emailParams['EMAIL_ADDRESS'] = $subscriber->subscribers_email_address;
        $emailParams['UNSUBSCRIBE_LINK'] = $unsubscribe_link;
        list($emailSubject, $emailText) = Mail::get_parsed_email_template('Successful Subscription', $emailParams);

        Mail::send(
            $subscriber->subscribers_firstname . ' ' . $subscriber->subscribers_lastname,
            $subscriber->subscribers_email_address,
            $emailSubject,
            $emailText,
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS
        );

        tep_redirect(Yii::$app->urlManager->createAbsoluteUrl(['subscribers/subscribed']));
    }
    
    public function actionUnsubscribe()
    {
        $subscriber = Subscribers::findOne(['subscribers_md5hash' => Yii::$app->request->get('subscriber')]);

        $emailParams = [];
        $emailParams['STORE_NAME'] = STORE_NAME;
        $emailParams['FIRST_NAME'] = $subscriber->subscribers_firstname;
        $emailParams['LAST_NAME'] = $subscriber->subscribers_lastname;
        $emailParams['EMAIL_ADDRESS'] = $subscriber->subscribers_email_address;
        list($emailSubject, $emailText) = Mail::get_parsed_email_template('Successful Unubscription', $emailParams);

        $subscriber->delete();

        Mail::send(
            $emailParams['FIRST_NAME'] . " " . $emailParams['LAST_NAME'],
            $emailParams['EMAIL_ADDRESS'],
            $emailSubject,
            $emailText,
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS
        );

        $link_redirect = Yii::$app->urlManager->createAbsoluteUrl(['subscribers/unsubscribed']);
        tep_redirect($link_redirect);
    }
    
    public function actionSendConfirmationSubscribe()
    {
        \common\helpers\Translation::init('subscribers');
        $emailAddress = Yii::$app->request->post('subscribers_email_address');
        $firstname = Yii::$app->request->post('subscribers_firstname');
        $lastname = Yii::$app->request->post('subscribers_lastname');
        if (!$emailAddress) {
            return EMAIL_REQUIRED;
        }
        if (!$firstname) {
            return NAME_REQUIRED;
        }

        $platformId = \common\classes\platform::currentId();
        $subscribersMd5hash = md5($emailAddress.$platformId);
        
        $subscribeLink = Yii::$app->urlManager->createAbsoluteUrl(['subscribers/subscribe','subscriber'=> $subscribersMd5hash]);

        $subscriber = Subscribers::findOne([
            'subscribers_email_address' => $emailAddress,
            'platform_id' => (int)$platformId
        ]);
        if (!$subscriber) {
            $subscriber = new Subscribers();
            $subscriber->attributes = [
                'platform_id' => (int)$platformId,
                'subscribers_md5hash' => $subscribersMd5hash,
                'subscribers_email_address' => $emailAddress,
                'subscribers_firstname' => $firstname,
                'subscribers_lastname' => $lastname,
                'subscribers_datetime' => 'now()',
            ];
            $subscriber->save();
        }

        $emailParams = [];
        $emailParams['STORE_NAME'] = STORE_NAME;
        $emailParams['SUBSCRIBE_LINK'] = $subscribeLink;
        list($emailSubject, $emailText) = Mail::get_parsed_email_template('New Subscribe Confirmation', $emailParams);

        Mail::send(
            $firstname." ".$lastname,
            $emailAddress,
            $emailSubject,
            $emailText,
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS
        );

        return TEXT_CHECK_EMAIL;
    }
    
    public function actionSendConfirmationUnsubscribe()
    {
        \common\helpers\Translation::init('subscribers');
        $email = tep_db_prepare_input(Yii::$app->request->post('subscribers_email_address'));

        $subscriber = Subscribers::findOne(['subscribers_email_address' => $email]);
        if(!$subscriber) {
            return TEXT_EMAIL_NOT_FOUND;
        }

        $platformId = \common\classes\platform::currentId();
        $subscribersMd5hash = md5($email . $platformId);
                
        $unsubscribeLink = Yii::$app->urlManager->createAbsoluteUrl(['subscribers/unsubscribe','subscriber'=> $subscribersMd5hash]);

        $emailParams = [];
        $emailParams['STORE_NAME'] = STORE_NAME;
        $emailParams['UNSUBSCRIBE_LINK'] = $unsubscribeLink;
        list($emailSubject, $emailText) = Mail::get_parsed_email_template('Unsubscribe Confirmation', $emailParams);

        Mail::send(
            $subscriber->subscribers_firstname . ' ' . $subscriber->subscribers_lastname,
            $email,
            $emailSubject,
            $emailText,
            STORE_OWNER,
            STORE_OWNER_EMAIL_ADDRESS
        );

        return TEXT_CHECK_EMAIL_UNSUBSCRIBE;
    }

    public function actionSubscribed ()
    {
        return $this->render('subscribed.tpl');
    }

    public function actionUnsubscribed ()
    {
        return $this->render('unsubscribed.tpl');
    }
}
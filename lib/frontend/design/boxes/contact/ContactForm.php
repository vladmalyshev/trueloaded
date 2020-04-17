<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\contact;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\forms\contact\Contact;

class ContactForm extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {        
        \common\helpers\Translation::init('js');
                
        $contact = new Contact(['captcha' => $this->settings[0]['show_captcha'] ]);
        $customer = null;
        if (!Yii::$app->user->isGuest) {
            $customer = Yii::$app->user->getIdentity();
            $contact->preloadCustomersData($customer);
        }

        $info = [];
        if (Yii::$app->request->isPost && $_GET['action'] == 'send'){
            if ( $contact->load(Yii::$app->request->post()) && $contact->validate() ){
                $contact->sendMessage();                
                tep_redirect(tep_href_link(FILENAME_CONTACT_US, 'action=success'));
            } else {
                foreach($contact->getErrors() as $error){
                    $info[] = $error[0] ?? '';
                }
            }
        }

        return IncludeTpl::widget(['file' => 'boxes/contact/contact-form.tpl', 'params' => [
            'info' => $info,
            'action' => $_GET['action'],
            'settings' => $this->settings,
            'id' => $this->id,            
            'contact' => $contact,            
        ]]);
    }
}
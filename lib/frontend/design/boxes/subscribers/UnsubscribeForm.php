<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\subscribers;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;

class UnsubscribeForm extends Widget
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
        return IncludeTpl::widget(['file' => 'boxes/subscribers/unsubscribe-form.tpl', 'params' => [
            'settings' => $this->settings,
            'link' => Yii::$app->urlManager->createUrl('subscribers/send-confirmation-unsubscribe'),
            'subscribers_firstname' => Yii::$app->request->get('subscribers_firstname', ''),
            'subscribers_lastname' => Yii::$app->request->get('subscribers_lastname', ''),
            'subscribers_email_address' => Yii::$app->request->get('subscribers_email_address', '')
        ]]);
    }
}
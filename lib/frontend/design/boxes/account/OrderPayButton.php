<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\SplitPageResults;
use common\helpers\Date as DateHelper;

class OrderPayButton extends Widget
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
        $order_id = (int)Yii::$app->request->get('order_id');

        $pay_link = false;
        if ($ext = \common\helpers\Acl::checkExtension('UpdateAndPay', 'payLink')) {
            $pay_link = $ext::payLink($order_id);
        }
        if (!$pay_link) {
            return '';
        }

        $text = $this->settings[0]['text'];
        if (defined($text)) {
            $text = constant($text);
        }
        if (!$text) {
            $text = $this->settings[0]['link'];
            if (!$this->settings[0]['link']) {
                $text = PAY;
            }
        }
        $page = \common\classes\design::pageName($this->settings[0]['link']);
        if ($page) {
            $url = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page, 'order_id' => $order_id]);
        }  else {
            $url = Yii::$app->urlManager->createUrl(['account', 'order_id' => $order_id]);
        }

        $active = false;
        if (Yii::$app->request->get('page_name') == $page) {
            $active = true;
        }

        return IncludeTpl::widget(['file' => 'boxes/account/order-pay-button.tpl', 'params' => [
            'settings' => $this->settings,
            'text' => $text,
            'url' => $pay_link,
            'active' => $active,
        ]]);
    }
}
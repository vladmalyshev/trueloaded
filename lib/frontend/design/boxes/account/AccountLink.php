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

use frontend\design\Info;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class AccountLink extends Widget
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
        $languages_id = \Yii::$app->settings->get('languages_id');
        if (!Info::isAdmin()) {
            if ($this->settings[0]['hide_link'] == 'credit_amount_history' && $this->params['mainData']['count_credit_amount'] == 0) {
                return '';
            }
            if ($this->settings[0]['hide_link'] == 'points_earnt_history' && !$this->params['mainData']['has_customer_points_history']) {
                return '';
            }
            if ($this->settings[0]['hide_link'] == 'wishlist' && !(is_array($this->params['wishlist']) && count($this->params['wishlist']) > 0)) {
                return '';
            }
            if ($this->settings[0]['hide_link'] == 'orders' && $this->params['mainData']['total_orders' == 0]) {
                return '';
            }
            if ($this->settings[0]['hide_link'] == 'quotations') {
                $quotations = [];
                if ($ext = \common\helpers\Acl::checkExtension('Quotations', 'getQuotationList')) {
                    $quotations = $ext::getQuotationList(Yii::$app->user->getId(), $languages_id);
                }
                if (count($quotations) == 0) {
                    return '';
                }
            }
            if ($this->settings[0]['hide_link'] == 'samples') {
                $samples = [];
                if ($ext = \common\helpers\Acl::checkExtension('Samples', 'getSamplesList')) {
                    $samples = $ext::getSamplesList(Yii::$app->user->getId(), $languages_id);
                }
                if (count($samples) == 0) {
                    return '';
                }
            }
            if ($this->settings[0]['hide_link'] == 'review') {
                $reviews = tep_db_fetch_array(tep_db_query("select count(*) as total from " . TABLE_REVIEWS . " where customers_id = '" . (int)Yii::$app->user->getId() . "'"));
                if ($reviews['total'] == 0) {
                    return '';
                }
            }
            if ($this->settings[0]['link'] == 'trade_form' && ENABLE_TRADE_FORM != 'True') {
                return '';
            }
        }

        if (defined($this->settings[0]['text'])) {
            $text = constant($this->settings[0]['text']);
        }
        if (!$text && $this->settings[0]['link'] == 'logoff') {
            $text = TEXT_LOGOFF;
        }  elseif (!$text && $this->settings[0]['link'] == 'bonus_program') {
            $text = TEXT_BONUS_PROGRAM_LINK;
        } elseif (!$text) {
            $text = $this->settings[0]['link'];
            if (!$this->settings[0]['link']) {
                $text = TEXT_DASHBOARD;
            }
        }

        $page = \common\classes\design::pageName($this->settings[0]['link']);
        
        $is_multi = \Yii::$app->get('storage')->get('is_multi');
        /** @var \common\extensions\CustomersMultiEmails\CustomersMultiEmails $CustomersMultiEmails */
            if ($is_multi && $CustomersMultiEmails = \common\helpers\Acl::checkExtension('CustomersMultiEmails', 'checkLink')) {
              if (!$CustomersMultiEmails::checkLink($this->settings[0]['link'])) {
                return '';
              }
            }
            /*
        if ($this->settings[0]['link'] == 'delete' || $page == 'address_book' || $page == 'account_edit' || $page == 'my_password') {
            $manager = new \common\services\OrderManager(Yii::$app->get('storage'));
            if ($manager->get('is_multi') == 1) {
                return '';
            }
        }*/

        /** @var \common\extensions\PersonalCatalog\PersonalCatalog $personalCatalog */
        if ($this->settings[0]['hide_link'] == 'personal_catalog') {
          if ($personalCatalog = \common\helpers\Acl::checkExtension('PersonalCatalog', 'allowed')) {
            if (!$personalCatalog::allowed()) {
              return '';
            }
          }
        }

// 2check outdated code below??
        if ($this->settings[0]['link'] == 'logoff') {
            $url = Yii::$app->urlManager->createUrl(['account/logoff']);
        } elseif ($this->settings[0]['link'] == 'personal-catalog') {
            $url = Yii::$app->urlManager->createUrl([FILENAME_PERSONAL_CATALOG]);
        } elseif ($this->settings[0]['link'] == 'bonus_program') {
            $url = Yii::$app->urlManager->createUrl(['promotions/actions']);
        } elseif ($this->settings[0]['link'] == 'download_orders') {
            $url = Yii::$app->urlManager->createUrl(['account/download-my-orders']);
        } elseif ($this->settings[0]['link'] == 'delete') {
            $url = Yii::$app->urlManager->createUrl(['account/delete']);
        } elseif ($this->settings[0]['link'] == 'trade_form') {
            $url = Yii::$app->urlManager->createUrl(['account/trade-form']);
        } elseif ($this->settings[0]['link']) {
            if ($this->settings[0]['order_id']) {
                $orderId = (int)Yii::$app->request->get('order_id');
                $url = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page, 'order_id' => $orderId]);
            } else {
                $url = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page]);
            }
        } else {
            $url = Yii::$app->urlManager->createUrl(['account']);
        }

        $active = false;
        $activeArr = explode(',', $this->settings[0]['active_link']);
        foreach ($activeArr as $activePge) {
            $activePge = \common\classes\design::pageName($activePge);
            if ($activePge && Yii::$app->request->get('page_name') == $activePge) {
                $active = true;
            }
        }

        if (Yii::$app->request->get('page_name') == $page) {
            $active = true;
        }

        return IncludeTpl::widget(['file' => 'boxes/account/account-link.tpl', 'params' => [
            'settings' => $this->settings,
            'text' => $text,
            'url' => $url,
            'active' => $active,
            'id' => $this->id,
        ]]);
    }
}

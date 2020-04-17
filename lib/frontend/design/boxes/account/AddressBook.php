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

class AddressBook extends Widget
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
        \common\helpers\Translation::init('account/address-book-process');

        if (defined($this->settings[0]['text'])) {
            $text = constant($this->settings[0]['text']);
        }
        if (!$text) {
            $text = $this->settings[0]['link'];
            if (!$this->settings[0]['link']) {
                $text = SMALL_IMAGE_BUTTON_EDIT;
            }
        }
        $page = \common\classes\design::pageName($this->settings[0]['link']);

        $customer = Yii::$app->user->getIdentity();
        
        $aBooks = $customer->getAddressBooks(true);
        $aBooks = \common\helpers\Address::skipEntryKey($aBooks);
        $address_array = array();
        foreach($aBooks as $addresses){
            $format_id = \common\helpers\Address::get_address_format_id($addresses['country_id']);
            $addresses['text'] = $addresses['city'] . ' ' . $addresses['postcode'] . ' ' . \common\helpers\Country::get_country_name($addresses['country_id']);
            $addresses['format'] = \common\helpers\Address::address_format($format_id, $addresses, true, '', ' ');

            if ($page) {
                $addresses['link_edit'] = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page, 'edit' => $addresses['address_book_id']]);
            } else {
                $addresses['link_edit'] = Yii::$app->urlManager->createUrl(['account', 'edit' => $addresses['address_book_id']]);
            }

            $addresses['link_delete'] = Yii::$app->urlManager->createUrl([
                'account/address-book-process',
                'delete' => $addresses['address_book_id'],
                'action' => 'deleteconfirm'
            ]);
            $addresses['default_address'] = $customer->customers_default_address_id;
            $addresses['customers'] = \common\helpers\Output::output_string_protected($addresses['firstname'] . ' ' . $addresses['lastname']);
            $address_array[] = $addresses;
        }


        $page_add = \common\classes\design::pageName($this->settings[0]['link_add']);
        $link_add = Yii::$app->urlManager->createUrl(['account', 'page_name' => $page_add]);

        if (defined($this->settings[0]['text_add'])) {
            $text_add = constant($this->settings[0]['text_add']);
        }
        if (!$text_add) {
            $text_add = $this->settings[0]['link_add'];
        }

        return IncludeTpl::widget(['file' => 'boxes/account/address-book.tpl', 'params' => [
            'address_array' => $address_array,
            'customer_id' => $customer->customers_id,
            'settings' => $this->settings,
            'id' => $this->id,
            'link_switch' => Yii::$app->urlManager->createUrl('account/switch-primary'),
            'text' => $text,
            'text_add' => $text_add,
            'link_add' => $link_add,
            'show_add' => count($aBooks) < MAX_ADDRESS_BOOK_ENTRIES,
        ]]);
    }
}
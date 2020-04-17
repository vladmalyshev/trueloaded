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
use common\forms\AddressForm;

class EditAddress extends Widget
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
        global $breadcrumb, $navigation;

        \common\helpers\Translation::init('account/address-book-process');

        if (Yii::$app->user->isGuest) {
            $navigation->set_snapshot();
            tep_redirect(tep_href_link('account/login', '', 'SSL'));
        }
        $messageStack = \Yii::$container->get('message_stack');

// error checking when updating or adding an entry
        $process = false;
        
        $customer = Yii::$app->user->getIdentity();
        $bookModel = new AddressForm(['scenario' => AddressForm::BILLING_ADDRESS]);

        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $entry = $customer->getAddressBook((int) $_GET['edit']);
            
            if (!$entry) {
                $messageStack->add_session(ERROR_NONEXISTING_ADDRESS_BOOK_ENTRY, 'addressbook');

                tep_redirect(tep_href_link('account/address-book', '', 'SSL'));
            }
            $bookModel->preload($entry);
        } else {
            $bookModel->preloadDefault();
        }

        $bookModelShipping = clone $bookModel;
        $bookModelShipping->addressType = AddressForm::SHIPPING_ADDRESS;
        $bookModelShipping->definePrefix();
        $phoneFieldRequiredShipping = constant($bookModelShipping->get('TELEPHONE'));
        $phoneFieldRequiredBilling = constant($bookModel->get('TELEPHONE'));
        $phoneFieldRequired = ($phoneFieldRequiredShipping === $phoneFieldRequiredBilling) ? $bookModelShipping->get('TELEPHONE') : '';

        $get_edit = $_GET['edit'];
        $action = tep_href_link('account/address-book-process', (isset($_GET['edit']) ? 'edit=' . $_GET['edit'] : ''), 'SSL');
        $title = (isset($_GET['edit']) ? HEADING_TITLE_MODIFY_ENTRY : (isset($_GET['delete']) ? HEADING_TITLE_DELETE_ENTRY : HEADING_TITLE_ADD_ENTRY));
        $message = '';
        if ($messageStack->size('addressbook') > 0) {
            $message = $messageStack->output('addressbook');
        }
        $address_label = '';
        if (isset($_GET['delete'])) {
            $address_label = \common\helpers\Address::address_label(Yii::$app->user->getId(), $_GET['delete'], true, ' ', '<br>');
        }
        $link_address_book = tep_href_link('account/address-book', '', 'SSL');
        $link_address_delete = tep_href_link('account/address-book-process', 'delete=' . $_GET['delete'] . '&action=deleteconfirm', 'SSL');
                
        $links = array();
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $links['back_url'] = tep_href_link('account/address-book', '', 'SSL');
            $links['back_text'] = IMAGE_BUTTON_BACK;
            $links['update'] = tep_draw_hidden_field('action', 'update') . tep_draw_hidden_field('edit', $_GET['edit']) . '<button class="btn-2">' . IMAGE_BUTTON_UPDATE . '</button>';
        } else {
            if (sizeof($navigation->snapshot) > 0) {
                $back_link = tep_href_link($navigation->snapshot['page'], \common\helpers\Output::array_to_string($navigation->snapshot['get'], array(tep_session_name())), $navigation->snapshot['mode']);
            } else {
                $back_link = tep_href_link('account/address-book', '', 'SSL');
            }
            $links['back_url'] = $back_link;
            $links['back_text'] = IMAGE_BUTTON_BACK;
            $links['update'] = tep_draw_hidden_field('action', 'process') . '<button class="btn-2">' . IMAGE_BUTTON_CONTINUE . '</button>';
        }

        $breadcrumb->add(TEXT_MY_ACCOUNT, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
        $breadcrumb->add(TEXT_ADDRESS_BOOK, tep_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));

        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $breadcrumb->add(NAVBAR_TITLE_MODIFY_ENTRY, tep_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'edit=' . $_GET['edit'], 'SSL'));
        } elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $breadcrumb->add(NAVBAR_TITLE_DELETE_ENTRY, tep_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'delete=' . $_GET['delete'], 'SSL'));
        } else {
            $breadcrumb->add(NAVBAR_TITLE_ADD_ENTRY, tep_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'));
        }
        
        $postcoder = new \common\modules\postcode\PostcodeTool();
        
        return IncludeTpl::widget(['file' => 'boxes/account/edit-address.tpl', 'params' => [
            'settings' => $this->settings,
            'id' => $this->id,
            'action' => $action,
            'title' => $title,
            'link_address_book' => $link_address_book,
            'link_address_delete' => $link_address_delete,
            'model' => $bookModel,
            'modelShipping' => $bookModelShipping,
            'phoneFieldRequired' => $phoneFieldRequired,
            'set_primary' => $bookModel->address_book_id != $customer->customers_default_address_id,
            'links' => $links,
            'message' => $message,
            'postcoder' => $postcoder,
        ]]);
    }
}
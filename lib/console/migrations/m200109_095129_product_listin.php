<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use common\classes\Migration;

/**
 * Class m200109_095129_product_listin
 */
class m200109_095129_product_listin extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('main', [
            'TEXT_LESS' => 'less',
            'TEXT_CHOOSE_OPTIONS' => 'Choose Options',
        ]);
        $this->addTranslation('admin/design', [
            'B2B_ADD_BUTTON' => 'B2bAddButton',
            'TEXT_LISTING_ITEM' => 'Listing Item',
            'TEXT_IMAGE' => 'Image',
            'TEXT_RATING' => 'Rating',
            'TEXT_RATING_COUNTS' => 'Rating Counts',
            'TEXT_VIEW_BUTTON' => 'View Button',
            'TEXT_PAYPAL_BUTTON' => 'PayPal Button',
            'TEXT_SHOW_PREVIEW' => 'Show Preview',
            'TEXT_HIDE_PREVIEW' => 'Hide Preview',
            'TEXT_CATALOG' => 'Catalog',
            'TEXT_PRODUCT_LISTING_ITEMS' => 'Product Listing Items',
            'TEXT_INFORMATIONS' => 'Informations',
            'TEXT_STEPS_CHECKOUT' => 'Steps Checkout',
            'TEXT_SAMPLE_CHECKOUT' => 'Sample Checkout',
            'TEXT_EMAIL_GIFT_CARD' => 'Email/Gift Card',
            'TEXT_COMPONENTS' => 'Components',
            'TEXT_ANY' => 'Any',
            'TEXT_DEVELOPMENT_MODE' => 'Development mode',
            'TEXT_OLD_LISTING' => 'Old listing',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('main',[
            'TEXT_LESS',
            'TEXT_CHOOSE_OPTIONS',
        ]);
        $this->removeTranslation('admin/design',[
            'B2B_ADD_BUTTON',
            'TEXT_LISTING_ITEM',
            'TEXT_IMAGE',
            'TEXT_RATING',
            'TEXT_RATING_COUNTS',
            'TEXT_VIEW_BUTTON',
            'TEXT_PAYPAL_BUTTON',
            'TEXT_SHOW_PREVIEW',
            'TEXT_HIDE_PREVIEW',
            'TEXT_CATALOG',
            'TEXT_PRODUCT_LISTING_ITEMS',
            'TEXT_INFORMATIONS',
            'TEXT_STEPS_CHECKOUT',
            'TEXT_SAMPLE_CHECKOUT',
            'TEXT_EMAIL_GIFT_CARD',
            'TEXT_COMPONENTS',
            'TEXT_ANY',
            'TEXT_DEVELOPMENT_MODE',
            'TEXT_OLD_LISTING',
        ]);
    }
}

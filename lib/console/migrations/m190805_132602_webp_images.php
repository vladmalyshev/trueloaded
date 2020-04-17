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
 * Class m190805_132602_webp_images
 */
class m190805_132602_webp_images extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design', [
            'TITLE_CREATE_WEBP_IMAGES' => 'Create webp images',
            'SERVER_DOEST_SUPPORT_WEBP_FORMAT' => 'Server does\'t support WebP format',
            'TEXT_PRODUCT_IMAGES' => 'Product images',
            'TEXT_CATEGORY_IMAGES' => 'Category images',
            'TEXT_BANNERS_IMAGES' => 'Banners images',
            'TEXT_ALL_IMAGES' => 'All images',
            'TEXT_PROCESSED_PRODUCTS' => 'Processed products',
            'TEXT_PROCESSED_PRODUCTS_IMAGES' => 'Processed products images',
            'TEXT_CREATED_NEW_PRODUCT_IMAGES' => 'Created new product images',
            'TEXT_PROCESSED_CATEGORIES' => 'Processed categories',
            'TEXT_PROCESSED_CATEGORIES_IMAGES' => 'Processed categories images',
            'TEXT_CREATED_NEW_CATEGORY_IMAGES' => 'Created new category images',
            'TEXT_PROCESSED_BANNERS' => 'Processed banners',
            'TEXT_PROCESSED_BANNERS_IMAGES' => 'Processed banners images',
            'TEXT_CREATED_NEW_BANNER_IMAGES' => 'Created new banner images',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/design', [
            'TITLE_CREATE_WEBP_IMAGES',
            'SERVER_DOEST_SUPPORT_WEBP_FORMAT',
            'TEXT_PRODUCT_IMAGES',
            'TEXT_CATEGORY_IMAGES',
            'TEXT_BANNERS_IMAGES',
            'TEXT_ALL_IMAGES',
            'TEXT_PROCESSED_PRODUCTS',
            'TEXT_PROCESSED_PRODUCTS_IMAGES',
            'TEXT_CREATED_NEW_PRODUCT_IMAGES',
            'TEXT_PROCESSED_CATEGORIES',
            'TEXT_PROCESSED_CATEGORIES_IMAGES',
            'TEXT_CREATED_NEW_CATEGORY_IMAGES',
            'TEXT_PROCESSED_BANNERS',
            'TEXT_PROCESSED_BANNERS_IMAGES',
            'TEXT_CREATED_NEW_BANNER_IMAGES',
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190805_132602_webp_images cannot be reverted.\n";

        return false;
    }
    */
}

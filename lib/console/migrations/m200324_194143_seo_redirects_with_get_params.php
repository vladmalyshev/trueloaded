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
 * Class m200324_194143_seo_redirects_with_get_params
 */
class m200324_194143_seo_redirects_with_get_params extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->db->createCommand(
            "INSERT INTO seo_redirects_named (seo_redirects_named_id, redirects_type, owner_id, old_seo_page_name, language_id, platform_id) " .
            "SELECT NULL, 'product', products_id, products_old_seo_page_name, 1, 1 FROM products WHERE LENGTH(products_old_seo_page_name) > 0"
        )->execute();
        $this->getDb()->createCommand(
            "UPDATE products SET products_old_seo_page_name = '' WHERE LENGTH(products_old_seo_page_name) > 0"
        )->execute();

        $this->db->createCommand(
            "INSERT INTO seo_redirects_named (seo_redirects_named_id, redirects_type, owner_id, old_seo_page_name, language_id, platform_id) " .
            "SELECT NULL, 'category', categories_id, categories_old_seo_page_name, 1, 0 FROM categories WHERE LENGTH(categories_old_seo_page_name) > 0"
        )->execute();
        $this->getDb()->createCommand(
            "UPDATE categories SET categories_old_seo_page_name = '' WHERE LENGTH(categories_old_seo_page_name) > 0"
        )->execute();

        $this->db->createCommand(
            "INSERT INTO seo_redirects_named (seo_redirects_named_id, redirects_type, owner_id, old_seo_page_name, language_id, platform_id) " .
            "SELECT NULL, 'brand', manufacturers_id, manufacturers_old_seo_page_name, 1, 0 FROM manufacturers WHERE LENGTH(manufacturers_old_seo_page_name) > 0"
        )->execute();
        $this->getDb()->createCommand(
            "UPDATE manufacturers SET manufacturers_old_seo_page_name = '' WHERE LENGTH(manufacturers_old_seo_page_name) > 0"
        )->execute();

        $this->db->createCommand(
            "INSERT INTO seo_redirects_named (seo_redirects_named_id, redirects_type, owner_id, old_seo_page_name, language_id, platform_id) " .
            "SELECT NULL, 'info', information_id, old_seo_page_name, languages_id, platform_id FROM information WHERE LENGTH(old_seo_page_name) > 0"
        )->execute();
        $this->getDb()->createCommand(
            "UPDATE information SET old_seo_page_name = '' WHERE LENGTH(old_seo_page_name) > 0"
        )->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200324_194143_seo_redirects_with_get_params cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200324_194143_seo_redirects_with_get_params cannot be reverted.\n";

        return false;
    }
    */
}

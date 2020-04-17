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
 * Class m190328_123503_seo_redirect_code
 */
class m190328_123503_seo_redirect_code extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('seo_redirect', 'redirect_code', $this->string(5)->notNull()->defaultValue('301'));
        $this->addColumn('seo_redirect', 'status', $this->integer(1)->notNull()->defaultValue(1));
        $this->addColumn('seo_redirect', 'last_checked', $this->dateTime()->notNull());
        $this->addColumn('seo_redirect', 'check_details', $this->string(2048)->notNull());

        $this->createIndex(
            'idx_status',
            'seo_redirect',
            ['status', 'platform_id', 'old_url'],
            false
        );
        $this->createIndex(
            'idx_last_checked',
            'seo_redirect',
            ['last_checked'],
            false
        );
        $this->createIndex(
            'idx_new_url',
            'seo_redirect',
            ['new_url'],
            false
        );

        $this->addTranslation('admin/main', [
          'TABLE_HEADING_CODE' => 'Code',
          'IMAGE_VALIDATE' => 'Validate',
          'TABLE_HEADING_VALIDATED' => 'Validated',
          'TEXT_MESSAGE_FINISHED' => 'Finished',
          'TEXT_VALIDATION_STARTED' => 'Validation has been started',
          'TEXT_BATCH_SIZE' => 'Number of records',
          
        ]);
        $this->addTranslation('admin/seo_redirects', [
          'TEXT_UPDATE_REDIRECTED' => 'Automatcally update multiple HTTP301 redirects (a.html => b.html, b.html => c.html replace with a.html => c.html)  ',
          'TEXT_COULD_BE_TOO_LONG' => ' (valitaion could take too much time)',
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('seo_redirect', 'redirect_code');
        $this->dropColumn('seo_redirect', 'status');
        $this->dropColumn('seo_redirect', 'last_checked');
        $this->dropColumn('seo_redirect', 'check_details');
        $this->dropIndex('idx_status','seo_redirect');
        $this->dropIndex('idx_new_url','seo_redirect');
        $this->removeTranslation('admin/seo_redirects', [
          'TEXT_UPDATE_REDIRECTED', 'TEXT_COULD_BE_TOO_LONG'
        ]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190328_123503_seo_redirect_code cannot be reverted.\n";

        return false;
    }
    */
}

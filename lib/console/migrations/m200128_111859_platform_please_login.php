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
 * Class m200128_111859_platform_please_login
 */
class m200128_111859_platform_please_login extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp() {
        try {
            $this->addTranslation('admin/platforms', [
                'ENTRY_PLATFORM_PLEASE_LOGIN' => 'Show price for logged only'
            ]);
            $this->addTranslation('main', [
                'TEXT_PLEASE_LOGIN' => 'Please <a href=\"%s\">log in</a> to see your price'
            ]);
            if ($this->isTableExists('platforms')) {
                if (!$this->isFieldExists('platform_please_login', 'platforms')) {
                    $this->addColumn('platforms', 'platform_please_login', $this->tinyInteger(1)->notNull()->defaultValue(0));
                }
            }
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200128_111859_platform_please_login cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200128_111859_platform_please_login cannot be reverted.\n";

        return false;
    }
    */
}

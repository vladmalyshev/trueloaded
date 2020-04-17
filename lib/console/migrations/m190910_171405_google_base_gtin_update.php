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
 * Class m190910_171405_google_base_gtin_update
 */
class m190910_171405_google_base_gtin_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Google Base gtin fields',
            'configuration_key' => 'GOOGLE_BASE_GTIN_CONFIG',
            'configuration_value' => 'upc,ean,-isbn',
            'configuration_description' => '',
            'configuration_group_id' => 0,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190910_171405_google_base_gtin_update cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190910_171405_google_base_gtin_update cannot be reverted.\n";

        return false;
    }
    */
}

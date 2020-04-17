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
 * Class m191126_102155_pca_choice
 */
class m191126_102155_pca_choice extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'PostCode Module',
            'configuration_key' => 'POSTCODE_PROVIDER',
            'configuration_value' => '',
            'configuration_group_id' => '100680',
            'date_added' => new \yii\db\Expression('NOW()'),
            'set_function' => 'tep_cfg_select_option(array(\'PostCodeAnywhere\', \'PostCoder\'),'
        ]);
        
        $this->update('configuration', ['configuration_title' => 'Service Key'], 'configuration_key="PCA_PREDICT_SERVICE_KEY"');
        $this->update('translation', ['translation_value' => 'Service Key'], 'translation_key="PCA_PREDICT_SERVICE_KEY_TITLE"');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('configuration', 'configuration_key="POSTCODE_PROVIDER"');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191126_102155_pca_choice cannot be reverted.\n";

        return false;
    }
    */
}

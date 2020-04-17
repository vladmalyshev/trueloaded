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
 * Class m191031_122054_attributes_templates
 */
class m191031_122054_attributes_templates extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('options_templates_attributes', 'default_option_value', $this->integer(1)->notNull()->defaultValue(0));
        $this->alterColumn('options_templates_attributes','options_values_price', $this->decimal(15,6)->notNull()->defaultValue('0.000000'));
        $this->alterColumn('options_templates_attributes','products_attributes_discount_price', $this->string(255)->notNull()->defaultValue(''));
        $this->alterColumn('options_templates_attributes','products_attributes_filename', $this->string(255)->notNull()->defaultValue(''));
        $this->alterColumn('options_templates_attributes_prices', 'attributes_group_price', $this->decimal(15,6)->notNull()->defaultValue(0));
        $this->alterColumn('options_templates_attributes_prices', 'attributes_group_discount_price', $this->string(255)->notNull()->defaultValue(''));
        $this->createIndex('options_templates_idx', 'options_templates_attributes', ['options_templates_id', 'options_id', 'options_values_id'] );
        $this->addTranslation('admin/categories', [
            'TEXT_CONFIRM_APPLY_OPTION_TEMPLATE' => 'Do you really want to apply the template. All attributes assigned to product will be deleted.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('options_templates_attributes', 'default_option_value');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191031_122054_attributes_templates cannot be reverted.\n";

        return false;
    }
    */
}

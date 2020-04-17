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
 * Class m190524_093427_order_label_update
 */
class m190524_093427_order_label_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('orders_label','admin_id', $this->integer(11)->notNull()->defaultValue(0));
        $this->addColumn('orders_label','date_created', $this->dateTime());
        $this->addColumn('orders_label','label_status', $this->integer(1)->notNull()->defaultValue(0));
        $this->addColumn('orders_label','label_module_error', $this->string(1024)->null());
        $this->update('orders_label',['label_status'=>1],['>',new \yii\db\Expression('LENGTH(parcel_label_pdf)'),1]);

        $this->addTranslation('admin/main',[
            'TEXT_PROCESS_SELECTED' => 'Process selected',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('orders_label','admin_id');
        $this->dropColumn('orders_label','date_created');
        $this->dropColumn('orders_label','label_status');
        $this->dropColumn('orders_label','label_module_error');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190524_093427_order_label_update cannot be reverted.\n";

        return false;
    }
    */
}

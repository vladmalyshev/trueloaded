<?php

use yii\db\Migration;

/**
 * Class m180817_150138_add_ean_asin_to_suuplier_product
 */
class m180817_150138_add_ean_asin_to_suuplier_product extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('suppliers_products', 'suppliers_asin', $this->string(10));
        $this->addColumn('suppliers_products', 'suppliers_isbn', $this->string(13));
        $this->addColumn('suppliers_products', 'suppliers_ean', $this->string(13));
        $this->alterColumn('suppliers_products', 'suppliers_upc', $this->string(14));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180817_150138_add_ean_asin_to_suuplier_product cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180817_150138_add_ean_asin_to_suuplier_product cannot be reverted.\n";

        return false;
    }
    */
}

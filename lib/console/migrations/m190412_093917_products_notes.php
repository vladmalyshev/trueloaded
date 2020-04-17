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
 * Class m190412_093917_products_notes
 */
class m190412_093917_products_notes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('products_notes', [
            'products_notes_id' => \yii\db\Schema::TYPE_PK,
            'products_id' => $this->integer(),
            'note' => $this->text(),
            'updated_at' => $this->integer(),
            'created_at' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        /*
        $this->addForeignKey(
            'FK_products_notes_products',
            'products_notes',
            'products_id',
            'products',
            'products_id',
            'CASCADE'
        );/**/
        $this->addTranslation('admin/categories', [
            'TAB_NOTES' => 'Notes',
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //$this->dropForeignKey('FK_products_notes_products', 'products_notes');
        $this->dropTable('products_notes');

        return true;
    }

}

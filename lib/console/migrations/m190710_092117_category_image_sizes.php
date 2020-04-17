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
 * Class m190710_092117_category_image_sizes
 */
class m190710_092117_category_image_sizes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('image_types',[
            'image_types_name' => 'Category gallery',
            'image_types_x' => 300,
            'image_types_y' => 300,
            'width_from' => 0,
            'width_to' => 0,
            'parent_id' => 0,
        ]);
        $this->insert('image_types',[
            'image_types_name' => 'Category hero',
            'image_types_x' => 1250,
            'image_types_y' => 500,
            'width_from' => 0,
            'width_to' => 0,
            'parent_id' => 0,
        ]);
        $this->insert('image_types',[
            'image_types_name' => 'Category homepage',
            'image_types_x' => 300,
            'image_types_y' => 300,
            'width_from' => 0,
            'width_to' => 0,
            'parent_id' => 0,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('image_types', ['image_types_name' => 'Category gallery']);
        $this->delete('image_types', ['image_types_name' => 'Category hero']);
        $this->delete('image_types', ['image_types_name' => 'Category homepage']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190710_092117_category_image_sizes cannot be reverted.\n";

        return false;
    }
    */
}

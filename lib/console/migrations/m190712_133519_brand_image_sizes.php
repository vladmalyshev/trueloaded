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
 * Class m190712_133519_brand_image_sizes
 */
class m190712_133519_brand_image_sizes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('image_types',[
            'image_types_name' => 'Brand gallery',
            'image_types_x' => 300,
            'image_types_y' => 300,
            'width_from' => 0,
            'width_to' => 0,
            'parent_id' => 0,
        ]);
        $this->insert('image_types',[
            'image_types_name' => 'Brand hero',
            'image_types_x' => 1250,
            'image_types_y' => 500,
            'width_from' => 0,
            'width_to' => 0,
            'parent_id' => 0,
        ]);

        $this->addColumn('manufacturers', 'manufacturers_image_2', $this->string(256)->notNull()->defaultValue(''));

        $this->alterColumn('manufacturers', 'manufacturers_image', $this->string(256));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('image_types', ['image_types_name' => 'Brand gallery']);
        $this->delete('image_types', ['image_types_name' => 'Brand hero']);
        $this->dropColumn('manufacturers', 'manufacturers_image_2');
        $this->alterColumn('manufacturers', 'manufacturers_image', $this->string(64));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190712_133519_brand_image_sizes cannot be reverted.\n";

        return false;
    }
    */
}

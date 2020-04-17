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
 * Class m190524_135457_product_image_resolutions
 */
class m190524_135457_product_image_resolutions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('image_types','width_from', $this->integer(11)->notNull()->defaultValue(0));
        $this->addColumn('image_types','width_to', $this->integer(11)->notNull()->defaultValue(0));
        $this->addColumn('image_types','parent_id', $this->integer(11)->notNull()->defaultValue(0));

        $this->addTranslation('admin/main',[
            'BOX_IMAGE_SETTINGS' => 'Image Settings',
            'TEXT_MAX_WIDTH' => 'Max Width',
            'TEXT_MAX_HEIGHT' => 'Max Height',
            'MAX_IMAGE_HEIGHT' => 'Max image height',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('image_types','width_from');
        $this->dropColumn('image_types','width_to');
        $this->dropColumn('image_types','parent_id');
    }
}

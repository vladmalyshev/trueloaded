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
 * Class m200302_130034_featured_types
 */
class m200302_130034_featured_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->isTableExists('featured_types')){
            $this->db->createCommand("
                CREATE TABLE featured_types(
                  featured_type_id INT(11) NOT NULL,
                  language_id INT(11) NOT NULL,
                  featured_type_name VARCHAR(64) NOT NULL DEFAULT '',
                  PRIMARY KEY(featured_type_id, language_id),
                  KEY(featured_type_name(8))
                ); 
            ")->execute();
        }
        if (!$this->isFieldExists('featured_type_id', 'featured')){
            $this->addColumn('featured', 'featured_type_id', $this->integer(11)->notNull()->defaultValue(0));
        }
        if (!$this->isFieldExists('sort_order', 'featured')){
            $this->addColumn('featured', 'sort_order', $this->integer(11)->notNull()->defaultValue(0));
        }
        $this->addTranslation('admin/main',[
            'TEXT_FEATURED_TYPES' => 'Featured types',
            'BOX_HEADING_FEATURED_TYPES' => 'Featured types',
        ]);
        $this->addTranslation('admin/design',[
            'TEXT_RANDOM' => 'Random',
        ]);
        $this->addTranslation('admin/featured-types',[
            'TEXT_INFO_HEADING_EDIT_FEATURED_TYPE' => 'Edit Featured types',
            'TEXT_INFO_HEADING_NEW_FEATURED_TYPE' => 'New Featured types',
            'TEXT_INFO_FEATURED_TYPE_NAME' => 'Featured type name',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/main',[
            'TEXT_FEATURED_TYPES',
            'BOX_HEADING_FEATURED_TYPES',
        ]);
        $this->removeTranslation('admin/design',[
            'TEXT_RANDOM',
        ]);
        $this->removeTranslation('admin/featured-types',[
            'TEXT_INFO_HEADING_EDIT_FEATURED_TYPE',
            'TEXT_INFO_HEADING_NEW_FEATURED_TYPE',
            'TEXT_INFO_FEATURED_TYPE_NAME',
        ]);
    }
}

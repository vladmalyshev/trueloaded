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
 * Class m200324_104029_limit_characters
 */
class m200324_104029_limit_characters extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->insert('configuration',[
            'configuration_title' => 'Meta description length',
            'configuration_key' => 'META_DESCRIPTION_TAG_LENGTH',
            'configuration_value' => '160',
            'configuration_description' => 'Meta description length',
            'configuration_group_id' => 'BOX_CONFIGURATION_SEO_OPTIONS',
            'sort_order' => 220,
            'date_added' => new \yii\db\Expression('NOW()'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->delete('configuration',['configuration_key' => 'META_DESCRIPTION_TAG_LENGTH']);
    }
}

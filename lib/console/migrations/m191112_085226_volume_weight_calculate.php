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
 * Class m191112_085226_volume_weight_calculate
 */
class m191112_085226_volume_weight_calculate extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $check = (new yii\db\Query())->from('configuration')->where(['configuration_key' => 'VOLUME_WEIGHT_COEFFICIENT'])->exists();
        if (!$check) {
            $this->insert('configuration', [
                'configuration_key' => 'VOLUME_WEIGHT_COEFFICIENT',
                'configuration_title' => 'Volume Weight Coefficient',
                'configuration_description' => 'Volume Weight Coefficient',
                'configuration_group_id' => '7',
                'configuration_value' => '4000',
                'sort_order' => '0',
                'date_added' => (new yii\db\Expression('now()'))
            ]);
        }
        $this->addColumn('products', 'volume_weight_cm', $this->decimal(8, 2)->notNull()->defaultValue(0));
        $this->addColumn('products', 'volume_weight_in', $this->decimal(8, 2)->notNull()->defaultValue(0));
        $this->addTranslation('admin/main',[
            'TEXT_ACTUAL_VOLUME_WEIGHT_KG' => 'Actual volume weight, kg',
            'TEXT_VOLUME_WEIGHT' => 'Volume Weight',
        ]);
        $this->addTranslation('main',[
            'TEXT_ACTUAL_VOLUME_WEIGHT_KG' => 'Actual volume weight, kg',
            'TEXT_VOLUME_WEIGHT' => 'Volume Weight',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        try {
            $this->delete('configuration',[
                'configuration_key' => 'VOLUME_WEIGHT_COEFFICIENT',
            ]);
        }catch (\Exception $e) {}
        $this->dropColumn('products', 'volume_weight_cm');
        $this->dropColumn('products', 'volume_weight_in');
        $this->removeTranslation('main', [
            'TEXT_ACTUAL_VOLUME_WEIGHT_KG',
            'TEXT_VOLUME_WEIGHT'
        ]);
        $this->removeTranslation('admin/main', [
            'TEXT_ACTUAL_VOLUME_WEIGHT_KG',
            'TEXT_VOLUME_WEIGHT'
        ]);
    }

}

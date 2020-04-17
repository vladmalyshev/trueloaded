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
 * Class m190910_103407_listing_sorting
 */
class m190910_103407_listing_sorting extends Migration
{
    private $sortingKeys = ['TEXT_BY_MODEL', 'TEXT_BY_NAME', 'TEXT_BY_MANUFACTURER', 'TEXT_BY_PRICE', 'TEXT_BY_QUANTITY', 'TEXT_BY_WEIGHT', 'TEXT_BY_POPULARITY', 'TEXT_BY_DATE'];
    private $sortingEntity = ['admin/design', 'main'];

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        foreach ($this->sortingEntity as $entity) {
            foreach ($this->sortingKeys as $key) {
                $translations = \common\models\Translation::find()->where([
                    'translation_key' => $key,
                    'translation_entity' => $entity,
                ])->asArray()->all();

                foreach ($translations as $translation) {
                    $translation['translation_key'] = $key . '_TO_LESS';
                    $this->insert('translation', $translation);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        foreach ($this->sortingEntity as $entity) {
            foreach ($this->sortingKeys as $key) {
                $this->removeTranslation($entity, [$key . '_TO_LESS']);
            }
        }
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190910_103407_listing_sorting cannot be reverted.\n";

        return false;
    }
    */
}

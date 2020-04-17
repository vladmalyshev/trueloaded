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
 * Class m190226_131022_promo_assignement
 */
class m190226_131022_promo_assignement extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('promotions_assignement', [
            'promo_assignement_id' => \yii\db\Schema::TYPE_PK,
            'promo_id' => $this->integer(),
            'promo_owner' => $this->integer(),
            'promo_owner_type' => $this->tinyInteger(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_promo_id', 'promotions_assignement', 'promo_id');
        $this->addTranslation('admin/promotions', [
            'TEXT_PERSONALIZATION' => 'Personalization',
            'TEXT_PERSONALIZE' => 'Personalize',
        ]);
        $this->addTranslation('admin/main', [
            'TEXT_SEARCH_CUSTOMER' => 'Email/Name/Phone',
            'TEXT_PERSONAL_PROMOTIONS' => 'Personalized Promotions'
        ]);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('idx_promo_id', 'promotions_assignement');
        $this->dropTable('promotions_assignement');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190226_131022_promo_assignement cannot be reverted.\n";

        return false;
    }
    */
}

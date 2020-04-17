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
 * Class m200219_200232_specials_start_end_date_not_null
 */
class m200219_200232_specials_transaltion extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
      $this->addTranslation('admin/main', [
        'TEXT_NOTHING_FOUND' => 'Nothing found',
        'TEXT_OVERLAPPED_DATE_RANGE' => 'Specified dates overlapped with following',
        'TEXT_SPECIALS_INTERSECT' => 'Incorrect dates',
        'TEXT_UPDATE_EXISTING' => 'Update existnig records',
      ]);

      $this->db->createCommand("update translation set translation_entity='admin/main' where translation_entity='admin/coupon_admin' and translation_key='DATE_CREATED' ")->execute();
      $this->db->createCommand("update translation set translation_value=left(translation_value, length(translation_value)-1) where translation_entity='admin/main' and translation_key='TEXT_PRODUCTS_PRICE_INFO' and translation_value like '%:' ")->execute();

      // useless \Yii::$app->getCache()->flush();


    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
//        echo "m200219_200232_specials_start_end_date_not_null cannot be reverted.\n";

//        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200219_200232_specials_start_end_date_not_null cannot be reverted.\n";

        return false;
    }
    */
}

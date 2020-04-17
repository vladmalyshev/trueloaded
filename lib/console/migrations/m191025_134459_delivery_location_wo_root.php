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
 * Class m191025_134459_delivery_location_wo_root
 */
class m191025_134459_delivery_location_wo_root extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $platforms = \common\models\SeoDeliveryLocation::find()
            ->where(['parent_id'=>0])
            ->select('platform_id')
            ->distinct()
            ->asArray()
            ->all($this->db);
        foreach ($platforms as $platform_id_arr){
            $platform_id = $platform_id_arr['platform_id'];
            $new_root = new \common\models\SeoDeliveryLocation();
            $new_root->loadDefaultValues();
            $new_root->platform_id = $platform_id;
            $new_root->date_added = new \yii\db\Expression('NOW()');
            if ($new_root->save(false)){
                foreach(\common\helpers\Language::get_languages(true) as $_lang_info) {
                    $new_text = new \common\models\SeoDeliveryLocationText([
                        'id' => $new_root->id,
                        'language_id' => $_lang_info['id'],
                        'location_name' => 'Delivery Location',
                        'seo_page_name' => 'delivery-location',
                    ]);
                    $new_text->loadDefaultValues();
                    $new_text->save(false);
                }
                $reRoot = \common\models\SeoDeliveryLocation::find()
                    ->where(['parent_id'=>0])
                    ->andWhere(['!=', 'id', $new_root->id])
                    ->andWhere(['platform_id'=>$platform_id])
                    ->all();
                foreach ($reRoot as $reRootModel){
                    $reRootModel->parent_id = $new_root->id;
                    $reRootModel->save();
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191025_134459_delivery_location_wo_root cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191025_134459_delivery_location_wo_root cannot be reverted.\n";

        return false;
    }
    */
}

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
 * Class m191024_154358_google_settings
 */
class m191024_154358_google_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main', [
            'BOX_GOOGLE_SETTINGS' => 'Google Settings',
            'BOX_GOOGLE_MAIN_SETTINGS' => 'Main Settings',
            'HEADING_TITLE' => 'Google Settings',
        ]);
        
        $this->appendAcl(['TEXT_SETTINGS', 'BOX_GOOGLE_SETTINGS']);
        $this->appendAcl(['TEXT_SETTINGS', 'BOX_GOOGLE_SETTINGS', 'BOX_GOOGLE_MAIN_SETTINGS']);
        $this->addAdminMenuAfter([
            'path' => 'google-settings',
            'title' => 'BOX_GOOGLE_SETTINGS',
            'box_type' => 1
        ], 'BOX_HEADING_SOCIALS');
        $this->appendAcl(['TEXT_SETTINGS', 'BOX_GOOGLE_SETTINGS', 'BOX_GOOGLE_MAIN_SETTINGS']);
        $parent = $this->db->createCommand("select box_id from admin_boxes where title = 'BOX_GOOGLE_SETTINGS'")->queryOne();
        if ($parent){
            $this->insert('admin_boxes', [
                'parent_id' => $parent['box_id'],
                'sort_order' => 1,
                'path' => 'google-settings',
                'title' => 'BOX_GOOGLE_MAIN_SETTINGS'
            ]);
            $this->update('admin_boxes', [
                'parent_id' => $parent['box_id']
            ], ['title' => 'BOX_HEADING_PRINTERS']);
        }
        
        $this->addTranslation('admin/google-settings', [
            'TEXT_SERVICE_ACCOUNT_CREDETIALS' => 'Service account credentials',
            'TEXT_ANALYTICS_VIEW_ID' => 'Analytics View ID',
        ]);
        $this->addTranslation('admin/google_keywords', [
            'TEXT_ANALYTICS_SETTINGS' => 'Change analytics settings',
        ]);
        
        
        /* transfer */
        //recaptcha start
        if (!$this->db->createCommand("select * from google_settings where module = 'recaptcha'")->queryOne()){
            $public = $private = '';
            foreach($this->db->createCommand("select configuration_key, configuration_value from social_addons where block_name = 'recaptcha'")->queryAll() as $value){
                if ($value['configuration_key'] == 'RECAPTCHA_PUBLIC_KEY' && !empty($value['configuration_value']) && !preg_match("/[\s]/", $value['configuration_value'])){
                    $public = (string)$value['configuration_value'];
                }
                if ($value['configuration_key'] == 'RECAPTCHA_SECRET_KEY' && !empty($value['configuration_value']) && !preg_match("/[\s]/", $value['configuration_value'])){
                    $private = (string)$value['configuration_value'];
                }
            }
            (new \common\components\GoogleTools)->getCaptchaProvider()->createSetting(['publicKey' => $public, 'privateKey'=> $private, 'version' => 'v2']);
        }
        $this->db->createCommand("delete from social_addons where block_name = 'recaptcha'")->execute();
        //recaptcha end
        
        //analytics start
        if (!$this->db->createCommand("select * from google_settings where module = 'report'")->queryOne()){
            
            $value = $this->db->createCommand("select configuration_value from social_addons where block_name = 'analytics' and socials_id = 1 and configuration_key='GAPI_SETTINGS'")->queryOne();
            $file = '';
            if ($value){
                $file = basename($value['configuration_value']);
            }
            $value = $this->db->createCommand("select configuration_value from social_addons where block_name = 'analytics' and socials_id = 1 and configuration_key='GAPI_VIEW_ID'")->queryOne();
            $viewId = '';
            if ($value){
                $viewId = basename($value['configuration_value']);
            }
            (new \common\components\GoogleTools)->getAnalyticsProvider()->createSetting(['jsonFile' => $file, 'viewId'=> $viewId], \common\classes\platform::defaultId());
        }
        $this->db->createCommand("delete from social_addons where block_name = 'analytics'")->execute();
        //analytics end
        /* transfer */
        
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191024_154358_google_settings cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191024_154358_google_settings cannot be reverted.\n";

        return false;
    }
    */
}

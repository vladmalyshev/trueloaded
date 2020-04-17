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
 * Class m190416_162102_platform_api_site_access
 */
class m190416_162102_platform_api_site_access extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('platforms_api', 'site_access_permission', $this->integer(1)->notNull()->defaultValue(0));
        $this->addTranslation('admin/soap_server',[
            'TEXT_SOAP_SERVER_SITE_ACCESS_PERMISSION' => 'Site Access Permission'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/soap_server',[
            'TEXT_SOAP_SERVER_SITE_ACCESS_PERMISSION',
        ]);
        $this->dropColumn('platforms_api', 'site_access_permission');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190416_162102_platform_api_site_access cannot be reverted.\n";

        return false;
    }
    */
}

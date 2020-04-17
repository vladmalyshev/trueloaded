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
 * Class m200214_113635_platfrom_api_ip_restrict
 */
class m200214_113635_platfrom_api_ip_restrict extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('platforms_api', 'ip_allowed', $this->text());

        $this->addTranslation('admin/soap_server',[
            'TEXT_SOAP_SERVER_ALLOWED_IP' => 'Allow API usage for IP',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200214_113635_platfrom_api_ip_restrict cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200214_113635_platfrom_api_ip_restrict cannot be reverted.\n";

        return false;
    }
    */
}

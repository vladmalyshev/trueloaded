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
 * Class m200214_131744_order_widgets
 */
class m200214_131744_order_widgets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/design',[
            'TEXT_IP_ADDRESS' => 'IP Address',
            'TEXT_TOTAL_PRODUCTS_QTY' => 'Total products qty',
            'TEXT_ORDER_TYPE' => 'Order Type',
            'TEXT_TRANSACTIONS' => 'Transactions',
        ]);
        $this->addTranslation('admin/orders',[
            'TEXT_MADE_BY_ADMIN' => 'walking',
            'TEXT_MADE_ONLINE' => 'online',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTranslation('admin/orders',[
            'TEXT_MADE_BY_ADMIN',
            'TEXT_MADE_ONLINE',
        ]);
    }
}

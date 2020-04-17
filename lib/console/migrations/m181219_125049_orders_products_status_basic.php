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
 * Class m181219_125049_orders_products_status_basic
 */
class m181219_125049_orders_products_status_basic extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            $this->addTranslation('admin/orders_products_status', [
                'HEADING_TITLE_ORDERS_PRODUCTS_STATUS' => 'Order product status',
                'TABLE_HEADING_ORDERS_PRODUCTS_STATUS' => 'Order product status list',
                'TEXT_INFO_HEADING_EDIT_ORDERS_PRODUCTS_STATUS' => 'Edit Order product status',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_NAME_LONG' => 'Order product status name',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_NAME' => 'Order product status short name',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_COLOUR' => 'Order product status colour',
            ]);
            $this->addTranslation('admin/orders_products_status_manual', [
                'HEADING_TITLE_ORDERS_PRODUCTS_STATUS' => 'Manual Order product status',
                'TEXT_INFO_HEADING_NEW_ORDERS_PRODUCTS_STATUS' => 'New Manual Order product status',
                'TABLE_HEADING_ORDERS_PRODUCTS_STATUS' => 'Manual Order product status list',
                'TEXT_INFO_HEADING_EDIT_ORDERS_PRODUCTS_STATUS' => 'Edit Manual Order product status',
                'TEXT_INFO_HEADING_NEW_ORDERS_PRODUCTS_STATUS' => 'New Manual Order product status',
                'ERROR_ORDERS_PRODUCTS_STATUS_USED_IN_ORDERS_PRODUCTS' => 'Manual Order product status is used in Order products and can\'t be deleted!',
                'ERROR_ORDERS_PRODUCTS_STATUS_USED_IN_ORDERS_PRODUCTS_HISTORY' => 'Manual Order product status is used in Order products history and can\'t be deleted!',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_NAME_LONG' => 'Manual Order product status name',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_NAME' => 'Manual Order product status short name',
                'TEXT_INFO_ORDERS_PRODUCTS_STATUS_COLOUR' => 'Manual Order product status colour',
            ]);
            $adminMain = [
                'BOX_SETTINGS_ORDERS_PRODUCTS_STATUS' => 'Order product status',
                'BOX_ORDERS_PRODUCTS_STATUS' => 'Order product status',
                'BOX_ORDERS_PRODUCTS_STATUS_MANUAL' => 'Manual Order product status',
                'TABLE_HEADING_STATUS_MANUAL' => 'Manual status'
            ];
            foreach (\common\helpers\OrderProduct::getStatusArray() as $opsArray) {
                $adminMain['TEXT_STATUS_LONG_' . $opsArray['key']] = $opsArray['long'];
                $adminMain['TEXT_STATUS_' . $opsArray['key']] = $opsArray['short'];
            }
            $this->addTranslation('admin/main', $adminMain);
            unset($adminMain);
            $adminOrdersStatus = [
                'TEXT_INFO_ORDERS_STATUS_ORDER_EVALUATION_STATE' => 'Bind status to order evaluation state'
            ];
            foreach (\common\helpers\Order::getEvaluationStateArray() as $oesArray) {
                $adminOrdersStatus['TEXT_EVALUATION_STATE_LONG_' . $oesArray['key']] = $oesArray['long'];
                $adminOrdersStatus['TEXT_EVALUATION_STATE_' . $oesArray['key']] = $oesArray['short'];
            }
            $this->addTranslation('admin/orders_status', $adminOrdersStatus);
            unset($adminOrdersStatus);
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $this->dropTable('orders_products_status');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        
        try {
            $this->createTable('orders_products_status', [
                'orders_products_status_id' => $this->integer(11)->notNull(),
                'language_id' => $this->integer(11)->notNull()->defaultValue(1),
                'orders_products_status_name' => $this->string(32)->notNull(),
                'orders_products_status_name_long' => $this->string(64)->notNull(),
                'orders_products_status_colour' => $this->string(16)->notNull()->defaultValue('#000000')
            ]);
            $this->addPrimaryKey('', 'orders_products_status', ['orders_products_status_id', 'language_id']);
            $this->createIndex('idx_orders_products_status_name', 'orders_products_status', 'orders_products_status_name');
            $this->createIndex('idx_orders_products_status_name_long', 'orders_products_status', 'orders_products_status_name_long');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        foreach (\yii\helpers\ArrayHelper::map(\common\models\Languages::find()->select('languages_id, code')->asArray()->all(), 'code', 'languages_id') as $languageId) {
            foreach (\common\helpers\OrderProduct::getStatusArray() as $opsId => $opsArray) {
                try {
                    $this->insert('orders_products_status', [
                        'orders_products_status_id' => $opsId,
                        'language_id' => $languageId,
                        'orders_products_status_name' => $opsArray['short'],
                        'orders_products_status_name_long' => $opsArray['long'],
                        'orders_products_status_colour' => $opsArray['colour']
                    ]);
                } catch (\Exception $exc) {
                    echo $exc->getMessage();
                }
            }
        }

        try {
            $this->createTable('orders_products_status_manual', [
                'orders_products_status_manual_id' => $this->integer(11)->notNull(),
                'language_id' => $this->integer(11)->notNull()->defaultValue(1),
                'orders_products_status_manual_name' => $this->string(32)->notNull(),
                'orders_products_status_manual_name_long' => $this->string(64)->notNull(),
                'orders_products_status_manual_colour' => $this->string(16)->notNull()->defaultValue('#000000')
            ]);
            $this->addPrimaryKey('', 'orders_products_status_manual', ['orders_products_status_manual_id', 'language_id']);
            $this->createIndex('idx_orders_products_status_manual_name', 'orders_products_status_manual', 'orders_products_status_manual_name');
            $this->createIndex('idx_orders_products_status_manual_name_long', 'orders_products_status_manual', 'orders_products_status_manual_name_long');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $this->dropTable('orders_products_status_history');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        
        try {
            $this->createTable('orders_products_status_history', [
                'orders_products_history_id' => $this->primaryKey(11),
                'orders_id' => $this->integer(11)->notNull(),
                'orders_products_id' => $this->integer(11)->notNull(),
                'orders_products_status_id' => $this->integer(11)->notNull(),
                'orders_products_status_manual_id' => $this->integer(11)->notNull(),
                'date_added' => $this->dateTime()->notNull() . ' DEFAULT NOW()',
                'comments' => $this->text()->null(),
                'admin_id' => $this->integer(11)->notNull()
            ]);
            $this->createIndex('idx_orders_id', 'orders_products_status_history', 'orders_id');
            $this->createIndex('idx_orders_products_id', 'orders_products_status_history', 'orders_products_id');
            $this->createIndex('idx_orders_products_status_id', 'orders_products_status_history', 'orders_products_status_id');
            $this->createIndex('idx_orders_products_status_manual_id', 'orders_products_status_history', 'orders_products_status_manual_id');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $this->createTable('orders_products_status_manual_matrix', [
                'orders_products_status_manual_id' => $this->integer(11)->notNull(),
                'orders_products_status_id' => $this->integer(11)->notNull()
            ]);
            $this->addPrimaryKey('', 'orders_products_status_manual_matrix', ['orders_products_status_manual_id', 'orders_products_status_id']);
            $this->createIndex('idx_orders_products_status_manual_id', 'orders_products_status_manual_matrix', 'orders_products_status_manual_id');
            $this->createIndex('idx_orders_products_status_id', 'orders_products_status_manual_matrix', 'orders_products_status_id');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $this->addColumn('orders_products', 'orders_products_status_manual', $this->integer(11)->notNull()->defaultValue(0)->after('orders_products_status'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_dlvd', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_dspd', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_rcvd', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_stck_ordr', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_stck_pndg', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_products', 'qty_cnld', $this->integer(11)->notNull()->defaultValue(0)->after('products_quantity'));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->addColumn('orders_status', 'order_evaluation_state_id', $this->integer(11)->notNull()->defaultValue(0));
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->createIndex('idx_order_evaluation_state_id', 'orders_status', 'order_evaluation_state_id');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        /*try {
            $this->dropColumn('orders_status', 'order_evaluation_state_id');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_cnld');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_stck_pndg');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_stck_ordr');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_rcvd');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_dspd');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'qty_dlvd');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropColumn('orders_products', 'orders_products_status_manual');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropTable('orders_products_status_manual_matrix');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropTable('orders_products_status_history');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropTable('orders_products_status_manual');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        try {
            $this->dropTable('orders_products_status');
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
        return true;*/

        echo "m181219_125049_orders_products_status_basic cannot be reverted.\n";
        return false;
    }
}
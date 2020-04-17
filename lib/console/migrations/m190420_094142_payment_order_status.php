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
 * Class m190420_094142_payment_order_status
 */
class m190420_094142_payment_order_status extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $defaultOS = $this->getConfigurationValueByKey('DEFAULT_ORDERS_STATUS_ID');
        if ($defaultOS === false) {
            return false;
        }

        $defaultOSOnlinePayment = (int) $defaultOS;
        $this->db->createCommand("INSERT INTO `configuration` 
            (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `date_added`) VALUES 
            ('Default Order Status For Online Payments', 'DEFAULT_ONLINE_PAYMENT_ORDERS_STATUS_ID', '{$defaultOSOnlinePayment}','Default Order Status For Online Payments', 1, 1, now())")
            ->execute();

        $this->addTranslation('admin/main',[
            'DEFAULT_ONLINE_PAYMENT_ORDERS_STATUS' => 'Default for Online Payments',
        ]);
        $this->addTranslation('admin/main',[
            'ERROR_REMOVE_DEFAULT_ONLINE_PAYMENT_ORDERS_STATUS' => 'Error: The default order status for online payments can not be removed. Please set another order status as default, and try again.',
        ]);

        $replacementOSH = [];
        $replacementCC = [];
        $replacementCP = [];

        $paymentsOS = $this->db->createCommand("SELECT DISTINCT orders_status_id FROM orders_status WHERE 
        (orders_status_name like '%WorldPay%') OR 
        (orders_status_name Like '%PayPal Pro HS%') OR
        (orders_status_name Like '%Amazon payment%') OR
        (orders_status_name Like '%Stripe%') OR
        (orders_status_name Like '%BrainTree%') OR
        (orders_status_name Like '%PxPay%') OR
        (orders_status_name Like '%DOF:%') OR
        (orders_status_name Like '%Paypal%') 
        ")->queryAll();
        if ($paymentsOS) {
            $replacementOSH = array_values(array_unique(array_map(function ($item) {
                return (int) $item['orders_status_id'];
            }, $paymentsOS)));
        }
        if (!$replacementOSH) {
            return false;
        }

        //WorldPay
        $prepareOS = $this->getConfigurationId('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID', $replacementOSH);
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $prepareOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_RBSWORLDPAY_HOSTED_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$prepareOS, $transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$prepareOSP, $transactionOSP]);

        // PayPal Pro HS
        $prepareOS = $this->getConfigurationId('MODULE_PAYMENT_PAYPAL_PRO_HS_PREPARE_ORDER_STATUS_ID', $replacementOSH);
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $prepareOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_PAYPAL_PRO_HS_PREPARE_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_PAYPAL_PRO_HS_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$prepareOS, $transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$prepareOSP, $transactionOSP]);

        // Amazon
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_AMAZON_PAYMENT_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);

        // Stripe
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_STRIPE_TRANSACTION_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);

        // BrainTree
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_BRAINTREE_HOSTED_TRANSACTION_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);

        // PayPal Express
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_PAYPAL_EXPRESS_APP_TRANSACTIONS_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);

        //PxPay
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_PXPAY_ORDER_PROCESS_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);

        //Paypal IPN
        $transactionOS = $this->getConfigurationId('MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID', $replacementOSH);
        $transactionOSP = $this->getPlatformConfigurationId('MODULE_PAYMENT_PAYPALIPN_ORDER_STATUS_ID', $replacementOSH);
        $this->addConfigurationIdToArray($replacementCC, [$transactionOS]);
        $this->addConfigurationIdToArray($replacementCP, [$transactionOSP]);


        $this->db->createCommand('DELETE FROM orders_status WHERE orders_status_id IN ('. implode(',', $replacementOSH) .')')->execute();
        $this->db->createCommand("UPDATE orders_status_history SET orders_status_id = '{$defaultOSOnlinePayment}' WHERE orders_status_id IN (". implode(',', $replacementOSH) . ')')->execute();
        $this->db->createCommand("UPDATE orders SET orders_status = '{$defaultOSOnlinePayment}' WHERE orders_status IN (". implode(',', $replacementOSH) . ')')->execute();

        if ($replacementCC) {
            $replacementCC = array_values(array_unique($replacementCC));
            $this->db->createCommand("UPDATE configuration SET configuration_value = '{$defaultOSOnlinePayment}' WHERE configuration_id IN (". implode(',', $replacementCC) . ')')->execute();
            $this->db->createCommand("UPDATE configuration SET configuration_value = '{$defaultOSOnlinePayment}' WHERE configuration_value IN (". implode(',', $replacementOSH) . ') AND (configuration_key LIKE "%STATUS_ID%") ')->execute();
        }
        if ($replacementCP) {
            $replacementCP = array_values(array_unique($replacementCP));
            $this->db->createCommand("UPDATE platforms_configuration SET configuration_value = '{$defaultOSOnlinePayment}' WHERE configuration_id IN (". implode(',', $replacementCP) . ')')->execute();
            $this->db->createCommand("UPDATE platforms_configuration SET configuration_value = '{$defaultOSOnlinePayment}' WHERE configuration_value IN (". implode(',', $replacementOSH) . ') AND (configuration_key LIKE "%STATUS_ID%") ')->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190420_094142_payment_order_status cannot be reverted.\n";

        return false;
    }

    /**
     * @param array $dest
     * @param array $values
     */
    private function  addConfigurationIdToArray (array &$dest, array $values)
    {
        foreach ($values as $value) {
            if(is_numeric($value)) {
                $dest[] = $value;
            } elseif ($value && is_array($value)) {
                $dest = array_merge($dest, $value);
            }
        }
    }

    /**
     * @param string $key
     * @param string|int|array $values
     * @return bool|int
     */
    private function getConfigurationId (string $key, $values = '')
    {
        return $this->getConfigureId('configuration', $key, $values);
    }

    /**
     * @param string $key
     * @param string|int|array $values
     * @return bool|int|array
     */
    private function getPlatformConfigurationId (string $key, $values = '')
    {
        return $this->getConfigureId('platforms_configuration', $key, $values, false);
    }

    /**
     * @param string $table
     * @param string $key
     * @param string|int|array $values
     * @param bool $one
     * @return bool|int|array
     */
    private function getConfigureId (string $table, string $key, $values = '', bool $one = true)
    {
        // !!!! Warning not escaped
        $valueString = '1';
        if (!empty($values)) {
            if (is_array($values)) {
                $valueString = sprintf('(configuration_value IN (%s))', implode(',', $values));
            } else {
                $valueString = sprintf('(configuration_value = "%s"', $values);
            }
        }

        try {
            $result = $this->db->createCommand("SELECT configuration_id FROM {$table} WHERE (configuration_key = '{$key}') AND $valueString ");
            if ($one) {
                $result = $result->queryOne();
                if ($result) {
                    return (int)$result['configuration_id'];
                }
            } else {
                $result = $result->queryAll();
                if ($result) {
                    return array_map(function($item) {
                        return $item['configuration_id'];
                    }, $result);

                }
            }
        } catch (\Exception $ex) {
            echo "Error in getConfigureId\n";
            echo "{$ex->getMessage()}\n";
            return false;
        }
        return false;
    }

    /**
     * @param string $key
     * @return bool|string
     */
    private function getConfigurationValueByKey(string $key)
    {

        try {
            $result = $this->db->createCommand("SELECT configuration_value FROM configuration WHERE (configuration_key = '{$key}')")->queryOne();
            if ($result) {
                return (string)$result['configuration_value'];
            }
        } catch (\Exception $ex) {
            echo "Error in getConfigureId\n";
            echo "{$ex->getMessage()}\n";
            return false;
        }
        return false;
    }
}

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
 * Class m200131_095332_configuration
 */
class m200131_095332_configuration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addTranslation('admin/main', [
            'BOX_PCA_PREDICT' => 'PCA Predict'
        ]);//100680
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_LOGGING' => 'Logging'
        ]);//10
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_RECHNUNGEN' => 'Rechnungen'
        ]);//1000
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_LINKS' => 'Links'
        ]);//100672
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_TRUSTPILOT' => 'Trustpilot Settings'
        ]);//100673
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_EXACT' => 'Exact Online'
        ]);//100674
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_PROMOTION' => 'Promotion Settings'
        ]);//100679
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_SUPPORT' => 'Support Settings'
        ]);//100682
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_CACHE' => 'Cache'
        ]);//11
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_DOWNLOAD' => 'Download'
        ]);//13
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_MAINTENANCE' => 'Site Maintenance'
        ]);//16
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_PANEL' => 'Control panel'
        ]);//26
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_PAGECACHE' => 'Page Cache Settings'
        ]);//26229
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_NEWSDESK' => 'Newsdesk configuration options'
        ]);//5002
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_NEWSDESK_STICKY' => 'Newsdesk Sticky Settings'
        ]);//5004
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_MODULE' => 'Module Options'
        ]);//6
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_FAQDESK_LISTING' => 'Faqdesk Listing Settings'
        ]);//601
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_FAQDESK_FRONTPAGE' => 'Faqdesk Frontpage Settings'
        ]);//602
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_FAQDESK_REVIEWS' => 'Faqdesk Reviews Settings'
        ]);//603
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_FAQDESK_STICKY' => 'Faqdesk Sticky Settings'
        ]);//604
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_TRASH' => 'Trash'
        ]);//606
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_ONEPAGE_CHECKOUT' => 'One Page Checkout Options'
        ]);//701
        $this->addTranslation('admin/main', [
            'BOX_CONFIGURATION_MOPICS' => 'Dynamic MoPics'
        ]);//99

        $this->getDb()->createCommand("DELETE FROM `configuration` WHERE `configuration_group_id` = 911;")->execute();
        $this->getDb()->createCommand("ALTER TABLE `configuration` CHANGE `configuration_group_id` `configuration_group_id` VARCHAR(128) NOT NULL DEFAULT '';")->execute();
        
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='' WHERE `configuration_group_id`='0';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MYSTORE' WHERE `configuration_group_id`='1';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MINIMUM_VALUES' WHERE `configuration_group_id`='2';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MAXIMUM_VALUES' WHERE `configuration_group_id`='3';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_WIDE_SEACH' WHERE `configuration_group_id`='333';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_CUSTOMER_DETAILS' WHERE `configuration_group_id`='5';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_SHIPPING_CUSTOMER_DETAILS' WHERE `configuration_group_id`='100683';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_BILLING_CUSTOMER_DETAILS' WHERE `configuration_group_id`='100684';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SHIPPING_PACKAGING' WHERE `configuration_group_id`='7';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_PCA_PREDICT' WHERE `configuration_group_id`='100680';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='TEXT_LISTING_PRODUCTS' WHERE `configuration_group_id`='8';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='TEXT_STOCK' WHERE `configuration_group_id`='9';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_E_MAIL_OPTIONS' WHERE `configuration_group_id`='12';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_RECOVER_CART_SALES' WHERE `configuration_group_id`='6501';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SESSIONS' WHERE `configuration_group_id`='15';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_EDIT_ORDER_SETTINGS' WHERE `configuration_group_id`='20';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CATALOG_SUPPIERS' WHERE `configuration_group_id`='100681';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SEO_OPTIONS' WHERE `configuration_group_id`='1128';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_BONUS_PROGRAMS' WHERE `configuration_group_id`='100676';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SMS_UPDATES_CONFIGURATION' WHERE `configuration_group_id`='100675';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_REFER_FRIEND_SETTINGS' WHERE `configuration_group_id`='100677';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_REPORTS' WHERE `configuration_group_id`='100678';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_EBAY' WHERE `configuration_group_id`='50';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_AMAZON' WHERE `configuration_group_id`='51';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SMSSERVICE' WHERE `configuration_group_id`='60';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_AFFILIATE_PROGRAM' WHERE `configuration_group_id`='900';")->execute();
        
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_LOGGING' WHERE `configuration_group_id`='10';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_RECHNUNGEN' WHERE `configuration_group_id`='1000';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_LINKS' WHERE `configuration_group_id`='100672';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_TRUSTPILOT' WHERE `configuration_group_id`='100673';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_EXACT' WHERE `configuration_group_id`='100674';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PROMOTION' WHERE `configuration_group_id`='100679';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SUPPORT' WHERE `configuration_group_id`='100682';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_CACHE' WHERE `configuration_group_id`='11';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_DOWNLOAD' WHERE `configuration_group_id`='13';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MAINTENANCE' WHERE `configuration_group_id`='16';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PANEL' WHERE `configuration_group_id`='26';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PAGECACHE' WHERE `configuration_group_id`='26229';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK' WHERE `configuration_group_id`='5002';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK_STICKY' WHERE `configuration_group_id`='5004';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MODULE' WHERE `configuration_group_id`='6';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_LISTING' WHERE `configuration_group_id`='601';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_FRONTPAGE' WHERE `configuration_group_id`='602';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_REVIEWS' WHERE `configuration_group_id`='603';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_STICKY' WHERE `configuration_group_id`='604';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_TRASH' WHERE `configuration_group_id`='606';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_ONEPAGE_CHECKOUT' WHERE `configuration_group_id`='701';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MOPICS' WHERE `configuration_group_id`='99';")->execute();
        
        $this->getDb()->createCommand("ALTER TABLE `platforms_configuration` CHANGE `configuration_group_id` `configuration_group_id` VARCHAR(128) NOT NULL DEFAULT '';")->execute();
        
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='' WHERE `configuration_group_id`='0';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MYSTORE' WHERE `configuration_group_id`='1';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MINIMUM_VALUES' WHERE `configuration_group_id`='2';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MAXIMUM_VALUES' WHERE `configuration_group_id`='3';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_WIDE_SEACH' WHERE `configuration_group_id`='333';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_CUSTOMER_DETAILS' WHERE `configuration_group_id`='5';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_SHIPPING_CUSTOMER_DETAILS' WHERE `configuration_group_id`='100683';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_BILLING_CUSTOMER_DETAILS' WHERE `configuration_group_id`='100684';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SHIPPING_PACKAGING' WHERE `configuration_group_id`='7';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_PCA_PREDICT' WHERE `configuration_group_id`='100680';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='TEXT_LISTING_PRODUCTS' WHERE `configuration_group_id`='8';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='TEXT_STOCK' WHERE `configuration_group_id`='9';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_E_MAIL_OPTIONS' WHERE `configuration_group_id`='12';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_RECOVER_CART_SALES' WHERE `configuration_group_id`='6501';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SESSIONS' WHERE `configuration_group_id`='15';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_EDIT_ORDER_SETTINGS' WHERE `configuration_group_id`='20';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CATALOG_SUPPIERS' WHERE `configuration_group_id`='100681';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SEO_OPTIONS' WHERE `configuration_group_id`='1128';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_BONUS_PROGRAMS' WHERE `configuration_group_id`='100676';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SMS_UPDATES_CONFIGURATION' WHERE `configuration_group_id`='100675';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_REFER_FRIEND_SETTINGS' WHERE `configuration_group_id`='100677';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_REPORTS' WHERE `configuration_group_id`='100678';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_EBAY' WHERE `configuration_group_id`='50';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_AMAZON' WHERE `configuration_group_id`='51';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SMSSERVICE' WHERE `configuration_group_id`='60';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_AFFILIATE_PROGRAM' WHERE `configuration_group_id`='900';")->execute();
        
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_LOGGING' WHERE `configuration_group_id`='10';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_RECHNUNGEN' WHERE `configuration_group_id`='1000';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_LINKS' WHERE `configuration_group_id`='100672';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_TRUSTPILOT' WHERE `configuration_group_id`='100673';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_EXACT' WHERE `configuration_group_id`='100674';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PROMOTION' WHERE `configuration_group_id`='100679';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_SUPPORT' WHERE `configuration_group_id`='100682';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_CACHE' WHERE `configuration_group_id`='11';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_DOWNLOAD' WHERE `configuration_group_id`='13';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MAINTENANCE' WHERE `configuration_group_id`='16';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PANEL' WHERE `configuration_group_id`='26';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_PAGECACHE' WHERE `configuration_group_id`='26229';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK' WHERE `configuration_group_id`='5002';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK_STICKY' WHERE `configuration_group_id`='5004';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MODULE' WHERE `configuration_group_id`='6';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_LISTING' WHERE `configuration_group_id`='601';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_FRONTPAGE' WHERE `configuration_group_id`='602';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_REVIEWS' WHERE `configuration_group_id`='603';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_STICKY' WHERE `configuration_group_id`='604';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_TRASH' WHERE `configuration_group_id`='606';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_ONEPAGE_CHECKOUT' WHERE `configuration_group_id`='701';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='BOX_CONFIGURATION_MOPICS' WHERE `configuration_group_id`='99';")->execute();
        
        $this->getDb()->createCommand("DROP TABLE configuration_group;")->execute();
        $this->getDb()->createCommand("DROP TABLE configuration_group_trash;")->execute();
        
        $this->getDb()->createCommand("TRUNCATE TABLE `configuration_trash`;")->execute();
        $this->getDb()->createCommand("ALTER TABLE `configuration_trash` CHANGE `configuration_group_id` `configuration_group_id` VARCHAR(128) NOT NULL DEFAULT '';")->execute();
        
        
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->getDb()->createCommand("CREATE TABLE `configuration_group_trash` (
  `configuration_group_id` int(11) NOT NULL,
  `configuration_group_title` varchar(64) NOT NULL,
  `configuration_group_description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;")->execute();
        
        $this->getDb()->createCommand("CREATE TABLE `configuration_group` (
  `configuration_group_id` int(11) NOT NULL,
  `configuration_group_title` varchar(64) NOT NULL,
  `configuration_group_description` varchar(255) NOT NULL,
  `sort_order` int(5) DEFAULT NULL,
  `visible` int(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;")->execute();
        
        $this->getDb()->createCommand("INSERT INTO `configuration_group` (`configuration_group_id`, `configuration_group_title`, `configuration_group_description`, `sort_order`, `visible`) VALUES
(1, 'My Store', 'General information about my store', 1, 1),
(2, 'Minimum Values', 'The minimum values for functions / data', 2, 1),
(3, 'Maximum Values', 'The maximum values for functions / data', 3, 1),
(5, 'Customer Details', 'Customer account configuration', 6, 1),
(6, 'Module Options', 'Hidden from configuration', 6, 0),
(7, 'Shipping/Packaging', 'Shipping options available at my store', 7, 1),
(8, 'Product Listing', 'Product Listing    configuration options', 8, 1),
(9, 'Stock', 'Stock configuration options', 9, 1),
(10, 'Logging', 'Logging configuration options', 10, 0),
(11, 'Cache', 'Caching configuration options', 11, 0),
(12, 'E-Mail Options', 'General setting for E-Mail transport and HTML E-Mails', 12, 1),
(13, 'Download', 'Downloadable products options', 13, 0),
(15, 'Sessions', 'Session options', 16, 1),
(900, 'Affiliate Program', 'Options for the Affiliate Program', 17, 1),
(99, 'Dynamic MoPics', 'The options which configure Dynamic MoPics.', 18, 0),
(16, 'Site Maintenance', 'Site Maintenance Options', 19, 0),
(100672, 'Links', 'Links Manager configuration options', 99, 0),
(1000, 'Rechnungen', 'Angaben für das Impressum und die Rechnungslegung', 1, 0),
(5002, 'Newsdesk configuration options', 'Newsdesk configuration options', 500, 0),
(5004, 'Newsdesk Sticky Settings', 'Newsdesk Sticky configuration options', 500, 0),
(601, 'Faqdesk Listing Settings', 'Faqdesk Listing Page configuration options', 600, 0),
(602, 'Faqdesk Frontpage Settings', 'Faqdesk Front Page configuration options', 600, 0),
(603, 'Faqdesk Reviews Settings', 'Faqdesk Reviews configuration options', 600, 0),
(604, 'Faqdesk Sticky Settings', 'Faqdesk Reviews configuration options', 600, 0),
(1128, 'SEO Options', 'SEO Options', 27, 1),
(701, 'One Page Checkout Options', '', 200, 0),
(26229, 'Page Cache Settings', 'Settings for the page cache contribution', 20, 0),
(333, 'Wide search', 'Wide search settings', 5, 1),
(6501, 'Recover Cart Sales', 'Recover Cart Sales (RCS) Configuration Values', 15, 1),
(20, 'Edit Order Settings', 'Edit Order Settings', 20, 1),
(100673, 'Trustpilot Settings', 'Trustpilot settings', 20, 0),
(100674, 'Exact Online', 'Exact Online', 5555, 0),
(100675, 'SMS Updates Configuration', 'SMS Updates Configuration for CardBoardFish', 100, 1),
(100676, 'Bonus programs', 'Bonus programs', 100, 1),
(100677, 'Refer Friend Settings', 'Refer Friend Settings', 101, 1),
(100678, 'Reports', 'Reports', 101, 1),
(100679, 'Promotion Settings', 'Promotion Settings', 30, 0),
(100680, 'PCA Predict', 'PCA Predict', 7, 1),
(100681, 'Suppliers', 'Suppliers settings', 21, 1),
(100682, 'Support Settings', 'Support Settings options', 501, 1),
(100683, 'Shipping Customer Details', 'Shipping Customer Details', 6, 1),
(100684, 'Billing Customer Details', 'Billing Customer Details', 6, 1),
(26, 'Control panel', 'Edit control panel settings', 20, 0),
(50, 'Ebay', 'General Ebay Module Settings', 1, 1),
(51, 'Amazon', 'General Amazon Module Settings', 1, 1),
(60, 'SMS Service', 'SMS Service Settings', 1, 1);")->execute();
        
        $this->getDb()->createCommand("TRUNCATE TABLE `configuration_trash`;")->execute();
        $this->getDb()->createCommand("ALTER TABLE `configuration_trash` CHANGE `configuration_group_id` `configuration_group_id` int(11) NOT NULL DEFAULT '0';")->execute();
        
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='99' WHERE `configuration_group_id`='BOX_CONFIGURATION_MOPICS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='701' WHERE `configuration_group_id`='BOX_CONFIGURATION_ONEPAGE_CHECKOUT';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='606' WHERE `configuration_group_id`='BOX_CONFIGURATION_TRASH';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='604' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_STICKY';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='603' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_REVIEWS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='602' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_FRONTPAGE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='601' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_LISTING';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='6' WHERE `configuration_group_id`='BOX_CONFIGURATION_MODULE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='5004' WHERE `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK_STICKY';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='5002' WHERE `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='26229' WHERE `configuration_group_id`='BOX_CONFIGURATION_PAGECACHE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='26' WHERE `configuration_group_id`='BOX_CONFIGURATION_PANEL';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='16' WHERE `configuration_group_id`='BOX_CONFIGURATION_MAINTENANCE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='13' WHERE `configuration_group_id`='BOX_CONFIGURATION_DOWNLOAD';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='11' WHERE `configuration_group_id`='BOX_CONFIGURATION_CACHE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100682' WHERE `configuration_group_id`='BOX_CONFIGURATION_SUPPORT';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100679' WHERE `configuration_group_id`='BOX_CONFIGURATION_PROMOTION';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100674' WHERE `configuration_group_id`='BOX_CONFIGURATION_EXACT';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100673' WHERE `configuration_group_id`='BOX_CONFIGURATION_TRUSTPILOT';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100672' WHERE `configuration_group_id`='BOX_CONFIGURATION_LINKS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='1000' WHERE `configuration_group_id`='BOX_CONFIGURATION_RECHNUNGEN';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='10' WHERE `configuration_group_id`='BOX_CONFIGURATION_LOGGING';")->execute();
        
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='900' WHERE `configuration_group_id`='BOX_AFFILIATE_PROGRAM';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='60' WHERE `configuration_group_id`='BOX_CONFIGURATION_SMSSERVICE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='51' WHERE `configuration_group_id`='BOX_AMAZON';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='50' WHERE `configuration_group_id`='BOX_EBAY';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100678' WHERE `configuration_group_id`='BOX_REPORTS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100677' WHERE `configuration_group_id`='BOX_CONFIGURATION_REFER_FRIEND_SETTINGS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100675' WHERE `configuration_group_id`='BOX_CONFIGURATION_SMS_UPDATES_CONFIGURATION';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100676' WHERE `configuration_group_id`='BOX_CONFIGURATION_BONUS_PROGRAMS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='1128' WHERE `configuration_group_id`='BOX_CONFIGURATION_SEO_OPTIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100681' WHERE `configuration_group_id`='BOX_CATALOG_SUPPIERS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='20' WHERE `configuration_group_id`='BOX_CONFIGURATION_EDIT_ORDER_SETTINGS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='15' WHERE `configuration_group_id`='BOX_CONFIGURATION_SESSIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='6501' WHERE `configuration_group_id`='BOX_CONFIGURATION_RECOVER_CART_SALES';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='12' WHERE `configuration_group_id`='BOX_CONFIGURATION_E_MAIL_OPTIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='9' WHERE `configuration_group_id`='TEXT_STOCK';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='8' WHERE `configuration_group_id`='TEXT_LISTING_PRODUCTS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100680' WHERE `configuration_group_id`='BOX_PCA_PREDICT';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='7' WHERE `configuration_group_id`='BOX_CONFIGURATION_SHIPPING_PACKAGING';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100684' WHERE `configuration_group_id`='BOX_BILLING_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='100683' WHERE `configuration_group_id`='BOX_SHIPPING_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='5' WHERE `configuration_group_id`='BOX_CONFIGURATION_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='333' WHERE `configuration_group_id`='BOX_CONFIGURATION_WIDE_SEACH';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='3' WHERE `configuration_group_id`='BOX_CONFIGURATION_MAXIMUM_VALUES';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='2' WHERE `configuration_group_id`='BOX_CONFIGURATION_MINIMUM_VALUES';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='1' WHERE `configuration_group_id`='BOX_CONFIGURATION_MYSTORE';")->execute();
        $this->getDb()->createCommand("UPDATE `platforms_configuration` SET `configuration_group_id`='0' WHERE `configuration_group_id`='';")->execute();
        
        $this->getDb()->createCommand("ALTER TABLE `platforms_configuration` CHANGE `configuration_group_id` `configuration_group_id` int(11) NOT NULL DEFAULT '0';")->execute();
        
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='99' WHERE `configuration_group_id`='BOX_CONFIGURATION_MOPICS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='701' WHERE `configuration_group_id`='BOX_CONFIGURATION_ONEPAGE_CHECKOUT';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='606' WHERE `configuration_group_id`='BOX_CONFIGURATION_TRASH';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='604' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_STICKY';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='603' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_REVIEWS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='602' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_FRONTPAGE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='601' WHERE `configuration_group_id`='BOX_CONFIGURATION_FAQDESK_LISTING';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='6' WHERE `configuration_group_id`='BOX_CONFIGURATION_MODULE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='5004' WHERE `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK_STICKY';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='5002' WHERE `configuration_group_id`='BOX_CONFIGURATION_NEWSDESK';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='26229' WHERE `configuration_group_id`='BOX_CONFIGURATION_PAGECACHE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='26' WHERE `configuration_group_id`='BOX_CONFIGURATION_PANEL';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='16' WHERE `configuration_group_id`='BOX_CONFIGURATION_MAINTENANCE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='13' WHERE `configuration_group_id`='BOX_CONFIGURATION_DOWNLOAD';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='11' WHERE `configuration_group_id`='BOX_CONFIGURATION_CACHE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100682' WHERE `configuration_group_id`='BOX_CONFIGURATION_SUPPORT';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100679' WHERE `configuration_group_id`='BOX_CONFIGURATION_PROMOTION';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100674' WHERE `configuration_group_id`='BOX_CONFIGURATION_EXACT';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100673' WHERE `configuration_group_id`='BOX_CONFIGURATION_TRUSTPILOT';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100672' WHERE `configuration_group_id`='BOX_CONFIGURATION_LINKS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='1000' WHERE `configuration_group_id`='BOX_CONFIGURATION_RECHNUNGEN';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='10' WHERE `configuration_group_id`='BOX_CONFIGURATION_LOGGING';")->execute();
        
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='900' WHERE `configuration_group_id`='BOX_AFFILIATE_PROGRAM';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='60' WHERE `configuration_group_id`='BOX_CONFIGURATION_SMSSERVICE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='51' WHERE `configuration_group_id`='BOX_AMAZON';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='50' WHERE `configuration_group_id`='BOX_EBAY';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100678' WHERE `configuration_group_id`='BOX_REPORTS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100677' WHERE `configuration_group_id`='BOX_CONFIGURATION_REFER_FRIEND_SETTINGS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100675' WHERE `configuration_group_id`='BOX_CONFIGURATION_SMS_UPDATES_CONFIGURATION';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100676' WHERE `configuration_group_id`='BOX_CONFIGURATION_BONUS_PROGRAMS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='1128' WHERE `configuration_group_id`='BOX_CONFIGURATION_SEO_OPTIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100681' WHERE `configuration_group_id`='BOX_CATALOG_SUPPIERS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='20' WHERE `configuration_group_id`='BOX_CONFIGURATION_EDIT_ORDER_SETTINGS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='15' WHERE `configuration_group_id`='BOX_CONFIGURATION_SESSIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='6501' WHERE `configuration_group_id`='BOX_CONFIGURATION_RECOVER_CART_SALES';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='12' WHERE `configuration_group_id`='BOX_CONFIGURATION_E_MAIL_OPTIONS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='9' WHERE `configuration_group_id`='TEXT_STOCK';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='8' WHERE `configuration_group_id`='TEXT_LISTING_PRODUCTS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100680' WHERE `configuration_group_id`='BOX_PCA_PREDICT';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='7' WHERE `configuration_group_id`='BOX_CONFIGURATION_SHIPPING_PACKAGING';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100684' WHERE `configuration_group_id`='BOX_BILLING_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='100683' WHERE `configuration_group_id`='BOX_SHIPPING_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='5' WHERE `configuration_group_id`='BOX_CONFIGURATION_CUSTOMER_DETAILS';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='333' WHERE `configuration_group_id`='BOX_CONFIGURATION_WIDE_SEACH';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='3' WHERE `configuration_group_id`='BOX_CONFIGURATION_MAXIMUM_VALUES';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='2' WHERE `configuration_group_id`='BOX_CONFIGURATION_MINIMUM_VALUES';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='1' WHERE `configuration_group_id`='BOX_CONFIGURATION_MYSTORE';")->execute();
        $this->getDb()->createCommand("UPDATE `configuration` SET `configuration_group_id`='0' WHERE `configuration_group_id`='';")->execute();
        
        $this->getDb()->createCommand("ALTER TABLE `configuration` CHANGE `configuration_group_id` `configuration_group_id` int(11) NOT NULL DEFAULT '0';")->execute();
    }

}

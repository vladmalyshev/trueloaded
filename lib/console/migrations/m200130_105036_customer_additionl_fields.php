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
 * Class m200130_105036_customer_additionl_fields
 */
class m200130_105036_customer_additionl_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {


        $this->getDb()->createCommand("TRUNCATE TABLE additional_fields")->execute();
        $this->getDb()->createCommand("TRUNCATE TABLE additional_fields_description")->execute();
        $this->getDb()->createCommand("TRUNCATE TABLE additional_fields_group")->execute();
        $this->getDb()->createCommand("TRUNCATE TABLE additional_fields_group_description")->execute();

        $this->getDb()->createCommand(" 
INSERT INTO `additional_fields` (`additional_fields_id`, `additional_fields_code`, `field_type`, `additional_fields_group_id`, `sort_order`, `required`) VALUES
(49, 'limited_company', 'checkbox', 9, 1, 0),
(50, 'sole_trader', 'checkbox', 9, 2, 0),
(51, 'fax_number', 'text', 9, 3, 0),
(52, 'nature_of_business', 'text', 9, 4, 0),
(53, 'bank_name', 'text', 10, 5, 0),
(54, 'account_no', 'text', 10, 6, 0),
(55, 'bank_address', 'text', 10, 7, 0),
(56, 'sale_or_return', 'checkbox', 11, 8, 0),
(57, 'cash_with_order', 'checkbox', 11, 9, 0),
(58, 'firm', 'checkbox', 11, 10, 0),
(59, 'cash_carry', 'checkbox', 11, 11, 0),
(60, 'reference_name_1', 'text', 12, 12, 0),
(61, 'referenc_address_1', 'text', 12, 13, 0),
(62, 'referenc_telephone_1', 'text', 12, 14, 0),
(63, 'referenc_name_2', 'text', 13, 15, 0),
(64, 'referenc_address_2', 'text', 13, 16, 0),
(65, 'referenc_telephone_2', 'text', 13, 17, 0),
(66, 'name_in_full', 'text', 14, 18, 0),
(67, 'position', 'text', 14, 19, 0),
(68, 'business_name', 'company', 15, 20, 0),
(69, 'phone_number', 'phone', 15, 21, 0),
(70, 'company_email', 'email', 15, 22, 0),
(71, 'company_postcode', 'postcode', 15, 23, 0),
(72, 'company_street_address', 'street_address', 15, 24, 0),
(73, 'company_suburb', 'suburb', 15, 25, 0),
(74, 'company_city', 'city', 15, 26, 0),
(75, 'company_state', 'state', 15, 27, 0),
(76, 'company_country_id', 'country_id', 15, 28, 0),
(77, 'owner_firstname', 'firstname', 16, 29, 0),
(78, 'owner_lastname', 'lastname', 16, 30, 0),
(79, 'owner_phone', 'phone', 16, 31, 0),
(80, 'owner_postcode', 'postcode', 16, 32, 0),
(81, 'oner_street_address', 'street_address', 16, 33, 0),
(82, 'owner_suburb', 'suburb', 16, 34, 0),
(83, 'owner_city', 'city', 16, 35, 0),
(84, 'owner_state', 'state', 16, 36, 0),
(85, 'owner_country', 'country_id', 16, 37, 0);
        ")->execute();

        $this->getDb()->createCommand(" 
INSERT INTO `additional_fields_description` (`additional_fields_id`, `language_id`, `title`) VALUES
(49, 1, 'Limited Company'),
(49, 3, 'Limited Company'),
(49, 10, 'Limited Company'),
(50, 1, 'Sole Trader'),
(50, 3, 'Sole Trader'),
(50, 10, 'Sole Trader'),
(51, 1, 'Fax Number'),
(51, 3, 'Fax Number'),
(51, 10, 'Fax Number'),
(52, 1, 'Nature of Business'),
(52, 3, 'Nature of Business'),
(52, 10, 'Nature of Business'),
(53, 1, 'Bank Name'),
(53, 3, 'Bank Name'),
(53, 10, 'Bank Name'),
(54, 1, 'Account No'),
(54, 3, 'Account No'),
(54, 10, 'Account No'),
(55, 1, 'Address'),
(55, 3, 'Address'),
(55, 10, 'Address'),
(56, 1, '33.33% Sale or Return'),
(56, 3, ''),
(56, 10, ''),
(57, 1, '38.25% Cash With Order'),
(57, 3, '38.25% Cash With Order'),
(57, 10, '38.25% Cash With Order'),
(58, 1, '35% Firm'),
(58, 3, '35% Firm'),
(58, 10, '35% Firm'),
(59, 1, '40% Cash & Carry'),
(59, 3, '40% Cash & Carry'),
(59, 10, '40% Cash & Carry'),
(60, 1, 'Name'),
(60, 3, 'Name'),
(60, 10, 'Name'),
(61, 1, 'Address'),
(61, 3, 'Address'),
(61, 10, 'Address'),
(62, 1, 'Telephone No.'),
(62, 3, 'Telephone No.'),
(62, 10, 'Telephone No.'),
(63, 1, 'Name'),
(63, 3, 'Name'),
(63, 10, 'Name'),
(64, 1, 'Address'),
(64, 3, 'Address'),
(64, 10, 'Address'),
(65, 1, 'Telephone No.'),
(65, 3, 'Telephone No.'),
(65, 10, 'Telephone No.'),
(66, 1, 'Name in Full'),
(66, 3, 'Name in Full'),
(66, 10, 'Name in Full'),
(67, 1, 'Position'),
(67, 3, 'Position'),
(67, 10, 'Position'),
(68, 1, 'Business Name'),
(68, 3, 'Business Name'),
(68, 10, 'Business Name'),
(69, 1, 'Phone number'),
(69, 3, 'Phone number'),
(69, 10, 'Phone number'),
(70, 1, 'E-Mail Address'),
(70, 3, 'E-Mail Address'),
(70, 10, 'E-Mail Address'),
(71, 1, 'Post Code'),
(71, 3, 'Post Code'),
(71, 10, 'Post Code'),
(72, 1, 'Street Address'),
(72, 3, 'Street Address'),
(72, 10, 'Street Address'),
(73, 1, 'Suburb'),
(73, 3, 'Suburb'),
(73, 10, 'Suburb'),
(74, 1, 'Town/City'),
(74, 3, 'Town/City'),
(74, 10, 'Town/City'),
(75, 1, 'County/State'),
(75, 3, 'County/State'),
(75, 10, 'County/State'),
(76, 1, 'Country'),
(76, 3, 'Country'),
(76, 10, 'Country'),
(77, 1, 'First Name'),
(77, 3, 'First Name'),
(77, 10, 'First Name'),
(78, 1, 'Last Name'),
(78, 3, 'Last Name'),
(78, 10, 'Last Name'),
(79, 1, 'Phone number'),
(79, 3, 'Phone number'),
(79, 10, 'Phone number'),
(80, 1, 'Post Code'),
(80, 3, 'Post Code'),
(80, 10, 'Post Code'),
(81, 1, 'Street Address'),
(81, 3, 'Street Address'),
(81, 10, 'Street Address'),
(82, 1, 'Suburb'),
(82, 3, 'Suburb'),
(82, 10, 'Suburb'),
(83, 1, 'Town/City'),
(83, 3, 'Town/City'),
(83, 10, 'Town/City'),
(84, 1, 'County/State'),
(84, 3, 'County/State'),
(84, 10, 'County/State'),
(85, 1, 'Country'),
(85, 3, 'Country'),
(85, 10, 'Country');
        ")->execute();

        $this->getDb()->createCommand(" 
INSERT INTO `additional_fields_group` (`additional_fields_group_id`, `sort_order`) VALUES
(9, 1),
(10, 2),
(11, 3),
(12, 4),
(13, 5),
(14, 6),
(15, 7),
(16, 8);
        ")->execute();

        $this->getDb()->createCommand(" 
INSERT INTO `additional_fields_group_description` (`additional_fields_group_id`, `language_id`, `title`) VALUES
(9, 1, 'Customer Details'),
(9, 3, 'Customer Details'),
(9, 10, 'Customer Details'),
(10, 1, 'Bank account details'),
(10, 3, 'Bank account details'),
(10, 10, 'Bank account details'),
(11, 1, 'Discount'),
(11, 3, 'Discount'),
(11, 10, 'Discount'),
(12, 1, 'Trade References 1'),
(12, 3, 'Trade References 1'),
(12, 10, 'Trade References 1'),
(13, 1, 'Trade References 2'),
(13, 3, 'Trade References 2'),
(13, 10, 'Trade References 2'),
(14, 1, 'Declaration'),
(14, 3, 'Declaration'),
(14, 10, 'Declaration'),
(15, 1, 'Company Details'),
(15, 3, 'Company Details'),
(15, 10, 'Company Details'),
(16, 1, 'Owner\'s details'),
(16, 3, 'Owner\'s details'),
(16, 10, 'Owner\'s details');
        ")->execute();

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
    }
}

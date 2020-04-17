<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;
use Yii;

class TradeForm {

    public static function printPdf($customersId) {
        \common\helpers\Translation::init('admin/customers');

        $additionalFields = \common\helpers\Customer::get_additional_fields($customersId);

        $customer = \common\helpers\Customer::getCustomerData($customersId);

        $platform = Yii::$app->get('platform')->config($customersId)->getPlatformData();
        $address = Yii::$app->get('platform')->config($customersId)->getPlatformAddress();

        $platform = array_merge($platform, $address);

        $padding_right = 8.5;
        $padding_left = 8.5;
        $width = 210 - $padding_right - $padding_left;

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Holbi');
        $pdf->SetTitle('');
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins($padding_left, 9.5, $padding_right);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();

        $headingFont = 'promptblacki';
        $labelFont = 'prompt';
        $fieldFont = 'courgette';
        $headingFont = $labelFont = $fieldFont = 'arial';


        $pdf->SetFont($headingFont, '', '25');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell($width / 2 + 2, 11, $platform['platform_name'], 0, 'L', 1, 0);


        $pdf->SetFont($headingFont, '', '17');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(5, 3, 0, 0);
        $pdf->MultiCell($width / 2, 11, NEW_ACCOUNT_FORM, 0, 'L', 1, 1);

        $text = $platform['street_address'] . ', ' . $platform['suburb'] . ',
' . $platform['city'] . ', ' . $platform['postcode'] . '
' . TEXT_TEL . ': ' . $platform['platform_telephone'] . ($platform['platform_landline'] ? ' ' . TEXT_FAX . ': ' : '') . $platform['platform_landline'] . '
' . TEXT_EMAIL . ': ' . $platform['platform_email_address'];

        $pdf->SetFont($labelFont, '', '10');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setCellPaddings(2, 1, 0, 5);
        $pdf->MultiCell('', '', $text, 0, 'L', 0, 1);

        $pdf->SetFont($headingFont, '', '15');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell('', '', CUSTOMER_DETAILS, 0, 'L', 1, 1);


        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->setCellPaddings(0, 1, 1, 5);
        $pdf->MultiCell(99, '', ' ', 0, 'L', 0, 0);

        $pdf->setCellPaddings(0, 1, 1, 5);
        $pdf->MultiCell(40, '', LIMITED_COMPANY, 0, 'L', 0, 0);
        if ($additionalFields['limited_company']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 6.5, '', '', '', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 6.5, '', '', '', '', 300, '', false, false, 0, 'LB');
        }

        $pdf->setCellPaddings(17, 1, 0, 5);
        $pdf->MultiCell(46, '', SOLE_TRADER, 0, 'L', 0, 0);
        if ($additionalFields['sole_trader']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 6.5, '', '', 'N', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 6.5, '', '', 'N', '', 300, '', false, false, 0, 'LB');
        }

        $pdf->setCellMargins(0, 2, 0);
        $pdf->setCellPaddings(3, 0, 0, 0);
        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', BUSINESS_NAME, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['name'], 'B', 'L', 0, 1);

        $pdf->setCellMargins(0, 0.2, 0);

        $data = [
            'street_address' => $additionalFields['street_address'],
            'suburb' => $additionalFields['suburb'],
            'city' => $additionalFields['city'],
            'state' => $additionalFields['state'],
            'country_id' => $additionalFields['country']
        ];
        $address = \common\helpers\Address::address_format(\common\helpers\Address::get_address_format_id($additionalFields['country']), $data, 1, ' ', ',');

        $address_arr = self::cutStr($address, 70, ' ');

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $address_arr[0], 'B', 'L', 0, 1);

        if (count($address_arr) > 1) {
            for ($i = 1; $i < count($address_arr); $i++) {
                $pdf->SetFont($labelFont, '', '11.1');
                $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                $pdf->SetFont($fieldFont, '', '11.6');
                $pdf->MultiCell($width - 45, '', $address_arr[$i], 'B', 'L', 0, 1);
            }
        }

        /* $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
          $pdf->MultiCell($width - 45, '', ' ', 'B', 'L', 0, 1); */

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_POSTCODE, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['postcode'], 'B', 'L', 0, 1);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_TELEPHONE_NO, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell(($width - 70) / 2, '', $additionalFields['phone'], 'B', 'L', 0, 0);
        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(25, '', TEXT_FAX_NO, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell(($width - 70) / 2, '', $additionalFields['fax'], 'B', 'L', 0, 1);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_EMAIL, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $customer['customers_email_address'], 'B', 'L', 0, 1);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', NATURE_OF_BUSINESS, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['nature_business'], 'B', 'L', 0, 1);

        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_OWNERS_NAME, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['owners_firstname'] . ' ' . $additionalFields['owners_lastname'], 'B', 'L', 0, 1);

        $pdf->setCellMargins(0, 0.2, 0);

        $data = [
            'street_address' => $additionalFields['owners_street_address'],
            'suburb' => $additionalFields['owners_suburb'],
            'city' => $additionalFields['owners_city'],
            'state' => $additionalFields['owners_state'],
            'country_id' => $additionalFields['owners_country']
        ];
        $address = \common\helpers\Address::address_format(\common\helpers\Address::get_address_format_id($additionalFields['country']), $data, 1, ' ', ',');

        $address_arr = self::cutStr($address, 70, ' ');

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $address_arr[0], 'B', 'L', 0, 1);

        if (count($address_arr) > 1) {
            for ($i = 1; $i < count($address_arr); $i++) {
                $pdf->SetFont($labelFont, '', '11.1');
                $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                $pdf->SetFont($fieldFont, '', '11.6');
                $pdf->MultiCell($width - 45, '', $address_arr[$i], 'B', 'L', 0, 1);
            }
        }

        /* $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
          $pdf->MultiCell($width - 45, '', ' ', 'B', 'L', 0, 1); */

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_POSTCODE, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['owners_postcode'], 'B', 'L', 0, 1);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_TELEPHONE_NO, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['owners_phone'], 'B', 'L', 0, 1);


        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($headingFont, '', '15');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell('', '', TEXT_DISCOUNT, 0, 'L', 1, 1);


        $pdf->SetFont($labelFont, '', '10');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->setCellMargins(0, 2, 0);
        $pdf->setCellPaddings(0, 1, 3, 5);
        $pdf->MultiCell(45, '', SALE_OR_RETURN, 0, 'R', 0, 0);
        if ($additionalFields['sale_return']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        }

        $pdf->MultiCell(32, '', TEXT_FIRM, 0, 'R', 0, 0);
        if ($additionalFields['firm']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        }

        $pdf->MultiCell(56, '', CASH_WITH_ORDER, 0, 'R', 0, 0);
        if ($additionalFields['cash_with_order']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 7.8, '', '', '', '', 300, '', false, false, 0, 'LB');
        }

        $pdf->MultiCell(46, '', CASH_CARRY, 0, 'R', 0, 0);
        if ($additionalFields['cash_carry']) {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/check_.png', '', '', 5, 7.8, '', '', 'N', '', 300, '', false, false, 0, 'LB');
        } else {
            $pdf->Image(Yii::getAlias('@webroot') . '/themes/basic/img/checkboks.png', '', '', 5, 7.8, '', '', 'N', '', 300, '', false, false, 0, 'LB');
        }


        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($headingFont, '', '15');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell('', '', BANK_ACCOUNT_DETAILS, 0, 'L', 1, 1);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->setCellMargins(0, 2, 0);

        $pdf->MultiCell(45, '', BANK_NAME, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['bank_name'], 'B', 'L', 0, 1);

        $pdf->setCellMargins(0, 0.2, 0);

        $address_arr = self::cutStr($additionalFields['bank_address'], 70, ' ');

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $address_arr[0], 'B', 'L', 0, 1);

        if (count($address_arr) > 1) {
            for ($i = 1; $i < count($address_arr); $i++) {
                $pdf->SetFont($labelFont, '', '11.1');
                $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                $pdf->SetFont($fieldFont, '', '11.6');
                $pdf->MultiCell($width - 45, '', $address_arr[$i], 'B', 'L', 0, 1);
            }
        }


        /* $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
          $pdf->MultiCell($width - 45, '', ' ', 'B', 'L', 0, 1); */

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', ACCOUNT_NO, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell($width - 45, '', $additionalFields['bank_account_no'], 'B', 'L', 0, 1);


        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($headingFont, '', '15');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell('', '', TRADE_REFERENCES, 0, 'L', 1, 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setCellMargins(0, 2, 0);

        if ($additionalFields['trade_name_2']) {
            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_NAME, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['trade_name_1'], 'B', 'L', 0, 0);

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_NAME, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['trade_name_2'], 'B', 'L', 0, 1);
            $pdf->setCellMargins(0, 0.2, 0);

            $address_arr_1 = self::cutStr($additionalFields['trade_address_1'], 18, ' ');
            $address_arr_2 = self::cutStr($additionalFields['trade_address_2'], 18, ' ');

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.1');
            $pdf->MultiCell(($width - 90) / 2, '', $address_arr_1[0], 'B', 'L', 0, 0);

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.1');
            $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['trade_address_2'], 'B', 'L', 0, 1);

            for ($i = 1; $i < 10; $i++) {
                if ($address_arr_1[$i] || $address_arr_2[$i]) {
                    $pdf->SetFont($labelFont, '', '11.1');
                    $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                    $pdf->SetFont($fieldFont, '', '11.1');
                    $pdf->MultiCell(($width - 90) / 2, '', $address_arr_1[$i] . ' ', 'B', 'L', 0, 0);

                    $pdf->SetFont($labelFont, '', '11.1');
                    $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                    $pdf->SetFont($fieldFont, '', '11.1');
                    $pdf->MultiCell(($width - 90) / 2, '', $address_arr_2[$i] . ' ', 'B', 'L', 0, 1);
                }
            }

            /* $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
              $pdf->MultiCell(($width-90)/2, '', ' ', 'B', 'L', 0, 0);
              $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
              $pdf->MultiCell(($width-90)/2, '', ' ', 'B', 'L', 0, 1);

              $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
              $pdf->MultiCell(($width-90)/2, '', ' ', 'B', 'L', 0, 0);
              $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
              $pdf->MultiCell(($width-90)/2, '', ' ', 'B', 'L', 0, 1); */

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_TELEPHONE_NO, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['trade_phone_1'], 'B', 'L', 0, 0);
            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_TELEPHONE_NO, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['trade_phone_2'], 'B', 'L', 0, 1);
        } else {

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_NAME, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell($width - 45, '', $additionalFields['trade_name_1'], 'B', 'L', 0, 1);

            $pdf->setCellMargins(0, 0.2, 0);

            $address_arr = self::cutStr($additionalFields['trade_address_1'], 70, ' ');

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_ADDRESS, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell($width - 45, '', $address_arr[0], 'B', 'L', 0, 1);

            if (count($address_arr) > 1) {
                for ($i = 1; $i < count($address_arr); $i++) {
                    $pdf->SetFont($labelFont, '', '11.1');
                    $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
                    $pdf->SetFont($fieldFont, '', '11.6');
                    $pdf->MultiCell($width - 45, '', $address_arr[$i], 'B', 'L', 0, 1);
                }
            }


            /* $pdf->MultiCell(45, '', ' ', 0, 'R', 0, 0);
              $pdf->MultiCell($width - 45, '', ' ', 'B', 'L', 0, 1); */

            $pdf->SetFont($labelFont, '', '11.1');
            $pdf->MultiCell(45, '', TEXT_TELEPHONE_NO, 0, 'R', 0, 0);
            $pdf->SetFont($fieldFont, '', '11.6');
            $pdf->MultiCell($width - 45, '', $additionalFields['trade_phone_1'], 'B', 'L', 0, 1);
        }


        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($headingFont, '', '15');
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setCellPaddings(2, 0, 0, 0);
        $pdf->MultiCell('', '', TEXT_DECLARATION, 0, 'L', 1, 1);

        $pdf->SetFont($labelFont, '', '10');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setCellMargins(0, 2, 0);

        $pdf->MultiCell('', '', sprintf(REQUEST_TEXT, $platform['platform_owner']), 0, 'L', 0, 1);


        $pdf->SetFont($labelFont, '', '11.1');

        $pdf->MultiCell(45, '', TEXT_SIGNED, 0, 'R', 0, 0);
        $pdf->MultiCell(($width - 90) / 2, '', ' ', 'B', 'L', 0, 0);
        $pdf->MultiCell(45, '', TEXT_DATE, 0, 'R', 0, 0);
        $pdf->MultiCell(($width - 90) / 2, '', ' ', 'B', 'L', 0, 1);
        $pdf->setCellMargins(0, 1.7, 0);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', NAME_IN_FULL, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['name_in_full'], 'B', 'L', 0, 0);

        $pdf->SetFont($labelFont, '', '11.1');
        $pdf->MultiCell(45, '', TEXT_POSITION, 0, 'R', 0, 0);
        $pdf->SetFont($fieldFont, '', '11.6');
        $pdf->MultiCell(($width - 90) / 2, '', $additionalFields['position'], 'B', 'L', 0, 1);

        $pdf->setCellMargins(0, 6, 0);
        $pdf->SetFont($fieldFont, '', '17');
        $pdf->MultiCell('', '', SCOTTISH_BOOKS_EXCEPTIONAL, 0, 'C', 0, 0);


        $pdf->Output('trade_acc_form', 'I');
        die;
    }

    public static function cutStr($str, $len = 70, $spacer = ' ') {

        $arr = array();
        if (strlen($str) > $len) {
            $n = stripos($str, $spacer, $len);
            if ($n !== false) {
                $arr[] = ltrim(substr($str, 0, $n + 1));
                $str = ltrim(substr($str, $n + 1));
                $arr = array_merge($arr, self::cutStr($str, $len, $spacer));
            } else {
                $arr[] = $str;
            }
        } else {
            $arr[] = $str;
        }
        return $arr;
    }

}

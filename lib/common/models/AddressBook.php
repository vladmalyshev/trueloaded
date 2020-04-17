<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

use Imagine\Exception\RuntimeException;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "address_book".
 *
 * @property int $address_book_id
 * @property int $customers_id
 * @property string $entry_gender
 * @property string $entry_company
 * @property string $entry_firstname
 * @property string $entry_lastname
 * @property string $entry_street_address
 * @property string $entry_suburb
 * @property string $entry_postcode
 * @property string $entry_city
 * @property string $entry_state
 * @property int $entry_country_id
 * @property int $entry_zone_id
 * @property string $entry_company_vat
 * @property string $entry_telephone
 */
class AddressBook extends ActiveRecord {

    /**
     * set table name
     * @return string
     */
    public static function tableName() {
        return 'address_book';
    }

    public static function create(array $attributes): self {
        $book = new static();
        foreach ($attributes as $attribute => $value) {
            if ($book->hasAttribute($attribute)) {
                $book->setAttribute($attribute, $value);
            }
        }
        return $book;
    }

    public function edit(array $attributes) {
        foreach ($attributes as $attribute => $value) {
            if ($this->hasAttribute($attribute)) {
                $this->setAttribute($attribute, $value);
            }
        }
    }

    public function getCustomer() {
        return $this->hasOne(Customers::className(), ['customers_id' => 'customers_id']);
    }
    
    public function getCountry(){
        $languages_id = \Yii::$app->settings->get('languages_id');
        return $this->hasOne(Countries::className(), ['countries_id' => 'entry_country_id'])
                ->where([Countries::tableName().'.language_id' => (int)$languages_id]);
    }

    public function editAddressBookAfterEdit(array $addressBook): void
    {
        $this->entry_firstname = $addressBook['entry_firstname'];
        $this->entry_lastname = $addressBook['entry_lastname'];
        $this->save();
    }

}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\Info;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\CustomersAdditionalFields;

class CustomerAdditionalField extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $languages_id;
        if (Yii::$app->user->isGuest && !$this->params['customers_id']) {
            return '';
        }
        if ($this->params['customers_id']) {
            $customersId = $this->params['customers_id'];
        } else {
            $customersId = Yii::$app->user->id;
        }

        $field = AdditionalFields::find()
            ->alias('f')
            ->select('f.*, fd.title')
            ->leftJoin('additional_fields_description fd', 'fd.additional_fields_id = f.additional_fields_id')
            ->where([
                'fd.language_id' => $languages_id,
                'f.additional_fields_id' => $this->settings[0]['field'],
            ])
            ->asArray()
            ->one();

        $value = CustomersAdditionalFields::find()
            ->where([
                'additional_fields_id' => $field['additional_fields_id'],
                'customers_id' => $customersId,
            ])
            ->asArray()
            ->one();

        if ($this->settings[0]['pdf'] || $this->params['pdf']){
            if ($field['field_type'] == 'checkbox') {
                $img = ''. 'themes/basic/img/';
                if ($value['value']) {
                    $img .= 'checked.jpg';
                } else {
                    $img .= 'not-checked.jpg';
                }

                if ( is_file(DIR_FS_CATALOG . $img) ) {
                    return '<img src="@' . base64_encode(file_get_contents(DIR_FS_CATALOG . $img)) . '">';
                }
            } else {
                if ($this->params['pdf']) {
                    $field = \common\models\AdditionalFieldsDescription::findOne($this->settings[0]['field']);
                    return $value['value'] ? $value['value'] : (Info::isAdmin() ? 'field: ' . $field->title : ' ');
                } else {
                    return $value['value'] ? $value['value'] : ' ';
                }
            }
            return '';
        }

        $countries = \common\helpers\Country::get_countries();

        return IncludeTpl::widget(['file' => 'boxes/account/customer-additional-field.tpl', 'params' => [
            'settings' => $this->settings,
            'id' => $this->id,
            'field' => $field,
            'value' => $value['value'],
            'countries' => $countries,
        ]]);
    }
}

<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\account;

use Yii;
use yii\base\Widget;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\AdditionalFieldsGroup;
use common\models\AdditionalFieldsGroupDescription;

class CustomerAdditionalField extends Widget
{

    public $id;
    public $params;
    public $settings;
    public $visibility;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $languages_id;

        $fields = AdditionalFields::find()
            ->alias('f')
            ->select('f.*, fd.title, gd.title as group_title')
            ->leftJoin('additional_fields_description fd', 'fd.additional_fields_id = f.additional_fields_id')
            ->leftJoin('additional_fields_group_description gd', 'gd.additional_fields_group_id = f.additional_fields_group_id')
            ->where('fd.language_id = ' . $languages_id . ' and gd.language_id = ' . $languages_id)
            ->orderBy(['additional_fields_group_id' => SORT_ASC, 'sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $fieldsByGroup = [];
        foreach ($fields as $field) {
            $fieldsByGroup[$field['group_title']][$field['additional_fields_id']] = $field['title'];
        }

        return $this->render('../../views/account/customer-additional-field.tpl', [
            'id' => $this->id,
            'params' => $this->params,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'fieldsByGroup' => $fieldsByGroup,
        ]);
    }
}
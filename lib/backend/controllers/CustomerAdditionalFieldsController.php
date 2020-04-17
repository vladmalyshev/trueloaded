<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use common\models\BannersGroupsImages;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\BannersLanguages;
use common\models\BannersGroups;
use common\models\ImageTypes;
use common\models\AdditionalFields;
use common\models\AdditionalFieldsDescription;
use common\models\AdditionalFieldsGroup;
use common\models\AdditionalFieldsGroupDescription;

class CustomerAdditionalFieldsController extends Sceleton
{

    public $acl = ['BOX_HEADING_CUSTOMERS', 'ADDITIONAL_CUSTOMER_FIELDS'];
    public $banner_extension;
    public $dir_ok = false;

    public function __construct($id, $module = null)
    {
        parent::__construct($id, $module);

        \common\helpers\Translation::init('admin/main');
        \common\helpers\Translation::init('admin/banner_manager');

    }

    public function actionIndex()
    {
        $level_type = Yii::$app->request->get('level_type', 'groups');
        $group_id = Yii::$app->request->get('group_id', 0);
        $field_id = Yii::$app->request->get('field_id', 0);
        $row = Yii::$app->request->get('row');
        $this->selectedMenu = array('customers', 'customer-additional-fields');

        $this->navigation[] = [
            'link' => Yii::$app->urlManager->createUrl('customer-additional-fields'),
            'title' => ADDITIONAL_CUSTOMER_FIELDS_GROUPS
        ];

        $this->topButtons[] = '
        <a href="'
            . Yii::$app->urlManager->createUrl(['customer-additional-fields/edit-group'])
            . '" class="btn btn-confirm btn-add-group"' . ($level_type == 'fields' ? ' style="display: none"' : '') . '>' . TEXT_ADD_GROUP . '</a>
        <a href="'
            . Yii::$app->urlManager->createUrl(['customer-additional-fields/edit'])
            . '" class="btn btn-confirm btn-add-field"' . ($level_type != 'fields' ? ' style="display: none"' : '') . '>' . TEXT_ADD_FIELD . '</a>';

        $this->view->headingTitle = ADDITIONAL_CUSTOMER_FIELDS_GROUPS;

        if ($level_type == 'fields' && !$row) $row = 1;
        return $this->render('index.tpl', [
            'level_type' => $level_type,
            'group_id' => $group_id,
            'field_id' => $field_id,
            'row' => $row,
        ]);
    }

    public function actionList()
    {
        global $languages_id;

        $draw = Yii::$app->request->get('draw', 1);
        $length = Yii::$app->request->get('length', 25);
        $search = Yii::$app->request->get('search');
        $start = Yii::$app->request->get('start', 0);
        $order = Yii::$app->request->get('order', 0);
        $formFilter = Yii::$app->request->get('filter');
        parse_str($formFilter, $output);

        if ($length == -1) {
            $length = 10000;
        }
        if (!$search['value']) {
            $search['value'] = '';
        }

        if ($output['level_type'] == 'fields'){

            $fields = AdditionalFields::find()
                ->alias('f')
                ->select('f.*, fd.title')
                ->leftJoin('additional_fields_description fd', 'fd.additional_fields_id = f.additional_fields_id')
                ->where('fd.language_id = ' . $languages_id)
                ->andWhere('f.additional_fields_group_id = ' . $output['group_id'])
                ->andWhere("fd.title LIKE '%" . $search['value'] . "%'")
                ->limit($length)
                ->offset($start)
                ->orderBy(['sort_order' => SORT_ASC])
                ->asArray()
                ->all();

            $responseList = [];
            $responseList[] = [
                '<div class="item item-back" data-group-id="'. $output['group_id'] . '"> ... </div>',
            ];
            foreach ($fields as $field) {
                $responseList[] = [
                    '<div class="item" data-field-id="'. $field['additional_fields_id'] . '" data-group-id="'. $output['group_id'] . '">'. $field['title'] . '</div>',
                ];
            }

        } else {

            $groups = AdditionalFieldsGroup::find()
                ->alias('g')
                ->select('gd.additional_fields_group_id as id, gd.title')
                ->leftJoin('additional_fields_group_description gd', 'gd.additional_fields_group_id = g.additional_fields_group_id')
                ->where('gd.language_id = ' . $languages_id)
                ->andWhere("gd.title LIKE '%" . $search['value'] . "%'")
                ->limit($length)
                ->offset($start)
                ->orderBy(['sort_order' => SORT_ASC])
                ->asArray()
                ->all();

            $responseList = [];
            foreach ($groups as $group) {
                $responseList[] = [
                    '<div class="item" data-group-id="'. $group['id'] . '">'. $group['title'] . '</div>',
                ];
            }
        }

        $countTypes = AdditionalFieldsGroup::find()
            ->select('additional_fields_group_id')
            ->count();

        $response = array(
            'draw'            => $draw,
            'recordsTotal'    => $countTypes,
            'recordsFiltered' => $countTypes,
            'data'            => $responseList
        );
        echo json_encode( $response );
    }

    public function actionEdit()
    {
        global $languages_id;
        $group_id = Yii::$app->request->get('group_id', '0');
        $field_id = Yii::$app->request->get('field_id', '0');
        $level_type = Yii::$app->request->get('level_type', 'groups');
        $row = Yii::$app->request->get('row', '0');

        $this->navigation[] = [
            'link' => Yii::$app->urlManager->createUrl('customer-additional-fields'),
            'title' => ADDITIONAL_CUSTOMER_FIELDS_EDIT
        ];
        $this->view->headingTitle = ADDITIONAL_CUSTOMER_FIELDS_EDIT;

        $fields = AdditionalFields::find()
            ->where(['additional_fields_id' => $field_id])
            ->asArray()
            ->one();

        $fieldsDescription = AdditionalFieldsDescription::find()
            ->where(['additional_fields_id' => $field_id])
            ->asArray()
            ->all();


        $fieldsDescriptionByLanguages = [];
        foreach ($fieldsDescription as $group) {
            $fieldsDescriptionByLanguages[$group['language_id']] = $group;
        }


        $languages = \common\helpers\Language::get_languages();
        $lang = array();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
            $lang[] = $languages[$i];
        }

        return $this->render('edit.tpl', [
            'fieldsDescriptionByLanguages' => $fieldsDescriptionByLanguages,
            'languages' => $lang,
            'languages_id' => $languages_id,
            'group_id' => $group_id,
            'field_id' => $field_id,
            'level_type' => $level_type,
            'row' => $row,
            'fields' => $fields,
            'url_back' => Yii::$app->urlManager->createUrl([
                'customer-additional-fields',
                'group_id' => $group_id,
                'field_id' => $field_id,
                'level_type' => 'fields',
                'row' => $row
            ])
        ]);
    }

    public function actionEditGroup()
    {
        global $languages_id;
        $group_id = Yii::$app->request->get('group_id', '');
        $level_type = Yii::$app->request->get('level_type', '');
        $row = Yii::$app->request->get('row', '');

        $this->navigation[] = [
            'link' => Yii::$app->urlManager->createUrl('customer-additional-fields'),
            'title' => ADDITIONAL_CUSTOMER_FIELDS_GROUPS_EDIT
        ];
        $this->view->headingTitle = ADDITIONAL_CUSTOMER_FIELDS_GROUPS_EDIT;

        $groups = AdditionalFieldsGroupDescription::find()
            ->where(['additional_fields_group_id' => $group_id])
            ->asArray()
            ->all();

        $groupsByLanguages = [];
        foreach ($groups as $group) {
            $groupsByLanguages[$group['language_id']] = $group;
        }


        $languages = \common\helpers\Language::get_languages();
        $lang = array();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $languages[$i]['logo'] = $languages[$i]['image'];
            $lang[] = $languages[$i];
        }

        return $this->render('edit-group.tpl', [
            'groupsByLanguages' => $groupsByLanguages,
            'languages' => $lang,
            'languages_id' => $languages_id,
            'group_id' => $group_id,
            'level_type' => $level_type,
            'row' => $row
        ]);
    }

    public function actionSave()
    {
        global $languages_id;
        $post = Yii::$app->request->post();

        if (!$post['additional_fields_code']){
            return json_encode([
                'text' => '<span style="color: #f00">You have to enter "field code"</span>',
                'code' => 'error'
            ]);
        } else {
            $field = AdditionalFields::find()->where([
                'additional_fields_code' => $post['additional_fields_code']
            ])->asArray()->one();
            if ($field && $field['additional_fields_id'] != $post['field_id']) {
                return json_encode([
                    'text' => '<span style="color: #f00">This "field code" already exist, enter other "field code"</span>',
                    'code' => 'error',
                    'code1' => $field['field_id'] . ' - ' . $post['field_id']
                ]);
            }
        }

        if (!$post['field_id']) {

            $maxSort = AdditionalFields::find()->max('sort_order');
            $field = new AdditionalFields();
            $field->sort_order = $maxSort+1;
            $field->additional_fields_code = $post['additional_fields_code'];
            $field->field_type = $post['field_type'];
            $field->required = $post['required'] == 'on' ? 1 : 0;
            $field->additional_fields_group_id = $post['group_id'];
            $field->save();
            $post['field_id'] = $field->getPrimaryKey();
        } else {
            $field = AdditionalFields::findOne(['additional_fields_id' => $post['field_id']]);
            $field->additional_fields_code = $post['additional_fields_code'];
            $field->field_type = $post['field_type'];
            $field->required = $post['required'] == 'on' ? 1 : 0;
            $field->additional_fields_group_id = $post['group_id'];
            $field->save();
        }

        foreach ($post['title'] as $language_id => $title) {
            $fieldDescription = AdditionalFieldsDescription::findOne([
                'language_id' => $language_id,
                'additional_fields_id' => $post['field_id']
            ]);
            if (!$fieldDescription) {
                $fieldDescription = new AdditionalFieldsDescription();
            }
            $fieldDescription->attributes = [
                'language_id' => $language_id,
                'additional_fields_id' => $post['field_id'],
                'title' => $title
            ];
            $fieldDescription->save();
        }

        return json_encode([
            'text' => MESSAGE_SAVED,
            'code' => 'success',
            'field_id' => $post['field_id']
        ]);
    }

    public function actionSaveGroup()
    {
        $post = Yii::$app->request->post();

        if (!$post['group_id']) {
            $maxSort = AdditionalFieldsGroup::find()->max('sort_order');
            $group = new AdditionalFieldsGroup();
            $group->sort_order = $maxSort+1;
            $group->save();
            $post['group_id'] = $group->getPrimaryKey();
        }

        foreach ($post['title'] as $language_id => $title) {
            $groupDescription = AdditionalFieldsGroupDescription::findOne([
                'language_id' => $language_id,
                'additional_fields_group_id' => $post['group_id']
            ]);
            if (!$groupDescription) {
                $groupDescription = new AdditionalFieldsGroupDescription();
            }
            $groupDescription->attributes = [
                'language_id' => $language_id,
                'additional_fields_group_id' => $post['group_id'],
                'title' => $title
            ];
            $groupDescription->save();
        }

        return MESSAGE_SAVED;
    }

    public function actionBar()
    {
        global $languages_id;
        $group_id = Yii::$app->request->get('group_id', 0);
        $field_id = Yii::$app->request->get('field_id', 0);
        $level_type = Yii::$app->request->get('level_type', 0);
        $row = Yii::$app->request->get('row', 0);

        $group = [];
        if ($level_type == 'groups' && $group_id) {
            $group = AdditionalFieldsGroupDescription::find()
                ->where([
                    'language_id' => $languages_id,
                    'additional_fields_group_id' => $group_id,
                ])
                ->asArray()
                ->one();
        }
        if ($level_type == 'fields' && $field_id) {
            $group = AdditionalFieldsDescription::find()
                ->where([
                    'language_id' => $languages_id,
                    'additional_fields_id' => $field_id,
                ])
                ->asArray()
                ->one();
        }


        $this->layout = false;
        return $this->render('bar.tpl', [
            'row' => $row,
            'data' => $group,
            'group_id' => $group_id,
            'field_id' => $field_id,
            'level_type' => $level_type,
            'action' => 'customer-additional-fields/edit' . ($level_type == 'groups' ? '-group' : '')
        ]);
    }

    public function actionDeleteConfirm()
    {
        global $languages_id;
        $group_id = Yii::$app->request->get('group_id', 0);
        $field_id = Yii::$app->request->get('field_id', 0);
        $level_type = Yii::$app->request->get('level_type', 0);

        if ($field_id) {
            $group = AdditionalFieldsDescription::find()
                ->where([
                    'language_id' => $languages_id,
                    'additional_fields_id' => $field_id,
                ])
                ->asArray()
                ->one();
        } else {
            $group = AdditionalFieldsGroupDescription::find()
                ->where([
                    'language_id' => $languages_id,
                    'additional_fields_group_id' => $group_id,
                ])
                ->asArray()
                ->one();
        }

        $this->layout = false;
        return $this->render('delete-confirm.tpl', [
            'title' => $group['title'],
            'group_id' => $group_id,
            'field_id' => $field_id,
            'level_type' => $level_type,
        ]);
    }

    public function actionDelete()
    {
        global $languages_id;
        $group_id = Yii::$app->request->get('group_id', 0);
        $field_id = Yii::$app->request->get('field_id', 0);
        $level_type = Yii::$app->request->get('level_type', 0);

        if ($field_id) {
            AdditionalFields::deleteAll(['additional_fields_id' => $field_id]);
            AdditionalFieldsDescription::deleteAll(['additional_fields_id' => $field_id]);
        } elseif ($group_id) {
            $additionalFields = AdditionalFields::find()
                ->where(['additional_fields_group_id' => $group_id])
                ->asArray()->all();
            foreach ($additionalFields as $field){
                AdditionalFields::deleteAll(['additional_fields_id' => $field['additional_fields_id']]);
                AdditionalFieldsDescription::deleteAll(['additional_fields_id' => $field['additional_fields_id']]);
            }
            AdditionalFieldsGroup::deleteAll(['additional_fields_group_id' => $group_id]);
            AdditionalFieldsGroupDescription::deleteAll(['additional_fields_group_id' => $group_id]);
        }

        $this->layout = false;
        return json_encode([
            'text' => 'removed',
            'code' => 'success',
        ]);
    }

}

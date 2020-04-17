<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\UserGroupsRestrictions;

use backend\services\GroupsService;
use common\models\GroupsCategories;
use common\models\GroupsProducts;
use common\models\ProductsPrices;
use Yii;
use yii\base\Widget;
use common\models\Groups;
use common\models\GroupsDiscounts;
use yii\db\Query;
use yii\helpers\ArrayHelper;

// User Groups
class UserGroupsRestrictions extends Widget {

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public static function allowed()
    {

        if (!defined('EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED')) {
            self::install();
            return false;
        } elseif (EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED != 'True') {
            return false;
        }
        \common\helpers\Translation::init('extensions/user-groups-restrictions');
        \common\helpers\Translation::init('admin/categories');
        \common\helpers\Translation::init('admin/products');
        return true;
    }

    public static function acl()
    {
        return ['BOX_USER_GROUPS_RESTRICTIONS'];
    }

    public static function install()
    {
        tep_db_query("INSERT INTO `configuration` (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) VALUES
            ('Enable Customers Groups Restrictions?', 'EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED', 'False', 'Hide Categories and Products For Customers Groups?', 0, 50, now(), now(), NULL, 'tep_cfg_select_option(array(\'True\', \'False\'),');");

        tep_db_query("INSERT IGNORE INTO `translation` (language_id, translation_key, translation_entity, translation_value) SELECT languages_id, 'BOX_USER_GROUPS_RESTRICTIONS', 'admin/main', 'Customers Groups Restrictions' FROM languages;");

        tep_db_query("CREATE TABLE `groups_categories` (
            `groups_id` INT(11) NOT NULL,
            `categories_id` INT(11) NOT NULL,
            PRIMARY KEY (`groups_id`, `categories_id`)
        )COLLATE='utf8_general_ci'
        ENGINE=InnoDB;");
        tep_db_query("CREATE TABLE `groups_products` (
            `groups_id` INT(11) NOT NULL,
            `products_id` INT(11) NOT NULL,
            PRIMARY KEY (`groups_id`, `products_id`)
        )COLLATE='utf8_general_ci'
        ENGINE=InnoDB;");

        static::installUpdate190423();
    }

    public static function installUpdate190423(){
        if (Yii::$app->getDb()->getTableSchema('groups_categories',true)){
            // installed
            // {{ assign all catalog
            $query = new Query();
            if ($query->from('groups_categories')->count()==0){
                Yii::$app->getDb()->createCommand(
                    "INSERT INTO groups_categories (groups_id, categories_id) ".
                    "SELECT groups_id, categories_id FROM categories, groups"
                )->execute();
            }
            $query = new Query();
            if ($query->from('groups_products')->count()==0){
                Yii::$app->getDb()->createCommand(
                    "INSERT INTO groups_products (groups_id, products_id) ".
                    "SELECT groups_id, products_id FROM products, groups"
                )->execute();
            }
            // }} assign all catalog
            Yii::$app->getDb()->createCommand(
                "DELETE assign FROM groups_products assign INNER JOIN products_prices pp ON pp.products_id=assign.products_id AND pp.groups_id=assign.groups_id AND products_group_price=-1"
            )->execute();

            foreach ( ProductsPrices::find()
                ->select(ProductsPrices::primaryKey())
                ->where(['products_group_price'=>-1])
                ->asArray()
                ->all() as $backupRow){
                $restoreRowSql = "UPDATE ".ProductsPrices::tableName()." SET products_group_price=-1 WHERE 1 ";
                foreach ($backupRow as $col=>$val){
                    $restoreRowSql .= " AND {$col}={$val}";
                }
                $restoreRowSql .= ";\n";
                @file_put_contents(Yii::getAlias('@runtime/backup_migrate_UserGroupsRestrictions.sql'),$restoreRowSql, FILE_APPEND);
            }
            Yii::$app->getDb()->createCommand(
                "UPDATE products_prices SET products_group_price=-2 WHERE products_group_price=-1"
            )->execute();

            Yii::$app->getDb()->createCommand(
                "UPDATE configuration SET configuration_value='True' WHERE configuration_key='EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED'"
            )->execute();
        }


        /*tep_db_query(
            "INSERT IGNORE INTO `translation` (language_id, translation_key, translation_entity, translation_value) ".
            "SELECT languages_id, 'BOX_USER_GROUPS_RESTRICTIONS', 'admin/main', 'Customers Groups Restrictions' FROM languages");*/
    }

    public static function adminShow()
    {
        $groupId = (int) Yii::$app->request->post('item_id',0);

        global $languages_id, $language;

        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);

        $item_id = (int) Yii::$app->request->post('item_id');
        return self::begin()->render('admin/show.tpl',[
            'groupId' => $item_id,
        ]);
    }
    public static function adminShowForm($groupId,$languageId)
    {
        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);

        $assigned = $groupService->getAssignedCatalog($groupId,$languageId);
        $treeInitData = $groupService->load_tree_slice(0,$languageId);

        foreach ($treeInitData as $key => $data) {
            if (isset($assigned[$data['key']])) {
                $treeInitData[$key]['selected'] = true;
            }
        }
        return self::begin()->render('admin/restriction-form.tpl', [
            'selected_data' => $assigned,
            'tree_data' => $treeInitData,
            'tree_server_url' => Yii::$app->urlManager->createUrl(['groups/load-tree']),
            'tree_server_save_url' => Yii::$app->urlManager->createUrl(['groups/update-catalog-selection','groupId'=>$groupId])
        ]);
    }
    public static function loadTree($do,$req_selected_data, $languagesId)
    {
        $response_data = [];
        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);

        if ($do == 'missing_lazy') {
            $category_id = Yii::$app->request->post('id');

            $selected_data = json_decode($req_selected_data, true);
            if (!is_array($selected_data)) {
                $selected_data = json_decode($selected_data, true);
            }

            if (substr($category_id, 0, 1) == 'c')
                $category_id = intval(substr($category_id, 1));

            $response_data['tree_data'] = $groupService->load_tree_slice($category_id,$languagesId);

            foreach ($response_data['tree_data'] as $_idx => $_data) {
                $response_data['tree_data'][$_idx]['selected'] = isset($selected_data[$_data['key']]);
            }
            $response_data = $response_data['tree_data'];
        }

        if ($do == 'update_selected') {
            $id = Yii::$app->request->post('id');
            $selected = Yii::$app->request->post('selected');
            $select_children = Yii::$app->request->post('select_children');
            $selected_data = json_decode($req_selected_data, true);
            if (!is_array($selected_data)) {
                $selected_data = json_decode($selected_data, true);
            }

            if (substr($id, 0, 1) == 'p') {
                list($ppid, $cat_id) = explode('_', $id, 2);
                if ($selected) {
                    // check parent categories
                    $parent_ids = array((int) $cat_id);
                    \common\helpers\Categories::get_parent_categories($parent_ids, $parent_ids[0], false);
                    foreach ($parent_ids as $parent_id) {
                        if (!isset($selected_data['c' . (int) $parent_id])) {
                            $response_data['update_selection']['c' . (int) $parent_id] = true;
                            $selected_data['c' . (int) $parent_id] = 'c' . (int) $parent_id;
                        }
                    }
                    if (!isset($selected_data[$id])) {
                        $response_data['update_selection'][$id] = true;
                        $selected_data[$id] = $id;
                    }
                } else {
                    if (isset($selected_data[$id])) {
                        $response_data['update_selection'][$id] = false;
                        unset($selected_data[$id]);
                    }
                }
            } elseif (substr($id, 0, 1) == 'c') {
                $cat_id = (int) substr($id, 1);
                if ($selected) {
                    $parent_ids = array((int) $cat_id);
                    \common\helpers\Categories::get_parent_categories($parent_ids, $parent_ids[0], false);
                    foreach ($parent_ids as $parent_id) {
                        if (!isset($selected_data['c' . (int) $parent_id])) {
                            $response_data['update_selection']['c' . (int) $parent_id] = true;
                            $selected_data['c' . (int) $parent_id] = 'c' . (int) $parent_id;
                        }
                    }
                    if ($select_children) {
                        $children = array();
                        $groupService->get_category_children($children,  $cat_id,$languagesId);
                        foreach ($children as $child_key) {
                            if (!isset($selected_data[$child_key])) {
                                $response_data['update_selection'][$child_key] = true;
                                $selected_data[$child_key] = $child_key;
                            }
                        }
                    }
                    if (!isset($selected_data[$id])) {
                        $response_data['update_selection'][$id] = true;
                        $selected_data[$id] = $id;
                    }
                } else {
                    $children = array();
                    $groupService->get_category_children($children,$cat_id,$languagesId);
                    foreach ($children as $child_key) {
                        if (isset($selected_data[$child_key])) {
                            $response_data['update_selection'][$child_key] = false;
                            unset($selected_data[$child_key]);
                        }
                    }
                    if (isset($selected_data[$id])) {
                        $response_data['update_selection'][$id] = false;
                        unset($selected_data[$id]);
                    }
                }
            }

            $response_data['selected_data'] = $selected_data;
        }
        return $response_data;
    }
    public static function updateSelection($groupId, $req_selected_data,$languageId)
    {
        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);

        $selected_data = json_decode($req_selected_data, true);
        if (!is_array($selected_data)) {
            $selected_data = json_decode($selected_data, true);
        }
        if (!isset($selected_data['c0']))
            $selected_data['c0'] = 'c0';

        $assigned = $groupService->getAssignedCatalog($groupId,$languageId);
        $assigned_products = [];

        foreach ($assigned as $assigned_key) {
            if (substr($assigned_key, 0, 1) == 'p') {
                $pid = intval(substr($assigned_key, 1));
                $assigned_products[$assigned_key] = $pid;
                unset($assigned[$assigned_key]);
            }
        }
        if (is_array($selected_data)) {
            $selected_products = [];
            $selected_categories = [];
            foreach ($selected_data as $selection) {
                if (substr($selection, 0, 1) == 'p') {
                    $pid = intval(substr($selection, 1));
                    $selected_products[$selection] = $pid;
                    continue;
                }
                if (isset($assigned[$selection])) {
                    unset($assigned[$selection]);
                } else {
                    if (substr($selection, 0, 1) == 'c') {
                        $cat_id = (int) substr($selection, 1);
                        unset($assigned[$selection]);
                        if ($cat_id == 0) {
                            continue;
                        }
                        $selected_categories[] = $cat_id;
                    }
                }
            }
            $upIds = [];
            foreach ($assigned as $clean_key) {
                if (substr($clean_key, 0, 1) == 'c') {
                    $cat_id = (int) substr($clean_key, 1);
                    if ($cat_id == 0) {
                        continue;
                    }
                    $upIds[] = $cat_id;
                    unset($assigned[$clean_key]);
                }
            }
            if($upIds){
                $groupService->removeCategoryToGroup($groupId,array_unique($upIds));
            }
            if($selected_categories){
                $groupService->addCategoryToGroup($groupId,array_unique($selected_categories));
            }
            $upIds = [];
            foreach ($selected_products as $key => $pid) {
                if (isset($assigned_products[$key])) {
                    unset($assigned_products[$key]);
                } else {
                    $upIds[] = $pid;
                }
            }
            if($upIds){
                $groupService->addProductToGroup($groupId,array_unique($upIds));
            }

        }
        $upIds = [];
        foreach ($assigned_products as $assigned_product_id) {
            $upIds[] = $assigned_product_id;
        }
        if($upIds){
            $groupService->removeProductToGroup($groupId,array_unique($upIds));
        }
        return true;
    }
    public static function isAllowed()
    {
        $customer_groups_id = (int) \Yii::$app->storage->get('customer_groups_id');
        if ((int)$customer_groups_id > 0 && defined('EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED') && EXTENSION_USER_GROUPS_RESTRICTIONS_ENABLED == 'True') {
            if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
                if ($ext::allowed()) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function select()
    {
        if ($ext = \common\helpers\Acl::checkExtension('UserGroupsRestrictions', 'allowed')) {
            if ($ext::allowed()) {
                $groups = \common\helpers\Group::get_customer_groups_list();
                return count($groups)>0;
            }
        }
        return false;
    }

    protected static function getCategoryCustomerGroups($categoryId)
    {
        $customer_groups_assigned = [];
        if ( $categoryId>0 ) {
            $customer_groups_assigned = ArrayHelper::map(GroupsCategories::find()
                ->where(['categories_id' => $categoryId])
                ->asArray()
                ->all(), 'groups_id', 'groups_id');
        }
        return $customer_groups_assigned;
    }

    protected static function renderSelectionGrid($customer_groups_assigned)
    {
        $groups = \common\helpers\Group::get_customer_groups_list();
        if ( count($groups)==0 ) return '';

        return self::begin()->render('admin/catalog-assign.tpl',[
            'customer_groups_assigned' => $customer_groups_assigned,
            'customer_groups' => $groups,
        ]);
    }

    public static function productEditBlock($pInfo)
    {
        if ( $pInfo->products_id>0 ) {
            $customer_groups_assigned = ArrayHelper::map(GroupsProducts::find()
                ->where(['products_id' => $pInfo->products_id])
                ->asArray()
                ->all(), 'groups_id', 'groups_id');
        }else{
            if ( isset($pInfo->current_assigned_categories) && is_array($pInfo->current_assigned_categories) && count($pInfo->current_assigned_categories)>0 ) {
                $categoryId = current($pInfo->current_assigned_categories);
            }else{
                $categoryId = intval(Yii::$app->request->get('category_id',0));
            }
            $customer_groups_assigned = static::getCategoryCustomerGroups($categoryId);
        }

        return static::renderSelectionGrid($customer_groups_assigned);
    }

    public static function categoryEditBlock($cInfo)
    {
        if ( $cInfo->categories_id>0 ) {
            $customer_groups_assigned = static::getCategoryCustomerGroups($cInfo->categories_id);
        }else{
            if ( isset($cInfo->parent_id) && !empty($cInfo->parent_id) ) {
                $categoryId = intval($cInfo->parent_id);
            }else{
                $categoryId = intval(Yii::$app->request->get('category_id',0));
            }
            $customer_groups_assigned = static::getCategoryCustomerGroups($categoryId);
        }

        return static::renderSelectionGrid($customer_groups_assigned);
    }

    public static function saveCategory($categoryId)
    {
        if ( !Yii::$app->request->post('customer_groups_assigned_present') ) return;
        $assigned_groups = Yii::$app->request->post('customer_groups_assigned',[]);
        if ( !is_array($assigned_groups) ) $assigned_groups = [];
        $assigned_groups = array_flip(array_map('intval', $assigned_groups));

        foreach (GroupsCategories::find()
                     ->where(['categories_id' => $categoryId])
                     ->all() as $model){
            if ( !isset($assigned_groups[$model->groups_id]) ) {
                $model->delete();
            }else{
                unset($assigned_groups[$model->groups_id]);
            }
        }
        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);
        foreach (array_keys($assigned_groups) as $assignGroupId ){
            $groupService->addCategoryToGroup($assignGroupId, $categoryId);
        }
    }

    public static function saveProduct($productId)
    {
        if ( !Yii::$app->request->post('customer_groups_assigned_present') ) return;
        $assigned_groups = Yii::$app->request->post('customer_groups_assigned',[]);
        if ( !is_array($assigned_groups) ) $assigned_groups = [];
        $assigned_groups = array_flip(array_map('intval', $assigned_groups));

        foreach (GroupsProducts::find()
            ->where(['products_id' => $productId])
            ->all() as $model){
            if ( !isset($assigned_groups[$model->groups_id]) ) {
                $model->delete();
            }else{
                unset($assigned_groups[$model->groups_id]);
            }
        }
        /** @var GroupsService $groupService */
        $groupService = \Yii::createObject(GroupsService::class);
        foreach (array_keys($assigned_groups) as $assignGroupId ){
            $groupService->addProductToGroup($assignGroupId, $productId);
        }
    }
}

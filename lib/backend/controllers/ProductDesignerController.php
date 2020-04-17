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
use Yii;
use yii\helpers\ArrayHelper;
use common\extensions\ProductDesigner\models as ORM;
use yii\data\ActiveDataProvider;

/**
 * Description of ProductDesignerController
 *
 * @author yuri
 */
class ProductDesignerController extends Sceleton
{
    public $acl = ['BOX_HEADING_CATALOG', 'BOX_CATALOG_PRODUCTDESIGNER'];
    
    public function beforeAction($action)
    {
        if (false === \common\helpers\Acl::checkExtensionAllowed('ProductDesigner', 'allowed')) {
            $this->redirect(array('/'));
            return false;
        }
        return parent::beforeAction($action);
    }
    
    /**
     * show admin main page
     */
    public function actionIndex()
    {

        \common\helpers\Translation::init('admin/productdesigner');
        
        
        $this->selectedMenu = array('catalog', 'product-designer');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('product-designer/index'), 'title' => HEADING_TITLE);

        $this->view->headingTitle = HEADING_TITLE;

//        $this->view->SupplierTable = array(
//            array(
//                'title' => TABLE_HEADING_SUPPLIERS,
//                'not_important' => 0,
//            ),
//            array(
//                'title' => TABLE_HEADING_SURCHARGE,
//                'not_important' => 0,
//            ),
//            array(
//                'title' => TABLE_HEADING_MARGIN,
//                'not_important' => 0,
//            ),
//        );

        $messages = $_SESSION['messages']??[];
        unset($_SESSION['messages']);

        $sID = Yii::$app->request->get('sID', 0);
        return $this->render('index', array('messages' => $messages, 'sID' => $sID));        
    }
    
    /**
     * show template page
     */
    public function actionTemplate()
    {
        
        \common\helpers\Translation::init('admin/productdesigner');
        $this->selectedMenu = array('catalog', 'product-designer','product-designer/template');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('product-designer/index'), 'title' => PRODUCTDESIGNER_TEMPLATE_TITLE);
        $this->topButtons[] = '<a href="#" onclick="return templateEdit(0)" class="create_item"><i class="icon-file-text"></i>' . IMAGE_NEW . ' ' . PRODUCTDESIGNER_TEMPLATE_TITLE . '</a>';
        $this->view->headingTitle = PRODUCTDESIGNER_TEMPLATE_TITLE;
        
        $this->view->templateTable = array(
            array(
                'title' => 'Name',
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages']??[];
        unset($_SESSION['messages']);
        
        $sID = Yii::$app->request->get('sID', 0);
        return $this->render('template', array('messages' => $messages, 'sID' => $sID));        
    }
    
    /**
     * show group page
     */
    public function actionGroup()
    {

        \common\helpers\Translation::init('admin/productdesigner');
        
        $this->selectedMenu = array('catalog', 'product-designer', 'product-designer/group');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('product-designer/index'), 'title' => PRODUCTDESIGNER_GROUP_TITLE);
        $this->topButtons[] = '<a href="#" onclick="return groupEdit(0)" class="create_item"><i class="icon-file-text"></i>' . IMAGE_NEW . ' ' . PRODUCTDESIGNER_GROUP_TITLE . '</a>';
        $this->view->headingTitle = PRODUCTDESIGNER_GROUP_TITLE;
        
        $this->view->templateTable = array(
            array(
                'title' => 'Name',
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages']??[];
        unset($_SESSION['messages']);
        
        $sID = Yii::$app->request->get('sID', 0);
        return $this->render('group', array('messages' => $messages, 'sID' => $sID));        
    }
    
    /** 
     * show item page
     */
    public function actionItem()
    {
        
        \common\helpers\Translation::init('admin/productdesigner');
        
        $this->selectedMenu = array('catalog', 'product-designer', 'product-designer/item');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('product-designer/index'), 'title' => PRODUCTDESIGNER_ITEM_TITLE);
        
//        Permission denied :-(
//        $this->topButtons[] = '<a href="#" onclick="return typeAdd()" class="create_item"><i class="icon-file-text"></i>' . 'Add new type' . '</a>';
        $this->topButtons[] = '<a href="#" onclick="return itemEdit(0)" class="create_item"><i class="icon-file-text"></i>' . IMAGE_NEW . ' ' . PRODUCTDESIGNER_ITEM_TITLE . '</a>';
        
        $this->view->headingTitle = PRODUCTDESIGNER_ITEM_TITLE;
        
        $currencies = Yii::$container->get('currencies');
        $defaultCurrenciy = $currencies->currencies[DEFAULT_CURRENCY]['symbol_left'];
        $this->view->templateTable = [
            [
                'title' => 'Name',
                'not_important' => 0,
            ],
            [
                'title' => 'Type',
                'not_important' => 0,
            ],
            [
                'title' => 'Field name',
                'not_important' => 0,
            ],
            [
                'title' => 'Default value',
                'not_important' => 0,
            ],
            [
                'title' => 'Price ' . $defaultCurrenciy,
                'not_important' => 0,
            ],
        ];

        $messages = $_SESSION['messages']??[];
        unset($_SESSION['messages']);
        
        $sID = Yii::$app->request->get('sID', 0);
        return $this->render('item', array('messages' => $messages, 'sID' => $sID));        
    }
    
    /**
     * return templates list
     */
    public function actionTemplate_list() 
    {
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        
        $search = Yii::$app->request->get('search');
        
        $current_page_number = ($start / $length) + 1;
        
        $aProductDesignerTemplates = ORM\ProductDesignerTemplate::find()
            ->where(['like', 'name', $search['value']]) 
            ->offset($start);
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'])
        {
            switch ($_GET['order'][0]['column'])
            {
                case 0:
                    $orderBy = "name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "id ask";
                    break;
            }
        }
        else
        {
            $orderBy = "id ask";
        }
        $aProductDesignerTemplates->orderBy($orderBy);
        
        $provider = new ActiveDataProvider([
            'query' => $aProductDesignerTemplates
        ]);
        
        $totalCount = $provider->getTotalCount();

        $aTemplates = $aProductDesignerTemplates->all();
        $data = [];
        
        foreach($aTemplates as $oProductDesignerTemplate)
        {
            $link = Yii::$app->urlManager->createUrl("product-designer/template_relations");
            $data[] = [
                "<a href='{$link}?template_id={$oProductDesignerTemplate->id}'>{$oProductDesignerTemplate->name}</a>"
                . "<input type='hidden' value='{$oProductDesignerTemplate->id}' name='template_id'/>",
            ];
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $totalCount,
            'data' => $data
        );
        echo json_encode($response);
    }
    
    /**
     * return groups list
     */
    public function actionGroup_list() 
    {
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        
        $search = Yii::$app->request->get('search');
        
        $current_page_number = ($start / $length) + 1;
        
        $aProductDesignerGroups= ORM\ProductDesignerGroup::find()
            ->where(['like', 'name', $search['value']])
            ->offset($start);
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'])
        {
            switch ($_GET['order'][0]['column'])
            {
                case 0:
                    $orderBy = "name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "id ask";
                    break;
            }
        }
        else
        {
            $orderBy = "id ask";
        }
        $aProductDesignerGroups->orderBy($orderBy);

        $provider = new ActiveDataProvider([
            'query' => $aProductDesignerGroups
        ]);
        
        $totalCount = $provider->getTotalCount();

        $aGroups = $aProductDesignerGroups->all();
        $data = [];
        
        foreach($aGroups as $oProductDesignerGroup)
        {
            $link = Yii::$app->urlManager->createUrl("product-designer/group_relations");
            $data[] = [
                "<a href='{$link}?group_id={$oProductDesignerGroup->id}'>{$oProductDesignerGroup->name}</a>"
                . "<input type='hidden' value='{$oProductDesignerGroup->id}' name='group_id'/>"
            ];
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $totalCount,
            'data' => $data
        );
        echo json_encode($response);
    }
    
    /**
     * return templates list
     */
    public function actionItem_list() 
    {
        
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);
        
        $search = Yii::$app->request->get('search');
        
        $current_page_number = ($start / $length) + 1;
        
        $aProductDesignerItems= ORM\ProductDesignerItem::find()
            ->where(['like', 'name', $search['value']])
            ->offset($start);
        
        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir'])
        {
            switch ($_GET['order'][0]['column'])
            {
                case 0:
                    $orderBy = "name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 1:
                    $orderBy = "type " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 2:
                    $orderBy = "field_name " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                case 3:
                    $orderBy = "default_value " . tep_db_prepare_input($_GET['order'][0]['dir']);
                    break;
                default:
                    $orderBy = "id ask";
                    break;
            }
        }
        else
        {
            $orderBy = "id ask";
        }
        $aProductDesignerItems->orderBy($orderBy);
        
        $provider = new ActiveDataProvider([
            'query' => $aProductDesignerItems
        ]);
        
        $totalCount = $provider->getTotalCount();

        $aItems = $aProductDesignerItems->all();
        $data = [];
        
        foreach($aItems as $oItem)
        {
            $item_type = isset($oItem::$type[$oItem->type]) ? $oItem::$type[$oItem->type] : 'none';
            $data[] = [
                "{$oItem->name} <input type='hidden' value='{$oItem->id}' name='item_id'/>",
                $item_type,
                $oItem->field_name,
                $oItem->default_value,
                $oItem->price
            ];
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $totalCount,
            'data' => $data
        );
        echo json_encode($response);
    }
    
    /**
     * 
     * @param string $tpl_name
     * @param integer $template_id
     */
    private function renderTemplate($tpl_name, $template_id)
    {
        Yii::$app->cache->flush();
       \common\helpers\Translation::init('admin/productdesigner');
       $oTemplate = ORM\ProductDesignerTemplate::findOne($template_id);
       return $this->renderPartial($tpl_name, ['oTemplate' => $oTemplate]);
    }
    
    /**
     * 
     * @param string $tpl_name
     * @param integer $group_id
     */
    private function renderGroup($tpl_name, $group_id)
    {
        Yii::$app->cache->flush();
        \common\helpers\Translation::init('admin/productdesigner');
        $oGroup = ORM\ProductDesignerGroup::findOne($group_id);
        return $this->renderPartial($tpl_name, ['oGroup' => $oGroup]);
    }
    
    /**
     * 
     * @param string $tpl_name
     * @param integer $item_id
     */
    private function renderItem($tpl_name, $item_id)
    {
        Yii::$app->cache->flush();
        \common\helpers\Translation::init('admin/productdesigner');
        $oItem = ORM\ProductDesignerItem::findOne($item_id);
        return $this->renderPartial($tpl_name, ['oItem' => $oItem]);
    }
    
    /**
     * render info block
     */
    public function actionTemplate_statusactions() 
    {
        \common\helpers\Translation::init('admin/productdesigner');
        $template_id = Yii::$app->request->post('template_id', 0);
        return $this->renderTemplate('edit_block_template', $template_id);
    }
    
    /**
     * render info block
     */
    public function actionGroup_statusactions() 
    {        
        \common\helpers\Translation::init('admin/productdesigner');
        $group_id = Yii::$app->request->post('group_id', 0);
        return $this->renderGroup('edit_block_group', $group_id);
    }
    
    /**
     * render info block
     */
    public function actionItem_statusactions() 
    {
        \common\helpers\Translation::init('admin/productdesigner');
        $item_id = Yii::$app->request->post('item_id', 0);
        return $this->renderItem('edit_block_item', $item_id);
    }

    /**
     * render view to action edit to group
     */
    public function actionGroup_edit() 
    {
        $group_id = Yii::$app->request->get('group_id', 0);
        return $this->renderGroup('action_edit_block_group', $group_id);
    }
    
    /**
     * render view to action edit to item
     */
    public function actionItem_edit() 
    {
        $item_id = Yii::$app->request->get('item_id', 0);
        return $this->renderItem('action_edit_block_item', $item_id);
    }
    
    /**
     * render view to action edit to template
     */
    public function actionTemplate_edit() 
    {
        $template_id = Yii::$app->request->get('template_id', 0);
        return $this->renderTemplate('action_edit_block_template', $template_id);
    }
    
    /**
     * render view to action confirmdelete to template
     */
    public function actionTemplate_confirmdelete()
    {
        $template_id = Yii::$app->request->post('template_id', 0);
        return $this->renderTemplate('action_confirmdelete_block_template', $template_id);
    }
    
    /**
     * render view to action confirmdelete to group
     */
    public function actionGroup_confirmdelete()
    {
        $group_id = Yii::$app->request->post('group_id', 0);
        return $this->renderGroup('action_confirmdelete_block_group', $group_id);
    }
    
    /**
     * render view to action confirmdelete to item
     */
    public function actionItem_confirmdelete()
    {
        $item_id = (int) Yii::$app->request->post('item_id', 0);
        return $this->renderItem('action_confirmdelete_block_item', $item_id);
    }
    
    /**
     * save item
     */
    public function actionItem_save()
    {
        $item_id = (int) Yii::$app->request->get('item_id', 0);
        
        $oItem = $item_id == 0 ? new ORM\ProductDesignerItem()
                : ORM\ProductDesignerItem::findOne($item_id);

        $aAttributes = $oItem->attributes();
        $flag_save = false;
        foreach ($aAttributes as $attribute)
        {
            $value = Yii::$app->request->post($attribute, false);
            if($value !== false)
            {
                $oItem->$attribute = $value;
                $flag_save = true;
            }
        }
        $flag_save && $oItem->save();
        
        echo json_encode(array('message' => 'Item updated', 'messageType' => 'alert-success'));
    }

    /**
     * save group
     */
    public function actionGroup_save()
    {
        $group_id = (int) Yii::$app->request->get('group_id', 0);

        $name = Yii::$app->request->post('name');
        
        $oGroup = $group_id == 0 ? new ORM\ProductDesignerGroup()
                : ORM\ProductDesignerGroup::findOne($group_id);

        if($name!= '')
        {
            $oGroup->name = $name;
            $oGroup->save();
        }

        echo json_encode(array('message' => 'Group updated', 'messageType' => 'alert-success'));
    }
    
    /**
     * save template
     */
    public function actionTemplate_save()
    {
        $template_id = (int) Yii::$app->request->get('template_id', 0);

        $name = Yii::$app->request->post('name');
        
        $oTemplate = $template_id == 0 ? new ORM\ProductDesignerTemplate()
                : ORM\ProductDesignerTemplate::findOne($template_id);

        if($name!= '')
        {
            $oTemplate->name = $name;
            $oTemplate->save();
        }

        echo json_encode(array('message' => 'Template updated', 'messageType' => 'alert-success'));
    }
    
    /**
     * delete template
     */
    public function actionTemplate_delete()
    {
        $template_id = Yii::$app->request->get('template_id', 0);

        $oTemplate = ORM\ProductDesignerTemplate::findOne($template_id);
        
        if($oTemplate->id > 0)
        {
            $oTemplate->unlinkAll('groups', true);
            $oTemplate->unlinkAll('items', true);
            $oTemplate->unlinkAll('products', false);
            $oTemplate->delete();
            echo 'reset';
        }
    }
    
    /**
     * delete group
     */
    public function actionGroup_delete()
    {
        $group_id = Yii::$app->request->get('group_id', 0);

        $oGroup = ORM\ProductDesignerGroup::findOne($group_id);
        
        if($oGroup->id > 0)
        {
            $oGroup->unlinkAll('templates', true);
            $oGroup->unlinkAll('items', true);
            $oGroup->delete();
            echo 'reset';
        }
    }
    
    /**
     * delete group
     */
    public function actionItem_delete()
    {
        $item_id = Yii::$app->request->get('item_id', 0);

        $oItem = ORM\ProductDesignerItem::findOne($item_id);
        
        if($oItem->id > 0)
        {
            $oItem->unlinkAll('templates', true);
            $oItem->unlinkAll('groups', true);
            $oItem->delete();
            echo 'reset';
        }
    }
    
    /**
     * render page with template relations
     */
    public function actionTemplate_relations()
    {
        \common\helpers\Translation::init('admin/productdesigner');
        
        $this->selectedMenu = array('catalog', 'product-designer', 'product-designer/template');
        
        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) 
        {
            $this->layout = false;
            $this->view->usePopupMode = true;
        }
        
        $template_id = Yii::$app->request->get('template_id', 0);
        $oTemplate = ORM\ProductDesignerTemplate::findOne($template_id);
        
        $this->view->templateItems = $oTemplate->items;
        
        $groupsTree = [];
        foreach ($oTemplate->groups as $oGroup)
        {
            $groupsTree[] = $oGroup->showItemsTree();
        }
        $this->view->templateGroups = $groupsTree;
        
        return $this->render('template_relations', array('oTemplate' => $oTemplate)); 
    }
    
    /**
     * build one-to-many relations for template
     */
    public function actionTemplate_relationsUpdate()
    {
        $aItemsId = Yii::$app->request->post('item_id', array());
        $aGroupsId = Yii::$app->request->post('group_id', array());
        $template_id = Yii::$app->request->post('template_id', 0);

        $oTemplate = ORM\ProductDesignerTemplate::findOne($template_id);
        if(!is_null($oTemplate->id))
        {
            // remove all relations with items
            $oTemplate->unlinkAll('items', true);
            // set new
            foreach ($aItemsId as $sort => $item_id)
            {
                $oItem = ORM\ProductDesignerItem::findOne($item_id);
                !is_null($oItem->id) && $oTemplate->link('items', $oItem);
            }
            
            // remove all relations with groups
            $oTemplate->unlinkAll('groups', true);
            // set new
            foreach ($aGroupsId as $sort => $group_id)
            {
                $oGroup = ORM\ProductDesignerGroup::findOne($group_id);
                !is_null($oGroup->id) && $oTemplate->link('groups', $oGroup, ['sort' => $sort]);
            }
        }
        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['product-designer/template']));
        }
    }
    
    /**
     * generate options with groups
     */
    public function actionGetGroupsList()
    {
        $q = Yii::$app->request->get('q');

        $groups_string = '';

        $aProductDesignerGroups = ORM\ProductDesignerGroup::find()
                ->where(['like', 'name', $q])
                ->all();
        
        foreach ($aProductDesignerGroups as $oGroup) {
            $groups_string .= "<option value='{$oGroup->id}'>{$oGroup->name}</option>";
        }

        echo $groups_string;
    }
    
    /**
     * generate options with groups
     */
    public function actionGetTemplateGroupList()
    {
        $template_id = Yii::$app->request->get('template_id');
        $products_id = Yii::$app->request->get('products_id');

        $oTemplate = ORM\ProductDesignerTemplate::findOne($template_id);
        
        $aReturn = [];
        $aProductDesignerGroups = $oTemplate->groups;
        
        $product_images = \common\helpers\Product::getProductImages($products_id);
        
        foreach ($product_images as $product_image)
        {
            $products_images_id = $product_image['products_images_id'];
            $groups_string = '<option value="0">--none--</option>';
            foreach ($aProductDesignerGroups as $oGroup)
            {
                $oImage = ORM\ProductDesignerImage::getRel($products_images_id, $oGroup->id, $template_id);
                $selected = $oImage ? 'selected="selected"' : '';
                $groups_string .= "<option {$selected} value='{$oGroup->id}'>{$oGroup->name}</option>";
            }
            $aReturn[$products_images_id] = $groups_string;
        }        

        echo json_encode($aReturn);
    }
    
    /**
     * add group info
     */
    public function actionAddRelGroup() 
    {
        \common\helpers\Translation::init('admin/productdesigner');
        $group_id = (int) Yii::$app->request->post('group_id', 0);
//        return $this->renderGroup('relation_group', $group_id);
        
        $oGroup = ORM\ProductDesignerGroup::findOne($group_id);
        return $this->renderPartial('relation_group', ['oGroup' => $oGroup->showItemsTree()]);
    }
    
    /**
     * render page with group relations
     */
    public function actionGroup_relations()
    {
        \common\helpers\Translation::init('admin/productdesigner');
        
        $this->selectedMenu = array('catalog', 'product-designer', 'product-designer/group');
        
        $this->view->usePopupMode = false;
        if (Yii::$app->request->isAjax) 
        {
            $this->layout = false;
            $this->view->usePopupMode = true;
        }
        
        $group_id = (int) Yii::$app->request->get('group_id', 0);
        $oGroup= ORM\ProductDesignerGroup::findOne($group_id);
        
        $this->view->groupItems = $oGroup->getItems()->all();
        
        return $this->render('group_relations', array('oGroup' => $oGroup));    
    }
    
    /**
     * build one-to-many relations for group
     */
    public function actionGroup_relationsUpdate()
    {
        $aItemsId = Yii::$app->request->post('item_id', array());
        $group_id = Yii::$app->request->post('group_id', 0);

        $oGroup = ORM\ProductDesignerGroup::findOne($group_id);

        if(!is_null($oGroup->id))
        {
            // remove all relations with items
            $oGroup->unlinkAll('items', true);
            // set new
            foreach ($aItemsId as $sort => $item_id)
            {
                $oItem = ORM\ProductDesignerItem::findOne($item_id);
                !is_null($oItem->id) && $oGroup->link('items', $oItem, ['sort' => $sort]);
            }
        }
        if (Yii::$app->request->isAjax) {
//          $this->layout = false;
        } else {
            return $this->redirect(Yii::$app->urlManager->createUrl(['product-designer/group']));
        }
    }
    
    /**
     * generate options with items
     */
    public function actionGetItemsList()
    {
        $q = Yii::$app->request->get('q');

        $items_string = '';

        $aProductDesignerItems= ORM\ProductDesignerItem::find()
                ->where(['like', 'name', $q])
                ->all();
        
        foreach ($aProductDesignerItems as $oItem) {
            $items_string .= "<option value='{$oItem->id}'>{$oItem->name}</option>";
        }

        echo $items_string;
    }
    
    public function actionAddRelItem() 
    {
        \common\helpers\Translation::init('admin/productdesigner');
        $item_id = (int) Yii::$app->request->post('item_id', 0);
        return $this->renderItem('relation_item', $item_id);
    }
    
    /**
     * render block for type add action
     */
    public function actionTypeAdd()
    {
        return $this->renderPartial('action_block_type_add');
    }
    
    /**
     * save new type
     */
    public function actionTypeSave()
    {
        $name = Yii::$app->request->post('name');
        if(ORM\ProductDesignerItem::saveTypeTpl($name))
        {
            echo json_encode(array('message' => 'Type saved', 'messageType' => 'alert-success'));
        }
        else
        {
            echo json_encode(array('message' => 'Type cant be saved', 'messageType' => 'alert-error'));
        }
    }
    
    public function actionForbidden() {
        global $languages_id, $language, $messageStack;
        
        \common\helpers\Translation::init('admin/productdesigner');
        $this->selectedMenu = array('catalog', 'product-designer', 'product-designer/forbidden');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('product-designer/index'), 'title' => BOX_CATALOG_PRODUCTDESIGNER_FORBIDDEN);
        $this->topButtons[] = '<a href="#" onclick="return forbiddenEdit(0)" class="create_item"><i class="icon-file-text"></i>' . IMAGE_NEW . ' ' . PRODUCTDESIGNER_WORD . '</a>';
        $this->view->headingTitle = BOX_CATALOG_PRODUCTDESIGNER_FORBIDDEN;
        
        $this->view->forbiddenTable = array(
            array(
                'title' => PRODUCTDESIGNER_WORD,
                'not_important' => 0,
            ),
        );

        $messages = $_SESSION['messages']??[];
        unset($_SESSION['messages']);
        
        $sID = Yii::$app->request->get('sID', 0);
        return $this->render('forbidden', array('messages' => $messages, 'sID' => $sID));  
    }
    
    public function actionForbiddenList() 
    {
        global $languages_id;
        $draw = Yii::$app->request->get('draw', 1);
        $start = Yii::$app->request->get('start', 0);
        $length = Yii::$app->request->get('length', 10);

        $responseList = array();
        if ($length == -1)
            $length = 10000;
        $query_numrows = 0;

        if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
            $keywords = tep_db_input(tep_db_prepare_input($_GET['search']['value']));
            $search_condition = " where word like '%" . $keywords . "%' ";
        } else {
            $search_condition = " where 1";
        }

        if (isset($_GET['order'][0]['column']) && $_GET['order'][0]['dir']) {
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderBy = "word " . tep_db_input(tep_db_prepare_input($_GET['order'][0]['dir']));
                    break;
                default:
                    $orderBy = "word";
                    break;
            }
        } else {
            $orderBy = "groups_name";
        }

        $groups_query_raw = "select * from " . TABLE_PRODUCT_DESIGNER_FORBIDDEN . $search_condition . " order by " . $orderBy;
        $current_page_number = ( $start / $length ) + 1;
        $_split = new \splitPageResults($current_page_number, $length, $groups_query_raw, $query_numrows, 'groups_id');
        $groups_query = tep_db_query($groups_query_raw);
        while ($groups = tep_db_fetch_array($groups_query)) {

            $responseList[] = array(
                $groups['word'] .
                '<input name="forbidden_id" type="hidden" value="' . $groups['forbidden_id'] . '">',
                trim(rtrim($groups['groups_discount'], '0'), '.') . '%',
            );
        }

        $response = array(
            'draw' => $draw,
            'recordsTotal' => $query_numrows,
            'recordsFiltered' => $query_numrows,
            'data' => $responseList
        );
        echo json_encode($response);
    }
    
    public function actionForbiddenStatus() 
    {
        $forbidden_id = (int)Yii::$app->request->post('forbidden_id');
        return $this->renderAjax('edit_block_forbidden', ['forbidden_id' => $forbidden_id]);
    }
    
    public function actionForbiddenEdit() 
    {
        $forbidden_id = (int)Yii::$app->request->post('forbidden_id');
        $_query = tep_db_query("select word from " . TABLE_PRODUCT_DESIGNER_FORBIDDEN . " where forbidden_id = '" . (int) $forbidden_id . "'");
        $item = tep_db_fetch_array($_query);
        return $this->renderAjax('action_edit_block_forbidden', ['forbidden_id' => $forbidden_id, 'word' => $item['word']]);
    }
    
    public function actionForbiddenSave() 
    {
        $forbidden_id = (int)Yii::$app->request->get('forbidden_id');
        $word = tep_db_prepare_input(Yii::$app->request->post('name'));
        if ($forbidden_id > 0) {
            tep_db_query("update " . TABLE_PRODUCT_DESIGNER_FORBIDDEN . " set word = '" . tep_db_input($word) . "' where forbidden_id = '" . (int)$forbidden_id . "'");
        } else {
            tep_db_query("insert into " . TABLE_PRODUCT_DESIGNER_FORBIDDEN . " set word = '" . tep_db_input($word) . "', forbidden_id = '" . (int)$forbidden_id . "'");
        }
    }
    
    public function actionForbiddenConfirmdelete() 
    {
        $forbidden_id = (int)Yii::$app->request->post('forbidden_id');
        tep_db_query("delete from " . TABLE_PRODUCT_DESIGNER_FORBIDDEN . " where forbidden_id = '" . (int)$forbidden_id . "'");
    }
    
}
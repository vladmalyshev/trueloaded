<?php
/**
 * Created by PhpStorm.
 * User: sancho
 * Date: 1/14/18
 * Time: 4:12 PM
 */

namespace common\api\models\Soap;


use common\api\models\AR\Categories;
use common\api\models\Soap\Categories\Category;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;
use yii\helpers\ArrayHelper;

class UpdateCategoryResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Categories\Category
     * @soap
     */
    public $category;

    public $categoryIn;

    public function setCategory(Category $category)
    {
        $this->categoryIn = $category;
    }

    public function build()
    {
        $categoryData = json_decode(json_encode($this->categoryIn),true);

        if ( !ServerSession::get()->acl()->allowUpdateCategory() ) {
            $this->error('categories update not allowed');
        }

        if ( !array_key_exists('categories_id', $categoryData) && empty($categoryData['categories_id']) ) {
            $this->error('categories_id required');
        }else{
            if ( !SoapHelper::hasCategory((int)$categoryData['categories_id']) ) {
                $this->error('Category id: ' . (int)$categoryData['categories_id'].' not found', 'ERROR_CATEGORY_NOT_FOUND');
            }
        }

        if ( array_key_exists('parent_id',$categoryData) ) {
            //$categoryData['parent_id'];
            if ( (int)$categoryData['parent_id']>0 ) {
                if (!SoapHelper::hasCategory((int)$categoryData['parent_id'])){
                    $this->error('Wrong parent category: '.(int)$categoryData['parent_id']);
                }
            }else{
                $categoryData['parent_id'] = 0;
            }

        }else{
            $this->error('parent category empty');
        }

        if ( isset($categoryData['date_added']) && $categoryData['date_added']>1000 ) {
            $categoryData['date_added'] = date('Y-m-d H:i:s', strtotime($categoryData['date_added']));
        }
        if ( isset($categoryData['last_modified']) && $categoryData['last_modified']>1000 ) {
            $categoryData['last_modified'] = date('Y-m-d H:i:s', strtotime($categoryData['last_modified']));
        }
        unset($categoryData['last_modified']);

        if ( isset($categoryData['descriptions']['description']) ) {
            $descriptions = ArrayHelper::isIndexed($categoryData['descriptions']['description'])?$categoryData['descriptions']['description']:[$categoryData['descriptions']['description']];
            $new_descriptions = [];
            foreach ($descriptions as $description){
                $languageCode = $description['language'];
                $new_descriptions[$languageCode.'_0'] = $description;
            }
            $categoryData['descriptions'] = $new_descriptions;
        }else{
            unset($categoryData['descriptions']);
        }
        if ( isset($categoryData['assigned_platforms']) ) {
             if (isset($categoryData['assigned_platforms']['assigned_platform']) && is_array($categoryData['assigned_platforms']['assigned_platform'])) {
                 $assigned_platforms = ArrayHelper::isIndexed($categoryData['assigned_platforms']['assigned_platform'])?$categoryData['assigned_platforms']['assigned_platform']:[$categoryData['assigned_platforms']['assigned_platform']];
                 $categoryData['assigned_platforms'] = $assigned_platforms;
             }else{
                 unset($categoryData['assigned_platforms']);
             }
        }
        if ( isset($categoryData['assigned_customer_groups']) ) {
            if (isset($categoryData['assigned_customer_groups']['assigned_customer_group']) && is_array($categoryData['assigned_customer_groups']['assigned_customer_group'])) {
                $assigned_customer_groups = ArrayHelper::isIndexed($categoryData['assigned_customer_groups']['assigned_customer_group'])?$categoryData['assigned_customer_groups']['assigned_customer_group']:[$categoryData['assigned_customer_groups']['assigned_customer_group']];
                $categoryData['assigned_customer_groups'] = $assigned_customer_groups;
            }else{
                unset($categoryData['assigned_customer_groups']);
            }
        }

        if (ServerSession::get()->getDepartmentId()){
            $categoryData['assigned_departments'] = [
                ['departments_id' => ServerSession::get()->getDepartmentId() ],
            ];
            unset($categoryData['assigned_customer_groups']);
        }

        if ( ServerSession::get()->acl()->siteAccessPermission() ) {

        }else {
            unset($categoryData['assigned_platforms']);
            unset($categoryData['assigned_customer_groups']);
        }

        if ( $this->status!='ERROR' ) {
            try {
                $categoryObj = Categories::findOne(['categories_id'=>$categoryData['categories_id']]);
                if ( !is_object($categoryObj) ) {
                    $this->error('Category not found','ERROR_CATEGORY_NOT_FOUND');
                }else {
                    //{{ same images
                    if (!empty($categoryData['categories_image_source_url']) && strval($categoryData['categories_image'])==strval($categoryObj->categories_image) ){
                        unset($categoryData['categories_image_source_url']);
                    }
                    if (!empty($categoryData['categories_image_2_source_url']) && strval($categoryData['categories_image_2'])==strval($categoryObj->categories_image_2) ){
                        unset($categoryData['categories_image_2_source_url']);
                    }
                    //}} same images

                    $categoryObj->indexedCollectionAppendMode('assigned_departments', true);

                    $categoryObj->importArray($categoryData);
                    // {{ check SAP Project Code
                    $sap_project_updated = false;
                    if ( $categoryObj->getDirtyAttributes(['sap_project_code']) ) {
                        $sap_project_updated = true;
                    }
                    // }} check SAP Project Code
                    if ($categoryObj->save()) {
                        $categoryObj->refresh();
                        $newCategoryData = $categoryObj->exportArray([]);
                        $this->category = new Category($newCategoryData);
                        // {{ check SAP Project Code
                        if ( $sap_project_updated && class_exists('\common\helpers\Sap') ) {
                            \common\helpers\Sap::departmentAddProjectCode((int)$this->departmentId, $categoryObj->sap_project_code);
                        }
                        // }} check SAP Project Code
                    }
                }
            }catch (\Exception $ex){
                $this->error('Save error: '.$ex->getMessage());
            }
        }

    }

}
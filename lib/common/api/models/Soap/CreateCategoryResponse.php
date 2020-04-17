<?php
/**
 * Created by PhpStorm.
 * User: sancho
 * Date: 1/14/18
 * Time: 4:04 PM
 */

namespace common\api\models\Soap;


use common\api\models\AR\Categories;
use common\api\models\Soap\Categories\Category;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;
use yii\helpers\ArrayHelper;

class CreateCategoryResponse extends SoapModel
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

        if ( !ServerSession::get()->acl()->allowCreateCategory() ) {
            $this->error('categories create not allowed');
        }

        if ( array_key_exists('categories_id', $categoryData) && !empty($categoryData['categories_id']) ) {
            unset($categoryData['categories_id']);
            $this->warning('categories_id readonly');
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

        if ( ServerSession::get()->getDepartmentId() ) {
            $categoryData['assigned_platforms'] = [
                ['platform_id' => \common\classes\platform::defaultId()],
            ];
            $categoryData['created_by_department_id'] = ServerSession::get()->getDepartmentId();
            $categoryData['assigned_departments'] = [
                ['departments_id' => ServerSession::get()->getDepartmentId() ],
            ];
            unset($categoryData['assigned_customer_groups']);
        }elseif ( ServerSession::get()->getPlatformId() ) {
            $categoryData['created_by_platform_id'] = ServerSession::get()->getPlatformId();
            if (ServerSession::get()->acl()->siteAccessPermission()){
                if ( !isset($categoryData['assigned_platforms']) || !is_array($categoryData['assigned_platforms']) ) {
                    $categoryData['assigned_platforms'] = [
                        ['platform_id' => ServerSession::get()->getPlatformId()]
                    ];
                }
            }else {
                $categoryData['assigned_platforms'] = [
                    ['platform_id' => ServerSession::get()->getPlatformId()],
                ];
                unset($categoryData['assigned_customer_groups']);
            }
        }

        if ( $this->status!='ERROR' ) {
            try {
                $categoryObj = new Categories();
                $categoryObj->importArray($categoryData);
                if ($categoryObj->save()){
                    $categoryObj->refresh();
                    $newCategoryData = $categoryObj->exportArray([]);
                    $this->category = new Category($newCategoryData);
                    // {{ check SAP Project Code
                    if ( class_exists('\common\helpers\Sap') && ServerSession::get()->getDepartmentId() && !empty($categoryObj->sap_project_code) ) {
                        \common\helpers\Sap::departmentAddProjectCode(ServerSession::get()->getDepartmentId(), $categoryObj->sap_project_code);
                    }
                    // }} check SAP Project Code
                }
            }catch (\Exception $ex){
                $this->error('Save error: '.$ex->getMessage());
            }
        }
    }

}
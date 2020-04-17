<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\SoapServer;


use common\models\PlatformsApi;

class ServerAcl
{

    protected function getData()
    {
        $data = array();
        if (ServerSession::get()->getDepartmentId()){
            $data = tep_db_fetch_array(tep_db_query(
                "SELECT ".
                " api_categories_allow_create AS categories_allow_create, ".
                " api_categories_allow_update AS categories_allow_update, ".
                " api_products_allow_create AS products_allow_create, ".
                " api_products_allow_update AS products_allow_update, ".
                " api_products_allow_remove_owned AS products_allow_remove_owned ".
                "FROM departments ".
                "WHERE departments_id='".ServerSession::get()->getDepartmentId()."'"
            ));
        }elseif (ServerSession::get()->getPlatformId()) {
            $data = PlatformsApi::find()
                ->where(['platform_id'=>ServerSession::get()->getPlatformId()])
                ->asArray(true)
                ->one();
        }
        return $data;
    }

    public function siteAccessPermission()
    {
        $data = $this->getData();
        return isset($data['site_access_permission'])?!!$data['site_access_permission']:false;
    }

    public function allowCreateProduct()
    {
        $data = $this->getData();
        return isset($data['products_allow_create'])?!!$data['products_allow_create']:false;
    }

    public function allowUpdateProduct()
    {
        $data = $this->getData();
        return isset($data['products_allow_update'])?!!$data['products_allow_update']:false;
    }

    public function allowRemoveProduct()
    {
        $data = $this->getData();
        return isset($data['products_allow_remove_owned'])?!!$data['products_allow_remove_owned']:false;
    }

    public function allowCreateCategory()
    {
        $data = $this->getData();
        return isset($data['categories_allow_create'])?!!$data['categories_allow_create']:false;
    }
    public function allowUpdateCategory()
    {
        $data = $this->getData();
        return isset($data['categories_allow_update'])?!!$data['categories_allow_update']:false;
    }

    public function updateProductFlags($forProductOwner=false)
    {
        $updateFlags = json_decode(SoapHelper::getServerKeyValue('product/UpdateDataFlag'), true);
        return $updateFlags;
    }
}
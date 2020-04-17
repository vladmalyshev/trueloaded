<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\AR\Products;
use common\api\models\Soap\Products\Product;
use common\api\SoapServer\ServerSession;
use common\api\SoapServer\SoapHelper;

class UpdateProductResponse extends SoapModel
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
     * @var \common\api\models\Soap\Products\Product {nillable = 1}
     * @soap
     */
    public $product;

    /**
     * @var \common\api\models\Soap\Products\Product
     */
    protected $productIn;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setProduct(\common\api\models\Soap\Products\Product $product)
    {
        if ( !ServerSession::get()->acl()->allowUpdateProduct() ) {
            $this->error('Product update is not allowed');
            return;
        }

        $product_owner = false;
        $get_owner = array('c'=>0);
        if ( !isset($product->products_id) || empty($product->products_id) ) {
            $this->error('Field "products_id" missing');
        }elseif(ServerSession::get()->getDepartmentId()){
            $get_owner = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c ".
                "FROM ".TABLE_PRODUCTS." ".
                "WHERE products_id='".(int)$product->products_id."' AND created_by_department_id='".ServerSession::get()->getDepartmentId()."'"
            ));
        }elseif (ServerSession::get()->getPlatformId()){
            $ownCheck = '';
            if ( !ServerSession::get()->acl()->siteAccessPermission() ){
                $ownCheck = " AND created_by_platform_id='" . ServerSession::get()->getPlatformId() . "' ";
            }
            $get_owner = tep_db_fetch_array(tep_db_query(
                "SELECT COUNT(*) AS c " .
                "FROM " . TABLE_PRODUCTS . " " .
                "WHERE products_id='" . (int)$product->products_id . "' ".
                " {$ownCheck}"
            ));
        }
        if ( $get_owner['c'] ) {
            $product_owner = true;
        }

        $pfx = '';
        if ( $product_owner ) $pfx = '_own';

        if ( $this->status!='ERROR' ) {
            $descriptionKeys = ['products_name', 'products_description', 'products_description_short'];
            // {{ UPDATE ONLY Own product prices
            if ( !$product_owner ) {
                unset($product->prices);
            }
            // }} UPDATE ONLY Own product prices
            $updateFlags = ServerSession::get()->acl()->updateProductFlags($product_owner);

            if (is_array($updateFlags) && count($updateFlags)>0) {
                if (isset($updateFlags['seo_server'.$pfx]) && $updateFlags['seo_server'.$pfx] === false && isset($updateFlags['description_server'.$pfx]) && $updateFlags['description_server'.$pfx] === false) {
                    unset($product->descriptions);
                }

                if (isset($updateFlags['prices_server'.$pfx]) && $updateFlags['prices_server'.$pfx] === false) {
                    unset($product->prices);
                }

                if (isset($updateFlags['stock_server'.$pfx]) && $updateFlags['stock_server'.$pfx] === false) {
                    unset($product->stock_info);
                }

                if (isset($updateFlags['attr_server'.$pfx]) && $updateFlags['attr_server'.$pfx] === false) {
                    unset($product->attributes);
                    unset($product->inventories);
                }

                if (isset($updateFlags['identifiers_server'.$pfx]) && $updateFlags['identifiers_server'.$pfx] === false) {
                    foreach (['products_model', 'products_ean', 'products_asin', 'products_isbn', 'products_upc', 'manufacturers_name', 'manufacturers_id'] as $identifier_key) {
                        unset($product->{$identifier_key});
                    }
                }

                if (isset($updateFlags['images_server'.$pfx]) && $updateFlags['images_server'.$pfx] === false) {
                    unset($product->images);
                }
                if (isset($updateFlags['dimensions_server'.$pfx]) && $updateFlags['dimensions_server'.$pfx] === false) {
                    unset($product->dimensions);
                }
                if (isset($updateFlags['properties_server'.$pfx]) && $updateFlags['properties_server'.$pfx] === false) {
                    unset($product->properties);
                }
            }else{
                // default - not configured
                if ( !$product_owner ) {
                    unset($product->prices);
                    unset($product->stock_info);
                }
            }

        }

        foreach ($product->inputValidate() as $validateResult){
            $this->addMessage($validateResult['code'], $validateResult['text']);
        }

        $this->productIn = $product->makeARArray();
        if ( isset($this->productIn['.warnings']) && is_array($this->productIn['.warnings']) ) {
            foreach ($this->productIn['.warnings'] as $transform_messages){
                $this->warning($transform_messages);
            }
        }
        if ( isset($this->productIn['.errors']) && is_array($this->productIn['.errors']) ) {
            foreach ($this->productIn['.errors'] as $transform_messages){
                $this->error($transform_messages);
            }
        }

        if ( ServerSession::get()->getDepartmentId() ) {
            //??
            foreach (['stock_indication_id', 'stock_indication_text', 'stock_delivery_terms_id', 'stock_delivery_terms_text'] as $resetKey) {
                unset($this->productIn[$resetKey]);
            }
        }

        if ( $this->status!='ERROR' ) {
            if ( is_array($updateFlags) && isset($this->productIn['description']) && is_array($this->productIn['description'])) {
                if (isset($updateFlags['seo_server'.$pfx]) && $updateFlags['seo_server'.$pfx] === false) {
                    foreach ($this->productIn['description'] as $__key => $__data) {
                        foreach (array_keys($__data) as $__descKey) {
                            if (!in_array($__descKey, $descriptionKeys)) unset($this->productIn['description'][$__key][$__descKey]);
                        }
                    }
                }
                if (isset($updateFlags['description_server'.$pfx]) && $updateFlags['description_server'.$pfx] === false) {
                    foreach ($this->productIn['description'] as $__key => $__data) {
                        foreach (array_keys($__data) as $__descKey) {
                            if (in_array($__descKey, $descriptionKeys)) unset($this->productIn['description'][$__key][$__descKey]);
                        }
                    }
                }
            }
        }

        if ( !SoapHelper::hasProduct($this->productIn['products_id']) ) {
            $this->error('Product not found');
        }

    }

    public function build()
    {

        if ( $this->status=='ERROR' ) return;

        $objProduct = Products::findOne(['products_id'=>$this->productIn['products_id']]);

        foreach ( $objProduct->getChildCollectionNames() as $collectionName ) {
            if ( array_key_exists($collectionName, $this->productIn) && !is_array($this->productIn[$collectionName]) ) {
                unset($this->productIn[$collectionName]);
            }
        }

        unset($this->productIn['assigned_departments']);
        if ( ServerSession::get()->acl()->siteAccessPermission() ) {

        }else {
            unset($this->productIn['assigned_platforms']);
            unset($this->productIn['assigned_customer_groups']);
        }

        // {{ create fillout all platforms
        if ( isset($this->productIn['descriptions']) && is_array($this->productIn['descriptions']) ) {
            $new_descriptions = [];
            $platforms = \common\models\Platforms::getPlatformsByType("non-virtual")->all();
            foreach($platforms as $platform){
                foreach ($this->productIn['descriptions'] as $langCode => $description) {
                    $keyCode = $langCode . '_' . $platform->platform_id;
                    $new_descriptions[$keyCode] = $description;
                }
            }
            $this->productIn['descriptions'] = $new_descriptions;
        }
        // }} create fillout all platforms

//echo '<pre>'; var_dump($this->productIn); echo '</pre>'; die;
        $objProduct->importArray($this->productIn);
        $objProduct->save();
        $objProduct->refresh();

        $productDataExport = $objProduct->exportArray([]);
        $this->product = new Product($productDataExport);
    }
}
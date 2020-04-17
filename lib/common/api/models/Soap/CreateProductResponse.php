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

class CreateProductResponse extends SoapModel
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
     * @var integer {nillable = 1}
     * @soap
     */
    public $productId;

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
        if (!ServerSession::get()->acl()->allowCreateProduct()){
            $this->error('Product create is not allowed');
            return;
        }

        foreach ($product->inputValidate() as $validateResult){
            $this->addMessage($validateResult['code'], $validateResult['text']);
        }

        $this->productIn = $product->makeARArray();

        if ( ServerSession::get()->getDepartmentId() ) {
            //??
            foreach (['stock_indication_id', 'stock_indication_text', 'stock_delivery_terms_id', 'stock_delivery_terms_text'] as $resetKey) {
                unset($this->productIn[$resetKey]);
            }
        }

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
        if ( isset($this->productIn['products_id']) ) {
            $this->warning('Field "products_id" read-only');
            unset($this->productIn['products_id']);
        }
    }

    public function build()
    {
        if ( $this->status=='ERROR' ) return;

        if ( ServerSession::get()->getDepartmentId() ) {
            $this->productIn['created_by_department_id'] = ServerSession::get()->getDepartmentId();
            $this->productIn['assigned_departments'][] = [
                'departments_id' => ServerSession::get()->getDepartmentId(),
            ];
            $this->productIn['assigned_platforms'] = [
                ['platform_id' => \common\classes\platform::defaultId()],
            ];
            unset($this->productIn['assigned_customer_groups']);
        }elseif ( ServerSession::get()->getPlatformId() ) {
            $this->productIn['created_by_platform_id'] = ServerSession::get()->getPlatformId();
            if (ServerSession::get()->acl()->siteAccessPermission()){
                if ( !isset($this->productIn['assigned_platforms']) || !is_array($this->productIn['assigned_platforms']) ) {
                    $this->productIn['assigned_platforms'] = [
                        ['platform_id' => ServerSession::get()->getPlatformId()]
                    ];
                }
            }else {
                $this->productIn['assigned_platforms'] = [
                    ['platform_id' => ServerSession::get()->getPlatformId()],
                ];
                unset($this->productIn['assigned_customer_groups']);
            }
        }

        if ( !isset($this->productIn['assigned_categories']) ) {
            $this->productIn['assigned_categories'] = [];
        }else{
            foreach ($this->productIn['assigned_categories'] as $idx=>$categoryInfo) {
                if ( isset($categoryInfo['categories_id']) && !SoapHelper::hasCategory($categoryInfo['categories_id']) ) {
                    unset($this->productIn['assigned_categories'][$idx]);
                }
            }
            $this->productIn['assigned_categories'] = array_values($this->productIn['assigned_categories']);
        }
        if ( count($this->productIn['assigned_categories'])==0 ) {
            $this->productIn['assigned_categories'] = [
                ['categories_id' => 0],
            ];
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

        $objProduct = new Products();
        $objProduct->importArray($this->productIn);
        $objProduct->save();
        $objProduct->refresh();
        $this->productId = $objProduct->products_id;
        $productDataExport = $objProduct->exportArray([]);
        $this->product = new Product($productDataExport);
    }
}
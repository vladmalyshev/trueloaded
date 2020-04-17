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

use common\api\models\AR\CatalogProperty;
use common\api\models\Soap\ArrayOfSearchConditions;
use common\api\models\Soap\Categories\Category;
use common\api\models\Soap\Customer\Customer;
use common\api\models\Soap\Customer\CustomerSearch;
use common\api\models\Soap\Order\Order;
use common\api\models\Soap\Paging;
use common\api\models\Soap\Products\CatalogProductProperty;
use common\api\models\Soap\Products\InventoryRef;
use common\api\models\Soap\Products\Manufacturer;
use common\api\models\Soap\Products\PriceAndStockInfo;
use common\api\models\Soap\Store\ArrayOfOrderStatus;
use common\api\models\Soap\Supplier\Supplier;

class Department
{

    public $authenticated = false;

    /**
     * @param string $api_key
     * @return boolean result of auth
     * @soap
     */
    public function auth($api_key)
    {

        if ( is_array($api_key) ) {
            $api_key = isset($api_key['api_key'])?$api_key['api_key']:'';
        }elseif ( is_object($api_key) ){
            $api_key = isset($api_key->api_key)?$api_key->api_key:'';
        }

        if ( !empty($api_key) ){
            $check_department_r = tep_db_query("SELECT departments_id FROM ".TABLE_DEPARTMENTS." WHERE api_key='".tep_db_input($api_key)."' LIMIT 1");
            if ( tep_db_num_rows($check_department_r)>0 ) {
                $check_department = tep_db_fetch_array($check_department_r);
                ServerSession::get()->setDepartmentId($check_department['departments_id']);
                \Yii::$app->get('department')->setActiveDepartmentId($check_department['departments_id']);
                $this->authenticated = true;
            }
        }
        return $this->authenticated;
    }

    public static function getClassMap()
    {
        return array(
            'ProductListResponse' => '\\common\\api\\models\\Soap\\GetProductListResponse',
            'CurrencyRate' => '\\common\\api\\models\\Soap\\Store\\CurrencyRate',
            'ProductRef' => '\\common\\api\\models\\Soap\\Products\\ProductRef',
            'InventoryRef' => '\\common\\api\\models\\Soap\\Products\\InventoryRef',
            'CustomerSearch' => '\\common\\api\\models\\Soap\\Customer\\CustomerSearch',
            'Order'=> '\\common\\api\\models\\Soap\\Order\\Order',
            'Customer' => '\\common\\api\\models\\Soap\\Customer\\Customer',
            'ArrayOfSearchConditions' => '\\common\\api\\models\\Soap\\ArrayOfSearchConditions',
            'SearchCondition' => '\\common\\api\\models\\Soap\\SearchCondition',
            'Paging' => '\\common\\api\\models\\Soap\\Paging',
            'Category' => '\\common\\api\\models\\Soap\\Categories\\Category',
            'Product' => '\\common\\api\\models\\Soap\\Products\\Product',
            'ArrayOfOrderStatus' => '\\common\\api\\models\\Soap\\Store\\ArrayOfOrderStatus',
            'OrderStatus' => '\\common\\api\\models\\Soap\\Store\\OrderStatus',
            'LanguageValue' => '\\common\\api\\models\\Soap\\LanguageValue',
            'ArrayOfLanguageValueMap' => '\\common\\api\\models\\Soap\\ArrayOfLanguageValueMap',
            'Supplier' => '\\common\\api\\models\\Soap\\Supplier\\Supplier',
            'CatalogProductProperty' => '\\common\\api\\models\\Soap\\Products\\CatalogProductProperty',
            'Manufacturer' => '\\common\\api\\models\\Soap\\Products\\Manufacturer',
            'PriceAndStockInfo' => '\\common\\api\\models\\Soap\\Products\\PriceAndStockInfo',
            'TaxClass' => '\\common\\api\\models\\Soap\\Store\\TaxClass',
            'AdminMember' => '\\common\\api\\models\\Soap\\Store\\AdminMember',
            'Coupon' => '\\common\\api\\models\\Soap\\Store\\Coupon',
            'Theme' => '\\common\\api\\models\\Soap\\Theme\\Theme',
        );
    }

    /**
     * @header
     * @return \common\api\models\Soap\Store\GetServerTimeResponse
     * @throws \SoapFault
     * @soap
     */
    public function getServerTime()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\Store\GetServerTimeResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @header
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetProductListResponse
     * @throws \SoapFault
     * @soap
     */
    public function getProductList(Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetProductListResponse([
                'paging' => $paging,
            ]);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     *
     * @header
     * @param int Catalog Product Id
     * @return \common\api\models\Soap\GetProductResponse
     * @throws \SoapFault
     * @soap
     */
    public function getProduct($productId)
    {
        if ( $this->authenticated ) {
            try {
                $response = new \common\api\models\Soap\GetProductResponse();
                $response->setProductId($productId);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     *
     * @header
     * @param \common\api\models\Soap\Products\Product Product
     * @return \common\api\models\Soap\CreateProductResponse
     * @throws \SoapFault
     * @soap
     */
    public function createProduct($product)
    {
        if ( $this->authenticated ) {
            try {
                $response = new \common\api\models\Soap\CreateProductResponse();
                $response->setProduct($product);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     *
     * @header
     * @param \common\api\models\Soap\Products\Product Product
     * @return \common\api\models\Soap\UpdateProductResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateProduct($product)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\UpdateProductResponse();
                $response->setProduct($product);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     *
     * @header
     * @param int ProductId
     * @return \common\api\models\Soap\RemoveProductResponse
     * @throws \SoapFault
     * @soap
     */
    public function removeProduct($productId)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\RemoveProductResponse();
                $response->setProductId($productId);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\ArrayOfSearchConditions $searchConditions
     * @param \common\api\models\Soap\Paging $paging
     * @return \common\api\models\Soap\GetCatalogPropertiesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getCatalogProperties(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetCatalogPropertiesResponse([
                'paging' => $paging,
            ]);

            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Products\CatalogProductProperty $property
     * @return \common\api\models\Soap\UpdateCatalogPropertiesResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateCatalogProperty(CatalogProductProperty $property)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\UpdateCatalogPropertiesResponse();
            $response->setCatalogProperty($property);

            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param int $manufacturerId
     * @return \common\api\models\Soap\GetManufacturerResponse
     * @throws \SoapFault
     * @soap
     */
    public function getManufacturer($manufacturerId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetManufacturerResponse();
            $response->setManufacturerId($manufacturerId);

            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\ArrayOfSearchConditions $searchConditions
     * @param \common\api\models\Soap\Paging $paging
     * @return \common\api\models\Soap\GetManufacturersResponse
     * @throws \SoapFault
     * @soap
     */
    public function getManufacturers(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetManufacturersResponse([
                'paging' => $paging,
            ]);

            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Products\Manufacturer $manufacturer
     * @return \common\api\models\Soap\CreateManufacturerResponse
     * @throws \SoapFault
     * @soap
     */
    public function createManufacturer(Manufacturer $manufacturer)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\CreateManufacturerResponse();

            $response->setManufacturer($manufacturer);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Products\Manufacturer $manufacturer
     * @return \common\api\models\Soap\UpdateManufacturerResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateManufacturer(Manufacturer $manufacturer)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\UpdateManufacturerResponse();

            $response->setManufacturer($manufacturer);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get stock
     * @header
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetStockResponse
     * @throws \SoapFault
     * @soap
     */
    public function getStock(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetStockResponse([
                'paging' => $paging,
            ]);

            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get prices
     * @header
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetPricesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getPrices(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetPricesResponse([
                'paging' => $paging,
            ]);

            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get stock and prices
     * @header
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetPriceAndStockResponse
     * @throws \SoapFault
     * @soap
     */
    public function getPriceAndStock(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetPriceAndStockResponse([
                'paging' => $paging,
            ]);

            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get store xsell custom types
     * @header
     * @return \common\api\models\Soap\GetXsellTypesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getXsellTypes()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetXsellTypesResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @header
     * @param int $categoryId Catalog Category Id
     * @return \common\api\models\Soap\GetCategoryResponse
     * @throws \SoapFault
     * @soap
     */
    public function getCategory($categoryId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetCategoryResponse();
            $response->setCategoryId($categoryId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @return \common\api\models\Soap\GetCategoriesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getCategories()
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\GetCategoriesResponse();
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Categories\Category $category
     * @return \common\api\models\Soap\CreateCategoryResponse
     * @throws \SoapFault
     * @soap
     */
    public function createCategory(Category $category)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\CreateCategoryResponse();
                $response->setCategory($category);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Categories\Category $category
     * @return \common\api\models\Soap\CreateCategoryResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateCategory(Category $category)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\UpdateCategoryResponse();
                $response->setCategory($category);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Search Customer
     * @header
     * @param  \common\api\models\Soap\Customer\CustomerSearch
     * @return \common\api\models\Soap\GetCustomerResponse
     * @throws \SoapFault
     * @soap
     */
    public function searchCustomer(CustomerSearch $searchCondition)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetCustomerResponse();
            $response->setSearchCondition($searchCondition);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Search Customers
     * @header
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\SearchCustomersResponse
     * @throws \SoapFault
     * @soap
     */
    public function searchCustomers(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\SearchCustomersResponse([
                'paging' => $paging,
            ]);
            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Create Customer
     * @header
     * @param  \common\api\models\Soap\Customer\Customer
     * @return \common\api\models\Soap\CreateCustomerResponse
     * @throws \SoapFault
     * @soap
     */
    public function createCustomer(Customer $customer)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\CreateCustomerResponse();
                $response->setCustomer($customer);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Create Customer
     * @header
     * @param  \common\api\models\Soap\Customer\Customer
     * @return \common\api\models\Soap\UpdateCustomerResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateCustomer(Customer $customer)
    {
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\UpdateCustomerResponse();
                $response->setCustomer($customer);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param integer $orderId
     * @return \common\api\models\Soap\GetOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function getOrder($orderId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetOrderResponse();
            $response->setOrderId($orderId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get orders info
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetOrdersInfoResponse
     * @throws \SoapFault
     * @soap
     */
    public function getOrdersInfo(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetOrdersInfoResponse([
                'paging' => $paging,
            ]);
            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Get server order statuses
     * @header
     * @return \common\api\models\Soap\GetOrderStatusesResponse
     * @throws \SoapFault
     * @soap
     */
    public function getOrderStatuses()
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetOrderStatusesResponse();
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * Inform server about client order statuses
     * @header
     * @param integer $statusMapVersion
     * @param \common\api\models\Soap\Store\ArrayOfOrderStatus $statuses
     * @return \common\api\models\Soap\PutOrderStatusesResponse
     * @throws \SoapFault
     * @soap
     */
    public function putOrderStatuses($statusMapVersion, ArrayOfOrderStatus $statuses)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\PutOrderStatusesResponse();
            $response->setRequestStatuses($statuses);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Order\Order
     * @return \common\api\models\Soap\CreateOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function createOrder(Order $order)
    {
        \common\helpers\Translation::init('admin/main');
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\CreateOrderResponse();
                $response->setOrder($order);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param integer $orderId
     * @param integer $clientOrderId
     * @return \common\api\models\Soap\CreateOrderAcknowledgmentResponse
     * @throws \SoapFault
     * @soap
     */
    public function createOrderAcknowledgment($orderId, $clientOrderId)
    {
        \common\helpers\Translation::init('admin/main');
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\CreateOrderAcknowledgmentResponse();
            $response->setOrderId($orderId);
            $response->setClientOrderId($clientOrderId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Order\Order
     * @return \common\api\models\Soap\UpdateOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateOrder(Order $order)
    {
        \common\helpers\Translation::init('admin/main');
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\UpdateOrderResponse();
                $response->setOrder($order);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param  integer[]
     * @return \common\api\models\Soap\UpdateOrderAcknowledgmentResponse
     * @throws \SoapFault
     * @soap
     */
    public function updateOrderAcknowledgment($orderIds)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\UpdateOrderAcknowledgmentResponse();
            $response->setOrderIds($orderIds);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param  integer
     * @param  integer
     * @return \common\api\models\Soap\ResetSapOrderErrorResponse
     * @throws \SoapFault
     * @soap
     */
    public function resetSapError($orderId, $newState)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\ResetSapOrderErrorResponse([
                'orderId' => $orderId,
                'newState' => $newState,
            ]);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param integer[] $orderIds
     * @return \common\api\models\Soap\SendOrdersToSapResponse
     * @throws \SoapFault
     * @soap
     */
    public function sendOrdersToSAP($orderIds)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\SendOrdersToSapResponse([
                'orderIds' => $orderIds,
            ]);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Supplier\Supplier $supplier
     * @return \common\api\models\Soap\CreateSupplierResponse
     * @throws \SoapFault
     * @soap
     */
    public function createSupplier(Supplier $supplier)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\CreateSupplierResponse([
                'supplierIn' => $supplier,
            ]);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param int supplier id
     * @return \common\api\models\Soap\CreateSupplierResponse
     * @throws \SoapFault
     * @soap
     */
    public function getSupplier($supplierId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetSupplierResponse();
            $response->setSupplierId($supplierId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Order\Order
     * @return \common\api\models\Soap\CreateOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function createPurchaseOrder(Order $order)
    {
        \common\helpers\Translation::init('admin/main');
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\CreateOrderResponse([
                    'asPurchaseOrder' => true,
                ]);
                $response->setOrder($order);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Order\Order
     * @return \common\api\models\Soap\UpdatePurchaseOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function updatePurchaseOrder(Order $order)
    {
        \common\helpers\Translation::init('admin/main');
        if ( $this->authenticated ) {
            try{
                $response = new \common\api\models\Soap\UpdatePurchaseOrderResponse([
                    'asPurchaseOrder' => true,
                ]);
                $response->setOrder($order);
                $response->build();
            }catch (\yii\db\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '[DB] Server DB Error');
            }catch (\Exception $ex){
                \Yii::error('SERVER FAULT: '.'['.$ex->getCode().']'. $ex->getMessage()."\n".$ex->getTraceAsString()."\n",'soap_server');
                throw new \SoapFault('500', '['.$ex->getCode().']'. $ex->getMessage());
            }
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }


    /**
     * Get orders info
     * @param  \common\api\models\Soap\ArrayOfSearchConditions
     * @param  \common\api\models\Soap\Paging
     * @return \common\api\models\Soap\GetOrdersInfoResponse
     * @throws \SoapFault
     * @soap
     */
    public function getPurchaseOrdersInfo(ArrayOfSearchConditions $searchConditions, Paging $paging)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetOrdersInfoResponse([
                'paging' => $paging,
                'asPurchaseOrder' => true,
            ]);
            $response->setSearchCondition($searchConditions);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param integer $orderId
     * @return \common\api\models\Soap\GetOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function getPurchaseOrder($orderId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\GetOrderResponse([
                'asPurchaseOrder' => true,
            ]);
            $response->setOrderId($orderId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Soap\Products\PriceAndStockInfo $product_price_stock
     * @return \common\api\models\Soap\UpdatePriceAndStockInfoResponse
     * @throws \SoapFault
     * @soap
     */
    public function updatePriceAndStock(\common\api\models\Soap\Products\PriceAndStockInfo $product_price_stock)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\Soap\UpdatePriceAndStockInfoResponse();
            $response->setPriceAndStockInfo($product_price_stock);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }
}
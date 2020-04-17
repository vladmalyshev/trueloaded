<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;


use common\api\models\Customer\CustomerSearch;
use common\api\models\Order\Order;

class ApiFrontendController extends Sceleton
{

    public $enableCsrfValidation = false;
    public $authenticated = false;

    public function actionIndex()
    {
        $this->layout = false;
    }

    /**
     * Search Customer
     * @header
     * @param  \common\api\models\Customer\CustomerSearch
     * @return \common\api\models\GetCustomerResponse
     * @throws \SoapFault
     * @soap
     */
    public function searchCustomer(CustomerSearch $searchCondition)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetCustomerResponse();
            $response->setSearchCondition($searchCondition);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param integer $orderId
     * @return \common\api\models\GetOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function getOrder($orderId)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\GetOrderResponse();
            $response->setOrderId($orderId);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param \common\api\models\Order\Order
     * @return \common\api\models\CreateOrderResponse
     * @throws \SoapFault
     * @soap
     */
    public function createOrder(Order $order)
    {
        if ( $this->authenticated ) {
            $response = new \common\api\models\CreateOrderResponse();
            $response->setOrder($order);
            $response->build();
            return $response;
        }else{
            throw new \SoapFault('403','Wrong api key');
        }
    }

    /**
     * @param string $api_key
     * @return boolean result of auth
     * @soap
     */
    public function auth($api_key)
    {

        if ( !empty($api_key) ){
            $check_department_r = tep_db_query("SELECT platform_id FROM ".TABLE_PLATFORMS." WHERE 1=0");
            if ( tep_db_num_rows($check_department_r)>0 ) {
                $check_department = tep_db_fetch_array($check_department_r);
                $check_department['platform_id'];
                $this->authenticated = true;
            }
        }
        return true;
    }

    public function actions()
    {
        return [
            'service' => [
                'class' => 'subdee\soapserver\SoapAction',
                'classMap'=>array(
                    'ProductListResponse' => '\\common\\api\\models\\GetProductListResponse',
                    'GetCurrenciesResponse' => '\\common\\api\\models\\GetCurrenciesResponse',
                    'ProductRef' => '\\common\\api\\models\\Products\\ProductRef',
                    'InventoryRef' => '\\common\\api\\models\\Products\\InventoryRef',
                    'CustomerSearch' => '\\common\\api\\models\\Customer\\CustomerSearch',
                    'Order'=> '\\common\\api\\models\\Order\\Order',
                ),
            ],
        ];
    }

    protected function setMeta($id, $params)
    {

    }

}

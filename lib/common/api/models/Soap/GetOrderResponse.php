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


use common\api\models\Soap\Order\Order;
use common\api\SoapServer\ServerSession;

class GetOrderResponse extends SoapModel
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
     * @var \common\api\models\Soap\Order\Order
     * @soap
     */
    public $order;

    public $asPurchaseOrder = false;

    protected $orderObject;

    public function setOrderId($orderId)
    {
        $this->orderObject = new \common\classes\Order($orderId);

        if ( $this->asPurchaseOrder ) {

        }else {
            if (ServerSession::get()->getDepartmentId() && intval($this->orderObject->info['department_id']) != intval(ServerSession::get()->getDepartmentId())) {
                unset($this->orderObject);
            }
        }

        if ( !isset($this->orderObject) || !is_object($this->orderObject) || empty($this->orderObject->info['orders_id']) ) {
            $this->error('Order not found','ERROR_ORDER_NOT_FOUND');
            $this->status = 'ERROR';
        }
    }

    public function build()
    {
        if ( $this->status!='ERROR' && isset($this->orderObject) && is_object($this->orderObject) ) {
            $orderData = (array)$this->orderObject;
            $orderData['status_history'] = $this->orderObject->getStatusHistory();
            // {{ GiftWrapMessage extension
            try {
                if ($ext = \common\helpers\Acl::checkExtensionAllowed('GiftWrapMessage', 'allowed')) {
                    $orderData['info']['gift_wrap_message'] = $ext::instance()->getMessage($this->orderObject);
                }
            }catch (\Exception $ex){ }
            // }} GiftWrapMessage extension
            if ($ext = \common\helpers\Acl::checkExtensionAllowed('OrderMarkers', 'allowed')) {
                $orderMarker = $ext::getOrderMarkers($this->orderObject->order_id);
                if ( is_array($orderMarker) ) {
                    $orderData['info']['flags'] = $orderMarker['flags'];
                    $orderData['info']['markers'] = $orderMarker['markers'];
                }
            }
            $this->order = new Order($orderData);
            $this->order->build();
        }
        parent::build();
    }


}
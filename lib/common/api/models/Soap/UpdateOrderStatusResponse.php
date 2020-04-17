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


use backend\models\EP\Tools;
use common\helpers\Acl;
use common\helpers\Translation;
use common\models\Orders;

class UpdateOrderStatusResponse extends SoapModel
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
     * @var \common\api\models\Soap\Order\OrderStatusAppend
     */
    public $orderStatus;

    public function build()
    {
        $orderObj = Orders::findOne($this->orderStatus->order_id);
        if ( $orderObj && $orderObj->orders_id ) {
            $order_status_id = null;
            $tools = Tools::getInstance();
            if ( !empty($this->orderStatus->orders_status_name) ) {
                $statusIdFromName = $tools->lookupOrderStatusId($this->orderStatus->orders_status_name);
                if ( empty($statusIdFromName) ) {
                    $this->error('orders_status_name "'.$this->orderStatus->orders_status_name.'" not found','ORDER_STATUS_NOT_FOUND');
                }else{
                    $order_status_id = $statusIdFromName;
                }
            }
            if ( is_null($order_status_id) && !empty($this->orderStatus->orders_status_id) ) {
                if ( $tools->orderStatusIdExists($this->orderStatus->orders_status_id) ) {
                    $order_status_id = (int)$this->orderStatus->orders_status_id;
                }else{
                    $this->error('orders_status_id "'.$this->orderStatus->orders_status_id.'" not found','ORDER_STATUS_NOT_FOUND');
                }
            }
            if ( is_null($order_status_id) ){
                $order_status_id = $orderObj->orders_status;
            }
            if ( $this->status!='ERROR' ){
                $date_added = null;
                if ( !empty($this->orderStatus->date_added) && $this->orderStatus->date_added>2000 ) {
                    $date_added = date('Y-m-d H:i:s', strtotime($this->orderStatus->date_added));
                }

                $email_headers = '';

                $comments = $this->orderStatus->comment;

                $customer_notified = 0;
                if ($this->orderStatus->customer_notify){
                    $order_language_id = $orderObj->language_id;
                    $platform_config = \Yii::$app->get('platform')->config($orderObj->platform_id);
                    \Yii::$app->get('platform')->config($orderObj->platform_id)->constant_up();

                    Translation::init('admin/orders',$order_language_id);
                    Translation::init('admin/main',$order_language_id);

                    $notify_comments = '';
                    if ($comments) {
                        $notify_comments = trim(sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)) . "\n\n";
                    }

                    //$order = new \common\classes\Order($orderObj->orders_id);
                    $manager = \common\services\OrderManager::loadManager();
                    $manager->setModulesVisibility(['shop_order']);
                    $order = $manager->getOrderInstanceWithId('\common\classes\Order', $orderObj->orders_id);

                    // Workaround for 'add_br' => 'no'
                    //$email_params['ORDER_COMMENTS'] = str_replace(array("\r\n", "\n", "\r"), '<br>', $notify_comments);

                    $order->info['order_status'] = $order_status_id;
                    $customer_notified = $order->send_status_notify($notify_comments, []);
                }
                \common\helpers\Order::setStatus($orderObj->orders_id, $order_status_id, [
                    'comments' => $comments,
                    'smscomments' => '',
                    'date_added' => $date_added,
                    'customer_notified' => $customer_notified
                ], false);

                if ($TrustpilotClass = Acl::checkExtension('Trustpilot', 'onOrderUpdateEmail')) {
                    $TrustpilotClass::onOrderUpdateEmail((int)$orderObj->orders_id, '');
                }
            }
        }else{
            $this->error('Order '.$this->orderStatus->order_id.' not found', 'ORDER_NOT_FOUND');
        }

        parent::build();
    }


}
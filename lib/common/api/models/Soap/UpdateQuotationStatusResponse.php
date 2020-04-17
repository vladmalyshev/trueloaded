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
use common\models\QuoteOrders;

class UpdateQuotationStatusResponse extends SoapModel
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
     * @var \common\api\models\Soap\Quotation\QuotationStatusAppend
     */
    public $quotationStatus;

    public function build()
    {
        $orderObj = QuoteOrders::findOne($this->quotationStatus->quotation_id);
        if ( $orderObj && $orderObj->orders_id ) {
            $order_status_id = null;
            $tools = Tools::getInstance();
            if ( !empty($this->quotationStatus->orders_status_name) ) {
                $statusIdFromName = $tools->lookupOrderStatusId($this->quotationStatus->orders_status_name, \common\helpers\Quote::getStatusTypeId());
                if ( empty($statusIdFromName) ) {
                    $this->error('orders_status_name "'.$this->quotationStatus->orders_status_name.'" not found','ORDER_STATUS_NOT_FOUND');
                }else{
                    $order_status_id = $statusIdFromName;
                }
            }
            if ( is_null($order_status_id) && !empty($this->quotationStatus->orders_status_id) ) {
                if ( $tools->orderStatusIdExists($this->quotationStatus->orders_status_id, \common\helpers\Quote::getStatusTypeId()) ) {
                    $order_status_id = (int)$this->quotationStatus->orders_status_id;
                }else{
                    $this->error('orders_status_id "'.$this->quotationStatus->orders_status_id.'" not found','ORDER_STATUS_NOT_FOUND');
                }
            }
            if ( is_null($order_status_id) ){
                $order_status_id = $orderObj->orders_status;
            }
            if ( $this->status!='ERROR' ){

                if ($orderObj->orders_status != $order_status_id) {
                    if ($order_status_id == \common\helpers\Quote::getStatus('Processed')) {
                        \Yii::$app->get('platform')->config($orderObj->platform_id)->constant_up();
                        $quote = new \common\extensions\Quotations\Quotation($orderObj->orders_id);
                        $quote->createOrder();
                    }
                }

                $date_added = null;
                if ( !empty($this->quotationStatus->date_added) && $this->quotationStatus->date_added>2000 ) {
                    $date_added = date('Y-m-d H:i:s', strtotime($this->quotationStatus->date_added));
                }

                $comments = $this->quotationStatus->comment;

                $customer_notified = 0;
                if ($this->quotationStatus->customer_notify){
                    $order_language_id = $orderObj->language_id;
                    $platform_config = \Yii::$app->get('platform')->config($orderObj->platform_id);
                    \Yii::$app->get('platform')->config($orderObj->platform_id)->constant_up();

                    $eMail_store = $platform_config->const_value('STORE_NAME');
                    $eMail_address = $platform_config->const_value('STORE_OWNER_EMAIL_ADDRESS');
                    $eMail_store_owner = $platform_config->const_value('STORE_OWNER');

                    Translation::init('admin/quotation');
                    Translation::init('admin/orders',$order_language_id);
                    Translation::init('admin/main',$order_language_id);


                    $notify_comments = '';
                    if ($comments) {
                        $notify_comments = trim(sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)) . "\n\n";
                    }

                    // {{
                    $email_params = array();
                    $email_params['STORE_NAME'] = $eMail_store;
                    $email_params['ORDER_NUMBER'] = $orderObj->orders_id;
                    if ( function_exists('tep_catalog_href_link') ) {
                        $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_catalog_href_link('account/historyinfo', 'order_id=' . $orderObj->orders_id, 'SSL'/* , $store['store_url'] */));
                    }else{
                        $email_params['ORDER_INVOICE_URL'] = \common\helpers\Output::get_clickable_link(tep_href_link('account/historyinfo', 'order_id=' . $orderObj->orders_id, 'SSL'/* , $store['store_url'] */));
                    }
                    $email_params['ORDER_DATE_LONG'] = \common\helpers\Date::date_long($orderObj->date_purchased);
                    $email_params['ORDER_COMMENTS'] = $notify_comments;
                    // Workaround for 'add_br' => 'no'
                    //$email_params['ORDER_COMMENTS'] = str_replace(array("\r\n", "\n", "\r"), '<br>', $notify_comments);
                    $email_params['NEW_ORDER_STATUS'] =  \common\helpers\Order::get_order_status_name($order_status_id, $order_language_id);

                    $emailTemplate = '';
                    $ostatus = tep_db_fetch_array(tep_db_query("select orders_status_template from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int) $order_language_id . "' and orders_status_id='" . (int) $order_status_id . "'"));
                    if (!empty($ostatus['orders_status_template'])) {
                        $get_template_r = tep_db_query("select * from " . TABLE_EMAIL_TEMPLATES . " where email_templates_key='" . tep_db_input($ostatus['orders_status_template']) . "'");
                        if (tep_db_num_rows($get_template_r) > 0) {
                            $emailTemplate = $ostatus['orders_status_template'];
                        }
                    }

                    if(!empty($emailTemplate)) {
                        $emailDesignTemplate = '';
                        if ($order_status_id && $orderObj->platform_id) {
                            $emailDesignTemplate = \common\models\OrdersStatusToDesignTemplate::findOne([
                                'orders_status_id' => $order_status_id,
                                'platform_id' => $orderObj->platform_id,
                            ])->email_design_template;
                        }

                        list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template($emailTemplate, $email_params, $order_language_id, $orderObj->platform_id, -1, $emailDesignTemplate);

                        \common\helpers\Mail::send(
                            $orderObj->customers_name, $orderObj->customers_email_address,
                            $email_subject, $email_text,
                            $eMail_store_owner, $eMail_address
                        );
                        $customer_notified = '1';
                    }
                    // }}

                }

                $orderObj->setAttributes([
                    'orders_status' => (int)$order_status_id,
                    'last_modified' => new \yii\db\Expression('now()'),
                ],false);
                $orderObj->save(false);

                $status_history = new \common\models\QuoteOrdersStatusHistory();
                $status_history->detachBehavior('date_added');
                $status_history->loadDefaultValues();
                $status_history->setAttributes([
                    'orders_id' => (int)$orderObj->orders_id,
                    'orders_status_id' => (int)$order_status_id,
                    'date_added' => $date_added?$date_added:(new \yii\db\Expression('now()')),
                    'customer_notified' => $customer_notified,
                    'comments' => $comments,
                    'admin_id' => 0,
                ],false);
                $status_history->save(false);
            }
        }else{
            $this->error('Quotation '.$this->quotationStatus->quotation_id.' not found', 'ORDER_NOT_FOUND');
        }

        parent::build();
    }


}
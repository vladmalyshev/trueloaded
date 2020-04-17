<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Provider\Unleashed;

use backend\models\EP\Messages;
use backend\models\EP\Provider\DatasourceInterface;

class DownloadSO implements DatasourceInterface
{
    protected $total_count = 0;
    protected $row_count = 0;
    protected $process_list = [];
    protected $config = [];

    /**
     * @var Client
     */
    protected $client;
    protected $hasMoreData = false;
    protected $fetchPage = 1;

    protected $SOExists = null;
    protected $startDate = null;


    function __construct($config)
    {
        $this->config = $config;

    }

    public function getProgress()
    {
        if ( $this->total_count>0 ) {
            $percentDone = min(100, ($this->row_count / $this->total_count) * 100);
        }else{
            $percentDone = 100;
        }
        return number_format(  $percentDone,1,'.','');
    }

    public function prepareProcess(Messages $message)
    {
      $q = (new \yii\db\Query())->from('ep_holbi_soap_link_orders')
          ->where([
            'ep_directory_id' => (int)$this->config['directoryId'],
            'cfg_export_as' => 'order',
            'track_remote_order' => 1,
          ])
          ->min('date_exported')
          ;
      if ($q) {
        $this->fetchPage = 1;
        $this->hasMoreData = false;
        $this->client = new Client($this->config['client']['API_ID'],$this->config['client']['API_KEY']);
        $this->startDate = strtotime($q);
        //date time is different ;( - shift 12h
        if ($this->startDate) {
          $this->startDate -= 12*3600;
        }

        $this->process_list = [];
        $this->fetchPage($message);

        reset($this->process_list);

        $message->info("SO for check - ".$this->total_count);
      } else {
        $message->info("None SO for check ");
      }
    }

    protected function fetchPage(Messages $message)
    {

        $response = $this->client->get('SalesOrders/'.$this->fetchPage,  'orderStatus=Deleted,Placed,Open,Backordered,Completed&pageSize=500&modifiedSince=' . date('Y-m-d\TH:i:s', $this->startDate) )->send();
        $data = $response->getData();

        $Pagination = $data['Pagination'];
        $this->hasMoreData = (int)$Pagination['PageNumber'] < (int)$Pagination['NumberOfPages'];
        $this->total_count = (int)$Pagination['NumberOfItems'];
        foreach ($data['Items'] as $item){
            $this->process_list[$item['Guid']] = $item;
        }
        return true;
    }

    public function processRow(Messages $message)
    {
        $guid = key($this->process_list);

        $this->processSO( $guid, $this->process_list[$guid], $message);

        if (next($this->process_list)){
            return true;
        }elseif($this->hasMoreData){
            $this->process_list = [];
            $this->fetchPage++;
            $message->info('fetch page ' . $this->fetchPage);
            return $this->fetchPage($message);
        }
    }

    public function postProcess(Messages $message)
    {
      $message->info('Done ');
    }

    protected function processSO($guid, $data, Messages $message)
    {
        \Yii::$container->setSingleton('products', '\common\components\ProductsContainer');

        $platform_config = \Yii::$app->get('platform')->config(\common\classes\platform::defaultId());
        $platform_config->constant_up();

        $tmp = \common\helpers\Language::get_language_id($platform_config->getDefaultLanguage());
        $languages_id = $tmp['languages_id'];
        \Yii::$app->settings->set('languages_id', $languages_id);
        
//echo "#### input <PRE>" .print_r($data, 1) ."</PRE>";
            /*
 {
      "SalesOrderLines": [
        {
          "LineNumber": 1,
          "LineType": null,
          "Product": {
            "Guid": "b5c87595-39fe-41a7-9a5b-0a748ed1a3bf",
            "ProductCode": "rrrrrrrr",
            "ProductDescription": "tstqty"
          },
          "DueDate": "/Date(1563304415000)/",
          "OrderQuantity": 1,
          "UnitPrice": 1,
          "DiscountRate": 0,
          "LineTotal": 1,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "AverageLandedPriceAtTimeOfSale": 0,
          "TaxRate": 0,
          "LineTax": 0,
          "XeroTaxCode": "NONE",
          "BCUnitPrice": 1,
          "BCLineTotal": 1,
          "BCLineTax": 0,
          "LineTaxCode": null,
          "XeroSalesAccount": null,
          "SerialNumbers": null,
          "BatchNumbers": null,
          "Guid": "049eaf1a-4334-4cdb-9268-9c5db7bb5f52",
          "LastModifiedOn": "/Date(1563305758424)/"
        },
        {
          "LineNumber": 2,
          "LineType": null,
          "Product": {
            "Guid": "8ef1569a-b73a-4efa-8dce-0aae1cc8330d",
            "ProductCode": "CONV",
            "ProductDescription": "CONV fee"
          },
          "DueDate": "/Date(1563304415000)/",
          "OrderQuantity": 1,
          "UnitPrice": 21.5,
          "DiscountRate": 0,
          "LineTotal": 21.5,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "AverageLandedPriceAtTimeOfSale": 0,
          "TaxRate": 0,
          "LineTax": 0,
          "XeroTaxCode": "NONE",
          "BCUnitPrice": 21.5,
          "BCLineTotal": 21.5,
          "BCLineTax": 0,
          "LineTaxCode": null,
          "XeroSalesAccount": null,
          "SerialNumbers": null,
          "BatchNumbers": null,
          "Guid": "26a21457-ef5a-43ab-aba4-6efd8ac6a5f1",
          "LastModifiedOn": "/Date(1563305758456)/"
        },
        {
          "LineNumber": 3,
          "LineType": null,
          "Product": {
            "Guid": "a4553453-b3f7-443f-898c-d65c7113474d",
            "ProductCode": "SHIP",
            "ProductDescription": "Shipping"
          },
          "DueDate": "/Date(1563304415000)/",
          "OrderQuantity": 1,
          "UnitPrice": 20,
          "DiscountRate": 0,
          "LineTotal": 20,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "AverageLandedPriceAtTimeOfSale": 0,
          "TaxRate": 0,
          "LineTax": 0,
          "XeroTaxCode": "NONE",
          "BCUnitPrice": 20,
          "BCLineTotal": 20,
          "BCLineTax": 0,
          "LineTaxCode": null,
          "XeroSalesAccount": null,
          "SerialNumbers": null,
          "BatchNumbers": null,
          "Guid": "84a5f572-e62e-45af-8816-2514bb95e4df",
          "LastModifiedOn": "/Date(1563305758471)/"
        },
        {
          "LineNumber": 4,
          "LineType": null,
          "Product": {
            "Guid": "8ef1569a-b73a-4efa-8dce-0aae1cc8330d",
            "ProductCode": "CONV",
            "ProductDescription": "CONV fee"
          },
          "DueDate": "/Date(1563304415000)/",
          "OrderQuantity": 1,
          "UnitPrice": 5,
          "DiscountRate": 0,
          "LineTotal": 5,
          "Volume": null,
          "Weight": null,
          "Comments": null,
          "AverageLandedPriceAtTimeOfSale": 0,
          "TaxRate": 0,
          "LineTax": 0,
          "XeroTaxCode": "NONE",
          "BCUnitPrice": 5,
          "BCLineTotal": 5,
          "BCLineTax": 0,
          "LineTaxCode": null,
          "XeroSalesAccount": null,
          "SerialNumbers": null,
          "BatchNumbers": null,
          "Guid": "33126c1f-a3e2-4ff2-afe9-72532e21c90f",
          "LastModifiedOn": "/Date(1563305758471)/"
        }
      ],
      "OrderNumber": "SO-00000140",
      "OrderDate": "/Date(1563304415000)/",
      "RequiredDate": null,
      "CompletedDate": null,
      "OrderStatus": "Parked",
      "Customer": {
        "CustomerCode": "VL_TEST",
        "CustomerName": "Company Vlad Koshelev",
        "CurrencyId": 110,
        "Guid": "567cc0d4-e000-415d-ab35-c6481f9594d3",
        "LastModifiedOn": "/Date(1563388738322)/"
      },
      "CustomerRef": "305275",
      "Comments": null,
      "Warehouse": {
        "WarehouseCode": "MAIN",
        "WarehouseName": "Main Warehouse",
        "IsDefault": true,
        "StreetNo": null,
        "AddressLine1": null,
        "AddressLine2": null,
        "Suburb": null,
        "City": null,
        "Region": null,
        "Country": null,
        "PostCode": null,
        "PhoneNumber": null,
        "FaxNumber": null,
        "MobileNumber": null,
        "DDINumber": null,
        "ContactName": null,
        "Obsolete": false,
        "Guid": "a538ffec-a633-4b01-9be8-ce975e2e787c",
        "LastModifiedOn": "/Date(1562683560176)/"
      },
      "ReceivedDate": null,
      "DeliveryName": "Vlad Koshelev",
      "DeliveryStreetAddress": "CALLEVA PARK ALDERMASTON",
      "DeliveryStreetAddress2": null,
      "DeliverySuburb": "line 2 Reading",
      "DeliveryCity": "City",
      "DeliveryRegion": "Florida",
      "DeliveryCountry": "United Kingdom",
      "DeliveryPostCode": "RG78NN",
      "Currency": {
        "CurrencyCode": "NZD",
        "Description": "New Zealand, Dollars",
        "Guid": "7d1782be-03d7-4753-b8ee-2f77b41af22b",
        "LastModifiedOn": "/Date(1562535217383)/"
      },
      "ExchangeRate": 1,
      "DiscountRate": 0,
      "Tax": {
        "TaxCode": "NONE",
        "Description": null,
        "TaxRate": 0,
        "CanApplyToExpenses": false,
        "CanApplyToRevenue": false,
        "Obsolete": false,
        "Guid": "00000000-0000-0000-0000-000000000000",
        "LastModifiedOn": null
      },
      "TaxRate": 0,
      "XeroTaxCode": "NONE",
      "SubTotal": 47.5,
      "TaxTotal": 0,
      "Total": 47.5,
      "TotalVolume": 0,
      "TotalWeight": 0,
      "BCSubTotal": 47.5,
      "BCTaxTotal": 0,
      "BCTotal": 47.5,
      "PaymentDueDate": "/Date(1566259200000)/",
      "AllocateProduct": true,
      "SalesOrderGroup": null,
      "DeliveryMethod": null,
      "SalesPerson": null,
      "SendAccountingJournalOnly": false,
      "SourceId": null,
      "CreatedBy": "vladkoshelev@gmail.com",
      "CreatedOn": "/Date(1563305758253)/",
      "LastModifiedBy": "vladkoshelev@gmail.com",
      "Guid": "b0e1f7b1-2e77-4751-b694-156330575685",
      "LastModifiedOn": "/Date(1563305758627)/"
    },
       */

      static::findSO($data, $this->config['directoryId']);
      
        if (!empty($data['SOExists']['local_orders_id'])) {
          $oID = $data['SOExists']['local_orders_id'];
        }

        if ($data && is_array($data)) {

          if (empty($data['SOExists']['track_remote_order'])) {
//debug $message->info('do not track '. $oID . ' ' . $data['OrderNumber']);
            return true;
          }


          if ($data['SOExists']&&  isset($data['LastModifiedOn']) && $data['LastModifiedOn']>0 ) {
            $_last_modified = $data['LastModifiedOn'];
            $local = date_create($data['SOExists']['local_order_last_modified']);
            if (!empty($data['SOExists']['local_order_last_modified']) && $_last_modified <= $local) {
              //not changed
$message->info('not changed (time)'. $oID . ' ' . $data['OrderNumber'] . ' UL, local ' . $_last_modified->format('Y-m-d H:i:s') . "  <= " . $local->format('Y-m-d H:i:s') . ' ' . $data['SOExists']['local_order_last_modified'] );
              return true;
            }
          }

          if ($data['OrderStatus'] == 'Completed') {
            $soStatus = $this->config['order']['so_complete_status'];
          } elseif ($data['OrderStatus'] == 'Deleted') {
            $soStatus = $this->config['order']['so_cancel_status'];
          } else {
            $soStatus = null;
          }


          $oID = $data['SOExists']['local_orders_id'];

          $manager = \common\services\OrderManager::loadManager(new \common\classes\shopping_cart);
          $manager->setModulesVisibility(['admin']);
          $order = $manager->getOrderInstanceWithId('\common\classes\Order', $oID);

          if (!$order->getDetails()){
            tep_db_perform(
                'ep_holbi_soap_link_orders',
                [
                    'local_order_last_modified' => 'now()',
                    'track_remote_order' => 0,
                ],
                'update',
                "ep_directory_id = '" .(int)$this->config['directoryId'] . "' and local_orders_id = '" . $oID . "'  and cfg_export_as = 'order'"
            );
            $message->info('Local order not found '. $oID . ' ' . $data['OrderNumber']);
            return false;
          }

          $check_status = $order->getDetails();
          $order_updated = false;
          $comments =  $smscomments = '';
          $login_id = 0;

          try { 
            if (!empty($soStatus) && (($check_status['orders_status'] != $soStatus) || $comments != '' || $smscomments != '' || ($soStatus == DOWNLOADS_ORDERS_STATUS_UPDATED_VALUE))) {

              $comments =  'import from unleashed';

              if ($soStatus == DOWNLOADS_ORDERS_STATUS_UPDATED_VALUE) {
                  tep_db_query("update " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " set download_maxdays = '" . tep_db_input(\common\helpers\Configuration::get_configuration_key_value('DOWNLOAD_MAX_DAYS')) . "', download_count = '" . tep_db_input(\common\helpers\Configuration::get_configuration_key_value('DOWNLOAD_MAX_COUNT')) . "' where orders_id = '" . (int) $oID . "'");
              }

              if ($platform_config->const_value('MODULE_ORDER_TOTAL_BONUS_POINTS_STATUS') == 'true'){
                  $_statuses = explode(", ", $platform_config->const_value('MODULE_ORDER_TOTAL_BONUS_POINTS_ORDER_STATUS_ID'));

                  if (in_array($soStatus, $_statuses)){
                      $_total = tep_db_fetch_array(tep_db_query("select sum(op.bonus_points_cost * op.products_quantity) as bonus_points_cost from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o where o.orders_id =  op.orders_id and o.orders_id ='" . (int) $oID . "' and o.bonus_applied = 0"));
                      if ($_total){
                          \common\classes\Bonuses::updateCustomerBonuses([
                              'customers_id' => $check_status['customers_id'],
                              'prefix' => '+',
                              'credit_amount' => (float)$_total['bonus_points_cost'],
                              'comments' => 'Order #' . $oID,
                              'admin_id' => $login_id,
                          ]);
                      }
                  } else { //another status
                      $_total = tep_db_fetch_array(tep_db_query("select sum(op.bonus_points_cost * op.products_quantity) as bonus_points_cost from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o where o.orders_id =  op.orders_id and o.orders_id ='" . (int) $oID . "' and o.bonus_applied = 1"));
                      if ($_total){
                          \common\classes\Bonuses::updateCustomerBonuses([
                              'customers_id' => $check_status['customers_id'],
                              'prefix' => '-',
                              'credit_amount' => (float)$_total['bonus_points_cost'],
                              'comments' => 'Order #' . $oID,
                              'admin_id' => $login_id,
                          ]);
                      }
                  }
                  \common\classes\Bonuses::setAppliedBonuses($oID);
              }

              $customer_notified = '0';

              $isAlternativeBehaviour = false;
              /*
              $orderStatusRecord = \common\models\OrdersStatus::findOne(['orders_status_id' => (int)$soStatus]);
              if ($orderStatusRecord->order_evaluation_state_id == \common\helpers\Order::OES_PENDING) {
                  $isAlternativeBehaviour = (int)Yii::$app->request->post('evaluation_state_reset_cancel', 0);
              } elseif ($orderStatusRecord->order_evaluation_state_id == \common\helpers\Order::OES_PROCESSING) {
              } elseif ($orderStatusRecord->order_evaluation_state_id == \common\helpers\Order::OES_CANCELLED) {
                  $isAlternativeBehaviour = (int)Yii::$app->request->post('evaluation_state_restock', 0);
              } elseif ($orderStatusRecord->order_evaluation_state_id == \common\helpers\Order::OES_DISPATCHED) {
                  $isAlternativeBehaviour = (int)Yii::$app->request->post('evaluation_state_force', 0);
              } elseif ($orderStatusRecord->order_evaluation_state_id == \common\helpers\Order::OES_DELIVERED) {
                  $isAlternativeBehaviour = (int)Yii::$app->request->post('evaluation_state_force', 0);
              }
              unset($orderStatusRecord);*/
              if ($data['OrderStatus'] == 'Completed') {
                //force as stock update script could run later
                $isAlternativeBehaviour = \common\helpers\Order::OES_DISPATCHED;
              }
              \common\helpers\Order::setStatus($oID, $soStatus, [
                  'comments' => $comments,
                  'smscomments' => $smscomments,
                  'customer_notified' => $customer_notified,
                  'date_added' => (in_array($data['OrderStatus'], ['Completed']) && $data['CompletedDate']?$data['CompletedDate']->format('Y-m-d H:i:s'):null)
              ], false, $isAlternativeBehaviour);
  /*
              if (Acl::checkExtensionAllowed('SMS','showOnOrderPage') && $sms = Acl::checkExtension('SMS', 'sendSMS')){
                  $commentid = tep_db_insert_id();
                  $response = $sms::sendSMS($oID, $commentid);
                  if (is_array($response) && count($response)){
                      $messages[] = ['message' => $response['message'], 'messageType' => $response['messageType']];
                  }
              }

              if ($ext = Acl::checkExtension('ReferFriend', 'rf_release_reference')){
                  $ext::rf_release_reference((int)$oID);
              }
  */

              if (method_exists('\common\helpers\Coupon', 'credit_order_check_state')){
                  \common\helpers\Coupon::credit_order_check_state((int) $oID);
              }

              $order_updated = true;
            }



          } catch (\Exception $ex) {
            $message->info('Save SO Exception '. $ex->getMessage() . ' ' . $ex->getTraceAsString());
            return false;
          }

          if ($data['SOExists']) {
            tep_db_perform(
                'ep_holbi_soap_link_orders',
                [
                    'local_order_last_modified' => 'now()',
                    'track_remote_order' => (in_array($data['OrderStatus'], ['Completed', 'Deleted'])?0:1),
                ],
                'update',
                "ep_directory_id = '" .(int)$this->config['directoryId'] . "' and local_orders_id = '" . $order->order_id . "' and cfg_export_as = 'order'"
            );
            $message->info('[+] Updated ' . $order->order_id . ' from ' . $data['OrderNumber']);
          }

          $this->row_count++;
          return true;
        
      }
      return false;

    }

  public static function findSO(&$d, $directoryId) {
    if (!isset($d['SOExists']) ) {
      $q = new \yii\db\Query();
      $q->from('ep_holbi_soap_link_orders');
      $q->andWhere([
        'ep_directory_id' => (int)$directoryId,
        'remote_order_number' => $d['OrderNumber'],
        'cfg_export_as' => 'order',
      ]);

      if ($q->count()>0) {
        $d['SOExists'] = $q->one();
      } else {
        $d['SOExists'] = false;
      }
    }
  }


}
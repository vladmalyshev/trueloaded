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


use common\models\Orders;
use yii\helpers\ArrayHelper;

class AddOrderTrackingNumberResponse extends SoapModel
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
     * @var \common\api\models\Soap\Order\OrderTrackingNumberAppend
     */
    public $orderTracking;

    public function build()
    {
        $orderObj = Orders::findOne($this->orderTracking->order_id);
        if ( $orderObj && $orderObj->orders_id ) {
            try{
                if ( $this->status!='ERROR' ){
                    $append_tracking_number = $this->orderTracking->tracking_number;
                    if ( !empty($this->orderTracking->carrier) ) {
                        $append_tracking_number = $this->orderTracking->carrier.','.$this->orderTracking->tracking_number;
                    }
                    $order = new \common\classes\Order($orderObj->orders_id);

                    //$addTracking = \common\classes\OrderTrackingNumber::instanceFromString($append_tracking_number, $order->order_id);
                    $orders_products_tracked = [];
                    if ( isset($this->orderTracking->products) && is_object($this->orderTracking->products) && !empty($this->orderTracking->products->product) ){
                        $update_product_list = ArrayHelper::isIndexed($this->orderTracking->products->product)?$this->orderTracking->products->product:[$this->orderTracking->products->product];

                        $already_tracked = [];
                        foreach(\common\models\TrackingNumbersToOrdersProducts::find()
                            ->select(['orders_products_id', 'products_quantity'])
                            ->where(['orders_id'=>$orderObj->orders_id])
                            ->asArray()
                            ->all() as $tracked){
                            if ( !isset($already_tracked[$tracked['orders_products_id']]) ) $already_tracked[$tracked['orders_products_id']] = 0;
                            $already_tracked[$tracked['orders_products_id']] += $tracked['products_quantity'];
                        }
                        $id_search = [];
                        $need_to_track = [];
                        foreach ( $order->products as $_oproduct ){
                            $need_to_track[$_oproduct['orders_products_id']] = $_oproduct['qty'] - (isset($already_tracked[$_oproduct['orders_products_id']])?$already_tracked[$_oproduct['orders_products_id']]:0);
                            if ( !isset($id_search[$_oproduct['id']]) ) $id_search[$_oproduct['id']] = [];
                            $id_search[$_oproduct['id']][] = $_oproduct['orders_products_id'];
                        }

                        foreach( $update_product_list as $product ){
                            if ( !isset($id_search[$product->id]) ){
                                $this->error('Product id='.$product->id.' not found in order','TRACKING_PRODUCT_NOT_FOUND');
                                continue;
                            }
                            $allocate_qty = $product->package_quantity;
                            $max_allowed = 0;
                            foreach( $id_search[$product->id] as $_opid_track ){
                                if ( $need_to_track[$_opid_track]<=0 ) continue;
                                $max_allowed += $need_to_track[$_opid_track];
                                if ( $allocate_qty>=$need_to_track[$_opid_track] ){
                                    $orders_products_tracked[$_opid_track] += $need_to_track[$_opid_track];
                                    $allocate_qty -= $need_to_track[$_opid_track];
                                    $need_to_track[$_opid_track] = 0;
                                }else{
                                    $orders_products_tracked[$_opid_track] += $allocate_qty;
                                    $need_to_track[$_opid_track] -= $allocate_qty;
                                    $allocate_qty = 0;
                                }
                                if ( $allocate_qty<=0 ) break;
                            }

                            if ( $product->package_quantity>$max_allowed ) {
                                $this->error('Product id='.$product->id.' maximum allowed qty is '.$max_allowed,'TRACKING_PRODUCT_MAX_QTY');
                            }
                        }
                    }

                    if ( $this->status!='ERROR' ) {
                        $addTracking = new \common\classes\OrderTrackingNumber([
                            'orders_id' => $order->order_id,
                            'tracking_number' => $append_tracking_number,
                        ]);
                        $addTracking->dataLoaded();
                        if (count($orders_products_tracked) > 0) {
                            $addTracking->setOrderProducts($orders_products_tracked);
                        }

                        $order->addTrackingNumber($addTracking);

                        $notify_mail = isset($this->orderTracking->customer_notify) ? !!$this->orderTracking->customer_notify : true;
                        $order->saveTrackingNumbers($notify_mail, true);
                    }
                }
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
        }else{
            $this->error('Order '.$this->orderTracking->order_id.' not found', 'ORDER_NOT_FOUND');
        }

        parent::build();
    }


}
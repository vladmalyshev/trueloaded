<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Datasource;


use backend\models\EP\DatasourceBase;
use backend\models\EP\DataSources;
use backend\models\EP\Directory;

class Unleashed extends DatasourceBase
{
    protected $remoteData = [];
    
    public function getName()
    {
        return 'Unleashed';
    }

    public function orderView($orderId): bool {
      return true;
    }

    public static function getProviderList()
    {
        return [
            'Unleashed\\DownloadProducts' => [
                'group' => 'Unleashed',
                'name' => 'Import products',
                'class' => 'Provider\\Unleashed\\DownloadProducts',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadProductsStock' => [
                'group' => 'Unleashed',
                'name' => 'Import stock',
                'class' => 'Provider\\Unleashed\\DownloadProductsStock',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadBOM' => [
                'group' => 'Unleashed',
                'name' => 'Import BOM (bundles info)',
                'class' => 'Provider\\Unleashed\\DownloadBOM',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadProductsPrices' => [
                'group' => 'Unleashed',
                'name' => 'Import customer prices',
                'class' => 'Provider\\Unleashed\\DownloadProductsPrices',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadCustomers' => [
                'group' => 'Unleashed',
                'name' => 'Import customers',
                'class' => 'Provider\\Unleashed\\DownloadCustomers',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadSuppliers' => [
                'group' => 'Unleashed',
                'name' => 'Import suppliers',
                'class' => 'Provider\\Unleashed\\DownloadSuppliers',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadPO' => [
                'group' => 'Unleashed',
                'name' => 'Import PO',
                'class' => 'Provider\\Unleashed\\DownloadPO',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\ExportOrders' => [
                'group' => 'Unleashed',
                'name' => 'Export Orders',
                'class' => 'Provider\\Unleashed\\ExportOrders',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
            'Unleashed\\DownloadSO' => [
                'group' => 'Unleashed',
                'name' => 'Update SO status',
                'class' => 'Provider\\Unleashed\\DownloadSO',
                'export' =>[
                    'disableSelectFields' => true,
                ],
            ],
        ];
    }

    public function getViewTemplate()
    {
        return 'datasource/unleashed.tpl';
    }
    
    //copy from holbi-soap, unchanged, a lot of not used
    public function prepareConfigForView($configArray)
    {
        $orderStatusesSelect = [
            '*' => '[Any order status]',
        ];
        foreach( \common\helpers\Order::getStatusesGrouped(true) as $option){
            $orderStatusesSelect[$option['id']] = html_entity_decode($option['text'],null,'UTF-8');
        }
        $configArray['order']['export_statuses'] = [
            'items' => $orderStatusesSelect,
            'value' => $configArray['order']['export_statuses'],
            'options' => [
                'class' => 'form-control',
                'multiple' => true,
                'options' => [
                ],
            ],
        ];

        $currentGroup = '';
        $items = [];
        foreach( \common\helpers\Order::getStatusesGrouped(true) as $orderStatusVariant ) {
            if ( strpos($orderStatusVariant['id'],'group_')===0 ) {
                $currentGroup = $orderStatusVariant['text'];
                continue;
            }
            $items[$currentGroup][str_replace('status_','',$orderStatusVariant['id'])]
                = str_replace('&nbsp;','',$orderStatusVariant['text']);
        }
        $configArray['order']['export_success_status'] = [
            'items' => array_merge([0=>'Don\'t change order status'], $items),
            'value' => $configArray['order']['export_success_status'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];
        $configArray['order']['so_cancel_status'] = [
            'items' => array_merge([0=>'Don\'t change order status'], $items),
            'value' => $configArray['order']['so_cancel_status'],
        ];

        $configArray['order']['po_complete_status'] = [
            'items' => \common\helpers\PurchaseOrder::getStatusList(),
            'value' => $configArray['order']['po_complete_status'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];

        $configArray['order']['po_cancel_status'] = [
            'items' => \common\helpers\PurchaseOrder::getStatusList(),
            'value' => $configArray['order']['po_cancel_status'],
        ];

        $configArray['order']['so_complete_status'] = [
            'items' => array_merge(['0'=>'Leave current status'], $items),
            'value' => $configArray['order']['so_complete_status'],
        ];
        $this->initRemoteData($configArray);
        $configArray['status_map_local_to_server'] = $this->settings['status_map_local_to_server'];

        $configArray['order']['server_dispatched_statuses'] = [
            'fetched' => false,
            'items' => [],
            'value' => $configArray['order']['server_dispatched_statuses'],
            'options' => [
                'class' => 'form-control',
                'multiple' => true,
                'options' => [
                ],
            ],
        ];
        if (isset($this->remoteData['order_statuses'])) {
            $configArray['order']['server_dispatched_statuses']['fetched'] = true;
            $configArray['order']['server_dispatched_statuses']['items'] = array_merge($configArray['order']['server_dispatched_statuses']['items'],$this->remoteData['order_statuses']);
        }

        $configArray['StockIndicationVariants'] = [];
        foreach ( \common\classes\StockIndication::get_variants() as $variant) {
            $configArray['StockIndicationVariants'][$variant['id']] = $variant['text'];
        }

        $configArray['StockDeliveryTermsVariants'] = [];
        foreach ( \common\classes\StockIndication::get_delivery_terms() as $variant) {
            $configArray['StockDeliveryTermsVariants'][$variant['id']] = $variant['text'];
        }

        $configArray['ServerProductsRemovedVariants'] = [
            '' => 'No action',
            'remove' => 'Remove from catalog',
            'disable' => 'Set as inactive',
        ];

        $configArray['LocalShopOrderStatuses'] = \common\helpers\Order::getStatusesGrouped(true);

        //$configArray['ServerShopOrderStatuses'] = $this->remoteData['order_statuses_list'];
        $configArray['ServerShopOrderStatuses'] = [0=>''];
        if ( is_array($this->remoteData['order_statuses']) ) {
            $configArray['ServerShopOrderStatuses'] = array_merge($configArray['ServerShopOrderStatuses'], $this->remoteData['order_statuses']);
        }
        $configArray['ServerShopOrderStatusesWithCreate'] = [0=>''];
        if ( isset($this->remoteData['order_statuses_list']) && is_array($this->remoteData['order_statuses_list']) ) {
            foreach ($this->remoteData['order_statuses_list'] as $serverStatus) {

                if (strpos($serverStatus['id'], 'group') === 0) {
                    $group_name = $serverStatus['name'];
                    continue;
                }
                if (!isset($configArray['ServerShopOrderStatusesWithCreate'][$group_name])) {
                    $configArray['ServerShopOrderStatusesWithCreate'][$group_name]['create_in_' . $serverStatus['group_id']] = '[Create in group "' . $group_name . '"]';
                }
                $configArray['ServerShopOrderStatusesWithCreate'][$group_name][$serverStatus['_id']] = $serverStatus['name'];
            }
        }

        $configArray['order']['disable_order_update'] = [
            'items' => [
                //'0'=>'Enabled',
                '1'=>'Disabled, accept server tracking number',
                '2'=>'Disabled, accept server tracking number, order statuses',
            ],
            'value' => $configArray['order']['disable_order_update'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];
        $configArray['order']['export_as'] = [
            'items' => [
                'order'=>'Order - Order',
                'po_order'=>'Order - Purchase Order',
            ],
            'value' => $configArray['order']['export_as'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];

        $configArray['customer']['ab_sync_server'] = [
            'items' => [
                'replace'=>'Replace',
                'append'=>'Append',
                'disable' => 'Disable customer synchronization'
            ],
            'value' => $configArray['customer']['ab_sync_server'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];
        $configArray['customer']['ab_sync_client'] = [
            'items' => [
                'replace'=>'Replace',
                'append'=>'Append',
                'disable' => 'Disable customer synchronization'
            ],
            'value' => $configArray['customer']['ab_sync_client'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];

        $configArray['products']['images_copy'] = [
            'items' => [
                'external'=>'Link (external images)',
                'copy'=>'Local copy',
            ],
            'value' => $configArray['products']['images_copy'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];

        $configArray['products']['default_tax_class'] = isset($configArray['products']['default_tax_class'])?$configArray['products']['default_tax_class']:'';
        $configArray['products']['create_on_client'] = isset($configArray['products']['create_on_client'])?!!$configArray['products']['create_on_client']:true;
        $configArray['products']['create_on_server'] = isset($configArray['products']['create_on_server'])?!!$configArray['products']['create_on_server']:false;
        $configArray['products']['update_on_client'] = isset($configArray['products']['update_on_client'])?!!$configArray['products']['update_on_client']:true;
        $configArray['products']['update_on_server'] = isset($configArray['products']['update_on_server'])?!!$configArray['products']['update_on_server']:false;

        foreach ( static::productFlags() as $flagInfo ) {
            if ( $flagInfo['server'] && isset($configArray['products'][$flagInfo['server']])) {
                $configArray['products']['custom_flags'] = true;
                break;
            }
            if ( $flagInfo['client'] && isset($configArray['products'][$flagInfo['client']])) {
                $configArray['products']['custom_flags'] = true;
                break;
            }
        }

        $configArray['order']['export_surcharge'] = [
            'items' => [
                'product'=>'Products',
                'charge'=>'Сharges',
            ],
            'value' => $configArray['order']['export_surcharge'],
            'options' => [
                'class' => 'form-control',
                'options' => [
                ],
            ],
        ];
        $configArray['order']['fee_product'] = isset($configArray['order']['fee_product'])?$configArray['order']['fee_product']:'';
        $configArray['order']['shipping_product'] = isset($configArray['order']['shipping_product'])?$configArray['order']['shipping_product']:'';

        $installed_shipping_modules = [];

        foreach (\common\classes\platform::getList(false) as $platform) {
            $modules = \common\helpers\Modules::shippingModules($platform['id']);
            $installed_shipping_modules =
                \yii\helpers\ArrayHelper::map(
                    $modules,
                    function ($m, $defaultValue) { return $m->code;},
                    'title'
                    );
            foreach ($modules as $code => $class) {
              if (method_exists($class, 'getAllMethodsKeys')) {

                $tmp = $class->getAllMethodsKeys($platform['id']);
                if (is_array($tmp)) {
                  foreach ($tmp as $key => $value) {
                    $installed_shipping_modules[$code . '_' . $key] = $installed_shipping_modules[$code] . ': ' . $value;
                  }
                  unset($installed_shipping_modules[$code]);
                  ksort($installed_shipping_modules);
                }
              }
            }
        }

        if (is_array($installed_shipping_modules) &&  count($installed_shipping_modules)) {
          foreach ($installed_shipping_modules as $code => $title) {
            $configArray['shipping'][$code] = [
                'title' => $title,
                'value' => $configArray['shipping'][$code],
            ];

          }
        }


        return parent::prepareConfigForView($configArray);
    }

    public function initRemoteData($configArray)
    {
      //not exists :( return parent::initRemoteData($configArray);
    }

    public static function productFlags()
    {
      return [];
    }
}
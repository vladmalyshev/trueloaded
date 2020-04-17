<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Products;


use backend\models\EP\Tools;
use common\api\models\Soap\SoapModel;


class WarehouseStock extends SoapModel
{

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $warehouse_id;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $warehouse_name;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $suppliers_id;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $suppliers_name;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $location_id;

    /**
     * @var string {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $location_name;

    public $products_id;
    public $prid;
    public $products_model;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $products_quantity;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $allocated_stock_quantity;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $temporary_stock_quantity;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $warehouse_stock_quantity;

    /**
     * @var integer {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $ordered_stock_quantity;

    public function __construct(array $config = [])
    {
        if ( !empty($config['warehouse_id']) && empty($config['warehouse_name']) ){
            $config['warehouse_name'] = Tools::getInstance()->getWarehouseName($config['warehouse_id']);
        }
        if ( !empty($config['suppliers_id']) && empty($config['suppliers_name']) ){
            $config['suppliers_name'] = Tools::getInstance()->getSupplierName($config['suppliers_id']);
        }
        if ( !empty($config['location_id']) && empty($config['location_name']) ){
            $config['location_name'] = Tools::getInstance()->getWarehouseLocationName($config['warehouse_id'], $config['location_id']);
        }

        parent::__construct($config);
    }

    public function inputValidate()
    {
        $validate = [];

        if (is_numeric($this->products_quantity)){
            $validate['warehouse_stock_products_quantity'] = ['code'=>'WARNING', 'text'=>'warehouse_stock.products_quantity readonly'];
            unset($this->products_quantity);
        }
        if (is_numeric($this->allocated_stock_quantity)){
            $validate['warehouse_stock_allocated_stock_quantity'] = ['code'=>'WARNING', 'text'=>'warehouse_stock.allocated_stock_quantity readonly'];
            unset($this->allocated_stock_quantity);
        }
        if (is_numeric($this->temporary_stock_quantity)){
            $validate['warehouse_stock_temporary_stock_quantity'] = ['code'=>'WARNING', 'text'=>'warehouse_stock.temporary_stock_quantity readonly'];
            unset($this->temporary_stock_quantity);
        }
        if (is_numeric($this->ordered_stock_quantity)){
            $validate['warehouse_stock_ordered_stock_quantity'] = ['code'=>'WARNING', 'text'=>'warehouse_stock.ordered_stock_quantity readonly'];
            unset($this->ordered_stock_quantity);
        }
        if ( $this->warehouse_name && !$this->warehouse_id ){
            $warehouse_id = Tools::getInstance()->getWarehouseIdByName($this->warehouse_name);
            if ( empty($warehouse_id) ){
                $validate['warehouse_stock_warehouse_name_'.$this->warehouse_name] = ['code'=>'ERROR', 'text'=>'warehouse_stock.warehouse_name "'.$this->warehouse_name.'" not found'];
            }else{
                $this->warehouse_id = $warehouse_id;
            }
        }
        unset($this->warehouse_name);

        if ( $this->suppliers_name && !$this->suppliers_id ){
            $suppliers_id = Tools::getInstance()->getSupplierIdByName($this->suppliers_name);
            if ( empty($suppliers_id) ){
                $validate['warehouse_stock_suppliers_name_'.$this->suppliers_name] = ['code'=>'ERROR', 'text'=>'warehouse_stock.suppliers_name "'.$this->suppliers_name.'" not found'];
            }else{
                $this->suppliers_id = $suppliers_id;
            }
        }
        unset($this->suppliers_name);

        if ( $this->location_name && !$this->location_id ){
            $location_id = Tools::getInstance()->getWarehouseLocationByName($this->warehouse_id, $this->location_name);
            if ( is_null($location_id) ){
                $validate['warehouse_stock_location_name_'.$this->location_name] = ['code'=>'ERROR', 'text'=>'warehouse_stock.location_name "'.$this->location_name.'" not found'];
            }else{
                $this->location_id = $location_id;
            }
        }
        unset($this->location_name);

        return $validate;
    }

}
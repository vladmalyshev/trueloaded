<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\LinkedProducts\ImportExport;


use backend\models\EP\Exception;
use backend\models\EP\Messages;
use backend\models\EP\Provider\ExportInterface;
use backend\models\EP\Provider\ImportInterface;
use backend\models\EP\Provider\ProviderAbstract;
use backend\models\EP\Tools;
use common\extensions\LinkedProducts\models\ProductsLinkedChildren;
use common\helpers\Html;

class ImportExport extends ProviderAbstract implements ImportInterface, ExportInterface
{

    protected $entry_counter = 0;
    protected $fields = array();

    protected $data = array();
    protected $export_query;

    protected $import_defaults = [
        'insert_new' => 'insert',
    ];

    protected $loadedLinked = [];

    function init() {
        parent::init();
        $this->initFields();
    }

    public static function allowed()
    {
        return true;
    }

    protected function initFields()
    {
        $this->fields = [];
/*        $this->fields[] = array(
            'prefix' =>'p',
            'name' => 'products_id',
            'value' => 'Linked Parent Product ID',
        );
        $this->fields[] = array(
            'prefix' =>'bs',
            'name' => 'linked_product_id',
            'value' => 'Linked Child Product ID',
        );*/

        $this->fields[] = array(
            'set'=> 'set_products_id',
            'name' => 'parent_products_model',
            'value' => 'Linked Parent Product Model',
            'is_key'=> true,
            'is_key_part'=> true,
            'calculated' => true,
        );
        $this->fields[] = array(
            'name' => 'products_name',
            'value' => 'Parent Product Name',
            'calculated'=> true,
        );
        $this->fields[] = array(
            'name' => 'child_products_model',
            'set'=> 'set_products_id',
            'value' => 'Linked Child Product Model',
            'is_key_part'=> true,
            'calculated' => true,
        );

        $this->fields[] = array(
            'prefix' =>'bs',
            'name' => 'linked_product_quantity',
            'value' => 'Linked Product Quantity',
        );
        $this->fields[] = array(
            'prefix' =>'bs',
            'name' => 'sort_order',
            'value' => 'Sort order',
        );
    }

    public function importOptions()
    {

        $insert_new = isset($this->import_config['insert_new'])?$this->import_config['insert_new']:$this->import_defaults['insert_new'];

        return '
<div class="widget box box-no-shadow">
    <div class="widget-header"><h4>Import options</h4></div>
    <div class="widget-content">
        <div class="row form-group">
            <div class="col-md-6"><label>Insert and update</label></div>
            <div class="col-md-6">'.Html::dropDownList('import_config[insert_new]',$insert_new, ['insert'=>'Yes, insert new and update','replace'=>'No, replace existing'],['class'=>'form-control']).'</div>
        </div>
    </div>
</div>
        ';

    }

    public function prepareExport($useColumns, $filter)
    {
        $this->buildSources($useColumns);

        $main_source = $this->main_source;

        $filter_sql = '';
        if ( is_array($filter) ) {
            if ( isset($filter['category_id']) && $filter['category_id']>0 ) {
                $categories = array((int)$filter['category_id']);
                \common\helpers\Categories::get_subcategories($categories, $categories[0]);
                $filter_sql .= "AND p.products_id IN(SELECT products_id FROM ".TABLE_PRODUCTS_TO_CATEGORIES." WHERE categories_id IN('".implode("','",$categories)."')) ";
            }
        }

        $main_sql =
            "SELECT {$main_source['select']} ".
            " bs.parent_product_id, pd.products_name, ".
            " p.products_model AS parent_products_model, ".
            " p1.products_model AS child_products_model ".
            "FROM " . TABLE_PRODUCTS . " p ".
            "  left join ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.products_id=p.products_id AND pd.platform_id='".\common\classes\platform::defaultId()."' AND pd.language_id='".\common\classes\language::defaultId()."' AND pd.department_id=0 ".
            "  left join products_linked_children bs on p.products_id=bs.parent_product_id " .
            "  left join " . TABLE_PRODUCTS . " p1 on bs.linked_product_id=p1.products_id " .
            "WHERE 1 {$filter_sql} ".
            "order by IF(bs.parent_product_id IS NULL, 1, 0), p.products_model, bs.sort_order ".
            "/*LIMIT 3*/";

        $this->export_query = tep_db_query( $main_sql );
    }

    public function exportRow()
    {
        $this->data = tep_db_fetch_array($this->export_query);
        if ( !is_array($this->data) ) return $this->data;

        $data_sources = $this->data_sources;
        $export_columns = $this->export_columns;

        foreach( $export_columns as $db_key=>$export ) {
            if( isset( $export['get'] ) && method_exists($this, $export['get']) ) {
                $this->data[$db_key] = call_user_func_array(array($this, $export['get']), array($export, $this->data['products_id']));
            }
        }
        return $this->data;
    }

    public function postProcess(Messages $message)
    {
        $insert_new = (is_array($this->import_config) && isset($this->import_config['insert_new']))?$this->import_config['insert_new']:$this->import_defaults['insert_new'];
        $replace_existing = $insert_new=='replace';
        if ( $replace_existing ){
            // clean loaded and not updated
            foreach ($this->loadedLinked as $modelCollection)
            {
                foreach ($modelCollection as $model)
                {
                    $model->delete();
                }
            }
        }
        unset($this->loadedLinked);
        $this->loadedLinked = [];

        $message->info('Processed '.$this->entry_counter.' products');
        $message->info('Done.');

        Tools::getInstance()->done('linked_import');
    }

    public function importRow($data, Messages $message)
    {
        $this->buildSources( array_keys($data) );

        $insert_new = (is_array($this->import_config) && isset($this->import_config['insert_new']))?$this->import_config['insert_new']:$this->import_defaults['insert_new'];
        $replace_existing = $insert_new=='replace';

        $export_columns = $this->export_columns;
        $main_source = $this->main_source;
        $data_sources = $this->data_sources;
        $file_primary_column = $this->file_primary_column;
        $file_primary_columns = $this->file_primary_columns;
        $file_primary_columns[$file_primary_column] = $file_primary_column;

        $this->data = $data;

        if (!is_array($file_primary_columns ) || count($file_primary_columns )==0 ) {
            throw new Exception('Primary key(s) not found in file ' . print_r($file_primary_columns,1));
        } elseif ( count(array_diff(array_keys($file_primary_columns), array_keys($data)))>0 ) {
            throw new Exception('Primary key(s) missed in file ' . print_r(array_diff(array_keys($file_primary_columns), array_keys($data)),1));
        }

        if ( empty($this->data[$file_primary_column]) ){
            $message->info('Empty "'.$export_columns[$file_primary_column]['value'].'" column. Row skipped');
            return false;
        }

        $this->data['parent_product_id'] = Tools::getInstance()->lookupProductId($this->data[$file_primary_column]);
        if ( empty($this->data['parent_product_id']) ) {
            $message->info('Product '.$export_columns[$file_primary_column]['value'].'="'.$this->data[$file_primary_column].'" not found. Row skipped');
            return false;
        }

        if ( !isset($this->loadedLinked[$this->data['parent_product_id']]) ) {
            $this->loadedLinked[$this->data['parent_product_id']] =
                ProductsLinkedChildren::find()
                    ->where(['parent_product_id'=>$this->data['parent_product_id']])
                    ->all();
            $this->entry_counter++;
        }
        if ( empty($this->data['child_products_model']) ) return true; // continue process

        $this->data['linked_product_id'] = Tools::getInstance()->lookupProductId($this->data['child_products_model']);
        if (empty($this->data['linked_product_id'])) {
            $message->info('Product ' . $export_columns['child_products_model']['value'] . '="' . $this->data['child_products_model'] . '" not found. Row skipped');
            return false;
        }
        // find match in loaded collection for update
        $updated = false;
        foreach ($this->loadedLinked[$this->data['parent_product_id']] as $_idx=>$ProductsLinkedChild){
            /**
             * @var ProductsLinkedChildren $ProductsLinkedChild
             */
            if ( $ProductsLinkedChild->linked_product_id==$this->data['linked_product_id'] ) {
                $update_data_array = $this->data;
                unset($update_data_array['parent_product_id']);
                unset($update_data_array['linked_product_id']);
                $ProductsLinkedChild->setAttributes($update_data_array, false);
                $ProductsLinkedChild->save(false);
                $updated = true;
                unset($this->loadedLinked[$this->data['parent_product_id']][$_idx]);
            }
        }

        if ( !$updated && !empty($this->data['parent_product_id']) && !empty($this->data['linked_product_id']) ) {
            $appendModel = new ProductsLinkedChildren();
            $appendModel->loadDefaultValues();
            $this->data['linked_product_quantity'] = max((isset($this->data['linked_product_quantity'])?intval($this->data['linked_product_quantity']):1),1);
            $appendModel->setAttributes($this->data, false);
            $appendModel->save(false);
        }

        return true;
    }

    function set_products_id( $field_data, $db_column, Messages $message) {
        $ret = 0; // actually fills in $this->data
        $field_value = $this->data[$field_data['name']];
        if (!empty(trim($field_value))) {
            $ret = Tools::getInstance()->lookupProductId($field_value);
        }
        if ((int)$ret==0) {
            $message->info('Incorrect product: ' . $field_value . ' => ' . $db_column);
        } else {
            $this->data[$db_column] = $ret;
        }
    }

}
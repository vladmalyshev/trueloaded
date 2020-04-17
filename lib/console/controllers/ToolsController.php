<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace console\controllers;

use backend\models\EP\DatasourceBase;
use backend\models\EP\DataSources;
use backend\models\EP\Directory;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Tools
 */
class ToolsController extends Controller
{

    public function actionUpdateCurrenciesRate()
    {
        $messages = \common\helpers\Currencies::batchRateUpdate(
            \common\models\Currencies::find()
        );
        foreach ( $messages as $message ){
            Console::output($message['message']);
        }
    }

    /**
     * remove unused product images
     */
    public function actionGcImages()
    {
        \common\classes\Images::collectGarbage();
    }

    /**
     * remove product watermark and reference images
     */
    public function actionCleanImageReference()
    {
        \common\classes\Images::cleanImageReference();
    }

    public function actionRemoveBrokenImageSymlinks()
    {
        exec("cd ".escapeshellarg(\common\classes\Images::getFSCatalogImagesPath())." && find . -type l -! -exec test -e {} \; -print | xargs rm",$xxx);
    }

    /**
     * rename product images
     */
    public function actionRegenerateImages()
    {
        $images_count = tep_db_fetch_array(tep_db_query(
            "SELECT COUNT(products_images_id) AS total FROM ".TABLE_PRODUCTS_IMAGES
        ));
        if ( $images_count['total']==0 ) return;
        Console::startProgress(0,$images_count['total']);
        $processedCount = 0;
        $pageSize = 1000;
        $page = 0;
        do {
            $page++;
            $get_images_page_r = tep_db_query(
                "SELECT products_id, products_images_id " .
                "FROM " . TABLE_PRODUCTS_IMAGES . " " .
                "ORDER BY products_id, products_images_id " .
                "LIMIT " . $pageSize*($page-1) . ",{$pageSize}"
            );
            if (tep_db_num_rows($get_images_page_r) > 0) {
                while ($image = tep_db_fetch_array($get_images_page_r)) {
                    \common\classes\Images::normalizeImageFiles($image['products_id'], $image['products_images_id']);
                    Console::updateProgress(++$processedCount,$images_count['total']);
                }
            } else {
                break;
            }
        }while(true);
        Console::endProgress(true);
        echo "Done.\n";
    }

    public function actionInstallDatasource($configJsonFile, $wsdl='', $api_key='')
    {
        $runConfig = false;
        if ( !is_file($configJsonFile) ) {
            Console::error("{$configJsonFile} not found");
            exit(-1);
        }else{
            $runConfig = json_decode(file_get_contents($configJsonFile),true);
        }
        if ( !is_array($runConfig) ) {
            Console::error("{$configJsonFile} not valid");
            exit(-1);
        }

        if ( !empty($wsdl) ){
            $runConfig['settings']['client']['wsdl_location'] = $wsdl;
        }
        if ( !empty($api_key) ){
            $runConfig['settings']['client']['department_api_key'] = $api_key;
        }

        DataSources::add(array(
            'name' => $runConfig['code'],
            'class' => $runConfig['class'],
        ));

        /**
         * @var $DataSource DatasourceBase
         */
        $DataSource = DataSources::getByName($runConfig['code']);
        try{
            $DataSource->update($runConfig['settings']);
            $directory = Directory::getDatasourceRoot($runConfig['code']);
            if ( $directory ) {
                tep_db_query("UPDATE ".TABLE_EP_DIRECTORIES." SET directory_config='".tep_db_input(json_encode($runConfig['directories']['datasource']))."' WHERE directory_id='".intval($directory->directory_id)."' ");
                Directory::getAll(true);
                $directory = Directory::findById($directory->directory_id);
                $directory->applyDirectoryConfig();
                $processedDir = $directory->getProcessedDirectory();
                if ( $processedDir ) {
                    tep_db_query("UPDATE ".TABLE_EP_DIRECTORIES." SET directory_config='".tep_db_input(json_encode($runConfig['directories']['processed']))."' WHERE directory_id='".intval($processedDir->directory_id)."' ");
                }
            }
            Console::output("OK");
        }catch (\Exception $ex){
            Console::error($ex->getMessage());
        }
    }

    public function actionBackupThemes($archiveName)
    {
        $tableList = [
            'themes',
            'themes_settings',
            'themes_settings_backups',
            'themes_steps',
            'themes_styles',
            'themes_styles_backups',
            'themes_styles_cache',
            'themes_styles_tmp',
            'design_backups',
            'design_boxes',
            'design_boxes_backups',
            'design_boxes_settings',
            'design_boxes_settings_backups',
            'design_boxes_settings_tmp',
            'design_boxes_tmp',
        ];

        exec(
            "mysqldump ".
            "-u".escapeshellarg(DB_SERVER_USERNAME)." ".
            "-h".escapeshellarg(DB_SERVER)." ".
            (DB_SERVER_PASSWORD?("-p".escapeshellarg(DB_SERVER_PASSWORD)." "):'').
            " ".escapeshellarg(DB_DATABASE)." ".
            implode(' ',$tableList).
            " | gzip > themes/tables.sql.gz "
            ,$x);
        exec(
            "tar -cpzf ".escapeshellarg($archiveName).".tgz themes lib/frontend/themes && rm themes/tables.sql.gz"
            ,$x);

    }

    /**
     * merge customers with the same email address. Guest, inactive accounts as low priority. Params: Skip (don't merge) guest accounts, preferred platform id
     * @param bool $skipGuests default true
     * @param int $platformId default 0 (no preferred platform)
     */
    public function actionMergeDuplicateCustomers($skipGuests = true, $platformId = 0)
    {
      $addressIgnoreFelds = ['address_book_id', 'customers_id', '_api_time_modified', 'entry_company_vat_date', 'entry_company_vat_status'];
      $ab = new \common\models\AddressBook();
      $abFields = array_keys($ab->getAttributes());
      $abFields = array_diff($abFields, $addressIgnoreFelds);
      $abSelect = [];
      foreach ($abFields as $field) {
        if (!empty(trim($field))) {
          $abSelect[$field] = new \yii\db\Expression('ifnull(' . $field . ', "")');
        }
      }
      

      $q = \common\models\Customers::find()
          ->addSelect('customers_email_address')
          ->addGroupBy('customers_email_address')
          ->having((new \yii\db\Expression('count(distinct customers_id)>1')));
      if ($skipGuests) {
        $q->andWhere(['opc_temp_account' => 0]);
      }
      $cnt = $q->count();
      if ($cnt == 0 ) {
        echo  "cnt  $cnt\n";
        return;
      }

      /** @var \common\extensions\MergeCustomers\MergeCustomers $ext */
      if ($ext = \common\helpers\Acl::checkExtension('MergeCustomers', 'doMerge')) {
        Console::startProgress(0,$cnt);
        $processedCount = 0;
        $list = $q->asArray()->column();
        //$list = ['vkoshelev@holbi.co.uk'];

        foreach ($list as $email) {
          //link to 1st all other
          //so 1st - active, not guest, from preferred platform
          $q = \common\models\Customers::find()
              ->addSelect('customers_id, customers_email_address')
              ->andWhere(['customers_email_address' => $email])
              ->addOrderBy('customers_status desc, opc_temp_account ')
              ;
          if ($skipGuests) {
            $q->andWhere(['opc_temp_account' => 0]);
          }
          if ((int)$platformId!=0) {
            $q->addOrderBy((new \yii\db\Expression('platform_id!=' . (int)$platformId)))
                ;
          }
          $q->addOrderBy('groups_id desc');
          
          $cs = $q->asArray()->all();

          $toC = array_shift($cs);
          foreach ($cs as $fromC) {
            $field = 'address_book_id';
            $qA = \common\models\AddressBook::find()->andWhere(['customers_id' => [$toC['customers_id'], $fromC['customers_id']] ])
                ->addSelect($abSelect)
                ->addSelect(new \yii\db\Expression('min(' . $field . ') as ' . $field))
                ->addGroupBy(array_keys($abSelect))
                ->asArray()
                ->indexBy('address_book_id')
                ;
//echo "\n" . $qA->createCommand()->rawSql;
            $toA = $qA->all();
            $aIds = array_keys($toA);
//echo "#### <PRE>"  . __FILE__ .':' . __LINE__ . ' ' . print_r($aIds, 1) ."</PRE>"; die;

            $r = $ext::doMerge($toC['customers_id'], $fromC['customers_id'], $aIds, $aIds);
          }

          \Yii::warning($email . ' deleted ' . count($cs) . ' addresses after ' . count($aIds), 'MERGED Customer');



          Console::updateProgress(++$processedCount, $cnt);
        }

        Console::endProgress(true);

      }
      echo "Done.\n";
    }
    
}
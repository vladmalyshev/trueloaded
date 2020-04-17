<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;

class Google_baseController extends Sceleton {
    
    public $acl = ['BOX_HEADING_SEO', 'BOX_MARKETING_GOOGLE_BASE'];

    private static function getGtinColumns()
    {
        return [
            'upc' => [
                'value' => 'upc',
                'label' => TEXT_UPC,
            ],
            'ean' => [
                'value' => 'ean',
                'label' => TEXT_EAN,
            ],
            'isbn' => [
                'value' => 'isbn',
                'label' => rtrim(TEXT_ISBN,':'),
            ],
        ];
    }

    private static function getColumns()
    {
        return array(
            "title",
            "description",
            "link",
            "thumbnail",
            "image_link",
            "id",
            "expiration_date",
            "price",
            "sale_price",
            "sale_price_effective_date",
            //"currency",
            "shipping",
            "model_number",
            "quantity",
            "weight",
            "condition",
            "brand",
            "mpn",
            "gtin",
            "availability",
            "product_type",
            "google_product_category",
        );  
    }
    
    public function actionIndex() {
        global $language;

        if (file_exists(DIR_WS_LANGUAGES . $language . '/' . 'google_base.php')) {
            include(DIR_WS_LANGUAGES . $language . '/' . 'google_base.php');
        }
        $this->selectedMenu = array('seo_cms', 'google_base');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('google_base/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;

        $google_config = [];
        $platformList = \common\classes\platform::getList(false);
        
        $feed_urls = [];
        
        $columns_config = [];
        $platform_code = [];
        $currentPlatformId = Yii::$app->get('platform')->config()->getId();

        $products_platform_items = [];
        foreach( \common\classes\platform::getList(true) as $platformInfo ) {
            $products_platform_items[$platformInfo['id']] = $platformInfo['text'];
        }
        
        foreach( $platformList as $platformInfo ) {
            $platformId = $platformInfo['id'];
            $platformConfig = Yii::$app->get('platform')->config($platformId);

            $columns_config = [];
            foreach(explode(',', $platformConfig->const_value('GOOGLE_BASE_FIELD_LIST', implode(',', self::getColumns()))) as $selected_column){
                $columns_config[$selected_column] = 1;
            }

            $platform_code = '';
            $google_shop_platform = '';
            if ( preg_match('/^(.*):(\d+)$/', $platformConfig->const_value('GOOGLE_BASE_SHOP_PLATFORM_ID'), $match) ) {
                $platform_code = $match[1];
                $google_shop_platform = $match[2];
            }

            $gtin_selected = explode(',',$platformConfig->const_value('GOOGLE_BASE_GTIN_CONFIG','upc,ean'));
            $gtin_config = [];
            $gtinListCopy = static::getGtinColumns();
            foreach ($gtin_selected as $gtin_select){
                $checked_column_flag = true;
                if (substr($gtin_select,0,1)=='-') $checked_column_flag = false;
                $gtin_select = ltrim($gtin_select,'-');
                $gtin_config[$gtin_select] = $gtinListCopy[$gtin_select];
                $gtin_config[$gtin_select]['checked'] = $checked_column_flag;
                unset($gtinListCopy[$gtin_select]);
            }
            $gtin_config = $gtin_config+$gtinListCopy;

            $google_config[ $platformId ] = [
                'feed_url' => tep_catalog_href_link('google_base.php'),
                'platform_code' => $platform_code,
                'google_shop_platform' => $google_shop_platform,
                'column_config' => $columns_config,
                'products_platform' => $platformConfig->const_value('GOOGLE_BASE_PRODUCTS_PLATFORM_ID'),
                'gtin_config' => $gtin_config,
            ];
        }
        Yii::$app->get('platform')->config($currentPlatformId);

        return $this->render('index', [
            'platforms' => $platformList,
            'isMultiPlatform' => \common\classes\platform::isMulti(),
            'selected_platform_id' => \common\classes\platform::defaultId(),
            'fields' => self::getColumns(),
            'products_platform_items' => $products_platform_items,
            'google_config' => $google_config,
        ]);
    }
    
    public function actionSave() {
        \common\helpers\Translation::init('admin/google_base');
        
        $google_config = Yii::$app->request->post('google_config');
        
        $platformList = \common\classes\platform::getList();
        foreach( $platformList as $platformInfo ) {
            $platformId = $platformInfo['id'];
            if ( !is_array($google_config) || !isset($google_config[$platformId]) || !is_array($google_config[$platformId])) continue;

            $platformConfig = Yii::$app->get('platform')->getConfig($platformId);
                
            $google_platform_config = $google_config[$platformId];

            if ( !is_array($google_platform_config['column_config']) ) $google_platform_config['column_config'] = [];
            $platformConfig->setConfigValue('GOOGLE_BASE_FIELD_LIST', implode(',',array_keys($google_platform_config['column_config'])));

            $gtin_config = [];
            foreach ($google_platform_config['gtin_config_sort'] as $gtin_column) {
                $gtin_config[] = (isset($google_platform_config['gtin_config'][$gtin_column])?'':'-').$gtin_column;
            }
            $platformConfig->setConfigValue('GOOGLE_BASE_GTIN_CONFIG', implode(',',$gtin_config));
            
            $platformConfig->setConfigValue('GOOGLE_BASE_PRODUCTS_PLATFORM_ID', $google_platform_config['products_platform']);
            $platformConfig->setConfigValue('GOOGLE_BASE_SHOP_PLATFORM_ID', $google_platform_config['platform_code'].':'.$google_platform_config['google_shop_platform']);
        }
        
        $message = TEXT_UPDATE_WARNING;
        $messageType = 'success';
?>
        <div class="popup-box-wrap pop-mess">
            <div class="around-pop-up"></div>
            <div class="popup-box">
                <div class="pop-up-close pop-up-close-alert"></div>
                <div class="pop-up-content">
                    <div class="popup-heading"><?php echo TEXT_NOTIFIC; ?></div>
                    <div class="popup-content pop-mess-cont pop-mess-cont-<?= $messageType?>">
                        <?= $message?>
                    </div>  
                </div>     
                <div class="noti-btn noti-btn-ok">
                    <div><span class="btn btn-primary"><?php echo TEXT_BTN_OK;?></span></div>
                </div>
            </div>  
            <script>
                //$('body').scrollTop(0);
                $('.popup-box-wrap.pop-mess').css('top',(window.scrollY+200)+'px');
                $('.pop-mess .pop-up-close-alert, .noti-btn .btn').click(function(){
                    $(this).parents('.pop-mess').remove();
                });
            </script>
        </div>
        
<?php
    }

}

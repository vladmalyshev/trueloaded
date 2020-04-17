<?php

/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes;

use common\classes\Images;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use yii\helpers\Html;

class Banner extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();

        \frontend\design\Info::addJsData(['widgets' => [
            $this->id => [ 'lazyLoad' => $this->settings[0]['lazy_load']]
        ]]);
    }

    public function run()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $banners = array();
        $banner_speed = '';

        if (!$this->settings[0]['banners_group'] && $this->settings[0]['params'])
            $this->settings[0]['banners_group'] = $this->settings[0]['params'];
        $_platform_id = \common\classes\platform::currentId();

        if ($this->params['banner_group']) {
            $this->settings[0]['banners_group'] = $this->params['banner_group'];
        }

        $use_phys_platform = true;
        if ($ext = \common\helpers\Acl::checkExtension('AdditionalPlatforms', 'allowed')){
            if ($ext::checkSattelite()){
                $s_platform_id = $ext::getSatteliteId();
                $sql = tep_db_query("select * from " . TABLE_BANNERS_TO_PLATFORM .
                        " nb2p, " . TABLE_BANNERS_NEW . " nb, " . TABLE_BANNERS_LANGUAGES .
                        " bl where bl.banners_id = nb.banners_id AND bl.language_id='" . $languages_id . "' AND nb2p.banners_id=nb.banners_id "
                        . "AND nb2p.platform_id='" . $s_platform_id . "'  and nb.banners_group = '" . $this->settings[0]['banners_group'] . "' "
                        ." and (nb.expires_date is null or nb.expires_date >= now()) and (nb.date_scheduled is null or nb.date_scheduled <= now()) "
                        . "AND (bl.banners_html_text!='' OR bl.banners_image!='' OR bl.banners_url)
                         order by " . ($this->settings[0]['banners_type'] == 'random' ? " RAND() LIMIT 1" : " nb.sort_order"));
                if (tep_db_num_rows($sql)){
                    $use_phys_platform = false;
                    $_platform_id = $s_platform_id;
                }
            }
        }
        if ($use_phys_platform){
            $sql = tep_db_query("select * from " . TABLE_BANNERS_TO_PLATFORM . " nb2p, " . TABLE_BANNERS_NEW . " nb, " . TABLE_BANNERS_LANGUAGES .
                    " bl where bl.banners_id = nb.banners_id AND bl.language_id='" . $languages_id . "' AND nb2p.banners_id=nb.banners_id AND"
                    . " nb2p.platform_id='" . $_platform_id . "'  and nb.banners_group = '" . $this->settings[0]['banners_group'] . "' AND"
                    ." (nb.expires_date is null or nb.expires_date >= now()) and (nb.date_scheduled is null or nb.date_scheduled <= now()) and "
                    . "(bl.banners_html_text!='' OR bl.banners_image!='' OR bl.banners_url) order by " . ($this->settings[0]['banners_type'] == 'random' ? " RAND() LIMIT 1" : " nb.sort_order"));
        }
        if ($this->settings[0]['banners_type'] == 'random') {
            $this->settings[0]['banners_type'] = 'banner';
        }
        
        if (!$this->settings[0]['banners_type']) {
            $type_sql_query = tep_db_query("select nb.banner_type from " . TABLE_BANNERS_TO_PLATFORM . " nb2p, " . TABLE_BANNERS_NEW . " nb where nb.banners_group = '" . $this->settings[0]['banners_group'] . "' AND nb2p.banners_id=nb.banners_id AND nb2p.platform_id='" . $_platform_id . "' limit 1");
            if (tep_db_num_rows($type_sql_query) > 0) {
                $type_sql = tep_db_fetch_array($type_sql_query);
                $type_array = $type_sql['banner_type'];
                $type_exp = explode(';', $type_array);
                if (isset($type_exp) && !empty($type_exp)) {
                    $this->settings[0]['banners_type'] = $type_exp[0];
                } else {
                    $this->settings[0]['banners_type'] = $type_sql['banner_type'];
                }
            }
        }

        $bannerGroupSettings = [];
        $groupSettings = \common\models\BannersGroups::find()
            ->where([ 'banners_group' => $this->settings[0]['banners_group']])
            ->asArray()
            ->all();
        if (is_array($groupSettings)) {
            foreach ($groupSettings as $group) {
                $bannerGroupSettings[$group['image_width']] = $group;
            }
        }

        while ($row = tep_db_fetch_array($sql)) {
            $row['is_banners_image_valid'] = (!empty($row['banners_image']) && is_file(Images::getFSCatalogImagesPath().$row['banners_image']));


            $row['banners_image'] = \common\classes\Images::getWebp($row['banners_image']);
            $row['banners_image_url'] = \common\helpers\Media::getAlias('@webCatalogImages/'.$row['banners_image']);
            if ($row['svg'] && $row['banner_display'] == 3) {
                $row['image'] = $row['svg'];
            } else {
                $row['image'] = self::bannerGroupImages(
                    $bannerGroupSettings,
                    $row['banners_id'],
                    $row['banners_image'],
                    $row['banners_title'],
                    $this->settings[0]['lazy_load']
                );
            }

            $row['text_position'] = self::textPosition($row['text_position']);

            $banners[] = $row;
        }

        if (count($banners) == 0) return '';

        return IncludeTpl::widget(['file' => 'boxes/banner.tpl', 'params' => [
            'id' => $this->id,
            'banners' => $banners,
            'banner_type' => $this->settings[0]['banners_type'],
            'banner_speed' => $banner_speed,
            'settings' => array_merge(self::$defaultSettings, $this->settings[0])
        ]]);
    }

    public static function bannerGroupImages ($bannerGroupSettings, $bannersId, $mainImage, $title = '', $lazyLoad = false){
        $languages_id = \Yii::$app->settings->get('languages_id');

        $naBanner = \frontend\design\Info::themeSetting('base64_banner');
        if (!$naBanner) {
            $naBanner = \frontend\design\Info::themeSetting('na_banner', 'hide');
        }

        $bannerGroupImages = \common\models\BannersGroupsImages::find()
            ->where([ 'banners_id' => $bannersId, 'language_id' => $languages_id])
            ->asArray()
            ->all();

        $sources = '';
        foreach ($bannerGroupImages as $image){
            if (!$bannerGroupSettings[$image['image_width']]) continue;

            $image['image'] = \common\classes\Images::getWebp($image['image']);
            $image['image'] = \common\helpers\Media::getAlias('@webCatalogImages/' . $image['image']);

            $media = '';
            if ($bannerGroupSettings[$image['image_width']]['width_from']) {
                $media .= '(min-width: ' . $bannerGroupSettings[$image['image_width']]['width_from'] . 'px)';
            }
            if ($bannerGroupSettings[$image['image_width']]['width_from'] && $bannerGroupSettings[$image['image_width']]['width_to']) {
                $media .= ' and ';
            }
            if ($bannerGroupSettings[$image['image_width']]['width_to']) {
                $media .= '(max-width: ' . $bannerGroupSettings[$image['image_width']]['width_to'] . 'px)';
            }

            $sourcesAttr = [
                'srcset' => $image['image'],
                'media' => $media,
            ];
            if ($lazyLoad) {
                $sourcesAttr['data-srcset'] = $image['image'];
                $sourcesAttr['srcset'] = $naBanner;
                $sourcesAttr['class'] = 'na-banner';
            }
            $sources .= Html::tag('source', '', $sourcesAttr);
        }

        $mainImage = \common\classes\Images::getWebp($mainImage);
        $mainImage = \common\helpers\Media::getAlias('@webCatalogImages/' . $mainImage);

        $attributes = [
            'title' => $title
        ];
        if ($lazyLoad) {
            $attributes['data-src'] = $mainImage;
            $attributes['class'] = 'na-banner';
            $mainImage = $naBanner;
        }

        $img = Html::img($mainImage, $attributes);
        return Html::tag('picture', $sources . $img);
    }

    public static $defaultSettings = [
        'effect' => 'random',
        'slices' => 15,
        'boxCols' => 8,
        'boxRows' => 4,
        'animSpeed' => 500,
        'pauseTime' => 3000,
        'directionNav' => 'true',
        'controlNav' => 'true',
        'controlNavThumbs' => 'false',
        'pauseOnHover' => 'true',
        'manualAdvance' => 'false',
    ];

    public static function textPosition ($key) {

        switch ($key) {
            case '0':
                return 'top-left';
            case '1':
                return 'top-center';
            case '2':
                return 'top-right';
            case '3':
                return 'middle-left';
            case '4':
                return 'middle-center';
            case '5':
                return 'middle-right';
            case '6':
                return 'bottom-left';
            case '7':
                return 'bottom-center';
            case '8':
                return 'bottom-right';
        }
        return '';
    }

}

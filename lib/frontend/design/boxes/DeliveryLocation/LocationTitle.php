<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\DeliveryLocation;

use common\helpers\Seo;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use yii\helpers\ArrayHelper;

class LocationTitle extends Widget
{

    public $file;
    public $params;
    public $content;
    public $settings;

    public $show_title = '';
    public $h1 = '';

    public function init()
    {

        $this->h1 = defined('HEAD_H1_TAG_DELIVERY_LOCATION') && tep_not_null(HEAD_H1_TAG_DELIVERY_LOCATION) ? HEAD_H1_TAG_DELIVERY_LOCATION : '';
        if ( isset(Yii::$app->controller->deliveryLocationData) && is_array(Yii::$app->controller->deliveryLocationData) ) {
            $this->show_title = Yii::$app->controller->deliveryLocationData['location_name'];

            if ($this->h1) {
                $info = Yii::$app->controller->deliveryLocationData;
                $this->h1 = str_replace('##LOCATION_NAME##', $info['name'], $this->h1);
                if ( stripos($this->h1,'##BREADCRUMB##')!==false ) {
                    if ( count($info['parents'])==0 && Seo::getMetaDefaultBreadcrumb('delivery-location') ) {
                        $this->h1 = str_ireplace('##BREADCRUMB##', Seo::getMetaDefaultBreadcrumb('delivery-location'), $this->h1);
                    }else {
                        $this->h1 = str_ireplace('##BREADCRUMB##', implode(' / ',ArrayHelper::getColumn($info['parents'],'location_name')), $this->h1);
                    }
                }
                if ( stripos($this->h1,'##LOCATION_TITLE_TAG##')!==false ) {
                    $this->h1 = str_ireplace('##LOCATION_TITLE_TAG##', $info['location_meta_title'], $this->h1);
                }
                if ( stripos($this->h1,'##LOCATION_DESCRIPTION_TAG##')!==false ) {
                    $this->h1 = str_ireplace('##LOCATION_DESCRIPTION_TAG##', $info['location_meta_description'], $this->h1);
                }
            }
        }
        parent::init();
    }

    public function run()
    {

        if ( $this->show_title || $this->h1 ) {
            return IncludeTpl::widget(['file' => 'boxes/delivery-location/location-title.tpl', 'params' => [
                'title' => $this->show_title,
                'h1' => $this->h1,
                'settings' => $this->settings,
            ]]);
        }
        return '';
    }
}
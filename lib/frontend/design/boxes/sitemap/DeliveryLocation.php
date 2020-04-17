<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\sitemap;
use Yii;
use yii\base\Widget;
use common\helpers\SeoDeliveryLocation;

class DeliveryLocation extends Widget {
    
    public $file;
    public $params;
    public $settings;
    public $isAjax;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $perParent = [];
        foreach(SeoDeliveryLocation::getTree(\common\classes\platform::currentId()) as $location){
            if ( !isset($perParent[ $location['parent_id'] ]) ) $perParent[ $location['parent_id'] ] = [];
            $perParent[ $location['parent_id'] ][] = $location;
        }

        $location = '';
        if ( count($perParent)>0 ) {
            $makeTreeLeaf = null;
            $makeTreeLeaf = function($currentParent) use($perParent, &$makeTreeLeaf){
                $location = '<ul>';
                foreach ($perParent[$currentParent] as $item) {
                    $location .= '<li>';
                    if ( isset($perParent[$item['id']]) ) {
                        $location .= '<span class="parrent_cat"><a href="'.Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$item['id']]).'">'.$item['name'].'</a></span>';
                        $location .= $makeTreeLeaf($item['id']);
                    }else{
                        $location .= '<a title="'.\common\helpers\Output::output_string(sprintf(TEXT_DELIVERY_LOCATION_LINK_TITLE_FORMAT,$item['name'])).'" href="'.Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$item['id']]).'">'.$item['name'].'</a>';
                    }
                    $location .= '</li>';
                }
                $location .= '</ul>';
                return $location;
            };
            $currentParent = 0;
            $location = $makeTreeLeaf($currentParent);
        }

        return $location;
    }
    
}
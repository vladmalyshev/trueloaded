<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\controllers;


use app\components\MetaCannonical;
use common\classes\Images;
use common\helpers\SeoDeliveryLocation;
use frontend\design\Info;

class DeliveryLocationController extends Sceleton
{

    public $deliveryLocationData = false;

    public function actionIndex()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');
        $id = \Yii::$app->request->get('id',0);

        if (\frontend\design\Info::isAdmin() && \Yii::$app->request->get('not_root',0)) {
            $notParent = \common\models\SeoDeliveryLocation::find()->where(['not like', 'parent_id', 0])->asArray()->one();
            $id = $notParent['id'];
        }

        $location_featured_list = [];

        $isRoot = \common\models\SeoDeliveryLocation::find()->where(['id'=>$id, 'parent_id'=>0])->count()>0;

        $get_r = tep_db_query(
            "SELECT fd.id, fdt.location_name ".
            "FROM ".TABLE_SEO_DELIVERY_LOCATION." fd ".
            " INNER JOIN ".TABLE_SEO_DELIVERY_LOCATION_TEXT." fdt ON fdt.id=fd.id ".
            "WHERE fd.platform_id='".(int)\common\classes\platform::currentId()."' AND fdt.language_id='".(int)$languages_id."' ".
            " AND fd.show_on_index=1 ".
            " AND fd.status=1 ".
            "ORDER BY fdt.location_name"
        );
        if ( tep_db_num_rows($get_r)>0 ) {
            while( $data = tep_db_fetch_array($get_r) ) {
                $location_featured_list[] = array_merge(
                    SeoDeliveryLocation::getItem($data['id'], (int)\common\classes\platform::currentId(), " AND fd.status=1 "),
                    [
                        'href' => \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$data['id']]),
                        'title' => sprintf(TEXT_DELIVERY_LOCATION_LINK_TITLE_FORMAT,$data['location_name']),
                    ]
                );
            }
        }

        $get_r = tep_db_query(
            "SELECT fd.id, fdt.location_name, ".
            " fd.featured, c.countries_iso_code_2 AS iso2 ".
            "FROM ".TABLE_SEO_DELIVERY_LOCATION." fd ".
            " INNER JOIN ".TABLE_SEO_DELIVERY_LOCATION_TEXT." fdt ON fdt.id=fd.id ".
            " LEFT JOIN ".TABLE_COUNTRIES." c ON c.countries_id=fd.international_country_id AND c.language_id='".(int)$languages_id."' ".
            "WHERE fd.platform_id='".(int)\common\classes\platform::currentId()."' AND fdt.language_id='".(int)$languages_id."' ".
            " AND fd.parent_id='".(int)$id."' ".
            " AND fd.status=1 ".
            "ORDER BY fd.date_added DESC, fdt.location_name"
        );
        $location_list = [];

        if (tep_db_num_rows($get_r)>0){
            while( $data = tep_db_fetch_array($get_r) ) {
                $data['href'] = \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$data['id']]);
                $data['title'] = sprintf(TEXT_DELIVERY_LOCATION_LINK_TITLE_FORMAT,$data['location_name']);

                $location_list[] = $data;
                if ( $data['featured'] || (int)$id==0 || $isRoot ) {
                    $locationItem = SeoDeliveryLocation::getItem($data['id'], (int)\common\classes\platform::currentId(), " AND fd.status=1 ");
                    if ( is_array($locationItem) ) {
                        $location_featured_list[] = array_merge(
                            $locationItem,
                            [
                                'href' => \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION, 'id' => $data['id']]),
                                'title' => sprintf(TEXT_DELIVERY_LOCATION_LINK_TITLE_FORMAT, $data['location_name']),
                            ]
                        );
                    }
                }
            }
        }

        $current_data = SeoDeliveryLocation::getItem($id,(int)\common\classes\platform::currentId(), " AND fd.status=1 AND fd.parent_id!=0 ", true);
        if ( is_array($current_data) ) {
            MetaCannonical::instance()->setCannonical([FILENAME_DELIVERY_LOCATION,'id'=>(int)$id]);

            $current_data['locations_list'] = $location_list;
            $current_data['location_featured_list'] = $location_featured_list;

            $level = ($current_data['parent_id']>0?count(\common\helpers\SeoDeliveryLocation::getParents(\common\classes\platform::currentId(),$current_data['parent_id'],true))+1:1);
            $current_data = \common\helpers\SeoDeliveryLocation::applyTemplate(
                \common\classes\platform::currentId(),
                $languages_id,
                $level,
                $current_data['parent_id'],
                $current_data
            );

            $current_data['parents'] = [];
            if ( $current_data['parent_id']>0 ) {
                $parents = \common\helpers\SeoDeliveryLocation::getParents(\common\classes\platform::currentId(),$current_data['id'],false);
                foreach( $parents as $idx=>$parent ) {
                    $parents[$idx]['href'] = \Yii::$app->urlManager->createUrl([FILENAME_DELIVERY_LOCATION,'id'=>$parent['id']]);
                }
                $current_data['parents'] = $parents;
            }

            $use_product_set_id = SeoDeliveryLocation::getProductSetGroupId($id);
            $current_data['use_product_set_id'] = $use_product_set_id;

            if ( empty($current_data['image_headline_alt']) ) {
                $current_data['image_headline_alt'] = $current_data['location_name'];
            }

            $this->deliveryLocationData = $current_data;
        }else{
            $this->deliveryLocationData = [];
            if ( count($location_featured_list)>0 ) {
                $this->deliveryLocationData['location_top_list'] = $location_featured_list;
            }else
            if ( count($location_list)>0 ) {
                $this->deliveryLocationData['locations_list'] = $location_list;
            }
        }

        if ( $id && !$isRoot ) {
            return $this->render('index.tpl', []);
        }
        return $this->render('index_default.tpl', []);
    }

}
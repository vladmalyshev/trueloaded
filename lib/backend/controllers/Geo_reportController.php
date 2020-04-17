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

class Geo_reportController extends Sceleton {

    public $acl = ['BOX_HEADING_REPORTS', 'BOX_REPORTS_GEO'];

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);
    }

    public function actionIndex() {
        $currencies = Yii::$container->get('currencies');

        $this->selectedMenu = array('reports', 'geo_report');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('geo_report/index'), 'title' => HEADING_TITLE);

        $this->view->headingTitle = HEADING_TITLE;

        $this->view->filter = new \stdClass();

        $this->view->filter->mapskey = \common\components\GoogleTools::instance()->getMapProvider()->getMapsKey();

        $origPlace = array(0, 0, 2);
        $country_info = tep_db_fetch_array(tep_db_query("select ab.entry_country_id from " . TABLE_PLATFORMS_ADDRESS_BOOK . " ab inner join " . TABLE_PLATFORMS . " p on p.is_default = 1 and p.platform_id = ab.platform_id where ab.is_default = 1"));
        $_country = (int) STORE_COUNTRY;
        if ($country_info) {
            $_country = $country_info['entry_country_id'];
        }
        if (defined('STORE_COUNTRY') && (int) STORE_COUNTRY > 0) {
            $origPlace = tep_db_fetch_array(tep_db_query("select lat, lng, zoom from " . TABLE_COUNTRIES . " where countries_id = '" . (int) $_country . "'"));
        }
        $params['origPlace'] = $origPlace;

        $this->view->filter->platforms_id = [];
        if (isset($_GET['platforms_id'])) {
            if (is_array($_GET['platforms_id'])) {
                $this->view->filter->platforms_id = $_GET['platforms_id'];
            } else {
                $this->view->filter->platforms_id[] = $_GET['platforms_id'];
            }
        }

        $limitQuery = \common\models\Orders::find()->select([ 'min_date' => 'min(date_purchased)', 'max_date' => 'max(date_purchased)'])
                ->where(['and', ['not in', 'lat', [0, 9999]], ['not in', 'lng', [0, 9999]]]);
        if (count($this->view->filter->platforms_id)){
            $limitQuery->andWhere(['in', 'platform_id', $this->view->filter->platforms_id]);
        }
        $limit = $limitQuery->asArray()->one();
        $params['filter'] = '';
        $params['min_date'] = $params['max_date'] = 0;
        $params['min'] = date("U", strtotime(" -1 month"));
        $params['max'] = date("U");
        if ($limit) {
            $params['min_date'] = $limit['min_date']!=null ? strtotime($limit['min_date']): $params['min'];
            $params['filter'] = $this->renderAjax('filter', $params);
        }

        $platforms = \common\classes\platform::getList(false, true);
        $params['platforms'] = \yii\helpers\ArrayHelper::map($platforms, 'id', 'text');

        if (Yii::$app->request->isAjax) {
            echo json_encode($params);
            exit();
        } else {
            return $this->render('index', $params);
        }
    }

    public function actionLocations() {
        $platforms_id = Yii::$app->request->get('platfroms_id', []);
        $from = Yii::$app->request->get('from', 0);
        $to = Yii::$app->request->get('to', 0);

        $orders_query = tep_db_query("select o.lat, o.lng, o.delivery_address_format_id, o.delivery_street_address, o.delivery_suburb, o.delivery_city, o.delivery_postcode, o.delivery_state, o.delivery_country from " . TABLE_ORDERS . " o where o.date_purchased >= FROM_UNIXTIME('" . tep_db_input($from) . "') and o.date_purchased <= FROM_UNIXTIME('" . tep_db_input($to) . "') and o.lat not in (0 , 9999) and o.lng not in (0 , 9999) " . (count($platforms_id) > 0 ? " and platform_id in (" . implode(",", $platforms_id) . ")" : ""));
        $founded = [];
        while ($orders = tep_db_fetch_array($orders_query)) {
            $orders['title'] = $orders['delivery_street_address'] . "\n" . $orders['delivery_city'] . "\n" . $orders['delivery_postcode'] . "\n" . $orders['delivery_state'] . "\n" . $orders['delivery_country'];
            $founded[] = $orders;
        }

        echo json_encode(array(
            'founded' => $founded,
            'orders_count' => count($founded),
        ));
        exit();
    }

}

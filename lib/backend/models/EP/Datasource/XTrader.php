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
use common\helpers\Categories;

class XTrader extends DatasourceBase
{

    public function getName()
    {
        return 'XTrader';
    }

    public function prepareConfigForView($configArray)
    {   
        $cats_list = Categories::get_category_tree(0,'','','',false,true,0, false);
        $cats_list = \yii\helpers\ArrayHelper::map($cats_list, 'id', 'text');
        $configArray['cats_list'] = $cats_list;        
        $configArray['select_filter_categories_auto_complete_url'] = \Yii::$app->urlManager->createUrl(['easypopulate/get-categories-list']);
        $configArray['category_name'] = Categories::get_categories_name($configArray['categories_id']);
        return parent::prepareConfigForView($configArray);
    }


    public function getViewTemplate()
    {
        return 'datasource/xtrader.tpl';
    }

}

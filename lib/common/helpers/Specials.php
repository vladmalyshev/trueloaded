<?php
/*
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group Ltd
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\helpers;

use Yii;

class Specials {

  public static function validateSave(
      $products_id,
      $special_price,
      $prices,
      $specials_id = 0,
      $status = 1,
      $specials_start_date = '',
      $specials_expires_date = ''
      ) {

      //check update other specials
      if (!empty($specials_start_date)) {
        $e = \common\models\Specials::find()->select('specials_id, start_date ')->andWhere("specials_id <> '" . (int) $specials_id . "'")->andWhere(['products_id' => $products_id])
            ->startBefore($specials_start_date)
            ->endAfter($specials_start_date)->asArray()->all();
        if (!empty($e)) {
          $ids = \yii\helpers\ArrayHelper::getColumn($e, 'specials_id');
          $columns = [
            'start_date' => new \yii\db\Expression('if(start_date="' . tep_db_input($specials_start_date) . '", '
                . ' DATE_SUB("' . tep_db_input($specials_start_date) . '", INTERVAL 1 SECOND), start_date)'),
            'specials_last_modified' => new \yii\db\Expression('now()'),
            'expires_date' => new \yii\db\Expression("DATE_SUB('" . tep_db_input($specials_start_date) . "', INTERVAL 1 SECOND)")
          ];
          \common\models\Specials::updateAll($columns, ['specials_id' => $ids]);
        }
/*
        $e = tep_db_fetch_array(tep_db_query("select specials_id, start_date from " . TABLE_SPECIALS . ""
            . " where products_id='" . (int)$products_id . "' and specials_id <> '" . (int) $specials_id . "'"
            . " and (start_date is null or start_date<='" . tep_db_input($specials_start_date) . "') "
            . " and (expires_date is null or expires_date>='" . tep_db_input($specials_start_date) . "') "));
        if (!empty($e['specials_id'])) {
          if ($e['start_date'] == $specials_start_date) { // 2check
            //if ==  then new expire date could be < start
            $sU = ", start_date = DATE_SUB('" . tep_db_input($specials_start_date) . "', INTERVAL 1 SECOND) ";
          } else {
            $sU = '';
          }
          tep_db_query("update ". TABLE_SPECIALS . " set specials_last_modified = now(), expires_date = DATE_SUB('" . tep_db_input($specials_start_date) . "', INTERVAL 1 SECOND) {$sU} where specials_id='" . (int)$e['specials_id'] . "'");
        }*/
      }
      
      if (!empty($specials_expires_date)) {
        $e = \common\models\Specials::find()->select('specials_id')->andWhere("specials_id <> '" . (int) $specials_id . "'")->andWhere(['products_id' => $products_id])
            ->startBefore($specials_expires_date)
            ->endAfter($specials_expires_date)->asArray()->all();
        if (!empty($e)) {
          $ids = \yii\helpers\ArrayHelper::getColumn($e, 'specials_id');
          $columns = [
            'start_date' => new \yii\db\Expression(' DATE_ADD("' . tep_db_input($specials_expires_date) . '", INTERVAL 1 SECOND)'),
            'specials_last_modified' => new \yii\db\Expression('now()')
          ];
          \common\models\Specials::updateAll($columns, ['specials_id' => $ids]);
        }
/*

        $e = tep_db_fetch_array(tep_db_query("select specials_id from " . TABLE_SPECIALS . ""
            . " where products_id='" . (int)$products_id . "' and specials_id <> '" . (int) $specials_id . "'"
            . " and (start_date is null or start_date<='" . tep_db_input($specials_expires_date) . "') "
            . " and (expires_date is null or expires_date>='" . tep_db_input($specials_expires_date) . "') "));
        if (!empty($e['specials_id'])) {
          tep_db_query("update ". TABLE_SPECIALS . " set specials_last_modified = now(), start_date = DATE_ADD('" . tep_db_input($specials_expires_date) . "', INTERVAL 1 SECOND) where specials_id='" . (int)$e['specials_id'] . "'");
        }*/
      }
      // fix possible errors with dates (set the same and deactivate).
      tep_db_query("update ". TABLE_SPECIALS . " set specials_last_modified = now(), expires_date=start_date, status=0 where expires_date<start_date and products_id='" . (int)$products_id . "'");

      if ($specials_start_date > date(\common\helpers\Date::DATABASE_DATETIME_FORMAT)) {
        $_status = 0;
      } else {
        $_status = $status;
      }

      if ((int)$specials_id > 0) {
        tep_db_query("update " . TABLE_SPECIALS . " set specials_new_products_price = '" . (float) $special_price . "', specials_last_modified = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', start_date = '" . tep_db_input($specials_start_date) . "', status = '" . $_status . "' where specials_id = '" . (int)$specials_id . "'");
        \common\models\SpecialsPrices::deleteAll(['specials_id' => (int)$specials_id]);
      } else {
        tep_db_query("insert into " . TABLE_SPECIALS . " set products_id = '" . (int) $products_id . "', specials_new_products_price = '" . (float) $special_price . "', specials_date_added = now(), expires_date = '" . tep_db_input($specials_expires_date) . "', start_date = '" . tep_db_input($specials_start_date) . "', status = '" . $_status . "'");
        $specials_id = tep_db_insert_id();
      }
      if (is_array($prices)) {
        foreach ($prices as $price) {
          try {
            $price['specials_id'] = $specials_id;
            $m = new \common\models\SpecialsPrices();
            $m->loadDefaultValues();
            $m->setAttributes($price, false);
            $m->save(false);
          } catch (\Exception $e) {
            \Yii::warning($e->getMessage(), 'SPECIALPRICES_ERROR');
          }
        }
      }
      return true;

  }
/**
 * save specials (either on categories or sales page in admin.
 * @param int $products_id
 * @param bool $deleteInactive default false
 * @return bool|string  true|false|error message?
 */
  public static function saveFromPost($products_id, $deleteInactive = false) {
    $ret = false;
    $currencies = Yii::$container->get('currencies');
    $_def_curr_id = $currencies->currencies[DEFAULT_CURRENCY]['id'];

    $specials_id = (int) \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'specials_id', 'dbdef' => 0, 'post' => 'specials_id'], $_def_curr_id, 0);
    $status = (int) \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'status', 'dbdef' => 0, 'post' => 'special_status'], $_def_curr_id, 0);
    if (!$status && $deleteInactive) {
      $s = \common\models\Specials::findOne(['specials_id' => $specials_id]);
      if ($s) {
        $s->delete();
        $ret = true;
      }
    } else {
      $specials_expires_date =  \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'expires_date', 'dbdef' => '', 'post' => 'special_expires_date'], $_def_curr_id, 0);
      $specials_expires_date = \common\helpers\Date::prepareInputDate($specials_expires_date, true);
      $specials_start_date =  \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'start_date', 'dbdef' => 'NULL', 'post' => 'special_start_date'], $_def_curr_id, 0);
      $specials_start_date = \common\helpers\Date::prepareInputDate($specials_start_date, true);
      $special_price =  \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'specials_new_products_price', 'dbdef' => '-1', 'post' => 'special_price'], $_def_curr_id, 0);
      if (!$specials_expires_date || $specials_expires_date=='' || $specials_expires_date=='NULL') {
  /*
          if ($special_price>0) {
            $dateFormat = date_create("+30 days");
            $specials_expires_date = $dateFormat?$dateFormat->format(\common\helpers\Date::DATABASE_DATETIME_FORMAT):'';
          } else {
            $status = 0;
          }
  */
      }

      if (!$specials_start_date || $specials_start_date=='' || $specials_start_date=='NULL') {
        $dateFormat = date_create();
        $specials_start_date = $dateFormat?$dateFormat->format(\common\helpers\Date::DATABASE_DATETIME_FORMAT):'';
      }

      $prices = $currencies_ids = $groups = [];
      if ($ext = \common\helpers\Acl::checkExtension('UserGroups', 'getGroupsArray')) {
        $groups = $ext::getGroupsArray();
        if (!isset($groups['0'])) {
          $groups['0'] = ['groups_id' => 0];
        }
      }

      if (USE_MARKET_PRICES == 'True') {
        foreach ($currencies->currencies as $key => $value)  {
          $currencies_ids[$currencies->currencies[$key]['id']] = $currencies->currencies[$key]['id'];
        }
      } else {
        $currencies_ids[$_def_curr_id] = '0'; /// here is the post and db currencies_id are different.
      }
      if (USE_MARKET_PRICES == 'True' || CUSTOMERS_GROUPS_ENABLE == 'True') {
        foreach ($currencies_ids as $post_currencies_id => $currencies_id) {
          foreach ($groups as $groups_id => $non) {
            $prices[] = [
              'currencies_id' => $currencies_id,
              'groups_id' => $groups_id,
              'specials_new_products_price' =>  \backend\models\ProductEdit\PostArrayHelper::getFromPostArrays(['db' => 'specials_new_products_price', 'dbdef' => -2, 'post' => 'special_price', 'f' => ['self', 'defGroupPrice']], $currencies_id, $groups_id)
              ];
          }
        }
      }

      $ret = self::validateSave($products_id, $special_price, $prices, $specials_id, $status, $specials_start_date, $specials_expires_date);
    }
    return $ret;
  }


}

<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\queries;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Specials]].
 *
 * @see Specials
 */
class SpecialsQuery extends ActiveQuery {
  public static $startEpoch = '0000-00-00 00:00:00';

  public function active($active = true) {
    return $this->andWhere(['status' => $active ? 1 : 0]);
  }

  public function dateInRange($dateTime = null) {
    if (empty($dateTime)) {
      $dateTime = date("Y-m-d H:i:s");
    } else {
/// nice to have - validate date format
    }
    return $this->startBefore($dateTime)->endAfter($dateTime);
  }

  /**
   * for active on (could be active iт the specified date range, either all the time or partially)
   * @param type $startDate
   * @param type $endDate
   */
  public function datesInRange($startDate, $endDate = null) {
    return $this->andWhere([
          'or',
          //specials start between the dates
          [
            'and',
            static::startAfterArray($startDate),
            static::startBeforeArray($endDate)
          ],
          //specials end between the dates
          [
            'and',
            static::endAfterArray($startDate),
            static::endBeforeArray($endDate)
          ],
          //specials full cover (specials starts before ends after)
          [
            'and',
            static::startBeforeArray($startDate),
            static::endAfterArray($endDate)
          ],
          //specials within (specials starts after ends before )
          [
            'and',
            static::startAfterArray($startDate),
            static::endBeforeArray($endDate)
          ],
    ]);
  }

  public function startBefore($dateTime = null) {
    if (empty($dateTime)) {
      $dateTime = date("Y-m-d H:i:s");
    } else {
/// nice to have - validate date format
    }
    return $this->andWhere(static::startBeforeArray($dateTime));
  }

  public function startAfter($dateTime = null) {
    if (empty($dateTime)) {
      $dateTime = date("Y-m-d H:i:s");
    } else {
/// nice to have - validate date format
    }
    return $this->andWhere(static::startAfterArray($dateTime));
  }

  public function endBefore($dateTime = null) {
    if (empty($dateTime)) {
      $dateTime = date("Y-m-d H:i:s");
    } else {
/// nice to have - validate date format
    }
    return $this->andWhere(static::endBeforeArray($dateTime));
  }

  /**
   * after date or null (not specified)
   * @param datetime $dateTime default - today
   * @return type
   */
  public function endAfter($dateTime = null) {
    if (empty($dateTime)) {
      $dateTime = date("Y-m-d H:i:s");
    } else {
/// nice to have - validate date format
    }
    return $this->andWhere(static::endAfterArray($dateTime));
  }

  private static function endBeforeArray($dateTime) {
    return 
      //'and',
      ['<=', 'expires_date', $dateTime]
      //['=', 'expires_date', self::$startEpoch]
    ;
  }

  private static function endAfterArray($dateTime) {
    return [
      'or',
      ['>=', 'expires_date', $dateTime],
      ['=', 'expires_date', self::$startEpoch], //workaround not null default 0000-00-00 00:00:00 | 1970-01-01 01:00:00
      ['is', 'expires_date', null]
    ];
  }

  private static function startBeforeArray($dateTime) {
    return [
      'or',
      ['<=', 'start_date', $dateTime],
      ['=', 'start_date', self::$startEpoch], //workaround not null default 0000-00-00 00:00:00 | 1970-01-01 01:00:00
      ['is', 'start_date', null]
    ];
  }

  private static function startAfterArray($dateTime) {
    return [
      '>=', 'start_date', $dateTime
    ];
  }
}

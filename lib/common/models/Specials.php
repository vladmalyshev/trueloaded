<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models;

use Yii;
use common\models\queries\SpecialsQuery;

/**
 * This is the model class for table "specials".
 *
 * @property int $specials_id
 * @property int $products_id
 * @property double $specials_new_products_price
 * @property string $specials_date_added
 * @property string $specials_last_modified
 * @property string $expires_date
 * @property string $date_status_change
 * @property int $status
 * @property string $start_date
 */
class Specials extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'specials';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['products_id', 'status'], 'integer'],
            [['specials_new_products_price'], 'number'],
            [['specials_date_added', 'specials_last_modified', 'expires_date', 'date_status_change', 'start_date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'specials_id' => 'Specials ID',
            'products_id' => 'Products ID',
            'specials_new_products_price' => 'Specials New Products Price',
            'specials_date_added' => 'Specials Date Added',
            'specials_last_modified' => 'Specials Last Modified',
            'expires_date' => 'Expires Date',
            'date_status_change' => 'Date Status Change',
            'status' => 'Status',
            'start_date' => 'Start Date',
        ];
    }

    public function beforeDelete() {
      SpecialsPrices::deleteAll(['specials_id' => $this->specials_id]);
      return parent::beforeDelete();
    }
    
    public static function find()
    {
        return new SpecialsQuery(get_called_class());

    }
    public function getPrices() {
      return $this->hasMany(\common\models\SpecialsPrices::class, ['specials_id' => 'specials_id']);
    }

    public function getProduct() {
      return $this->hasOne(\common\models\Products::class, ['products_id' => 'products_id']);
    }

    public function getBackendProductDescription() {
      $languages_id = \Yii::$app->settings->get('languages_id');

      if (\backend\models\ProductNameDecorator::instance()->useInternalNameForListing()) {
        $nameColumn = new \yii\db\Expression("IF(LENGTH(products_internal_name), products_internal_name, products_name)");
      } else {
        $nameColumn = 'products_name';
      }

      return $this->hasOne(\common\models\ProductsDescription::class, ['products_id' => 'products_id'])->via('product')
                ->select(['products_name' => $nameColumn])
                ->addSelect(['platform_id', 'products_id', 'language_id'])
                ->where(['language_id' => (int)$languages_id,
                         'platform_id' => intval(\common\classes\platform::defaultId())
                  ])
                ->orderBy($nameColumn);
    }

}

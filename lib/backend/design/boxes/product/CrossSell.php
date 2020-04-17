<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes\product;

use common\helpers\Translation;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class CrossSell extends Widget
{

  public $id;
  public $params;
  public $settings;
  public $visibility;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
      global $languages_id;

      $xsellTypeVariants = [
          0 => Translation::getTranslationValue('FIELDSET_ASSIGNED_XSELL_PRODUCTS','admin/categories'),
      ];

      $tmp = \common\models\ProductsXsellType::find()
          ->select('xsell_type_name, xsell_type_id')
          ->andWhere(['language_id' => (int)$languages_id])
          ->orderBy('xsell_type_name')
          ->asArray()->indexBy('xsell_type_id')->column();
      if (is_array($tmp)) {
        $xsellTypeVariants += $tmp;
      }
      
      $platformList = \common\classes\platform::getList();

      return $this->render('../../views/cross-sell.tpl', [
          'id' => $this->id, 'params' => $this->params, 'settings' => $this->settings,
          'visibility' => $this->visibility,
          'xsellTypeVariants' => $xsellTypeVariants,
          'platformList' => $platformList,
      ]);
  }
}
<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design\boxes;

use Yii;
use yii\base\Widget;
use common\classes\platform;
use common\helpers\Date;

class PredefinedPromotion extends Widget
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
    
    $platform_id = platform::currentId();
    if (!$platform_id)
        $platform_id = platform::defaultId ();
    
    $promo = [];
    foreach(\common\models\promotions\Promotions::getCurrentPromotions($platform_id, [0, 1])->indexBy('promo_id')->all() as $promo_id => $obj){
        $hasRange = intval($obj->promo_date_start) || intval($obj->promo_date_expired);
        $promo[$promo_id] = $obj->promo_label . ($hasRange? " (" . (intval($obj->promo_date_start)? Date::date_short($obj->promo_date_start) :'') . "-" . (intval($obj->promo_date_expired)? Date::date_short($obj->promo_date_expired) :'')  . ")" : '');
    }

    return $this->render('predefiend-promotion.tpl', [
      'id' => $this->id,
      'params' => $this->params,
      'promo' => $promo,
      'settings' => $this->settings,
      'visibility' => $this->visibility,
    ]);
  }
}
<?php

/* 
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */


namespace frontend\design\boxes;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\extensions\Trustpilot;


class TrustPilotReviews extends Widget
{

  public $params;
  public $settings;

  public function init()
  {
    parent::init();
  }

  public function run()
  {
    if (\common\helpers\Acl::checkExtension('Trustpilot', 'allowed')) {
      $client = new Trustpilot\Trustpilot();
      if ($data = $client->getReviewsSummary(\common\classes\platform::currentId())) {
          return IncludeTpl::widget(['file' => 'boxes/trustpilot-summary.tpl', 'params' => [
            'identifying' => $data['summary']->name->identifying,
            'score' => $data['summary']->trustScore,
            'qty' => $data['summary']->numberOfReviews->usedForTrustScoreCalculation,
            'starsURL' => $data['stars']->image130x24->url,
            'starsLabel' => $data['starsLabel']->string,
            'tpLogo' => $data['logos']['icons']->image40x40->url,
          ]])
              //. "<pre>". print_r($data , true)
              ;
      }
    }
    return '';

  }
  
}
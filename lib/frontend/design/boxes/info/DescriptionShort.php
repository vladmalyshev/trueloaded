<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\info;

use common\classes\platform;
use frontend\models\repositories\InformationReadRepository;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class DescriptionShort extends Widget
{

  public $file;
  public $params;
  public $settings;

    private $informationRepository;

    public function __construct(InformationReadRepository $informationRepository, $config = [])
    {
        parent::__construct($config);
        $this->informationRepository = $informationRepository;
    }

    public function run()
  {

    $languages_id = \Yii::$app->settings->get('languages_id');

    $platformId = (bool)platform::currentId()?(int)platform::currentId():(int)platform::currentId();
    $info_id = (int)Yii::$app->request->get('info_id',0);
    if(!$info_id){
        return '';
    }

    $information = $this->informationRepository->findById($info_id,$platformId,$languages_id);
    if($information === false){
        return '';
    }
      $text =  \frontend\design\EditData::addEditDataTeg(stripslashes($information->description_short), 'info', 'description_short', $info_id);

    return IncludeTpl::widget(['file' => 'boxes/info/description-short.tpl', 'params' => [
        'description_short' => $text,
    ]]);
  }
}

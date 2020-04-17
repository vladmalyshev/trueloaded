<?php
/**
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
use frontend\design\Info;

class Brands extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
    }

    public function run()
    {

        $manufacturers = \common\models\Manufacturers::find()->alias('m')->joinWith('manufacturersInfo')
            ->addSelect('m.manufacturers_id, manufacturers_name, manufacturers_image, manufacturers_h2_tag')
            ->addSelect(['f_letter' => new \yii\db\Expression('lower(left(manufacturers_name,1))')])
            ->orderBy('manufacturers_name')
//;echo $manufacturers->createCommand()->rawSql; die;
            ->asArray()->all();

        $alphabets = $alphabet = [];
        $prevChar = '';
        $_lng = '0-9';


        if (!empty($manufacturers)) {
          foreach($manufacturers as $k => $m) {
            $manufacturers[$k]['link'] = \Yii::$app->urlManager->createUrl(['catalog', 'manufacturers_id' => $m['manufacturers_id']]);
            $manufacturers[$k]['h2'] = $m['manufacturers_h2_tag'];
            $manufacturers[$k]['img'] = \common\classes\Images::getImageSet(
                  $m['manufacturers_image'],
                  'Brand gallery',
                  [],
                  Info::themeSetting('na_category', 'hide')
              );

            if (!empty($this->settings[0]['show_abc']) && $prevChar != $m['f_letter']) {
              $prevChar = $m['f_letter'];
              if (preg_match('/\d+/', $prevChar)){
                if (!isset($alphabets['0-9'])) {
                  $alphabets[$_lng]['letters'] = ['0-9'];//range(0, 9);
                  $alphabets[$_lng]['active'][] = '0-9';//$prevChar;
                }
                $manufacturers[$k]['f_letter'] = '0-9';
              } elseif (preg_match('/\pL/', $prevChar) ) {
                if (!isset($alphabet[$prevChar])) {
                  $tmp = \common\helpers\Language::getPossibleLanguage($prevChar);
                  if (!empty($tmp) && !isset($alphabets[$tmp]['letters'])) {
                    $_lng = $tmp;
                    $alphabets[$_lng]['letters'] = \common\helpers\Language::alphabets([$_lng]);
                    $alphabet += array_flip($alphabets[$_lng]['letters']);
                  } elseif (!in_array($prevChar, $alphabets[$_lng]['letters'])) {
                    $alphabets[$_lng]['letters'][] = $prevChar;
                    $alphabet[$prevChar] = $_lng;
                  }
                }
                if (!is_array($alphabets[$_lng]['active']) || !in_array($prevChar, $alphabets[$_lng]['active'])) {
                  $alphabets[$_lng]['active'][] = $prevChar;
                }
              }
            }

          }

            return IncludeTpl::widget([
                'file' => 'boxes/brands.tpl',
                'params' => ['brands' => $manufacturers, 'alphabets' => $alphabets]
            ]);

        }

        return '';
    }
}
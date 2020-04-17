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

class Image extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();

        if ($this->settings[0]['lazy_load']) {
            \frontend\design\Info::addJsData(['widgets' => [
                $this->id => ['lazyLoad' => $this->settings[0]['lazy_load']]
            ]]);
        }
    }

    public function run()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        if ($this->params['language_id'] && $this->settings[0]['pdf']) {
            $languages_id = $this->params['language_id'];
        }

        $settings = $this->settings[$languages_id];

        $image = \frontend\design\Info::themeImage($settings['logo'],
            [$this->settings[\common\classes\language::defaultId()]['logo'], $this->settings[0]['params']]);

        if ($this->settings[0]['pdf']){

            if (function_exists('tep_catalog_href_link')){
                $img = tep_catalog_href_link($image);
            } else {
                $img = HTTP_SERVER . DIR_WS_HTTP_CATALOG . $image;
            }

            $img = '<img src="' . $img . '">';

            return $img;

        } else {

            $image = \common\classes\Images::getWebp($image, '');
            $imageUrl =  Yii::$app->request->baseUrl . '/' . $image;
            if ($this->params['absoluteUrl']) {
                $imageUrl = Yii::$app->urlManager->createAbsoluteUrl($image);
            }

            $attributes = [];
            if ($settings['alt']) {
                $attributes['alt'] = $settings['alt'];
            }
            if ($settings['title']) {
                $attributes['title'] = $settings['title'];
            }
            if ($settings['target_blank']) {
                $attributes['target'] = '_blank';
            }
            if ($settings['no_follow']) {
                $attributes['rel'] = 'nofollow';
            }
            if ($this->settings[0]['lazy_load']) {
                $attributes['data-src'] = $imageUrl;
                $imageUrl = '';
            }

            $html =  \Yii\helpers\Html::img($imageUrl, $attributes);

            if ($settings['img_link']) {
                $html = \Yii\helpers\Html::a($html, $settings['img_link']);
            }
            return $html;

        }

    }
}
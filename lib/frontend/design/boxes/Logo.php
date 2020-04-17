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

class Logo extends Widget
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
        $languages_id = \Yii::$app->settings->get('languages_id');

        if ($this->params['language_id'] && $this->settings[0]['pdf']) {
            $lang_id = $this->params['language_id'];
        }else {
            $lang_id = $languages_id;
        }

        if ($this->settings[0]['logo_from'] == 'platform') {
            $platform = \common\models\Platforms::find()
                ->select('logo')
                ->where(['platform_id' => \common\classes\platform::currentId()])
                ->asArray()
                ->one();
            if ($platform['logo'] && is_file(DIR_FS_CATALOG . $platform['logo'])) {
                $image = $platform['logo'];
            }
        }

        if ($this->settings[0]['logo_from'] == 'theme') {
            $image = \frontend\design\Info::themeSetting('logo', 'hide');
        }

        if (!$image) {
            $image = Info::themeImage($this->settings[$lang_id]['logo'],
                [$this->settings[\common\classes\language::defaultId()]['logo'], $this->settings[0]['params']]);
        }

        if (!$image || strpos($image, '/na.png')) {
            $logo = Info::widgetSettings('Logo', false, 'header');
            if ($logo[$lang_id]['logo']) {
                $image = Info::themeImage($logo[$lang_id]['logo']);
            }
        }

        if ($this->settings[0]['pdf']){

            if (function_exists('tep_catalog_href_link')){
                $img = tep_catalog_href_link($image);
            } else {
                $img = HTTP_SERVER . DIR_WS_HTTP_CATALOG . $image;
            }
            if ( is_file(DIR_FS_CATALOG . $this->settings[$lang_id]['logo']) ) {
                return $img = '<img src="@' . base64_encode(file_get_contents(DIR_FS_CATALOG.$this->settings[$lang_id]['logo'])) . '">';
            }

            $img = '<img src="' . $img . '">';

            return $img;

        } else {

            $url = '';
            if (Yii::$app->controller->id != 'index' || Yii::$app->controller->action->id != 'index') {
                $url = tep_href_link('/');
            }

            $imageUrl =  Yii::$app->request->baseUrl . '/' . $image;
            if ($this->params['absoluteUrl']) {
                $imageUrl = \Yii::$app->get('platform')->config()->getCatalogBaseUrl(Yii::$app->request->getIsSecureConnection()).$image;
            }

            return IncludeTpl::widget([
                'file' => 'boxes/logo.tpl',
                'params' => [
                    'url' => $url,
                    'image' => $imageUrl,
                ],
            ]);
        }
    }
}
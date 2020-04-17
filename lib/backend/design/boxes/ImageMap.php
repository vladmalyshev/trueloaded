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

class ImageMap extends Widget
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

        $mapImage = '';
        $mapTitle = '';

        $map = \common\models\ImageMaps::findOne($this->settings[0]['maps_id']);
        if ($map) {
            $mapImage = $map->image;
            $mapTitle = $map->getTitle($languages_id);
        }

        return $this->render('image-map.tpl', [
            'id' => $this->id,
            'params' => $this->params,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'mapsTitle' => $mapTitle,
            'mapsImage' => $mapImage,
        ]);
    }
}
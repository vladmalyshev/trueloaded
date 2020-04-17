<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\product;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use common\classes\Images as cImages;

class ImagesAdditional extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        if (!is_array($this->params) ) $this->params = array();
        parent::init();
    }

    public function run()
    {
        if ( isset($this->params['uprid']) && $this->params['uprid']>0 ) {
            $show_uprid = $this->params['uprid'];
        }else {
            $show_uprid = Yii::$app->request->get('products_id',0);
        }

        if (!$show_uprid) {
            return '';
        }

        $images = \common\classes\Images::getImageList($show_uprid);
        if ( count($images)==0 ) {
            $show_uprid = \common\helpers\Inventory::get_prid($show_uprid);
            $images = \common\classes\Images::getImageList($show_uprid);
        }

        foreach( $images as $imgId => $__image ) {
            $_srcsetSizes = cImages::getImageSrcsetSizes($show_uprid, 'Small', -1, $imgId);
            $images[$imgId]['srcset'] = $_srcsetSizes['srcset'];
            $images[$imgId]['sizes'] = $_srcsetSizes['sizes'];
        }

        return IncludeTpl::widget(['file' => 'boxes/product/images-additional.tpl', 'params' => [
            'images' => $images,
            'images_count' => count($images),
            'settings' => $this->settings
        ]]);
    }
}
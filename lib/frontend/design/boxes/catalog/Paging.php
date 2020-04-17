<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\catalog;

use frontend\design\Info;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Paging extends Widget
{

    public $file;
    public $params;
    public $settings;

    public function init()
    {
        parent::init();
        Info::addBoxToCss('slick');
    }

    public function run()
    {

        if (Info::widgetSettings('Listing', 'fbl')){
            return '';
        }

        if (
            !isset($this->params['listing_split'])
            || !is_object($this->params['listing_split'])
            || !is_a($this->params['listing_split'], 'frontend\design\splitPageResults' )
        ) {
            return '';
        }

        $listing_split = $this->params['listing_split'];
        if (!$listing_split->number_of_rows){
            return '';
        }

        $links = $listing_split->display_links(
            MAX_DISPLAY_PAGE_LINKS,
            \common\helpers\Output::get_all_get_params(array('page', 'info', 'x', 'y', 'ajax', 't', 'filter', 'split')),
            $this->params['this_filename']
        );

        return IncludeTpl::widget([
            'file' => 'boxes/catalog/paging.tpl',
            'params' => [
                'box_id' => $this->id,
                'links' => $links,
                'settings' => $this->settings,
                'hidden_fields' => \common\helpers\Output::get_all_get_params(array('sort'), true),
            ]
        ]);



    }
}
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

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\SplitPageResults;
use frontend\design\Info;

class CompareButton extends Widget
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
        if ( !isset($this->params['listing_split']) || !is_object($this->params['listing_split']) || !is_a($this->params['listing_split'], 'frontend\design\splitPageResults' ) ) {
            return '';
        }
        $listing_split = $this->params['listing_split'];
        /**
         * @var $listing_split SplitPageResults
         */
        if ($listing_split->number_of_rows > 0 && !$this->settings[0]['compare_button']){

            Info::addJsData(['tr' => [
                'BOX_HEADING_COMPARE_LIST' => BOX_HEADING_COMPARE_LIST
            ]]);
            return IncludeTpl::widget([
                'file' => 'boxes/catalog/compare-button.tpl',
                'params' => [
                ]
            ]);
        }
    }
}
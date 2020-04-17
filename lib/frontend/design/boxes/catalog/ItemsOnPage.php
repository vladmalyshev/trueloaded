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
use frontend\design\ListingSql;
use frontend\design\SplitPageResults;
use frontend\design\Info;

class ItemsOnPage extends Widget
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
    if ($listing_split->number_of_rows > 0 && !Info::widgetSettings('Listing', 'fbl', $this->params['page_name'])){

      $searchResults = Info::widgetSettings('Listing', 'items_on_page', $this->params['page_name']);
      if (!$searchResults) $searchResults = SEARCH_RESULTS_1;

      $view = array();
      $view[] = $searchResults * 1;
      $view[] = $searchResults * 2;
      $view[] = $searchResults * 4;
      $view[] = $searchResults * 8;

      Info::sortingId();
      return IncludeTpl::widget([
        'file' => 'boxes/catalog/items-on-page.tpl',
        'params' => [
            'box_id' => $this->id,
            'sorting_link' => tep_href_link($this->params['this_filename'], \common\helpers\Output::get_all_get_params(array('max_items'))),
          'view' => $view,
          'view_id' => $_SESSION['max_items'],
          'hidden_fields' => '',//\common\helpers\Output::get_all_get_params(array('max_items'), true),
          'settings' => $this->settings
        ]
      ]);
    }



  }
}
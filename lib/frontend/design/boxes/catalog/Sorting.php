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

class Sorting extends Widget
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
    if ($listing_split->number_of_rows > 0){

      $sorting_link = tep_href_link($this->params['this_filename'], \common\helpers\Output::get_all_get_params(array('sort')));
      
      if (!isset($this->params['sorting_id'])) {
        $this->params['sorting_id'] = Info::sortingId();
      }

      $sorting = \common\helpers\Sorting::getSorting($this->settings[0], true, true);



      $searchResults = Info::widgetSettings('Listing', 'items_on_page', 'products');
      if (!$searchResults) $searchResults = SEARCH_RESULTS_1;

      return IncludeTpl::widget([
        'file' => 'boxes/catalog/sorting.tpl',
        'params' => [
          'box_id' => $this->id,
          'view_id' => $_SESSION['max_items'],
          'sorting_link' => $sorting_link,
          'sorting' => $sorting,
          'settings' => $this->settings,
          'sorting_id' => $this->params['sorting_id'],
          'hidden_fields' => '',//\common\helpers\Output::get_all_get_params(array('sort'), true),
          'gl' => $_SESSION['gl'],
          'fbl' => Info::widgetSettings('Listing', 'fbl'),
        ]
      ]);
    }



  }
}
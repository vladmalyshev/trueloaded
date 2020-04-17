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
use frontend\design\Block;
use frontend\design\Info;

class Tabs extends Widget
{

    public $settings;
    public $params;
    public $id;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $languages_id = \Yii::$app->settings->get('languages_id');

        $accordion = array();
        $media_query_arr = tep_db_query("select t.setting_value from " . TABLE_THEMES_SETTINGS . " t, " . (Info::isAdmin() ? TABLE_DESIGN_BOXES_SETTINGS_TMP : TABLE_DESIGN_BOXES_SETTINGS) . " b where b.setting_name = 'accordion' and  b.visibility = t.id and  b.box_id = '" . (int)$this->id . "'");
        while ($item = tep_db_fetch_array($media_query_arr)){
            $accordion[] = $item['setting_value'];
        }

        $block_id = 'block-' . $this->id;
        $tabs_headings = '';
        $var = '';
        $var_content = '';

        for($i = 1; $i <= 10; $i++){

            if ($this->settings[0]['tab_' . $i]) {
                $title = $this->settings[0]['tab_' . $i];
            } elseif ($this->settings[$languages_id]['tab_' . $i]){
                $title = $this->settings[$languages_id]['tab_' . $i];
            } else {
                continue;
            }
            $title = \frontend\design\Info::translateKeys($title);

            $widget = Block::widget(['name' => $block_id . '-' . $i, 'params' => ['params' => $this->params, 'cols' => $i, 'tabs' => true]]);

            if (($widget || Info::isAdmin())) {

                $var_content .= '<div
                    class="accordion-heading tab-' . $block_id . '-' . $i . '"
                    data-tab="tab-' . $block_id . '-' . $i . '"
                    data-href="#tab-' . $block_id . '-' . $i . '"
                    style="display: none"><span>' . $title . '</span></div>';

                $var_content .= $widget;

                $tabs_headings .= '<div
                    class="tab-' . $block_id . '-' . $i . ' tab-li"
                    data-tab="tab-' . $block_id . '-' . $i . '"
                    ><a class="tab-a" data-href="#tab-' . $block_id . '-' . $i . '">' . $title . '</a></div>';
            }
        }


        if ($var_content) {

            $var .= '<div class="tab-navigation">' . $tabs_headings . '</div>';
            $var .= $var_content;

            $var .= IncludeTpl::widget(['file' => 'boxes/tabs.tpl', 'params' => [
                'id' => $this->id,
                'accordion' => $accordion
            ]]);

            return $var;

        } else {

            return "<div class=\"no-block-settings_tab\"></div>
  <script type=\"text/javascript\">
  tl('" . Info::themeFile('/js/main.js') . "', function(){
    setTimeout(function(){
      $('.no-block-settings_tab').closest('.box-block').find('.edit-box').trigger('click')
    }, 2000)
  })
  </script>";

        }

    }
}
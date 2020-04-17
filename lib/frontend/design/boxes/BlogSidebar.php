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

class BlogSidebar extends Widget
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
      if (!\frontend\design\Info::hasBlog()){
          return '';
      }
    global $Blog;

    $Blog->wpLoad();
    if ($this->settings[0]['blog_sidebar'] == 1) {
      dynamic_sidebar('sidebar-1');
      //return $Blog->sidebar_1();
    } elseif ($this->settings[0]['blog_sidebar'] == 2) {
      dynamic_sidebar('sidebar-2');
      //return $Blog->sidebar_2();
    } elseif ($this->settings[0]['blog_sidebar'] == 3) {
      dynamic_sidebar('sidebar-3');
      //return $Blog->sidebar_3();
    }
  }
}
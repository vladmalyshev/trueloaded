<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design\boxes\checkout;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class Comments extends Widget
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
        if (isset($this->params['manager'])){
            $comments = $this->params['manager']->has('comments')?$this->params['manager']->get('comments'):'';
            $this->params['comments'] = $comments;
        }
        
        return IncludeTpl::widget(['file' => 'boxes/checkout/comments.tpl', 'params' => $this->params]);
    }
}
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

class CommentsConfirm extends Widget
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
        $this->params['empty'] = true;
        $this->params['settings'] = $this->settings;
        $this->params['id'] = $this->id;

        if (isset($this->params['manager'])){
            $comments = $this->params['manager']->has('comments')?$this->params['manager']->get('comments'):'';
            $this->params['comments'] = $comments;
        }

        if ($comments) $this->params['empty'] = false;
        
        return IncludeTpl::widget(['file' => 'boxes/checkout/comments-confirm.tpl', 'params' => $this->params]);
    }
}
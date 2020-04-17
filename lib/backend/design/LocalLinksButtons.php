<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\design;

use Yii;
use yii\base\Widget;

class LocalLinksButtons extends Widget
{

    public $editor;
    public $platform_id;
    public $languages_id;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        global $languages_id;

        $action = 'information_manager/page-links';

        $platform_id = $this->platform_id ? $this->platform_id : \common\classes\platform::defaultId();
        $lang_id = $this->languages_id ? $this->languages_id : $languages_id;

        $buttons = \common\classes\TlUrl::buttons($this->editor, $platform_id, $lang_id);

        return $this->render('local-links-buttons.tpl', [
            'buttons' => $buttons,
            'editor' => $this->editor,
            'platform_id' => $platform_id,
            'languages_id' => $this->languages_id ? $this->languages_id : $lang_id,
            'action' => 'information_manager/page-links'
        ]);
    }
}
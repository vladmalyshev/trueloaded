<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap\Theme;

use common\api\models\Soap\SoapModel;
use yii\db\ActiveRecord;

class ThemeListResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Theme\ArrayOfThemes
     * @soap
     */
    var $themes;

    public function build()
    {
        $this->themes = new ArrayOfThemes();
        $themes = \common\models\Themes::find()->all();
        foreach ($themes as $theme) {
            /**
             * @var $theme ActiveRecord
             */
            $this->themes->theme[] = new \common\api\models\Soap\Theme\Theme($theme->getAttributes());
        }
        parent::build();
    }


}
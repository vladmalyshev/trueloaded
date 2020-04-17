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

class ThemeGetResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var integer
     */
    public $themeId;

    /**
     * @var boolean
     */
    public $withArchive;

    /**
     * @var \common\api\models\Soap\Theme\Theme {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $theme;

    public function build()
    {
        $model = \common\models\Themes::findOne(['id'=>$this->themeId]);
        $this->theme = new \common\api\models\Soap\Theme\Theme($model->getAttributes());
        if ( $this->withArchive ) {
            $tmp_file = \backend\design\Theme::export($model->theme_name, 'filename');
            $this->theme->theme_archive = chunk_split(base64_encode(file_get_contents($tmp_file)));
            unlink($tmp_file);
        }

        parent::build();
    }


}
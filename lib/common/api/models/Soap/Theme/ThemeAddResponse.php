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


use common\api\models\DataMapBehavior;
use common\api\models\Soap\SoapModel;

class ThemeAddResponse extends SoapModel
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
     * @var \common\api\models\Soap\Theme\Theme {minOccurs=0, maxOccurs = 1}
     * @soap
     */
    public $theme;

    public function build()
    {

        if ( strlen($this->theme->theme_name)==0 && strlen($this->theme->title)>0 ) {
            $name = strtolower($this->theme->title);
            $name = str_replace(' ', '_', $name);
            $this->theme->theme_name = preg_replace('/[^a-z0-9_-]/', '', $name);
        }

        if (strlen($this->theme->theme_name)==0) {
            $this->error('theme_name required');
        }elseif(!preg_match("/^[a-z0-9_\-]+$/", $this->theme->theme_name)){
            $this->error('Enter only lowercase letters and numbers for theme name');
        }else{
            $softCheckUniq = \common\models\Themes::find()
                ->where(['theme_name'=>$this->theme->theme_name])
                ->count();
            if ( $softCheckUniq>0 ) {
                $this->error('Theme with this name already exist');
            }
        }

        if ( $this->status=='ERROR' ){
            $this->theme = null;
        }else {
            $model = new \common\models\Themes();
            $model->attachBehavior('DataMap', [
                'class' => DataMapBehavior::className(),
            ]);
            $model->populateAR((array)$this->theme);
            if ( is_null($model->install) ) {
                $model->install = 1;
            }
            if ($model->save()) {
                $model->refresh();
                if ( isset($this->theme->theme_archive) && !empty($this->theme->theme_archive) ) {
                    $tmp_file = tempnam(\Yii::getAlias('@runtime'.DIRECTORY_SEPARATOR),'theme_arch_').'.zip';
                    file_put_contents($tmp_file, base64_decode($this->theme->theme_archive));
                    \backend\design\Theme::import($model->theme_name, $tmp_file);
                    unlink($tmp_file);
                }
                $this->theme = new \common\api\models\Soap\Theme\Theme($model->getAttributes());
            } else {
                $this->theme = null;
            }
        }
        parent::build();
    }


}
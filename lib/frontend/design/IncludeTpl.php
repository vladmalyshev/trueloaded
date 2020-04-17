<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace frontend\design;

use Yii;
use yii\base\Widget;

class IncludeTpl extends Widget
{

    public $file;
    public $params;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if ( substr($this->file, 0, 1)==='@' && is_file(Yii::getAlias($this->file)) ) {
            return $this->render($this->file, $this->params);
        }

        for ($i = 0; $i < count(Yii::$app->view->theme->pathMap['@app/views']); $i++) {
            if (file_exists(Yii::getAlias(Yii::$app->view->theme->pathMap['@app/views'][$i]) . '/' . $this->file)) {
                return $this->render(Yii::$app->view->theme->pathMap['@app/views'][$i] . '/' . $this->file, $this->params);
            }
        }

        // if file does not found in frontend, search it in backend
        for ($i = 0; $i < count(Yii::$app->view->theme->pathMap['@app/views']); $i++) {
            $path = Yii::getAlias(Yii::$app->view->theme->pathMap['@app/views'][$i]);
            $path = str_replace('lib/backend', 'lib/frontend', $path);
            if (file_exists($path . '/' . $this->file)) {
                $path2 = str_replace('@app', '@app/../frontend', Yii::$app->view->theme->pathMap['@app/views'][$i]);
                return $this->render($path2 . '/' . $this->file, $this->params);
            }
        }
        return '';
    }
}
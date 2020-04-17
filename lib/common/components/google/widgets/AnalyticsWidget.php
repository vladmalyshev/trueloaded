<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\components\google\widgets;

use Yii;

class AnalyticsWidget extends \yii\base\Widget
{
    public $jsonFile;
    public $viewId;
    public $owner;
    public $description;
    public $platformId;
    
    public function init(){
        parent::init();
    }
    
    public function run(){
        
        Yii::$app->getView()->registerJsFile(Yii::$app->request->baseUrl . '/plugins/fileupload/jquery.fileupload.js');
        
        return $this->render('analytics-config', [
            'jsonFile' => $this->jsonFile,
            'viewId' => $this->viewId,
            'platformId' => $this->platformId,
            'owner' => $this->owner,
            'description' => $this->description,
        ]);
    }
}

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

class CaptchaWidget extends \yii\base\Widget
{
    public $publicKey;
    public $privateKey;
    public $version;
    public $owner;
    public $description;
    
    public function init(){
        parent::init();
    }
    
    public function run(){
        return $this->render('captcha-config', [
            'publicKey' => $this->publicKey,
            'privateKey' => $this->privateKey,
            'owner' => $this->owner,
            'version' => $this->version,
            'description' => $this->description,
        ]);
    }
}

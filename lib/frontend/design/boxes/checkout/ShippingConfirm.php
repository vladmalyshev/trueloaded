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

use common\services\storages\StorageInterface;
use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class ShippingConfirm extends Widget
{

    public $file;
    public $params;
    public $settings;
    /** @var StorageInterface */
    private $storage;

    public function __construct($config = [])
    {
        $this->storage = Yii::$app->get('storage');
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $manager = $this->params['manager'];
        $_shipping = $manager->getShipping();
        $module = $manager->getShippingCollection()->get($_shipping['module']);
        if (is_object($module) && method_exists($module, 'getAdditionalOrderParams')){
            $params = $this->storage->get('shippingparam');
            if (is_array($params)) {
                $this->params['shipping_additional_info_block'] = $module->getAdditionalOrderParams($params);
            }
        }
        
        if (is_object($module) && method_exists($module, 'getCollectAddress')){
            $this->params['shipping_additional_info_block'] = $module->getCollectAddress($_shipping['id']);
        }
        
        return IncludeTpl::widget(['file' => 'boxes/checkout/shipping-confirm.tpl', 'params' => $this->params]);
    }
}

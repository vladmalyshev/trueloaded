<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);


namespace common\modules\label\np\widgets;


use common\modules\label\np\forms\NovaPoshtaCreateDocumentCollectForm;

class NovaPoshtaDocument extends \yii\base\Widget
{
    /** @var NovaPoshtaCreateDocumentCollectForm */
    public $form;
    public $view = 'form.tpl';

    public function run()
    {
        return $this->render('form.tpl',[
            'form' => $this->form,
        ]);
    }
}

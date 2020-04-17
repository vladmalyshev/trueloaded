<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Quotation\Quotation;
use common\api\SoapServer\ServerSession;

class GetQuotationResponse extends SoapModel
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
     * @var \common\api\models\Soap\Quotation\Quotation
     * @soap
     */
    public $quotation;

    protected $orderObject;

    public function setQuotationId($quotationId)
    {
        $this->orderObject = new \common\extensions\Quotations\Quotation($quotationId);

        if (ServerSession::get()->getDepartmentId() && intval($this->orderObject->info['department_id']) != intval(ServerSession::get()->getDepartmentId())) {
            unset($this->orderObject);
        }

        if ( !isset($this->orderObject) || !is_object($this->orderObject) || empty($this->orderObject->order_id) ) {
            $this->error('Quotation not found','ERROR_ORDER_NOT_FOUND');
            $this->status = 'ERROR';
        }
    }

    public function build()
    {
        if ( isset($this->orderObject) && is_object($this->orderObject) ) {
            $orderData = (array)$this->orderObject;
            $orderData['status_history'] = $this->orderObject->getStatusHistory();
            $this->quotation = new Quotation($orderData);
            $this->quotation->build();
        }
        parent::build();
    }
}
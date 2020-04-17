<?php
declare (strict_types=1);

namespace common\modules\orderShipping\NovaPoshta\API\EndPoints;

use common\modules\orderShipping\NovaPoshta\API\DTO\InternetDocument;
use GuzzleHttp\RequestOptions;

class Document extends AbstractApi
{
    protected $options = [
        'json' => [
            'modelName' => 'InternetDocument',
            'calledMethod' => '',
            'methodProperties' => null,
        ],
    ];

    /**
     * @param string $description
     * @param float $cost
     * @param float $weight
     * @param string $serviceType
     * @param string $senderRef
     * @param string $citySenderRef
     * @param string $senderAddressRef
     * @param string $contactSenderRef
     * @param string $sendersPhone
     * @param string $recipientCity
     * @param string $recipientArea
     * @param string $recipientAddressName
     * @param string $recipient
     * @param string $contactRecipient
     * @param string $recipientsPhone
     * @param string $dateShipment
     * @param string $recipientType
     * @param string $payerType
     * @param string $paymentMethod
     * @param string $cargoType
     * @param float $volumeGeneral
     * @param int $seatsAmount
     * @param string $recipientHouse
     * @param string $recipientFlat
     * @param string $recipientName
     * @param int $backwardDelivery
     * @param float $backwardDeliveryCost
     * @return InternetDocument
     */
    public function save(
        string $description,
        float $cost,
        float $weight,
        string $serviceType,
        string $senderRef,
        string $citySenderRef,
        string $senderAddressRef,
        string $contactSenderRef,
        string $sendersPhone,
        string $recipientCity,
        string $recipientArea,
        string $recipientAddressName,
        string $recipient,
        string $contactRecipient,
        string $recipientsPhone,
        string $dateShipment,
        string $recipientType,
        string $payerType,
        string $paymentMethod,
        string $cargoType,
        float $volumeGeneral,
        int $seatsAmount,
        string $recipientHouse,
        string $recipientFlat,
        string $recipientName,
        int $backwardDelivery,
        float $backwardDeliveryCost
    ): InternetDocument
    {
        $backward = [];
        if ($backwardDelivery === 1) {
            $backward = [
                'BackwardDeliveryData' => [
                    [
                        'PayerType' => 'Recipient',
                        'CargoType' => 'Money',
                        'RedeliveryString' => $backwardDeliveryCost,
                    ]
                ]
            ];
        }
        $data = array_merge([
            'NewAddress' => 0,
            'PayerType' => $payerType,
            'PaymentMethod' => $paymentMethod,
            'CargoType' => $cargoType,
            'VolumeGeneral' => $volumeGeneral / 250.2,
            'Weight' => $weight,
            'ServiceType' => $serviceType,
            'SeatsAmount' => $seatsAmount,
            'Description' => $description,
            'Cost' => $cost,
            'CitySender' => $citySenderRef,
            'Sender' => $senderRef,
            'SenderAddress' => $senderAddressRef,
            'ContactSender' => $contactSenderRef,
            'senderIsWarehouse' => true,
            'SendersPhone' => $sendersPhone,
            'SenderCounterpartyType' => 'PrivatePerson',
            'RecipientCounterpartyType' => 'PrivatePerson',
            'Recipient' => $recipient,
            'ContactRecipient' => $contactRecipient,
            'CityRecipient' => $recipientCity,
            'AreaRecipient' => $recipientArea,
            'RecipientAddress' => $recipientAddressName,
            'RecipientType' => $recipientType,
            'RecipientsPhone' => $recipientsPhone,
            'DateTime' => $dateShipment,
            'RecipientHouse' => $recipientHouse,
            'RecipientFlat' => $recipientFlat,
            'RecipientName' => trim($recipientName),
        ], $backward);
        $internetDocument = $this->client->post('', $this->getOptions('save', $data));
        if (is_array($internetDocument->data) && $internetDocument->data) {
            $doc = InternetDocument::create(
                $internetDocument->data[0]->Ref,
                (float)$internetDocument->data[0]->CostOnSite,
                \DateTimeImmutable::createFromFormat('d.m.Y', $internetDocument->data[0]->EstimatedDeliveryDate),
                $internetDocument->data[0]->IntDocNumber,
                $internetDocument->data[0]->TypeDocument
            );
            return $doc;
        }
        $this->throwError($internetDocument, 'Nova Poshta could not save Internet Document');
    }

    public function delete(string $ref): bool
    {
        $internetDocument = $this->client->post('', $this->getOptions('delete', [
            'DocumentRefs' => $ref,
        ]));
        return (bool)$internetDocument->success;
    }

    public function printDocument(string $id, string $type = 'pdf')
    {
        $response = $this->client->getOriginal(
            $this->getDocumentLink($id, $type),
            [
                //RequestOptions::SINK            => $stream, // the body of a response
                RequestOptions::CONNECT_TIMEOUT => 10.0,    // request
                RequestOptions::TIMEOUT => 60.0,    // response
            ]
        );
        return $response->getBody()->getContents();
    }

    /**
     * @param string $id
     * @param string $type
     * @return string
     */
    public function getDocumentLink(string $id, string $type = 'pdf'): string
    {
        $apiKey = $this->client->getApiKey();
        return "https://my.novaposhta.ua/orders/printDocument/orders[]/{$id}/type/{$type}/apiKey/{$apiKey}";

    }
}

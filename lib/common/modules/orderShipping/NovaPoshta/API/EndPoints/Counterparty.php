<?php
declare (strict_types=1);

namespace common\modules\orderShipping\NovaPoshta\API\EndPoints;


use common\modules\orderShipping\NovaPoshta\API\DTO\ContactPerson;
use common\modules\orderShipping\NovaPoshta\API\DTO\Persone;
use common\modules\orderShipping\NovaPoshta\API\DTO\PersonInfo;

class Counterparty extends AbstractApi
{
    protected $options = [
        'json' => [
            'modelName' => 'Counterparty',
            'calledMethod' => '',
            'methodProperties' => null,
        ],
    ];

    /**
     * @param string $counterpartyProperty
     * @param string $cityRef
     * @param string $findByString
     * @param int $page
     * @return \common\modules\orderShipping\NovaPoshta\API\DTO\Counterparty[]|array
     */
    public function getCounterparties(
        string $counterpartyProperty = 'Recipient',
        string $cityRef = '',
        string $findByString = '',
        int $page = 0
    ): array
    {
        $counterparts = $this->client->post('', $this->getOptions('getCounterparties', [
            'CounterpartyProperty' => $counterpartyProperty,
            'Page' => $page,
            'CityRef' => $cityRef,
            'FindByString' => $findByString,
        ]));
        if (is_array($counterparts->data) && $counterparts->data) {
            $counterparty = [];
            foreach ($counterparts->data as $data) {
                $counterparty[] = \common\modules\orderShipping\NovaPoshta\API\DTO\Counterparty::create(
                    $data->Ref,
                    $data->Description,
                    $data->City,
                    $data->FirstName,
                    $data->LastName,
                    $data->MiddleName,
                    $data->CounterpartyFullName,
                    $data->OwnershipFormRef,
                    $data->OwnershipFormDescription,
                    $data->EDRPOU,
                    $data->CounterpartyType,
                    $data->CityDescription,
                    $data->Counterparty
                );
            }
            return $counterparty;
        }
        $this->throwError($counterparts, 'Nova Poshta could not receive Counterparts');
    }

    /**
     * @param string $ref
     * @return array|PersonInfo[]
     */
    public function getCounterpartyContactPersons(string $ref): array
    {
        $contact = $this->client->post('', $this->getOptions('getCounterpartyContactPersons', [
            'Ref' => $ref,
        ]));
        if (is_array($contact->data) && $contact->data) {
            $personInfo = [];
            foreach ($contact->data as $data) {
                $personInfo[] = PersonInfo::create(
                    $data->Ref,
                    $data->Description,
                    $data->FirstName,
                    $data->LastName,
                    $data->MiddleName,
                    $data->Email,
                    $data->Phones
                );
            }
            return $personInfo;
        }
        $this->throwError($contact, 'Nova Poshta could not receive ContactPersons');
    }
    /**
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @param string $phone
     * @param string $email
     * @param string $counterpartyType
     * @param string $counterpartyProperty
     * @return Persone
     */
    public function save(
        string $firstName,
        string $middleName,
        string $lastName,
        string $phone,
        string $email,
        string $counterpartyType = 'PrivatePerson',
        string $counterpartyProperty = 'Recipient'
    ): Persone
    {
        $counterparty = $this->client->post('', $this->getOptions('save', [
            'FirstName' => $firstName,
            'MiddleName' => $middleName,
            'LastName' => $lastName,
            'Phone' => $phone,
            'Email' => $email,
            'CounterpartyType' => $counterpartyType,
            'CounterpartyProperty' => $counterpartyProperty,
        ]));
                if (is_array($counterparty->data) && $counterparty->data) {
            $person = Persone::create(
                $counterparty->data[0]->Ref,
                $counterparty->data[0]->Description,
                $counterparty->data[0]->FirstName,
                $counterparty->data[0]->MiddleName,
                $counterparty->data[0]->LastName,
                (string)$counterparty->data[0]->Counterparty,
                (string)$counterparty->data[0]->OwnershipForm,
                (string)$counterparty->data[0]->OwnershipFormDescription,
                $counterparty->data[0]->EDRPOU,
                $counterparty->data[0]->CounterpartyType,
                ContactPerson::create(
                    $counterparty->data[0]->ContactPerson->data[0]->Ref,
                    $counterparty->data[0]->ContactPerson->data[0]->Description,
                    $counterparty->data[0]->ContactPerson->data[0]->FirstName,
                    $counterparty->data[0]->ContactPerson->data[0]->LastName,
                    $counterparty->data[0]->ContactPerson->data[0]->MiddleName
                )
            );
            return $person;
        }
        $this->throwError($counterparty, 'Nova Poshta could not save Counterparty');
    }

    /**
     * !!!!NOT USE!!!!! PREPARE
     * @param string $ref
     * @param int $page
     * @return mixed|null
     */
    public function getCounterpartyAddresses(
        string $ref = '',
        int $page = 0
    )
    {
        $counterparts = $this->client->post('', $this->getOptions('getCounterpartyAddresses', [
            'Page' => $page,
            'Ref' => $ref,
        ]));
        return $counterparts;
        // throw new \RuntimeException('Nova Poshta could not receive Counterparty Addresses');
    }
}

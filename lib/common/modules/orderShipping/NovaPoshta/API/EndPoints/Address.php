<?php
declare (strict_types=1);

namespace common\modules\orderShipping\NovaPoshta\API\EndPoints;

use common\modules\orderShipping\NovaPoshta\API\DTO\Area;
use common\modules\orderShipping\NovaPoshta\API\DTO\City;
use common\modules\orderShipping\NovaPoshta\API\DTO\Street;
use common\modules\orderShipping\NovaPoshta\API\DTO\StreetAddress;
use common\modules\orderShipping\NovaPoshta\API\DTO\Warehouse;

class Address extends AbstractApi
{
    protected $options = [
        'json' => [
            'modelName' => 'Address',
            'calledMethod' => '',
            'methodProperties' => null,
        ],
    ];

    /**
     * @param string $areaRef
     * @param int $page
     * @return Area[]|array
     * @throws \RuntimeException
     */
    public function getAreas(string $areaRef = '', int $page = 0 )
    {
        $areas = $this->getFromCache(static::class.'-'.__FUNCTION__.'-'.$areaRef);
        if ($areas) {
            return $areas;
        }
        $areas = $this->client->post('', $this->getOptions('getAreas', [
            'Ref' => $areaRef,
            'Page' => $page,
        ]));
        if (is_array($areas->data) && $areas->data) {
            $districts = [];
            foreach ($areas->data as $data) {
                $districts[] = Area::create($data->Ref, $data->Description, $data->AreasCenter);
            }
            $this->setToCache(static::class.'-'.__FUNCTION__.'-'.$areaRef, $districts);
            return $districts;
        }
        $this->throwError($areas, 'Nova Poshta could not receive Areas');
    }

    /**
     * @param string $cityRef
     * @param string $findByString
     * @param int $page
     * @param bool $trimArea
     * @return array
     */
    public function getCities(string $cityRef = '', string $findByString = '', int $page = 0, bool $trimArea = true)
    {
        $cities = $this->getFromCache(static::class.'-'.__FUNCTION__.'-'.$cityRef.'-'.$findByString);
        if ($cities) {
            return $cities;
        }
        $cities = $this->client->post('', $this->getOptions('getCities',[
            'Ref' => $cityRef,
            'FindByString' => $findByString,
            'Page' => $page,
        ]));
        if (is_array($cities->data) && $cities->data) {
            $towns = [];
            foreach ($cities->data as $data) {
                $description = property_exists($data, 'Description'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'Description'.ucfirst($this->client->getLanguage())} ?: $data->Description)
                    : $data->Description;
                if ($trimArea && mb_strpos($description, ' обл') !== false) {
                    $description = trim(preg_replace('/(\(|\.|\,)([^\(\.\,]*)(\s)(обл(\.*)(\,*))(\.|\s|\))/iu', '', $description), ')');
                    $description = implode(' ', array_map('trim',explode('(', $description)));
                }
                $descriptionSettlement = property_exists($data, 'SettlementTypeDescription'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'SettlementTypeDescription'.ucfirst($this->client->getLanguage())} ?: $data->SettlementTypeDescription)
                    : $data->SettlementTypeDescription;

                $towns[] = City::create(
                    (string)$data->Ref,
                    $description,
                    (string)$data->Area,
                    (int)$data->Delivery1,
                    (int)$data->Delivery2,
                    (int)$data->Delivery3,
                    (int)$data->Delivery4,
                    (int)$data->Delivery5,
                    (int)$data->Delivery6,
                    (int)$data->Delivery7,
                    (string)$data->SettlementType,
                    (string)$descriptionSettlement,
                    (int)$data->CityID,
                    (int)$data->IsBranch,
                    (string)$data->PreventEntryNewStreetsUs,
                    (string)json_encode($data->Conglomerates),
                    (int)$data->SpecialCashCheck
                    );
            }
            $this->setToCache(static::class.'-'.__FUNCTION__.'-'.$cityRef.'-'.$findByString, $towns);
            return $towns;
        }
        $this->throwError($cities, 'Nova Poshta could not receive Cities');
    }

    public function getWarehouses(
        string $cityRef = '',
        string $cityName = '',
        int $page = 0,
        int $limit = 0
    )
    {
        $warehouses = $this->getFromCache(static::class.'-'.__FUNCTION__.'-'.$cityRef.'-'.$cityName);
        if ($warehouses) {
            return $warehouses;
        }
        $warehouses = $this->client->post('', $this->getOptions('getWarehouses',[
            'CityRef' => $cityRef,
            'CityName' => $cityName,
            'Page' => $page,
            'Limit' => $limit,
            'Language' => $this->client->getLanguage(),
        ], 'AddressGeneral'));
        if (is_array($warehouses->data) && $warehouses->data) {
            $stores = [];
            foreach ($warehouses->data as $data) {
                $description = property_exists($data, 'Description'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'Description'.ucfirst($this->client->getLanguage())} ?: $data->Description)
                    : $data->Description;
                $shortAddress = property_exists($data, 'ShortAddress'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'ShortAddress'.ucfirst($this->client->getLanguage())} ?: $data->ShortAddress)
                    : $data->ShortAddress;
                $cityDescription = property_exists($data, 'CityDescription'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'CityDescription'.ucfirst($this->client->getLanguage())} ?: $data->CityDescription)
                    : $data->CityDescription;
                $stores[] = Warehouse::create(
                    (string)$data->Ref,
                    (string)$description,
                    (string)$data->SiteKey,
                    (string)$shortAddress,
                    (string)$data->Phone,
                    (string)$data->TypeOfWarehouse,
                    (string)$data->Number,
                    (string)$data->CityRef,
                    (string)$cityDescription,
                    (int)$data->PostFinance,
                    (int)$data->BicycleParking,
                    (int)$data->PaymentAccess,
                    (int)$data->POSTerminal,
                    (int)$data->InternationalShipping,
                    (int)$data->TotalMaxWeightAllowed,
                    (int)$data->PlaceMaxWeightAllowed,
                    (string)$data->DistrictCode,
                    (string)$data->WarehouseStatus,
                    (string)$data->CategoryOfWarehouse
                );
            }
            $this->setToCache(static::class.'-'.__FUNCTION__.'-'.$cityRef.'-'.$cityName, $stores);
            return $stores;
        }
        $this->throwError($warehouses, 'Nova Poshta could not receive Warehouses');
    }

    public function getStreet(string $cityRef, string $findByString = '', int $page = 0)
    {
        $streets = $this->client->post('', $this->getOptions('getStreet',[
            'CityRef' => $cityRef,
            'FindByString' => $findByString,
            'Page' => $page,
        ]));
        if (is_array($streets->data) && $streets->data) {
            $street = [];
            foreach ($streets->data as $data) {
                $street[] = Street::create($data->Ref, $data->Description, $data->StreetsTypeRef, $data->StreetsType);
            }
            return $street;
        }
        $this->throwError($streets, 'Nova Poshta could not receive Cities');
    }

    public function save(string $counterpartyRef, string $streetRef, string $buildingNumber, string $flat, string $note = '')
    {
        $address = $this->client->post('', $this->getOptions('save',[
            'CounterpartyRef' => $counterpartyRef,
            'StreetRef' => $streetRef,
            'BuildingNumber' => $buildingNumber,
            'Flat' => $flat,
            'Note' => $note,
        ]));
        if (is_array($address->data) && $address->data) {
            return StreetAddress::create($address->data[0]->Ref, $address->data[0]->Description);
        }
        $this->throwError($address, 'Nova Poshta could not save Address');
    }

}

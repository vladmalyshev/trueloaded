<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API\EndPoints;


use common\modules\orderShipping\NovaPoshta\API\DTO\CargoDescription;
use common\modules\orderShipping\NovaPoshta\API\DTO\CargoType;

class Common extends AbstractApi
{
    protected $options = [
        'json' => [
            'modelName' => 'Common',
            'calledMethod' => '',
            'methodProperties' => null,
        ],
    ];

    /**
     * @return CargoType[]|array
     * @throws \RuntimeException
     */
    public function getCargoTypes(): array
    {
        $cargoes = $this->getFromCache(static::class.'-'.__FUNCTION__);
        if ($cargoes) {
            return $cargoes;
        }
        $cargoes = $this->client->post('', $this->getOptions('getCargoTypes'));
        if (is_array($cargoes->data) && $cargoes->data) {
            $types = [];
            foreach ($cargoes->data as $data) {
                $types[] = CargoType::create($data->Ref, $data->Description);
            }
            $this->setToCache(static::class.'-'.__FUNCTION__, $types);
            return $types;
        }
        $this->throwError($cargoes, 'Nova Poshta could not receive Cargo Types');
    }

    /**
     * @return array|CargoDescription[]
     * @throws \RuntimeException
     */
    public function getCargoDescription(): array
    {
        $cargoDescriptions = $this->getFromCache(static::class.'-'.__FUNCTION__);
        if ($cargoDescriptions) {
            return $cargoDescriptions;
        }
        $cargoDescriptions = $this->client->post('', $this->getOptions('getCargoDescriptionList'));
        if (is_array($cargoDescriptions->data) && $cargoDescriptions->data) {
            $descriptions = [];
            foreach ($cargoDescriptions->data as $data) {
                $description = property_exists($data, 'Description'.ucfirst($this->client->getLanguage()))
                    ? ($data->{'Description'.ucfirst($this->client->getLanguage())} ?: $data->Description)
                    : $data->Description;
                $descriptions[] = CargoDescription::create($data->Ref, $description);
            }
            $this->setToCache(static::class.'-'.__FUNCTION__, $descriptions);
            return $descriptions;
        }
        $this->throwError($cargoDescriptions, 'Nova Poshta could not receive Cargo Descriptions');
    }

}

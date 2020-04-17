<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare (strict_types=1);

namespace common\modules\label\np\forms;

use common\modules\label\np;
use common\modules\orderShipping\NovaPoshta\API\DTO\Area;
use common\modules\orderShipping\NovaPoshta\API\DTO\CargoType;
use common\modules\orderShipping\NovaPoshta\API\DTO\City;
use common\modules\orderShipping\NovaPoshta\API\DTO\Warehouse;
use yii\base\Model;

/**
 *
 * @property array $warehousesRecipientArray
 * @property array $cargoTypeArray
 * @property array $recipientWarehouses
 * @property array $warehousesSenderArray
 * @property array $areasArray
 * @property array $citiesArray
 * @property array $senderWarehouses
 */
class NovaPoshtaCreateDocumentCollectForm extends Model
{
    /** @var CargoType[] */
    private $cargoType = [];
    /** @var string */
    public $cargoDescriptions;
    /** @var Area[] */
    private $areas = [];
    /** @var City[] */
    private $cities = [];

    /** @var Warehouse[] */
    private $warehousesSender = [];
    /** @var Warehouse[] */
    private $warehousesRecipient = [];

    /** @var Area */
    private $areaSenderData;
    /** @var City */
    private $citySenderData;
    /** @var Warehouse */
    private $warehouseSenderData;

    /** @var string */
    public $areaSender;
    /** @var string */
    public $citySender;
    /** @var string */
    public $warehouseSender;
    /** @var string */
    public $telephoneSender;
    /** @var Area */
    private $areaRecipientData;
    /** @var City */
    private $cityRecipientData;
    /** @var Warehouse */
    private $warehouseRecipientData;

    /** @var string */
    public $areaRecipient;
    /** @var string */
    public $cityRecipient;
    /** @var string */
    public $warehouseRecipient;

    /** @var string */
    public $cityNameWarehouseRecipient;
    /** @var string */
    public $areaNameWarehouseRecipient;
    /** @var string */
    public $addressNameWarehouseRecipient;

    /** @var string */
    public $cityNameRecipient;
    /** @var string */
    public $areaNameRecipient;
    /** @var string */
    public $areaRegionsNameRecipient;
    /** @var string */
    public $addressNameRecipient;
    /** @var string */
    public $houseRecipient;
    /** @var string */
    public $flatRecipient;

    /** @var string */
    public $firstname;
    /** @var string */
    public $lastname;
    /** @var string */
    public $middlename;
    /** @var string */
    public $telephone;
    /** @var string */
    public $email;
    /** @var float */
    public $seatsAmount = 1;
    /** @var float */
    public $weight = 0;
    /** @var float */
    public $volumeGeneral = 0;
    /** @var float */
    public $cost = 0;
    /** @var string */
    public $description;
    /** @var string */
    public $cargo;
    /** @var string */
    public $senderRef;
    /** @var string */
    public $senderContactRef;
    /** @var string */
    public $type;
    /** @var string */
    public $payerType = 'Recipient';
    /** @var string */
    public $deliveryDate;
    /** @var int */
    public $backwardDelivery = 0;
    public $backwardCost = 0;
    public $ordersLabelId = 0;
    public $orderId = 0;
    public function rules()
    {
        return [
            [['weight', 'seatsAmount', 'volumeGeneral', 'cost', 'backwardCost'], 'number'],
            [['type','payerType'], 'string'],
            [['areaSender', 'citySender', 'warehouseSender', 'telephoneSender'], 'string'],
            [['cityNameWarehouseRecipient', 'areaNameWarehouseRecipient', 'addressNameWarehouseRecipient'], 'string'],
            [['areaNameRecipient', 'cityNameRecipient', 'addressNameRecipient', 'houseRecipient', 'flatRecipient'], 'string'],
            [['areaRecipient', 'cityRecipient', 'warehouseRecipient'], 'string'],
            [['firstname', 'lastname', 'telephone', 'middlename'], 'string'],
            [['cargo', 'description'], 'string'],
            [['senderRef', 'senderContactRef'], 'string'],
            [['deliveryDate'], 'string'],
            [['backwardDelivery', 'ordersLabelId', 'orderId'], 'number'],
        ];
    }

    public function __construct($config = [])
    {
        $this->areaSenderData = Area::createDumb();
        $this->citySenderData = City::createDumb();
        $this->warehouseSenderData = Warehouse::createDumb();

        $this->areaRecipientData = Area::createDumb();
        $this->cityRecipientData = City::createDumb();
        $this->warehouseRecipientData = Warehouse::createDumb();

        $this->areaSender = $this->areaSenderData->getRef();
        $this->citySender = $this->citySenderData->getRef();
        $this->warehouseSender = $this->warehouseSenderData->getRef();

        $this->areaRecipient = $this->areaRecipientData->getRef();
        $this->cityRecipient = $this->cityRecipientData->getRef();
        $this->warehouseRecipient = $this->warehouseRecipientData->getRef();

        parent::__construct($config);
    }

    public function withPrepareInfo
    (
        array $cargoType = [],
        string $cargoDescriptions,
        string $cargoDescription = '',
        array $areas = [],
        array $cities = [],
        array $warehouses = [],
        float $amount = 0,
        float $weight = 0,
        float $volumeGeneral = 0,
        string $deliveryDate ='',
        int $ordersLabelId = 0,
        int $orderId = 0,
        int $backwardDelivery = 0
    )
    {
        $this->cargoType = $cargoType;
        $this->cargoDescriptions = $cargoDescriptions;
        $this->description = $cargoDescription;
        $this->areas = $areas;
        $this->cities = $cities;
        $this->warehousesSender = $warehouses;
        $this->warehousesRecipient = $warehouses;
        $this->cost = $amount;
        $this->weight = $weight;
        $this->volumeGeneral = $volumeGeneral;
        $this->deliveryDate = $deliveryDate;
        $this->ordersLabelId = $ordersLabelId;
        $this->orderId = $orderId;
        $this->backwardDelivery = $backwardDelivery;
        $this->backwardCost = $amount;
        return $this;
    }

    public function setSenderWarehouses(array $warehouses = [])
    {
        $this->warehousesSender = $warehouses;
        return $this;
    }

    public function setRecipientWarehouses(array $warehouses = [])
    {
        $this->warehousesRecipient = $warehouses;
        return $this;
    }
    public function withSenderInfo(
        Area $area,
        City $city,
        Warehouse $warehouse,
        string $telephone,
        string $senderRef,
        string $senderContactRef
    )
    {
        $this->areaSenderData = $area;
        $this->areaSender = $area->getRef();
        $this->citySenderData = $city;
        $this->citySender = $city->getRef();
        $this->warehouseSenderData = $warehouse;
        $this->warehouseSender = $warehouse->getRef();
        $this->telephoneSender = $telephone;
        $this->senderRef = $senderRef;
        $this->senderContactRef = $senderContactRef;
        return $this;
    }
    public function withRecipientCollectInfo(
        Area $area,
        City $city,
        Warehouse $warehouse,
        string $firstname,
        string $lastname,
        string $middlename,
        string $telephone,
        string $email
    )
    {
        $this->areaRecipientData = $area;
        $this->areaRecipient = $area->getRef();
        $this->cityRecipientData = $city;
        $this->cityRecipient = $city->getRef();
        $this->warehouseRecipientData = $warehouse;
        $this->warehouseRecipient = $warehouse->getRef();

        $this->cityNameWarehouseRecipient = $city->getDescription();
        $this->areaNameWarehouseRecipient = $area->getDescription();
        $this->addressNameWarehouseRecipient = $warehouse->getDescription();
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->middlename = $middlename;
        $this->telephone = $telephone;
        $this->email = $email;
        return $this;
    }

    public function withRecipientAddressInfo(
        string $area,
        string $areaRegions,
        string $city,
        string $address,
        string $house,
        string $flat,
        string $firstname,
        string $lastname,
        string $middlename,
        string $telephone,
        string $email
    )
    {
        $this->cityNameRecipient = $city;
        $this->areaNameRecipient = $area;
        $this->areaRegionsNameRecipient = $areaRegions;
        $this->addressNameRecipient = $address;
        $this->houseRecipient = $house;
        $this->flatRecipient = $flat;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->middlename = $middlename;
        $this->telephone = $telephone;
        $this->email = $email;
        return $this;
    }

    public function withType(string $type = np::TYPE_DELIVERY_WAREHOUSE_DOORS): self
    {
        if (array_key_exists($type, np::TYPE_DELIVERIES)) {
            $this->type = $type;
        }
        return $this;
    }

    public function attributeLabels()
    {
        return [
            'cargoDescription' => TEXT_CARGO_DESCRIPTION_NP,
            'cargoType' => TEXT_TYPE_OF_CARGO_NP,
            'seatsAmount' => TEXT_SEATS_AMOUNT_NP,
            'weight' => TEXT_ACTUAL_WEIGHT_NP,
            'volumeGeneral' => TEXT_VOLUME_GENERAL_NP,
            'citySender' => TEXT_SENDER_CITY_NP,
            'warehouseSender' => TEXT_SENDER_WAREHOUSE_NP,
            'areaSender' => TEXT_SENDER_AREAS_NP,
            'firstname' => ENTRY_FIRST_NAME,
            'lastname' => ENTRY_LAST_NAME,
            'telephone' => ENTRY_TELEPHONE_NUMBER,
            'type' => TEXT_TYPE_DELIVERY,
            'cityRecipient' => TEXT_RECIPIENT_CITY_NP,
            'warehouseRecipient' => TEXT_RECIPIENT_WAREHOUSE_NP,
            'areaRecipient' => TEXT_RECIPIENT_AREAS_NP,
            'addressNameRecipient' => ENTRY_STREET_ADDRESS,
            'houseRecipient' => TEXT_RECIPIENT_HOUSE_NP,
            'flatRecipient' => TEXT_RECIPIENT_FLAT_NP,
            'cost' => TEXT_AMOUNT,
            'email' => TEXT_EMAIL,
            'payerType' => TEXT_SHIPMENT_PAY,
            'backwardDelivery' => TEXT_NALOZHENNUJ_PLATEZH_NP
        ];
    }

    public function getLabelByField(string $field): string
    {
        static $labels = null;
        if ($labels === null) {
            $model = new self();
            $labels = $model->attributeLabels();
        }
        return $labels[$field] ?? '';
    }


    /**
     * @return CargoType[]
     */
    public function getCargoType(): array
    {
        return $this->cargoType;
    }

    /**
     * @return Area[]
     */
    public function getAreas(): array
    {
        return $this->areas;
    }

    public function getAreasArray(): array
    {
        $result = [];
        foreach ($this->areas as $area) {
            $result[$area->getRef()] = $area->getDescription();
        }
        return $result;
    }

    public function getCitiesArray(): array
    {
        $result = [];
        foreach ($this->cities as $city) {
            $result[$city->getRef()] = $city->getDescription();
        }
        return $result;
    }

    public function getCitiesRecipientArray(): array
    {
        $result = [];
        foreach ($this->cities as $city) {
            if ($city->getAreaRef() === $this->areaRecipient) {
                $result[$city->getRef()] = $city->getDescription();
            }
        }
        return $result;
    }

    public function getCitiesSenderArray(): array
    {
        $result = [];
        foreach ($this->cities as $city) {
            if ($city->getAreaRef() === $this->areaSender) {
                $result[$city->getRef()] = $city->getDescription();
            }
        }
        return $result;
    }

    public function getWarehousesSenderArray(): array
    {
        $result = [];
        foreach ($this->warehousesSender as $warehouse) {
            $result[$warehouse->getRef()] = $warehouse->getDescription();
        }
        return $result;
    }

    public function getWarehousesRecipientArray(): array
    {
        $result = [];
        foreach ($this->warehousesRecipient as $warehouse) {
            $result[$warehouse->getRef()] = $warehouse->getDescription();
        }
        return $result;
    }
    public function getCargoTypeArray(): array
    {
        $result = [];
        foreach ($this->cargoType as $type) {
            $result[$type->getRef()] = $type->getDescription();
        }
        return $result;
    }

    /**
     * @return City[]
     */
    public function getCities(): array
    {
        return $this->cities;
    }

    public function getCitiesJson(): string
    {
        $result = [];
        foreach ($this->cities as $city) {
            $result[] = [
                'Ref' => $city->getRef(),
                'Area' => $city->getAreaRef(),
                'Description' => $city->getDescription(),
            ];
        }
        return json_encode($result);
    }

    public function getPayers(): array
    {
        return [
            \common\modules\label\np::COUNTERPARTY_RECIPIENT => TEXT_RECIPIENT,
            \common\modules\label\np::COUNTERPARTY_SENDER => TEXT_SENDER,
        ];
    }
    public function getDeliveryDate(): \DateTimeImmutable
    {
        return $this->deliveryDate ? \DateTimeImmutable::createFromFormat('Y-m-d', $this->deliveryDate) : new \DateTimeImmutable();
    }
}

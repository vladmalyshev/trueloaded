<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\extensions\PersonalCatalog\services;

use common\classes\extended\OrderAbstract;
use common\classes\shopping_cart;
use common\extensions\PersonalCatalog\Enums\PersonalCatalogFlag;
use common\extensions\PersonalCatalog\repositories\PersonalCatalogRepository;
use common\models\PersonalCatalog;
use common\models\Customers;
use frontend\design\Info;

class PersonalCatalogService
{
    /** @var PersonalCatalogRepository */
    private $personalCatalogRepository;

    public function __construct(PersonalCatalogRepository $personalCatalogRepository)
    {
        $this->personalCatalogRepository = $personalCatalogRepository;
    }

    /**
     * @param int $customerId
     * @param int $languageId
     * @param bool|int $limit
     * @param int $sort
     * @return array|bool
     */
    public function setListingProductsToContainer(
        int $customerId,
        int $languageId = 1,
        $limit = false,
        int $sort = SORT_ASC
    )
    {
        $products = $this->personalCatalogRepository->findProductsForCustomer(
            $customerId,
            $languageId,
            $limit,
            $sort,
            true
        );
        if (!$products) {
            return false;
        }
        $productsAssoc = [];
        foreach ($products as $product) {
            $productsAssoc[$product['uprid']] = $product;
        }
        $productsInfo = Info::getListProductsDetails(array_column($products, 'uprid'), ['personal-catalog' => 1]);
        array_walk($productsInfo, static function(&$item) use ($productsAssoc) {
            if (isset($productsAssoc[$item['products_id']])) {
                $item['add_qty'] = $productsAssoc[$item['products_id']]['qty'];
            }
        });
        return $productsInfo;
    }

    /**
     * @param int $customerId
     * @param int|string $productId
     * @return bool
     */
    public function isInPersonalCatalog(int $customerId, $productId): bool
    {
        if ($customerId <= 0 || (int)$productId <= 0) {
            return false;
        }
        return $this->personalCatalogRepository->isInPersonalCatalog($customerId, $productId);
    }

    /**
     * @param $productsId
     * @param bool $getButton
     * @param string $saveButtonId
     * @return string
     * @throws \Exception
     */
    public function ajaxButton($productsId, bool $getButton = true, string $saveButtonId = ''): string
    {
        $params = [];
        $params['get_button'] = true;
        $params['products_id'] = $productsId;
        $params['saveButtonId'] = $saveButtonId;
        return \frontend\design\boxes\product\PersonalCatalogButton::widget(['params' => $params]);
    }

    /**
     * @param bool|Customers $customer
     * @return bool
     */
    public function isAllowed($customer): bool
    {
        /** @var Customers $customer */
        return !(($customer === false) ||
			!$customer->customers_id ||
            !$this->isModuleActive() ||
            ((defined('GROUPS_DISABLE_CHECKOUT') && GROUPS_DISABLE_CHECKOUT)) ||
            ($customer->opc_temp_account === 1)
        );
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function isModuleActive(string $moduleName = 'PersonalCatalog'): bool
    {
        return \common\helpers\Acl::checkExtensionAllowed($moduleName, 'allowed');
    }

    /**
     * @param int $customerId
     * @param string|int $productId
     * @param int $typeAdded
     * @param int $qty
     * @return bool
     */
    public function createAndSave(
        int $customerId,
        $productId,
        int $typeAdded = PersonalCatalogFlag::ADDED_FROM_PRODUCT,
        int $qty = 1
    ): bool
    {
        $product = PersonalCatalog::create($customerId, $productId, $typeAdded, $qty);
        return $this->personalCatalogRepository->save($product);
    }

    /**
     * @param $productId
     * @param int $customerId
     * @param bool $asArray
     * @return array|PersonalCatalog|null
     */
    public function findByPrimary($productId, int $customerId, bool $asArray = false)
    {
        return $this->personalCatalogRepository->findByPrimary($productId, $customerId, $asArray);
    }

    /**
     * @param $productId
     * @param int $customerId
     * @param bool $asArray
     * @return array|PersonalCatalog|null
     * @throws \DomainException
     */
    public function getByPrimary($productId, int $customerId, bool $asArray = false)
    {
        return $this->personalCatalogRepository->getByPrimary($productId, $customerId, $asArray);
    }

    /**
     * @param PersonalCatalog $product
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function remove(PersonalCatalog $product)
    {
        return $this->personalCatalogRepository->remove($product);
    }

    public function saveFromOrder(OrderAbstract $order)
    {
        /** @var Customers $customer */
        $customer = $order->manager->getCustomersIdentity();
        if (!$this->isAllowed($customer)) {
            return;
        }
        /** @var shopping_cart $cart */
        $cart = $order->getCart();
        foreach ($order->products as $product) {
            try {
                if ($cart->isAmount($product['id'])) {
                    continue;
                }
                if (!$this->isInPersonalCatalog($customer->customers_id, $product['id'])) {
                    $this->createAndSave($customer->customers_id, $product['id'], PersonalCatalogFlag::ADDED_FROM_ORDER, (int)$product['qty']);
                }
            } catch (\Exception $e) {
                \Yii::warning($e->getMessage());
            }

        }
    }
}

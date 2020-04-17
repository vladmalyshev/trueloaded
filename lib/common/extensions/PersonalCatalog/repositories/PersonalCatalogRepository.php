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

namespace common\extensions\PersonalCatalog\repositories;

use common\models\PersonalCatalog;
use yii\db\ActiveQuery;

class PersonalCatalogRepository
{
    /**
     * @param int $customerId
     * @param int|string $productId
     * @return bool
     */
    public function isInPersonalCatalog(int $customerId, $productId): bool
    {
        $check = PersonalCatalog::find()
            ->where(['customers_id' => $customerId, 'uprid' => $productId])
            ->exists();
        return $check;
    }

    /**
     * @param PersonalCatalog $product
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \RuntimeException
     */
    public function remove(PersonalCatalog $product)
    {
        if ($product->delete() === false) {
            throw new \RuntimeException('Product from Personal Catalog remove error');
        }
        return true;
    }

    /**
     * @param PersonalCatalog $product
     * @param bool $validation
     * @return bool
     * @throws \RuntimeException
     */
    public function save(PersonalCatalog $product, bool $validation = false)
    {
        if ($product->save($validation) === false) {
            throw new \RuntimeException('Product saving in Personal Catalog error.');
        }
        return true;
    }

    /**
     * @param PersonalCatalog $product
     * @param array $params
     * @param bool $validation
     * @param bool $safeOnly
     * @return array|bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function edit(PersonalCatalog $product, array $params = [], bool $validation = false, bool $safeOnly = false)
    {
        foreach ($params as $attribute => $param) {
            if (!$product->hasAttribute($attribute)) {
                unset($params[$attribute]);
            }
        }
        $product->setAttributes($params, $safeOnly);
        if ($product->update($validation, array_keys($params)) === false) {
            return $product->getErrors();
        }
        return true;
    }

    /**
     * @param string|int $productId
     * @param int $customerId
     * @param bool $asArray
     * @return array|PersonalCatalog|null
     */
    public function findByPrimary($productId, int $customerId, bool $asArray = false)
    {
        $product = PersonalCatalog::find()
            ->where(['uprid' => $productId, 'customers_id' => $customerId])
            ->limit(1)
            ->asArray($asArray);
        return $product->one();
    }

    /**
     * @param string|int $productId
     * @param int $customerId
     * @param bool $asArray
     * @return array|PersonalCatalog|null
     * @throws \DomainException
     */
    public function getByPrimary($productId, int $customerId, bool $asArray = false)
    {
        $product = $this->findByPrimary($productId, $customerId, $asArray);
        if (!$product) {
            throw new \DomainException('Product in Personal Catalog not found');
        }
        return $product;
    }

    /**
     * @param int $customerId
     * @param int $languageId
     * @param int $limit
     * @param int $sort
     * @param bool $asArray
     * @return array|PersonalCatalog[]
     */
    public function findProductsForCustomer(int $customerId, int $languageId, int $limit, int $sort, bool $asArray = false)
    {
        $products = PersonalCatalog::find()
            ->innerJoin('platforms_products', 'platforms_products.products_id=products.products_id')
            ->where(['customers_id' => $customerId, 'products_status' => 1, 'platforms_products.platform_id' => $languageId])
            ->joinWith(['productsDescriptions' => static function (ActiveQuery $query) use ($languageId) {
                return $query->where(['language_id' => $languageId, 'products_description.platform_id' => $languageId]);
            }])
            ->with('products')
            ->orderBy(['created_at' => $sort]);
        if ($limit) {
            $products->limit($limit);
        }
        return $products->asArray($asArray)->all();
    }
}

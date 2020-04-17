<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\models\repositories;

use common\models\ProductsOptions;

/**
 * Class GroupsRepository
 * @package common\models\repositories
 */
class ProductsOptionsRepository
{

    /**
     * @param ProductsOptions $option
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function remove(ProductsOptions $option)
    {
        if ($option->delete() === false) {
            throw new \RuntimeException('Product Option remove error');
        }
        return true;
    }

    /**
     * @param ProductsOptions $option
     * @param bool $validation
     * @return bool
     */
    public function save(ProductsOptions $option, bool $validation = false)
    {
        if ($option->save($validation) === false) {
            throw new \RuntimeException('Product Option saving error.');
        }
        return true;
    }

    /**
     * @param ProductsOptions $option
     * @param array $params
     * @param bool $validation
     * @param bool $safeOnly
     * @return array|bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function edit(ProductsOptions $option, array $params = [], bool $validation = false, bool $safeOnly = false)
    {
        foreach ($params as $attribute => $param) {
            if (!$option->hasAttribute($attribute)) {
                unset($params[$attribute]);
            }
        }
        $option->setAttributes($params, $safeOnly);
        if ($option->update($validation, array_keys($params)) === false) {
            return $option->getErrors();
        }
        return true;
    }

    /**
     * @param int $optionId
     * @param int $languageId
     * @param bool $asArray
     * @return array|ProductsOptions|null
     */
    public function findByPrimary(int $optionId, int $languageId, bool $asArray = false)
    {
        $productOption = ProductsOptions::find()
            ->where(['products_options_id' => $optionId, 'language_id' => $languageId])
            ->limit(1)
            ->asArray($asArray);
        return $productOption->one();
    }

    /**
     * @param int $optionId
     * @param int $languageId
     * @param bool $asArray
     * @return array|ProductsOptions|null
     */
    public function getByPrimary(int $optionId, int $languageId, bool $asArray = false)
    {
        $productOption = $this->findByPrimary($optionId, $languageId, $asArray);
        if (!$productOption) {
            throw new \DomainException('Product option not found');
        }
        return $productOption;
    }
}

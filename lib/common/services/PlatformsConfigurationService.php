<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\services;

use common\models\repositories\PlatformsConfigurationRepository;

final class PlatformsConfigurationService
{

    /** @var PlatformsConfigurationRepository */
    private $platformsConfigurationRepository;

    public function __construct(PlatformsConfigurationRepository $platformsConfigurationRepository)
    {

        $this->platformsConfigurationRepository = $platformsConfigurationRepository;
    }

    /**
     * @param string $key
     * @param string $value
     * @param int|null $platformId
     * @return array|bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function updateByKey(string $key, string $value, ?int $platformId = null)
    {
        return $this->platformsConfigurationRepository->updateByKey($key, $value, $platformId);
    }

    /**
     * @param string $key
     * @param int|null $platformId
     * @param bool $asArray
     * @return array|\common\models\PlatformsConfiguration[]|\yii\db\ActiveRecord[]
     */
    public function findByKey(string $key, ?int $platformId = null, bool $asArray = false)
    {
        return $this->platformsConfigurationRepository->findByKey($key, $platformId, $asArray);
    }

    /**
     * @param string $key
     * @param int|null $platformId
     * @return bool
     */
    public function existByKey(string $key, ?int $platformId = null)
    {
        return $this->platformsConfigurationRepository->existByKey($key, $platformId);
    }
}

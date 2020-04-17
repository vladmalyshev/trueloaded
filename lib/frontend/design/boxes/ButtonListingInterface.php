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


namespace frontend\design\boxes;

interface ButtonListingInterface
{
    /** @return bool */
    public function isAllowed(): bool;
    /**
     * ascending
     * @return int
     */
    public function getPriority(): int;
    /**
     * @param int $priority
     * @return ButtonListingInterface
     */
    public function setPriority(int $priority): ButtonListingInterface;
}

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


namespace common\components\EventDispatcher\Provider;


use common\components\EventDispatcher\ListenerProviderInterface;

class ProvidersAggregate implements ListenerProviderInterface
{
    /**
     * @var ListenerProviderInterface[]
     */
    private $providers;

    public function getListenersForEvent($event)
    {
        foreach ($this->providers as $provider) {
            yield from $provider->getListenersForEvent($event);
        }
    }

    public function attach(ListenerProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }
}

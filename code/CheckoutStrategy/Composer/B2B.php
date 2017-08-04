<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy\Composer;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class B2B implements CheckoutStrategyInterface
{
    /**
     * @var AppConfig
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param AppConfig $config
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $release
     * @param array|string $commit
     * @throws \Exception
     */
    public function checkout($release, $commit)
    {
        throw new \Exception('This logic is not implemented yet');
    }
}

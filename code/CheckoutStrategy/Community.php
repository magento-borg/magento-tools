<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\Config;

class Community implements CheckoutStrategyInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $release
     * @param string $commit
     */
    public function checkout($release, $commit)
    {
        $path = $this->config->getSourceCodePath(Config::CE_EDITION, $release);
        if (!file_exists($path . '/composer.json')) {
            $this->config->createFolder($path);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(Config::CE_EDITION) . '/ ' . $path . '/');
            exec('cd ' . $path . '; git checkout ' . $commit);
            exec('cd ' . $path . '; composer install');
        }
    }
}

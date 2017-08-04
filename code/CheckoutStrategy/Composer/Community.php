<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy\Composer;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class Community implements CheckoutStrategyInterface
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
     * @param string $commit
     */
    public function checkout($release, $commit)
    {
        $path = $this->config->getSourceCodePath(AppConfig::CE_EDITION, $release);
        if (!file_exists($path . '/composer.json')) {
            $this->config->createFolder($path);
            exec('cd ' . $path . '; composer create-project magento/project-community-edition=' . $release . ' --repository-url=https://repo.magento.com ./');
        }
    }
}

<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\ProjectStrategy\Composer;

use \Magento\DeprecationTool\ProjectStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class Community implements ProjectStrategyInterface
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
            exec('cd ' . $path . '; /usr/local/bin/composer create-project --ignore-platform-reqs magento/project-community-edition=' . $release . ' --repository-url=https://repo.magento.com ./');
        }
    }

    /**
     * @param string $release
     * @param string $message
     * @return mixed
     */
    public function deploy($release, $message)
    {
        // TODO: Implement deploy() method.
    }
}

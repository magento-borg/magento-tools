<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy\Git;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class Enterprise implements CheckoutStrategyInterface
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
        $cePath = $this->config->getSourceCodePath(AppConfig::EE_EDITION, $release);
        if (!file_exists($cePath . '/composer.json')) {
            $this->config->createFolder($cePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(AppConfig::CE_EDITION) . '/ ' . $cePath . '/');
            exec('cd ' . $cePath . '; git checkout ' . $commit);
        }

        $eePath =  $cePath . '/magento2ee';
        if (!file_exists($eePath . '/composer.json')) {
            $this->config->createFolder($eePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(AppConfig::EE_EDITION) . '/ ' . $eePath . '/');
            exec('cd ' . $eePath . '; git checkout ' . $commit);
            $this->executeLinkCommand($eePath, $cePath);
            exec('cp ' . $eePath . '/composer.json ' . $cePath . '/composer.json');
            exec('cp ' . $eePath . '/composer.lock ' . $cePath . '/composer.lock');
            exec('cd ' . $cePath . '; composer install');
        }
    }

    /**
     * @param string $eePath
     * @param string $cePath
     */
    private function executeLinkCommand($eePath, $cePath)
    {
        $command = 'cd ' . $cePath . '; php %s/dev/tools/build-ee.php --ce-source="%s" --ee-source="%s" --command="link" --exclude=true';
        $command = sprintf($command, $eePath, $cePath, $eePath);
        echo exec($command) . PHP_EOL;
    }
}

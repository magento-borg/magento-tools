<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\Config;

class Enterprise implements CheckoutStrategyInterface
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
        $cePath = $this->config->getSourceCodePath(Config::EE_EDITION, $release);
        if (!file_exists($cePath . '/composer.json')) {
            $this->config->createFolder($cePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(Config::CE_EDITION) . '/ ' . $cePath . '/');
            exec('cd ' . $cePath . '; git checkout ' . $commit);
        }

        $eePath =  $cePath . '/magento2ee';
        if (!file_exists($eePath . '/composer.json')) {
            $this->config->createFolder($eePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(Config::EE_EDITION) . '/ ' . $eePath . '/');
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

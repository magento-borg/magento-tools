<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy\Git;

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
     * @param string|array $commit
     */
    public function checkout($release, $commit)
    {
        $cePath = $this->config->getSourceCodePath(AppConfig::B2B_EDITION, $release);
        if (!file_exists($cePath . '/composer.json')) {
            $this->config->createFolder($cePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(AppConfig::CE_EDITION) . '/ ' . $cePath . '/');
            exec('cd ' . $cePath . '; git checkout ' . $commit['ce']);
        }

        $eePath =  $cePath . '/magento2ee';
        if (!file_exists($eePath . '/composer.json')) {
            $this->config->createFolder($eePath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(AppConfig::EE_EDITION) . '/ ' . $eePath . '/');
            exec('cd ' . $eePath . '; git checkout ' . $commit['ee']);
            $this->executeLinkCommand($eePath, $cePath, $eePath);

            $b2bPath =  $cePath . '/magento2b2b';
            $this->config->createFolder($b2bPath);
            exec('cp -r ' . $this->config->getMasterSourceCodePath(AppConfig::B2B_EDITION) . '/ ' . $b2bPath . '/');
            exec('cd ' . $b2bPath . '; git checkout ' . $commit['b2b']);
            $this->executeLinkCommand($b2bPath, $cePath, $eePath);

            exec('cp ' . $eePath . '/composer.json ' . $cePath . '/composer.json');
            exec('cp ' . $eePath . '/composer.lock ' . $cePath . '/composer.lock');
            exec('cd ' . $cePath . '; composer install');
        }
    }

    /**
     * @param string $eePath
     * @param string $cePath
     * @param $toolPath
     */
    private function executeLinkCommand($eePath, $cePath, $toolPath)
    {
        $command = 'cd ' . $cePath . '; php %s/dev/tools/build-ee.php --ce-source="%s" --ee-source="%s" --command="link" --exclude=true';
        $command = sprintf($command, $toolPath, $cePath, $eePath);
        echo exec($command) . PHP_EOL;
    }
}

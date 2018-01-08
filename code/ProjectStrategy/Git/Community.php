<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\ProjectStrategy\Git;

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
            $releaseFolder = $this->config->getReleaseFolderPath($release);
            if (!file_exists($releaseFolder)) {
                $this->config->createFolder($releaseFolder);
            }
            execVerbose('cp -r %s %s', $this->config->getMasterSourceCodePath(AppConfig::CE_EDITION), $releaseFolder);
            execVerbose('ls -la %s', $path);
            execVerbose('cd %s; git checkout %s', $path, $commit);
            execVerbose('cd %s; /usr/local/bin/composer install', $path);
        }
    }

    /**
     * @param string $release
     * @param string $message
     * @return void
     */
    public function deploy($release, $message)
    {
        $path = $this->config->getSourceCodePath(AppConfig::CE_EDITION, $release);
        execVerbose('cd %s; git config user.name %s', $path, $this->config->getGitUserName());
        execVerbose('cd %s; git config user.email %s', $path, $this->config->getGitUserEmail());
        execVerbose('cd %s; git config push.default simple', $path);
        execVerbose('cd %s; git status', $path);
        execVerbose('cd %s; git diff-index --quiet HEAD || git commit -a -m %s', $path, $message);
        execVerbose('cd %s; git push', $path);
    }
}

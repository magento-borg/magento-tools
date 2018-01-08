<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\ProjectStrategy\Git;

use \Magento\DeprecationTool\ProjectStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class Enterprise implements ProjectStrategyInterface
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
            $releaseFolder = $this->config->getReleaseFolderPath($release);
            if (!file_exists($releaseFolder)) {
                $this->config->createFolder($releaseFolder);
            }
            execVerbose('cp -r %s %s', $this->config->getMasterSourceCodePath(AppConfig::CE_EDITION), $cePath);
            execVerbose('ls -la %s', $cePath);
            execVerbose('cd %s; git checkout %s', $cePath, $commit);
        }

        $eePath =  $cePath . '/magento2ee';
        if (!file_exists($eePath . '/composer.json')) {
            execVerbose('cp -r %s %s', $this->config->getMasterSourceCodePath(AppConfig::EE_EDITION), $eePath);
            execVerbose('ls -la %s', $eePath);
            execVerbose('cd %s; git checkout %s', $eePath, $commit);
            $this->executeLinkCommand($eePath, $cePath);
            execVerbose('cp %s/composer.json %s/composer.json', $eePath, $cePath);
            execVerbose('cp %s/composer.lock %s/composer.lock', $eePath, $cePath);
            execVerbose('cd %s; /usr/local/bin/composer install', $cePath);
        }
    }

    /**
     * @param string $eePath
     * @param string $cePath
     */
    private function executeLinkCommand($eePath, $cePath)
    {
        execVerbose(
            'cd %s; php %s/dev/tools/build-ee.php --ce-source=%s --ee-source=%s --command="link" --exclude=true',
            $cePath,
            $eePath,
            $cePath,
            $eePath
        );
    }

    /**
     * @param string $release
     * @param string $message
     * @return void
     */
    public function deploy($release, $message)
    {
        $path = $this->config->getSourceCodePath(AppConfig::EE_EDITION, $release) . '/magento2ee';
        execVerbose('cd %s; git config user.name %s', $path, $this->config->getGitUserName());
        execVerbose('cd %s; git config user.email %s', $path, $this->config->getGitUserEmail());
        execVerbose('cd %s; git config push.default simple', $path);
        execVerbose('cd %s; git status', $path);
        execVerbose('cd %s; git diff-index --quiet HEAD || git commit -a -m %s', $path, $message);
        execVerbose('cd %s; git push', $path);
    }
}

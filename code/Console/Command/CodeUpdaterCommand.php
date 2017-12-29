<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DeprecationTool\AppConfig;

class CodeUpdaterCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('update-code')
            ->setDescription('Update source code with @since/@deprecated annotations');
    }

    /**
     * Run code updater
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userInput = $this->getUserInput($input);

        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require BP . '/vendor/autoload.php';
        $appConfig = new AppConfig();
        $appConfig->setEditions([$userInput['edition']]);
        $appConfig->setTags($userInput['edition'], $userInput['tags']);
        $appConfig->setLatestRelease($userInput['edition'], $userInput['latest']);
        $appConfig->setLatestCommit($userInput['edition'], $userInput['branch']);

        /** @var \Magento\DeprecationTool\CodeUpdater\AbstractUpdater[] $workers */
        $workers = [];
        foreach ($appConfig->getEditions() as $edition) {
            $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedClasses($loader, $appConfig, $edition);
            $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedMethods($loader, $appConfig, $edition);
            $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedProperties($loader, $appConfig, $edition);

            $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceClasses($loader, $appConfig, $edition);
            $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceMethods($loader, $appConfig, $edition);
            $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceProperties($loader, $appConfig, $edition);
        }

        foreach ($workers as $worker) {
            $worker->run();
        }
    }
}
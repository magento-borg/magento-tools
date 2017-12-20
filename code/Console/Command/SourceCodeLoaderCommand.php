<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DeprecationTool\AppConfig;

class SourceCodeLoaderCommand extends AbstractCommand
{
    protected function configure(){
        parent::configure();
        $this->setName('load-source-code')
            ->setDescription('Load composer packages and Git repos');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $userInput = $this->getUserInput($input);
        } catch (\Symfony\Component\Console\Exception\InvalidOptionException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }

        $config = new AppConfig();
        $gitStrategy = [];
        $composerStrategy = [];
        $editions = [];

        switch ($userInput['edition']) {
            case AppConfig::B2B_EDITION:
                array_unshift($editions, AppConfig::B2B_EDITION);
                $gitStrategy[AppConfig::B2B_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Git\B2B($config);
                $composerStrategy[AppConfig::B2B_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Composer\B2B($config);
                //Exclude break intentionally

            case AppConfig::EE_EDITION:
                array_unshift($editions, AppConfig::EE_EDITION);
                $gitStrategy[AppConfig::EE_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Git\Enterprise($config);
                $composerStrategy[AppConfig::EE_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Composer\Enterprise($config);
                //Exclude break intentionally

            case AppConfig::CE_EDITION:
                array_unshift($editions, AppConfig::CE_EDITION);
                $gitStrategy[AppConfig::CE_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Git\Community($config);
                $composerStrategy[AppConfig::CE_EDITION]
                    = new \Magento\DeprecationTool\ProjectStrategy\Composer\Community($config);
                break;

            default:
                return false;
        }

        $config->setEditions($editions);
        foreach ($editions as $edition) {
            $config->setTags($edition, $userInput['tags']);
            $config->setLatestRelease($edition, $userInput['latest']);
            $config->setLatestCommit($edition, $userInput['branch']);
        }

        $gitProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool($gitStrategy);
        $composerProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool($composerStrategy);

        foreach ($config->getEditions() as $edition) {
            foreach ($config->getTags($edition) as $release) {
                $composerProjectStrategyPool->getStrategy($edition)->checkout($release, $release);
            }
        }

        foreach ($config->getEditions() as $edition) {
            $masterPath = $config->getMasterSourceCodePath($edition);
            if (!file_exists($masterPath)) {
                $config->createFolder($masterPath);
                echo $config->getRepository($edition) . " clone to " . $masterPath . " START\n";
                execVerbose('git clone %s %s', $config->getRepository($edition), $masterPath);
                execVerbose('ls -la %s', $masterPath);
                echo $config->getRepository($edition) . " clone to " . $masterPath . " END\n";
            }

            $latestTag = $config->getLatestRelease($edition);
            $latestCommit = $config->getLatestCommit($edition);
            $gitProjectStrategyPool->getStrategy($edition)->checkout($latestTag, $latestCommit);
        }
    }
}
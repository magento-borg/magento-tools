<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DeprecationTool\AppConfig;

class CommitCodeCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('commit-code')
            ->setDescription('Commit updated source code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userInput = $this->getUserInput($input);

        $appConfig = new AppConfig();

        $appConfig->setEditions([$userInput['edition']]);
        $appConfig->setTags($userInput['edition'], $userInput['tags']);
        $appConfig->setLatestRelease($userInput['edition'], $userInput['latest']);
        $appConfig->setLatestCommit($userInput['edition'], $userInput['branch']);

        $gitProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool(
            [
                AppConfig::CE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Community($appConfig),
                AppConfig::EE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Enterprise($appConfig),
                AppConfig::B2B_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\B2B($appConfig),
            ]
        );

        foreach ($appConfig->getEditions() as $edition) {
            $latestTag = $appConfig->getLatestRelease($edition);
            $commitMessage = $appConfig->getCommitMessage();
            $gitProjectStrategyPool->getStrategy($edition)->deploy($latestTag, $commitMessage);
        }
    }
}
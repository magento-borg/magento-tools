<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DeprecationTool\AppConfig;

class MetadataGeneratorCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('generate-metadata')
            ->setDescription('Generate source code metadata');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userInput = $this->getUserInput($input);

        $loader = require BP . '/vendor/autoload.php';
        $config = new AppConfig();
        $config->setEditions([$userInput['edition']]);
        $config->setTags($userInput['edition'], $userInput['tags']);
        $config->setLatestRelease($userInput['edition'], $userInput['latest']);
        $config->setLatestCommit($userInput['edition'], $userInput['branch']);

        $fileReader = new \Magento\DeprecationTool\FileReader();
        $classReader = new \Magento\DeprecationTool\ClassReader();
        $worker = new \Magento\DeprecationTool\MetadataGenerator();
        $worker->generate($config, $fileReader, $classReader, $loader);
    }
}
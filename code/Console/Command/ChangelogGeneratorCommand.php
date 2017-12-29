<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\DeprecationTool\AppConfig;

class ChangelogGeneratorCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('generate-changelog')
            ->setDescription('Generate information about classes/methods that must be updated.');
    }

    /**
     * Run changelog generator
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userInput = $this->getUserInput($input);
        $config = new AppConfig();
        $config->setEditions([$userInput['edition']]);
        $config->setTags($userInput['edition'], $userInput['tags']);
        $config->setLatestRelease($userInput['edition'], $userInput['latest']);
        $config->setLatestCommit($userInput['edition'], $userInput['branch']);

        $dataStructureFactory = new \Magento\DeprecationTool\DataStructureFactory($config);
        $command = new \Magento\DeprecationTool\Compare\Command();
        $writer = new \Magento\DeprecationTool\Compare\ArtifactWriter($config);

        exec('rm -rf ' . BP . '/var/changelog');

        foreach ($config->getEditions() as $edition) {
            $dataStructures = $dataStructureFactory->create($edition);

            foreach ($dataStructures as $packageName => $dataStructure) {
                $classesChangelog = $command->compareDeprecatedClasses($dataStructure);
                $writer->write($packageName, $classesChangelog, \Magento\DeprecationTool\AppConfig::TYPE_DEPRECATED_CLASSES);

                $methodsChangelog = $command->compareDeprecatedMethods($dataStructure);
                $writer->write($packageName, $methodsChangelog, \Magento\DeprecationTool\AppConfig::TYPE_DEPRECATED_METHODS);

                $propertiesChangelog = $command->compareDeprecatedProperties($dataStructure);
                $writer->write($packageName, $propertiesChangelog, \Magento\DeprecationTool\AppConfig::TYPE_DEPRECATED_PROPERTIES);

                $newClassesChangelog = $command->compareNewClasses($dataStructure);
                $writer->write($packageName, $newClassesChangelog, \Magento\DeprecationTool\AppConfig::TYPE_SINCE_CLASSES);

                $newMethodsChangelog = $command->compareNewMethods($dataStructure);
                $writer->write($packageName, $newMethodsChangelog, \Magento\DeprecationTool\AppConfig::TYPE_SINCE_METHODS);

                $newPropertiesChangelog = $command->compareNewProperties($dataStructure);
                $writer->write($packageName, $newPropertiesChangelog, \Magento\DeprecationTool\AppConfig::TYPE_SINCE_PROPERTIES);
            }
        }
    }
}
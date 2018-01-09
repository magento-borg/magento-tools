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
        $appConfig = new AppConfig();
        $appConfig->setEditions([$userInput['edition']]);
        $appConfig->setTags($userInput['edition'], $userInput['tags']);
        $appConfig->setLatestRelease($userInput['edition'], $userInput['latest']);
        $appConfig->setLatestCommit($userInput['edition'], $userInput['branch']);

        $dataStructureFactory = new \Magento\DeprecationTool\DataStructureFactory($appConfig);
        $command = new \Magento\DeprecationTool\Compare\Command();
        $writer = new \Magento\DeprecationTool\Compare\ArtifactWriter($appConfig);

        foreach ($appConfig->getEditions() as $edition) {
            exec('rm -rf ' . $appConfig->getEditionChangelogPath($edition));
            $dataStructures = $dataStructureFactory->create($edition);

            foreach ($dataStructures as $packageName => $dataStructure) {
                $classesChangelog = $command->compareDeprecatedClasses($dataStructure);
                $writer->write($packageName, $classesChangelog, AppConfig::TYPE_DEPRECATED_CLASSES);

                $methodsChangelog = $command->compareDeprecatedMethods($dataStructure);
                $writer->write($packageName, $methodsChangelog, AppConfig::TYPE_DEPRECATED_METHODS);

                $propertiesChangelog = $command->compareDeprecatedProperties($dataStructure);
                $writer->write($packageName, $propertiesChangelog, AppConfig::TYPE_DEPRECATED_PROPERTIES);

                $newClassesChangelog = $command->compareNewClasses($dataStructure);
                $writer->write($packageName, $newClassesChangelog, AppConfig::TYPE_SINCE_CLASSES);

                $newMethodsChangelog = $command->compareNewMethods($dataStructure);
                $writer->write($packageName, $newMethodsChangelog, AppConfig::TYPE_SINCE_METHODS);

                $newPropertiesChangelog = $command->compareNewProperties($dataStructure);
                $writer->write($packageName, $newPropertiesChangelog, AppConfig::TYPE_SINCE_PROPERTIES);
            }
        }
    }
}
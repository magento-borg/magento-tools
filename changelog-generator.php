<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';
/**
 * @var array $tags
 * @var string $basePath
 * @var string $artifacts
 */
/** @var \Magento\DeprecationTool\DataStructure $structure */

$config = new \Magento\DeprecationTool\AppConfig();
$dataStructureFactory = new \Magento\DeprecationTool\DataStructureFactory($config);
$command = new \Magento\DeprecationTool\Compare\Command();
$writer = new \Magento\DeprecationTool\Compare\ArtifactWriter($config);

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

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
        $writer->write($packageName, $classesChangelog, 'deprecated-classes');

        $methodsChangelog = $command->compareDeprecatedMethods($dataStructure);
        $writer->write($packageName, $methodsChangelog, 'deprecated-methods');

        $propertiesChangelog = $command->compareDeprecatedProperties($dataStructure);
        $writer->write($packageName, $propertiesChangelog, 'deprecated-properties');

        $newClassesChangelog = $command->compareNewClasses($dataStructure);
        $writer->write($packageName, $newClassesChangelog, 'since-classes');

        $newMethodsChangelog = $command->compareNewMethods($dataStructure);
        $writer->write($packageName, $newMethodsChangelog, 'since-methods');

        $newPropertiesChangelog = $command->compareNewProperties($dataStructure);
        $writer->write($packageName, $newPropertiesChangelog, 'since-properties');
    }
}

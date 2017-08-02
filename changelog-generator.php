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

$config = new \Magento\DeprecationTool\Config();
$dataStructureFactory = new \Magento\DeprecationTool\DataStructureFactory($config);
$command = new \Magento\DeprecationTool\Compare\Command();
$writer = new \Magento\DeprecationTool\Compare\ArtifactWriter($config);

foreach ($config->getEditions() as $edition) {
    $dataStructure = $dataStructureFactory->create($edition);

    $classesChangelog = $command->compareDeprecatedClasses($dataStructure);
    $writer->write($edition, $classesChangelog, 'deprecated.classes');

    $methodsChangelog = $command->compareDeprecatedMethods($dataStructure);
    $writer->write($edition, $methodsChangelog, 'deprecated.methods');

    $propertiesChangelog = $command->compareDeprecatedProperties($dataStructure);
    $writer->write($edition, $propertiesChangelog, 'deprecated.properties');

    $newClassesChangelog = $command->compareNewClasses($dataStructure);
    $writer->write($edition, $newClassesChangelog, 'new.classes');

    $newMethodsChangelog = $command->compareNewMethods($dataStructure);
    $writer->write($edition, $newMethodsChangelog, 'new.methods');

    $newPropertiesChangelog = $command->compareNewProperties($dataStructure);
    $writer->write($edition, $newPropertiesChangelog, 'new.properties');
}

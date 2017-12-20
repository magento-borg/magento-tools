<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';

$application = new \Symfony\Component\Console\Application();

$application->add(new \Magento\DeprecationTool\Console\Command\SourceCodeLoaderCommand());
$application->add(new \Magento\DeprecationTool\Console\Command\MetadataGeneratorCommand());
$application->add(new \Magento\DeprecationTool\Console\Command\ChangelogGeneratorCommand());
$application->add(new \Magento\DeprecationTool\Console\Command\CodeUpdaterCommand());
$application->add(new \Magento\DeprecationTool\Console\Command\CommitCodeCommand());

$application->run();

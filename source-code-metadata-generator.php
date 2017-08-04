<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require_once 'bootstrap.php';

$loader = require './vendor/autoload.php';
$config = new \Magento\DeprecationTool\AppConfig();
$fileReader = new \Magento\DeprecationTool\FileReader();
$classReader = new \Magento\DeprecationTool\ClassReader();
$worker = new \Magento\DeprecationTool\MetadataGenerator();
$worker->generate($config, $fileReader, $classReader, $loader);


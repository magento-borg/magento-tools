<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require_once 'bootstrap.php';

$loader = require './vendor/autoload.php';
$config = new \Magento\DeprecationTool\Config();
$fileReader = new \Magento\DeprecationTool\FileReader();
$classReader = new \Magento\DeprecationTool\ClassReader();

/** @var \Magento\DeprecationTool\MetadataGenerator[] $workers */
$workers = [];
foreach ($config->getEditions() as $index => $edition) {
    $workers[$index] = new \Magento\DeprecationTool\MetadataGenerator($config, $fileReader, $classReader, $edition, $loader);
    $workers[$index]->start();
}

while (!empty($workers)) {
    foreach ($workers as $index => $worker) {
        if (!$worker->isRunning()) {
            unset($workers[$index]);
        }
    }
    sleep(1);
}



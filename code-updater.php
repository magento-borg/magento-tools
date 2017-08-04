<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require './vendor/autoload.php';
$config = new \Magento\DeprecationTool\AppConfig();
/** @var \Magento\DeprecationTool\CodeUpdater\AbstractUpdater[] $workers */
$workers = [];
foreach ($config->getEditions() as $edition) {
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedClasses($loader, $config, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedMethods($loader, $config, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedProperties($loader, $config, $edition);

    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceClasses($loader, $config, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceMethods($loader, $config, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceProperties($loader, $config, $edition);
}

foreach ($workers as $worker) {
    $worker->start(); //start new thread
    $worker->join(); //wait until thread is finished
}

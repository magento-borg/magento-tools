<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require './vendor/autoload.php';
$appConfig = new \Magento\DeprecationTool\AppConfig();
/** @var \Magento\DeprecationTool\CodeUpdater\AbstractUpdater[] $workers */
$workers = [];
foreach ($appConfig->getEditions() as $edition) {
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedClasses($loader, $appConfig, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedMethods($loader, $appConfig, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\DeprecatedProperties($loader, $appConfig, $edition);

    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceClasses($loader, $appConfig, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceMethods($loader, $appConfig, $edition);
    $workers[] = new \Magento\DeprecationTool\CodeUpdater\SinceProperties($loader, $appConfig, $edition);
}

foreach ($workers as $worker) {
    $worker->run();
}

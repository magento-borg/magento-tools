<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';
/** @var \Composer\Autoload\ClassLoader $loader */
$appConfig = new \Magento\DeprecationTool\AppConfig();

$gitProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool(
    [
        \Magento\DeprecationTool\AppConfig::CE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Community($appConfig),
        \Magento\DeprecationTool\AppConfig::EE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Enterprise($appConfig),
        \Magento\DeprecationTool\AppConfig::B2B_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\B2B($appConfig),
    ]
);

foreach ($appConfig->getEditions() as $edition) {
    $latestTag = $appConfig->getLatestRelease($edition);
    $commitMessage = $appConfig->getCommitMessage();
    $gitProjectStrategyPool->getStrategy($edition)->deploy($latestTag, $commitMessage);
}

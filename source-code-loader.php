<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';

$config = new \Magento\DeprecationTool\AppConfig();

$gitProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool(
    [
        \Magento\DeprecationTool\AppConfig::CE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Community($config),
        \Magento\DeprecationTool\AppConfig::EE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\Enterprise($config),
        \Magento\DeprecationTool\AppConfig::B2B_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Git\B2B($config),
    ]
);

$composerProjectStrategyPool = new \Magento\DeprecationTool\ProjectStrategyPool(
    [
        \Magento\DeprecationTool\AppConfig::CE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Composer\Community($config),
        \Magento\DeprecationTool\AppConfig::EE_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Composer\Enterprise($config),
        \Magento\DeprecationTool\AppConfig::B2B_EDITION => new \Magento\DeprecationTool\ProjectStrategy\Composer\B2B($config),
    ]
);

foreach ($config->getEditions() as $edition) {
    foreach ($config->getTags($edition) as $release) {
        $composerProjectStrategyPool->getStrategy($edition)->checkout($release, $release);
    }
}


foreach ($config->getEditions() as $edition) {
    $masterPath = $config->getMasterSourceCodePath($edition);
    if (!file_exists($masterPath)) {
        $config->createFolder($masterPath);
        echo $config->getRepository($edition) . " clone to " . $masterPath . " START\n";
        execVerbose('git clone %s %s', $config->getRepository($edition), $masterPath);
        execVerbose('ls -la %s', $masterPath);
        echo $config->getRepository($edition) . " clone to " . $masterPath . " END\n";
    }

    $latestTag = $config->getLatestRelease($edition);
    $latestCommit = $config->getLatestCommit($edition);
    $gitProjectStrategyPool->getStrategy($edition)->checkout($latestTag, $latestCommit);
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';

$config = new \Magento\DeprecationTool\AppConfig();

$gitCheckoutStrategyPool = new \Magento\DeprecationTool\CheckoutStrategyPool(
    [
        \Magento\DeprecationTool\AppConfig::CE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Git\Community($config),
        \Magento\DeprecationTool\AppConfig::EE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Git\Enterprise($config),
        \Magento\DeprecationTool\AppConfig::B2B_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Git\B2B($config),
    ]
);

$composerCheckoutStrategyPool = new \Magento\DeprecationTool\CheckoutStrategyPool(
    [
        \Magento\DeprecationTool\AppConfig::CE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Composer\Community($config),
        \Magento\DeprecationTool\AppConfig::EE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Composer\Enterprise($config),
        \Magento\DeprecationTool\AppConfig::B2B_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Composer\B2B($config),
    ]
);

foreach ($config->getEditions() as $edition) {
    foreach ($config->getTags($edition) as $release) {
        $composerCheckoutStrategyPool->getStrategy($edition)->checkout($release, $release);
    }
}


foreach ($config->getEditions() as $edition) {
    $masterPath = $config->getMasterSourceCodePath($edition);
    if (!file_exists($masterPath . '/composer.json')) {
        $config->createFolder($masterPath);
        exec('git clone ' . $config->getRepository($edition) . ' ' . $masterPath);
    }

    $latestTag = $config->getLatestRelease($edition);
    $latestCommit = $config->getLatestCommit($edition);
    $gitCheckoutStrategyPool->getStrategy($edition)->checkout($latestTag, $latestCommit);
}

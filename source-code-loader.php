<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'bootstrap.php';

$config = new \Magento\DeprecationTool\Config();

$checkoutStrategyPool = new \Magento\DeprecationTool\CheckoutStrategyPool(
    [
        \Magento\DeprecationTool\Config::CE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Community($config),
        \Magento\DeprecationTool\Config::EE_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\Enterprise($config),
        \Magento\DeprecationTool\Config::B2B_EDITION => new \Magento\DeprecationTool\CheckoutStrategy\B2B($config),
    ]
);

foreach ($config->getEditions() as $edition) {
    $masterPath = $config->getMasterSourceCodePath($edition);
    if (!file_exists($masterPath . '/composer.json')) {
        $config->createFolder($masterPath);
        exec('git clone ' . $config->getRepository($edition) . ' ' . $masterPath);
    }

    $tags = $config->getTags($edition);
    $checkoutConfig = array_combine($tags, $tags);
    $latestTag = $config->getLatestRelease($edition);
    $latestCommit = $config->getLatestCommit($edition);
    $checkoutConfig[$latestTag] = $latestCommit;

    foreach ($checkoutConfig as $release => $commit) {
        $checkoutStrategyPool->getStrategy($edition)->checkout($release, $commit);
    }
}

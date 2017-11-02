<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\CheckoutStrategy\Composer;

use \Magento\DeprecationTool\CheckoutStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class B2B implements CheckoutStrategyInterface
{
    const B2B_EE_MAPPING = [
        '1.0.0' => '2.2.0',
        '1.0.1' => '2.2.1',
    ];

    /**
     * @var AppConfig
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param AppConfig $config
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $release
     * @param array|string $commit
     * @throws \Exception
     */
    public function checkout($release, $commit)
    {
        $path = $this->config->getSourceCodePath(AppConfig::B2B_EDITION, $release);
        if (!file_exists($path . '/composer.json')) {
            $this->config->createFolder($path);
            exec('cd ' . $path . '; composer create-project magento/project-enterprise-edition=' . self::B2B_EE_MAPPING[$release] . ' --repository-url=https://repo.magento.com ./');
            exec('cd ' . $path . '; composer require magento/extension-b2b');
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\ProjectStrategy\Composer;

use \Magento\DeprecationTool\ProjectStrategyInterface;
use \Magento\DeprecationTool\AppConfig;

class B2B implements ProjectStrategyInterface
{
    const B2B_EE_MAPPING = [
        '1.0.0' => '2.2.0',
        '1.0.1' => '2.2.1',
        '1.0.2' => '2.2.2',
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
            $eeRelease = isset(self::B2B_EE_MAPPING[$release]) ? self::B2B_EE_MAPPING[$release] : $release;
            exec('cd ' . $path . '; /usr/local/bin/composer create-project magento/project-enterprise-edition=' . $eeRelease . ' --repository-url=https://repo.magento.com ./');
            exec('cd ' . $path . '; /usr/local/bin/composer require magento/extension-b2b');
        }
    }

    /**
     * @param string $release
     * @param string $message
     * @return mixed
     */
    public function deploy($release, $message)
    {
        // TODO: Implement deploy() method.
    }
}

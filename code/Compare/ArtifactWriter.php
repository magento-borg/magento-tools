<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Compare;

use Magento\DeprecationTool\AppConfig;

class ArtifactWriter
{
    /**
     * @var AppConfig
     */
    private $appConfig;

    /**
     * Initialize dependencies.
     *
     * @param AppConfig $appConfig
     */
    public function __construct(AppConfig $appConfig)
    {
        $this->appConfig = $appConfig;
    }

    public function write($packageName, $data, $type)
    {
        if (!empty($data)) {
            $packageName = str_replace('/', '-', $packageName);
            if (!file_exists($this->appConfig->getChangelogPath($type))) {
                @mkdir($this->appConfig->getChangelogPath($type), 0777, true);
            }
            $jsonReportPath = $this->appConfig->getChangelogPath($type) . '/' . $packageName . '.json';
            file_put_contents($jsonReportPath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}

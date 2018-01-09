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
        foreach ($this->appConfig->getEditions() as $edition) {
            if (!empty($data)) {
                if (!file_exists($this->appConfig->getChangelogPath($edition, $type))) {
                    @mkdir($this->appConfig->getChangelogPath($edition, $type), 0777, true);
                }
                $jsonReportPath = $this->appConfig->getChangelogPath($edition, $type, $packageName);
                file_put_contents($jsonReportPath, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }
}

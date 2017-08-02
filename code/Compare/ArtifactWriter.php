<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Compare;

use Magento\DeprecationTool\Config;

class ArtifactWriter
{
    /**
     * @var Config
     */
    private $config;

    const TYPE_DEPRECATED = 1;
    const TYPE_NEW_CODE = 2;

    /**
     * Initialize dependencies.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function write($edition, $data, $fileName)
    {
        if (!empty($data)) {
            if (!file_exists($this->config->getChangelogPath($edition))) {
                @mkdir($this->config->getChangelogPath($edition), 0777, true);
            }
            $jsonReportPath = $this->config->getChangelogPath($edition) . '/' . $fileName . '.json';
            file_put_contents($jsonReportPath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}

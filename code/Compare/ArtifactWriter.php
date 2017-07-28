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

    public function write($edition, $data, $fileName, $type)
    {
        if (empty($data)) {
            return;
        }
        $array = array_column($data, 'entity');
        $sort = function ($rowA, $rowB) {
            $a = strlen($rowA);
            $b = strlen($rowB);
            if ($a == $b) {
                return 0;
            }

            return $a > $b ? -1 : 1;
        };
        usort($array, $sort);
        $item = current($array);
        $maxLenEntity = strlen($item);

        $array = array_column($data, 'expectedSince');
        usort($array, $sort);
        $item = current($array);
        $maxLenSince = strlen($item);
        $lines = [];
        $colOne = $colTwo = $colThree = 0;
        foreach ($data as $index => $row) {
            $entity = str_pad($row['entity'], $maxLenEntity, ' ', STR_PAD_RIGHT);
            $msg = ($type == self::TYPE_DEPRECATED) ? '@deprecated since ' : 'available since ';
            $since = $msg . str_pad($row['expectedSince'], $maxLenSince, ' ', STR_PAD_RIGHT);
            $indexLine = str_pad($index + 1, strlen((string)count($data)), 0, STR_PAD_LEFT);
            $colOne = strlen($indexLine);
            $colTwo = strlen($entity);
            $colThree = strlen($since);
            $lines[] = sprintf('| %s | %s | %s |', $indexLine, $entity, $since);
        }
        $headerRow = sprintf(
            '| %s | %s | %s |',
            str_pad('#', $colOne, ' ', STR_PAD_RIGHT),
            str_pad('Entity', $colTwo, ' ', STR_PAD_RIGHT),
            str_pad('Since', $colThree, ' ', STR_PAD_RIGHT)
        );
        $closingLine = sprintf('+%s+%s+%s+', str_repeat('-', $colOne + 2), str_repeat('-', $colTwo + 2), str_repeat('-', $colThree + 2));

        $lines = array_merge([$closingLine], [$headerRow], [$closingLine], $lines, [$closingLine]);

        if (!file_exists($this->config->getChangelogPath($edition))) {
            @mkdir($this->config->getChangelogPath($edition), 0777, true);
        }
        $textReportPath = $this->config->getChangelogPath($edition) . '/' . $fileName . '.log';
        $jsonReportPath = $this->config->getChangelogPath($edition) . '/' . $fileName . '.json';

        file_put_contents($textReportPath, implode(PHP_EOL, $lines));
        file_put_contents($jsonReportPath, json_encode($data, JSON_PRETTY_PRINT));

    }
}

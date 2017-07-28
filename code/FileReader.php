<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;
use \Zend\Stdlib\Glob;

class FileReader
{
    /**
     * Read file names from filesystem.
     *
     * @param array $dirPatterns
     * @param $fileNamePattern
     * @param bool $recursive
     * @return string[] filePath => $className
     */
    public function read(array $dirPatterns, $fileNamePattern, $recursive = true)
    {
        $result = [];
        foreach ($dirPatterns as $oneDirPattern) {
            $oneDirPattern = str_replace('\\', '/', $oneDirPattern);
            $entriesInDir = Glob::glob("{$oneDirPattern}/{$fileNamePattern}", Glob::GLOB_NOSORT | Glob::GLOB_BRACE);
            $subDirs = Glob::glob("{$oneDirPattern}/*", Glob::GLOB_ONLYDIR | Glob::GLOB_NOSORT | Glob::GLOB_BRACE);
            $filesInDir = array_diff($entriesInDir, $subDirs);

            if ($recursive) {
                $filesInSubDir = $this->read($subDirs, $fileNamePattern);
                $result = array_merge($result, $filesInDir, $filesInSubDir);
            }
        }
        $result = array_filter(
            $result,
            function ($item) {
                return strpos($item, '/Test/') === false;
            }
        );
        return $result;
    }
}

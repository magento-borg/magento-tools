<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class PackagesListReader
{
    public static function getGitPackages($basePath)
    {
        $allowedTypes = ['magento2-module', 'magento2-library'];

        $output = [];
        $path = $basePath . '/app/code/Magento/*/composer.json';
        $files = glob($path);
        $frameworkPackages = glob($basePath . '/lib/internal/Magento/Framework/{composer.json,*/composer.json}', GLOB_BRACE);
        $files = array_merge($files, $frameworkPackages);

        foreach ($files as $file) {
            $json = json_decode(file_get_contents($file), true);
            $type = isset($json['type']) ? $json['type'] : null;
            if (in_array($type, $allowedTypes)) {
                preg_match('/^([0-9]*\.[0-9]*\.[0-9]*).*/', $json['version'], $matches);
                $version = $matches[1]; //sanitize version number
                $output[$json['name']] = [
                    'version' => $version,
                    'path' => [dirname($file)]
                ];
            }
        }

        return $output;
    }

    public static function getComposerPackages($path)
    {
        $allowedTypes = ['magento2-module', 'magento2-library'];
        $output = [];
        $path .= '/vendor/magento/*/composer.json';
        $files = glob($path);
        foreach ($files as $file) {
            $json = json_decode(file_get_contents($file), true);
            $type = isset($json['type']) ? $json['type'] : null;
            if (in_array($type, $allowedTypes)) {
                $output[$json['name']] = [
                    'version' => $json['version'],
                    'path' => [dirname($file)]
                ];
            }
        }
        return $output;
    }
}

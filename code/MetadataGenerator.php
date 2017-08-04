<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

use Composer\Autoload\ClassLoader;

class MetadataGenerator
{
    /**
     * @param AppConfig $appConfig
     * @param FileReader $fileReader
     * @param ClassReader $classReader
     * @param $edition
     * @param ClassLoader $loader
     */
    public function generate(AppConfig $appConfig, FileReader $fileReader, ClassReader $classReader, ClassLoader $loader)
    {
        $directories = [];
        foreach ($appConfig->getEditions() as $index => $edition) {
            $path = $appConfig->getGitSourceCodeLocation($edition, $appConfig->getLatestRelease($edition));
            foreach (PackagesListReader::getGitPackages($path) as $name => $info) {
                if (!isset($directories[$name][$info['version']])) {
                    $directories[$name][$info['version']] = [
                        'path' => $info['path'],
                        'autoloader' => $appConfig->getSourceCodePath($edition, $appConfig->getLatestRelease($edition)) . '/vendor/autoload.php',
                        'version' => $info['version'],
                        'release' => $appConfig->getLatestRelease($edition),
                        'edition' => $edition,
                        'name' => $name
                    ];
                }
            }
            foreach ($appConfig->getTags($edition) as $tag) {
                $path = $appConfig->getSourceCodePath($edition, $tag);
                foreach (PackagesListReader::getComposerPackages($path) as $name => $info) {
                    if (!isset($directories[$name][$info['version']])) {
                        $directories[$name][$info['version']] = [
                            'path' => $info['path'],
                            'autoloader' => $appConfig->getSourceCodePath($edition, $tag) . '/vendor/autoload.php',
                            'version' => $info['version'],
                            'release' => $tag,
                            'edition' => $edition,
                            'name' => $name
                        ];
                    }
                }
            }
            foreach ($directories as $packageName => $versions) {
                foreach ($versions as $version => $config) {
                    $artifactPath = $appConfig->getMetadataPath($config['name'], $config['version']);
                    if (file_exists($artifactPath)) {
                        continue;
                    }

                    echo $packageName . ' '  . $version . PHP_EOL;
                    $paths = $config['path'];
                    $autoloader = $config['autoloader'];
                    $files = $fileReader->read($paths, '*.php');
                    $generator = new MetadataGeneratorThread($files, $autoloader, $appConfig, $config, $classReader, $loader);
                    $generator->start();
                }
            }
        }

    }


}

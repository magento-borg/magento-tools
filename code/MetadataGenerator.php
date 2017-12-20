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
     * @param ClassLoader $loader
     */
    public function generate(AppConfig $appConfig, FileReader $fileReader, ClassReader $classReader, ClassLoader $loader)
    {
        $logger = new \Zend_Log();
        $infoWriter = new \Zend_Log_Writer_Stream($appConfig->getLogPath('metadata-generator.log'));
        $logger->addWriter($infoWriter);

        $jobs = [];
        $directories = [];

        foreach ($appConfig->getEditions() as $index => $edition) {
            $gitPath = $appConfig->getGitSourceCodeLocation($edition, $appConfig->getLatestRelease($edition));
            $packagesGit = PackagesListReader::getGitPackages($gitPath);
            foreach ($packagesGit as $name => $info) {
                if (!isset($directories[$name][$info['version']])) {
                    $directories[$name][$info['version']] = [
                        'path' => $info['path'],
                        'autoloader' => $appConfig->getSourceCodePath($edition,
                                $appConfig->getLatestRelease($edition)) . '/vendor/autoload.php',
                        'version' => $info['version'],
                        'release' => $appConfig->getLatestRelease($edition),
                        'edition' => $edition,
                        'name' => $name
                    ];
                }
            }
            foreach ($appConfig->getTags($edition) as $tag) {
                $composerPath = $appConfig->getSourceCodePath($edition, $tag);
                $allPackagesComposer = PackagesListReader::getComposerPackages($composerPath);
                $editionPackagesComposer = array_intersect_key($allPackagesComposer, $packagesGit);
                foreach ($editionPackagesComposer as $name => $info) {
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
        }
        $fn = \Closure::bind(function () {
            foreach (self::$paths as $key => $path) {
                self::$paths[$key] = [];
            }
        }, null, \Magento\Framework\Component\ComponentRegistrar::class);
        if (class_exists(\Magento\Framework\Component\ComponentRegistrar::class)) {
            $fn();
        }

        $autoloaders = [];
        foreach ($directories as $packageName => $versions) {
            foreach ($versions as $version => $config) {
                $key = $config['name'] . ' = ' . $config['version'];
                $paths = $config['path'];
                if (!isset($autoloaders[$config['autoloader']])) {
                    foreach ($autoloaders as $autoloader) {
                        $autoloader->unregister();
                        $fn();
                    }
                    $autoloaders[$config['autoloader']] = require_once $config['autoloader'];
                    $autoloaders[$config['autoloader']]->unregister();
                }
                $autoloader = $autoloaders[$config['autoloader']];
                $files = $fileReader->read($paths, '*.php');

                $jobs[$key] = new MetadataGeneratorThread($files, $autoloader, $appConfig, $config, $classReader, $loader);
                $jobs[$key]->run();
            }
        }
    }
}
